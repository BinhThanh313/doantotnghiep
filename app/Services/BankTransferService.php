<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankTransferService
{
    private string $bankName;
    private string $accountNumber;
    private string $accountName;
    private string $branch;

    public function __construct()
    {
        $this->bankName      = config('payment.bank.name', 'Vietcombank');
        $this->accountNumber = config('payment.bank.account_number', '1234567890');
        $this->accountName   = config('payment.bank.account_name', 'CONG TY TNHH ELECTRO');
        $this->branch        = config('payment.bank.branch', 'Chi nhánh Hà Nội');
    }

    // ─────────────────────────────────────────────────────────
    // QR Code generation
    // ─────────────────────────────────────────────────────────

    /**
     * Sinh URL QR VietQR chuẩn Napas cho chuyển khoản nhanh.
     * Không cần thư viện — dùng VietQR API công khai.
     *
     * @return array { qr_url, qr_data_url, transfer_content, amount, bank_info }
     */
    public function generateQrCode(Order $order): array
    {
        $amount          = (int) ($order->total_amount + ($order->shipping_fee ?? 0) - ($order->discount_amount ?? 0));
        $transferContent = $this->buildTransferContent($order);

        // VietQR Quick Link (chuẩn quốc gia — không cần API key)
        // Format: https://img.vietqr.io/image/{bank_id}-{account_no}-{template}.png?amount=...&addInfo=...&accountName=...
        $bankId   = config('payment.bank.vietqr_bank_id', 'VCB'); // VCB = Vietcombank
        $template = 'compact2'; // print | compact | compact2 | qr_only

        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s',
            $bankId,
            $this->accountNumber,
            $template,
            $amount,
            urlencode($transferContent),
            urlencode($this->accountName)
        );

        // Nội dung QR dạng text (EMV QRCPS-MPM — chuẩn Napas)
        $qrData = $this->buildEMVQRString($amount, $transferContent);

        return [
            'qr_url'           => $qrUrl,
            'qr_data_string'   => $qrData,   // dùng để render QR bằng thư viện FE
            'transfer_content' => $transferContent,
            'amount'           => $amount,
            'bank_info'        => $this->getBankInfo(),
            'expires_at'       => now()->addHours(24)->toISOString(),
        ];
    }

    /**
     * Xây dựng nội dung chuyển khoản chuẩn, dễ nhận diện.
     * VD: "ELECTRO ORD-ABCD1234"
     */
    public function buildTransferContent(Order $order): string
    {
        $ref = $order->tracking_number ?? ('ORDER' . $order->id);
        // Loại bỏ ký tự đặc biệt, giới hạn 50 ký tự
        return Str::upper(preg_replace('/[^A-Z0-9 ]/', '', "ELECTRO {$ref}"));
    }

    // ─────────────────────────────────────────────────────────
    // Manual verification (admin)
    // ─────────────────────────────────────────────────────────

    /**
     * Admin xác nhận thủ công giao dịch chuyển khoản.
     * Cập nhật Payment + Order + ghi log.
     *
     * @param  Payment $payment
     * @param  array   $data { transaction_id, confirmed_amount, note?, confirmed_by }
     * @return array   { success, message }
     */
    public function adminVerify(Payment $payment, array $data): array
    {
        if ($payment->status === 'success') {
            return ['success' => false, 'message' => 'Giao dịch này đã được xác nhận trước đó.'];
        }

        if ($payment->status === 'refunded') {
            return ['success' => false, 'message' => 'Giao dịch đã hoàn tiền, không thể xác nhận lại.'];
        }

        $confirmedAmount = (float) ($data['confirmed_amount'] ?? $payment->amount);

        // Kiểm tra số tiền khớp (cho phép sai lệch ±1000đ do phí ngân hàng)
        $tolerance = 1000;
        if (abs($confirmedAmount - $payment->amount) > $tolerance) {
            Log::warning('BankTransfer verify: amount mismatch', [
                'payment_id'       => $payment->id,
                'expected'         => $payment->amount,
                'confirmed_amount' => $confirmedAmount,
            ]);
            // Vẫn cho phép xác nhận nhưng ghi chú
        }

        \DB::transaction(function () use ($payment, $data, $confirmedAmount) {
            $payment->update([
                'status'         => 'success',
                'transaction_id' => $data['transaction_id'] ?? ('MANUAL_' . now()->format('YmdHis')),
                'paid_at'        => now(),
                'gateway_response' => [
                    'method'           => 'manual_verify',
                    'confirmed_by'     => $data['confirmed_by'] ?? 'admin',
                    'confirmed_amount' => $confirmedAmount,
                    'note'             => $data['note'] ?? null,
                    'confirmed_at'     => now()->toISOString(),
                ],
            ]);

            $payment->order->update(['payment_status' => 'paid']);
        });

        Log::info('BankTransfer manually verified', [
            'payment_id'   => $payment->id,
            'order_id'     => $payment->order_id,
            'confirmed_by' => $data['confirmed_by'] ?? 'admin',
        ]);

        return [
            'success' => true,
            'message' => 'Đã xác nhận chuyển khoản thành công.',
        ];
    }

    /**
     * Admin từ chối / đánh dấu giao dịch thất bại.
     */
    public function adminReject(Payment $payment, string $reason): array
    {
        if (!in_array($payment->status, ['pending', 'processing'])) {
            return ['success' => false, 'message' => 'Chỉ có thể từ chối giao dịch đang chờ xử lý.'];
        }

        $payment->update([
            'status'           => 'failed',
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                ['rejected_reason' => $reason, 'rejected_at' => now()->toISOString()]
            ),
        ]);

        return ['success' => true, 'message' => 'Đã từ chối giao dịch.'];
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function getBankInfo(): array
    {
        return [
            'bank_name'      => $this->bankName,
            'account_number' => $this->accountNumber,
            'account_name'   => $this->accountName,
            'branch'         => $this->branch,
        ];
    }

    /**
     * Sinh chuỗi EMV QR (QRCPS-MPM) — chuẩn quốc tế dùng cho VietQR.
     * FE có thể dùng thư viện qrcode.js để render thành ảnh.
     */
    private function buildEMVQRString(int $amount, string $addInfo): string
    {
        $bankId  = config('payment.bank.vietqr_bank_id', 'VCB');
        $acctNo  = $this->accountNumber;

        // GUID cho service code Napas
        $napasGuid = '0006970436';

        $merchantAccountInfo = $this->tlv('00', 'A000000727')
            . $this->tlv('01', '01')
            . $this->tlv('02', $napasGuid)
            . $this->tlv('03', $bankId)
            . $this->tlv('04', $acctNo);

        $body = $this->tlv('00', '01')                              // Payload Format
            . $this->tlv('01', '12')                               // Point of Initiation (dynamic)
            . $this->tlv('38', $merchantAccountInfo)               // Merchant Account Info
            . $this->tlv('52', '5999')                             // Merchant Category Code
            . $this->tlv('53', '704')                              // Transaction Currency (VND)
            . $this->tlv('54', (string) $amount)                   // Transaction Amount
            . $this->tlv('58', 'VN')                               // Country Code
            . $this->tlv('59', substr($this->accountName, 0, 25))  // Merchant Name
            . $this->tlv('60', 'Hanoi')                            // Merchant City
            . $this->tlv('62', $this->tlv('08', $addInfo));        // Additional Data

        // CRC (cuối cùng)
        $body .= '6304';
        $crc  = sprintf('%04X', $this->crc16($body));

        return $body . $crc;
    }

    private function tlv(string $tag, string $value): string
    {
        return $tag . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private function crc16(string $data): int
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? ($crc << 1) ^ 0x1021 : $crc << 1;
            }
        }
        return $crc & 0xFFFF;
    }
}