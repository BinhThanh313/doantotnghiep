<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // POST /api/payment/create
    public function create(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'method'   => 'required|in:cod,bank',
            'ip_addr'  => 'nullable|ip',
        ]);

        $order = Order::with('payment')->findOrFail($data['order_id']);

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Đơn hàng đã được thanh toán'], 422);
        }

        $grandTotal = $order->total_amount + ($order->shipping_fee ?? 0) - ($order->discount_amount ?? 0);

        try {
            return DB::transaction(function () use ($order, $data, $grandTotal) {
                $method = $data['method'];

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

                return response()->json([
                    'success'    => true,
                    'method'     => $method,
                    'payment_id' => $payment->id,
                    'message'    => $method === 'cod'
                        ? 'Đơn hàng COD đã được tạo. Thanh toán khi nhận hàng.'
                        : 'Vui lòng chuyển khoản theo thông tin tài khoản của cửa hàng.',
                    'bank_info'  => $method === 'bank' ? $this->getBankInfo($order) : null,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Payment create failed', ['error' => $e->getMessage(), 'order' => $order->id]);
            return response()->json(['message' => 'Lỗi tạo phiên thanh toán: ' . $e->getMessage()], 500);
        }
    }

    // GET /api/payment/{id}/status
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

    // POST /api/payment/{id}/refund
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

        try {
            DB::transaction(function () use ($payment, $request, $refundAmount) {
                $payment->update(['status' => 'refunded']);
                $payment->order->update([
                    'payment_status' => 'refunded',
                    'return_reason'  => $request->reason,
                    'refund_amount'  => $refundAmount,
                    'status'         => 'cancelled',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Hoàn tiền thủ công (COD/Bank) đã được ghi nhận',
            ]);
        } catch (\Exception $e) {
            Log::error('Payment refund failed', ['payment_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Private helpers
    private function markPaymentSuccess(Payment $payment, string $txnId, array $raw = []): void
    {
        DB::transaction(function () use ($payment, $txnId, $raw) {
            $payment->update([
                'status'           => 'success',
                'transaction_id'   => $txnId,
                'gateway_response' => $raw ?: null,
                'paid_at'          => now(),
            ]);
            $payment->order->update(['payment_status' => 'paid']);
        });
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