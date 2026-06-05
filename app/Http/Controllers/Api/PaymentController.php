<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\VNPayService;
use App\Services\MomoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private VNPayService $vnpay,
        private MomoService  $momo,
    ) {}

    // =========================================================
    // POST /api/payment/create
    // =========================================================

    /**
     * Tạo phiên thanh toán cho một đơn hàng.
     * Body: { order_id, method: "vnpay"|"momo"|"cod"|"bank", ip_addr? }
     */
    public function create(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'method'   => 'required|in:vnpay,momo,cod,bank',
            'ip_addr'  => 'nullable|ip',
            'bank_code'=> 'nullable|string',
        ]);

        $order = Order::with('payment')->findOrFail($data['order_id']);

        // Không cho phép tạo lại nếu đã paid
        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Đơn hàng đã được thanh toán'], 422);
        }

        $grandTotal = $order->total_amount + ($order->shipping_fee ?? 0) - ($order->discount_amount ?? 0);

        try {
            return DB::transaction(function () use ($order, $data, $grandTotal) {
                $method = $data['method'];

                // Upsert payment record
                $payment = Payment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'amount'         => $grandTotal,
                        'payment_method' => strtoupper($method),
                        'status'         => 'pending',
                        'transaction_id' => null,
                        'paid_at'        => null,
                    ]
                );

                // ─── COD / Bank Transfer ──────────────────────────
                if (in_array($method, ['cod', 'bank'])) {
                    return response()->json([
                        'success'    => true,
                        'method'     => $method,
                        'payment_id' => $payment->id,
                        'message'    => $method === 'cod'
                            ? 'Đơn hàng COD đã được tạo. Thanh toán khi nhận hàng.'
                            : 'Vui lòng chuyển khoản theo thông tin tài khoản của cửa hàng.',
                        'bank_info'  => $method === 'bank' ? $this->getBankInfo($order) : null,
                    ]);
                }

                // ─── VNPay ───────────────────────────────────────
                if ($method === 'vnpay') {
                    $payUrl = $this->vnpay->buildPaymentUrl([
                        'amount'     => $grandTotal,
                        'txn_ref'    => $order->tracking_number ?? $order->id,
                        'order_info' => 'Thanh toan don hang ' . ($order->tracking_number ?? $order->id),
                        'ip_addr'    => $data['ip_addr'] ?? request()->ip(),
                        'bank_code'  => $data['bank_code'] ?? null,
                    ]);

                    return response()->json([
                        'success'    => true,
                        'method'     => 'vnpay',
                        'payment_id' => $payment->id,
                        'pay_url'    => $payUrl,
                        'message'    => 'Redirect đến VNPay để thanh toán',
                    ]);
                }

                // ─── MoMo ────────────────────────────────────────
                if ($method === 'momo') {
                    $result = $this->momo->buildPaymentRequest([
                        'order_id'   => (string) ($order->tracking_number ?? $order->id),
                        'amount'     => $grandTotal,
                        'order_info' => 'Thanh toán đơn hàng ' . ($order->tracking_number ?? $order->id),
                        'extra_data' => ['order_db_id' => $order->id, 'payment_id' => $payment->id],
                    ]);

                    if (!$result['success']) {
                        return response()->json([
                            'success' => false,
                            'message' => $result['message'],
                        ], 502);
                    }

                    return response()->json([
                        'success'    => true,
                        'method'     => 'momo',
                        'payment_id' => $payment->id,
                        'pay_url'    => $result['pay_url'],
                        'deeplink'   => $result['deeplink'],
                        'qr_code'    => $result['qr_code'],
                        'message'    => 'Redirect đến MoMo để thanh toán',
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Payment create failed', ['error' => $e->getMessage(), 'order' => $order->id]);
            return response()->json(['message' => 'Lỗi tạo phiên thanh toán: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // POST /api/payment/{id}/verify
    // =========================================================

    /**
     * Xác minh kết quả callback từ VNPay/MoMo.
     * Body: toàn bộ query params từ redirect
     */
    public function verify(Request $request, int $id)
    {
        $payment = Payment::with('order')->findOrFail($id);
        $method  = strtolower($payment->payment_method);
        $data    = $request->all();

        try {
            $result = match ($method) {
                'vnpay' => $this->verifyVNPay($payment, $data),
                'momo'  => $this->verifyMoMo($payment, $data),
                default => ['success' => false, 'message' => 'Phương thức không hỗ trợ xác minh tự động'],
            };

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Payment verify failed', ['payment_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // GET /api/payment/{id}/status
    // =========================================================

    public function status(int $id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        return response()->json([
            'payment_id'     => $payment->id,
            'order_id'       => $payment->order_id,
            'amount'         => $payment->amount,
            'method'         => $payment->payment_method,
            'status'         => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'paid_at'        => $payment->paid_at,
            'order_status'   => $payment->order->status ?? null,
            'payment_status' => $payment->order->payment_status ?? null,
        ]);
    }

    // =========================================================
    // POST /api/payment/{id}/refund
    // =========================================================

    /**
     * Hoàn tiền một giao dịch.
     * Body: { reason, amount? }
     */
    public function refund(Request $request, int $id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
            'amount' => 'nullable|numeric|min:1|max:' . $payment->amount,
        ]);

        if ($payment->status !== 'success') {
            return response()->json(['message' => 'Chỉ có thể hoàn tiền giao dịch thành công'], 422);
        }

        $refundAmount = $request->amount ?? $payment->amount;
        $method       = strtolower($payment->payment_method);

        try {
            $result = match ($method) {
                'vnpay' => $this->refundVNPay($payment, $refundAmount, $request->reason),
                'momo'  => $this->refundMoMo($payment, $refundAmount, $request->reason),
                default => ['success' => true, 'message' => 'Hoàn tiền thủ công (COD/Bank) đã được ghi nhận'],
            };

            if ($result['success']) {
                DB::transaction(function () use ($payment, $request, $refundAmount) {
                    $payment->update(['status' => 'refunded']);
                    $payment->order->update([
                        'payment_status' => 'refunded',
                        'return_reason'  => $request->reason,
                        'refund_amount'  => $refundAmount,
                        'status'         => 'cancelled',
                    ]);
                });
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Payment refund failed', ['payment_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // Callbacks (public - không cần auth)
    // =========================================================

    /** GET|POST /api/payment/vnpay/callback */
    public function vnpayCallback(Request $request)
    {
        $data = $request->all();

        if (!$this->vnpay->verifySignature($data)) {
            Log::warning('VNPay: invalid signature', $data);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature'], 400);
        }

        $parsed = $this->vnpay->parseCallback($data);
        $this->processPaymentResult('vnpay', $parsed['txn_ref'], $parsed);

        // VNPay yêu cầu trả về RspCode 00 để xác nhận đã nhận
        return response()->json(['RspCode' => '00', 'Message' => 'Confirm success']);
    }

    /** POST /api/payment/momo/notify */
    public function momoNotify(Request $request)
    {
        $data = $request->all();

        if (!$this->momo->verifyMAC($data)) {
            Log::warning('MoMo IPN: invalid MAC', $data);
            return response()->json(['message' => 'Invalid MAC'], 400);
        }

        $parsed = $this->momo->parseCallback($data);
        $this->processPaymentResult('momo', $parsed['order_id'], $parsed);

        return response()->json(['message' => 'success']);
    }

    /** GET /api/payment/momo/callback (redirect từ app MoMo) */
    public function momoCallback(Request $request)
    {
        $data   = $request->all();
        $parsed = $this->momo->parseCallback($data);

        return response()->json([
            'success' => $parsed['success'],
            'message' => $parsed['message'],
        ]);
    }

    // =========================================================
    // Private helpers
    // =========================================================

    private function verifyVNPay(Payment $payment, array $data): array
    {
        if (!$this->vnpay->verifySignature($data)) {
            return ['success' => false, 'message' => 'Chữ ký không hợp lệ'];
        }

        $parsed = $this->vnpay->parseCallback($data);

        if ($parsed['success']) {
            $this->markPaymentSuccess($payment, $parsed['transaction_no'], $parsed['raw']);
        }

        return [
            'success'     => $parsed['success'],
            'message'     => $parsed['message'],
            'transaction' => $parsed['transaction_no'],
        ];
    }

    private function verifyMoMo(Payment $payment, array $data): array
    {
        if (!$this->momo->verifyMAC($data)) {
            return ['success' => false, 'message' => 'MAC không hợp lệ'];
        }

        $parsed = $this->momo->parseCallback($data);

        if ($parsed['success']) {
            $this->markPaymentSuccess($payment, (string) $parsed['trans_id'], $parsed['raw']);
        }

        return [
            'success'     => $parsed['success'],
            'message'     => $parsed['message'],
            'transaction' => $parsed['trans_id'],
        ];
    }

    private function markPaymentSuccess(Payment $payment, string $txnId, array $raw): void
    {
        DB::transaction(function () use ($payment, $txnId, $raw) {
            $payment->update([
                'status'           => 'success',
                'transaction_id'   => $txnId,
                'gateway_response' => $raw,
                'paid_at'          => now(),
            ]);
            $payment->order->update(['payment_status' => 'paid']);
        });
    }

    private function processPaymentResult(string $method, string $txnRef, array $parsed): void
    {
        // txnRef có thể là tracking_number hoặc order_id
        $order = Order::where('tracking_number', $txnRef)
            ->orWhere('id', $txnRef)
            ->first();

        if (!$order) {
            Log::warning("Payment callback: order not found for txnRef={$txnRef}");
            return;
        }

        $payment = $order->payment;
        if (!$payment) return;

        if ($parsed['success']) {
            $txnId = $method === 'vnpay' ? $parsed['transaction_no'] : (string) $parsed['trans_id'];
            $this->markPaymentSuccess($payment, $txnId, $parsed['raw']);
        } else {
            $payment->update([
                'status'           => 'failed',
                'gateway_response' => $parsed['raw'],
            ]);
        }
    }

    private function refundVNPay(Payment $payment, float $amount, string $reason): array
    {
        $refundParams = $this->vnpay->buildRefundRequest([
            'txn_ref'    => $payment->transaction_id ?? ($payment->order->tracking_number ?? $payment->order_id),
            'amount'     => $amount,
            'reason'     => $reason,
            'trans_date' => $payment->paid_at?->format('YmdHis') ?? now()->format('YmdHis'),
        ]);

        // Gọi VNPay refund API
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->post(config('payment.vnpay.refund_url', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'), $refundParams);

            $result = $response->json();
            $success = ($result['vnp_ResponseCode'] ?? '') === '00';

            return [
                'success' => $success,
                'message' => $success ? 'Hoàn tiền VNPay thành công' : ($result['vnp_Message'] ?? 'Hoàn tiền thất bại'),
                'raw'     => $result,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Lỗi kết nối VNPay: ' . $e->getMessage()];
        }
    }

    private function refundMoMo(Payment $payment, float $amount, string $reason): array
    {
        $result = $this->momo->buildRefundRequest([
            'order_id'    => (string) ($payment->order->tracking_number ?? $payment->order_id),
            'amount'      => $amount,
            'trans_id'    => $payment->transaction_id ?? '',
            'description' => $reason,
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Hoàn tiền MoMo thành công' : $result['message'],
            'raw'     => $result['raw'] ?? [],
        ];
    }

    private function getBankInfo(Order $order): array
    {
        return [
            'bank_name'      => config('payment.bank.name', 'Vietcombank'),
            'account_number' => config('payment.bank.account_number', '1234567890'),
            'account_name'   => config('payment.bank.account_name', 'CONG TY ELECTRO'),
            'transfer_note'  => 'Thanh toan don hang ' . ($order->tracking_number ?? $order->id),
            'amount'         => $order->total_amount + ($order->shipping_fee ?? 0) - ($order->discount_amount ?? 0),
        ];
    }
}