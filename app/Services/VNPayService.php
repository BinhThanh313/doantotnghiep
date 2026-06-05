<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class VNPayService
{
    private string $tmnCode;
    private string $hashSecret;
    private string $url;
    private string $returnUrl;

    public function __construct()
    {
        $this->tmnCode   = config('payment.vnpay.tmn_code', 'DEMOVNPAY');
        $this->hashSecret = config('payment.vnpay.hash_secret', 'SECRETKEY');
        $this->url       = config('payment.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        $this->returnUrl = config('payment.vnpay.return_url', url('/api/payment/vnpay/callback'));
    }

    /**
     * Tạo URL redirect để thanh toán VNPay
     */
    public function buildPaymentUrl(array $params): string
    {
        $vnpParams = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $this->tmnCode,
            'vnp_Amount'     => (int) ($params['amount'] * 100), // VNPay tính theo đơn vị xu
            'vnp_CurrCode'   => 'VND',
            'vnp_TxnRef'     => $params['txn_ref'],              // Mã đơn hàng duy nhất
            'vnp_OrderInfo'  => $params['order_info'] ?? 'Thanh toan don hang ' . $params['txn_ref'],
            'vnp_OrderType'  => $params['order_type'] ?? 'billpayment',
            'vnp_Locale'     => $params['locale'] ?? 'vn',
            'vnp_ReturnUrl'  => $this->returnUrl,
            'vnp_IpAddr'     => $params['ip_addr'] ?? request()->ip(),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_ExpireDate' => now()->addMinutes(15)->format('YmdHis'),
        ];

        if (!empty($params['bank_code'])) {
            $vnpParams['vnp_BankCode'] = $params['bank_code'];
        }

        // Sắp xếp theo alphabet key trước khi ký
        ksort($vnpParams);

        $query     = http_build_query($vnpParams, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha512', $query, $this->hashSecret);

        return $this->url . '?' . $query . '&vnp_SecureHash=' . $signature;
    }

    /**
     * Xác minh chữ ký HMAC từ VNPay callback
     */
    public function verifySignature(array $data): bool
    {
        $receivedHash = $data['vnp_SecureHash'] ?? '';

        // Loại bỏ các trường hash trước khi tính lại
        $filtered = collect($data)
            ->except(['vnp_SecureHash', 'vnp_SecureHashType'])
            ->filter(fn($v) => $v !== '' && $v !== null)
            ->toArray();

        ksort($filtered);

        $query         = http_build_query($filtered, '', '&', PHP_QUERY_RFC3986);
        $expectedHash  = hash_hmac('sha512', $query, $this->hashSecret);

        return hash_equals($expectedHash, $receivedHash);
    }

    /**
     * Parse response callback từ VNPay
     */
    public function parseCallback(array $data): array
    {
        $responseCode = $data['vnp_ResponseCode'] ?? '';
        $txnRef       = $data['vnp_TxnRef'] ?? '';
        $amount       = isset($data['vnp_Amount']) ? (int) $data['vnp_Amount'] / 100 : 0;
        $bankCode     = $data['vnp_BankCode'] ?? '';
        $transNo      = $data['vnp_TransactionNo'] ?? '';

        return [
            'success'         => $responseCode === '00',
            'txn_ref'         => $txnRef,
            'amount'          => $amount,
            'bank_code'       => $bankCode,
            'transaction_no'  => $transNo,
            'response_code'   => $responseCode,
            'message'         => $this->getResponseMessage($responseCode),
            'raw'             => $data,
        ];
    }

    /**
     * Mã lỗi VNPay → message tiếng Việt
     */
    public function getResponseMessage(string $code): string
    {
        return match ($code) {
            '00' => 'Giao dịch thành công',
            '07' => 'Trừ tiền thành công, giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)',
            '09' => 'Thẻ/Tài khoản chưa đăng ký Internet Banking',
            '10' => 'Xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Đã hết hạn chờ thanh toán, vui lòng thực hiện lại',
            '12' => 'Thẻ/Tài khoản bị khóa',
            '13' => 'Nhập sai mật khẩu OTP quá số lần quy định',
            '24' => 'Khách hàng hủy giao dịch',
            '51' => 'Tài khoản không đủ số dư',
            '65' => 'Tài khoản vượt hạn mức giao dịch trong ngày',
            '75' => 'Ngân hàng thanh toán đang bảo trì',
            '79' => 'Nhập sai mật khẩu thanh toán quá số lần quy định',
            '99' => 'Lỗi không xác định',
            default => "Lỗi không xác định (mã: {$code})",
        };
    }

    /**
     * Tạo request hoàn tiền qua VNPay
     */
    public function buildRefundRequest(array $params): array
    {
        $refundParams = [
            'vnp_RequestId'      => $params['request_id'] ?? uniqid('REF_'),
            'vnp_Version'        => '2.1.0',
            'vnp_Command'        => 'refund',
            'vnp_TmnCode'        => $this->tmnCode,
            'vnp_TransactionType'=> $params['full_refund'] ?? true ? '02' : '03',
            'vnp_TxnRef'         => $params['txn_ref'],
            'vnp_Amount'         => (int) ($params['amount'] * 100),
            'vnp_OrderInfo'      => $params['reason'] ?? 'Hoan tien don hang ' . $params['txn_ref'],
            'vnp_TransDate'      => $params['trans_date'],
            'vnp_CreateBy'       => $params['created_by'] ?? 'admin',
            'vnp_CreateDate'     => now()->format('YmdHis'),
            'vnp_IpAddr'         => request()->ip(),
        ];

        ksort($refundParams);
        $hashData = implode('|', [
            $refundParams['vnp_RequestId'],
            $refundParams['vnp_Version'],
            $refundParams['vnp_Command'],
            $refundParams['vnp_TmnCode'],
            $refundParams['vnp_TransactionType'],
            $refundParams['vnp_TxnRef'],
            $refundParams['vnp_Amount'],
            $refundParams['vnp_TransDate'],
            $refundParams['vnp_CreateBy'],
            $refundParams['vnp_CreateDate'],
            $refundParams['vnp_IpAddr'],
            $refundParams['vnp_OrderInfo'],
        ]);

        $refundParams['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $this->hashSecret);

        return $refundParams;
    }
}