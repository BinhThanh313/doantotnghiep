<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoService
{
    private string $partnerCode;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private string $returnUrl;
    private string $notifyUrl;

    public function __construct()
    {
        $this->partnerCode = config('payment.momo.partner_code', 'MOMOBKUN20180529');
        $this->accessKey   = config('payment.momo.access_key', 'klm05TvNBzhg7h7j');
        $this->secretKey   = config('payment.momo.secret_key', 'at67qH6mk8w5Y1nAyMoTkhwnSubUGbLt');
        $this->endpoint    = config('payment.momo.endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create');
        $this->returnUrl   = config('payment.momo.return_url', url('/api/payment/momo/callback'));
        $this->notifyUrl   = config('payment.momo.notify_url', url('/api/payment/momo/notify'));
    }

    /**
     * Tạo request thanh toán MoMo và trả về payUrl
     */
    public function buildPaymentRequest(array $params): array
    {
        $orderId     = $params['order_id'];
        $requestId   = $params['request_id'] ?? $orderId . '_' . time();
        $amount      = (string) (int) $params['amount'];
        $orderInfo   = $params['order_info'] ?? 'Thanh toán đơn hàng ' . $orderId;
        $extraData   = base64_encode(json_encode($params['extra_data'] ?? []));
        $requestType = 'payWithMethod'; // captureWallet | payWithMethod

        // Build raw signature string
        $rawSignature = implode('&', [
            'accessKey='   . $this->accessKey,
            'amount='      . $amount,
            'extraData='   . $extraData,
            'ipnUrl='      . $this->notifyUrl,
            'orderId='     . $orderId,
            'orderInfo='   . $orderInfo,
            'partnerCode=' . $this->partnerCode,
            'redirectUrl=' . $this->returnUrl,
            'requestId='   . $requestId,
            'requestType=' . $requestType,
        ]);

        $signature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        $body = [
            'partnerCode' => $this->partnerCode,
            'accessKey'   => $this->accessKey,
            'requestId'   => $requestId,
            'amount'      => $amount,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $this->returnUrl,
            'ipnUrl'      => $this->notifyUrl,
            'extraData'   => $extraData,
            'requestType' => $requestType,
            'signature'   => $signature,
            'lang'        => 'vi',
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->endpoint, $body);

            $result = $response->json();

            return [
                'success'    => ($result['resultCode'] ?? -1) === 0,
                'pay_url'    => $result['payUrl'] ?? null,
                'deeplink'   => $result['deeplink'] ?? null,
                'qr_code'    => $result['qrCodeUrl'] ?? null,
                'request_id' => $requestId,
                'result_code'=> $result['resultCode'] ?? -1,
                'message'    => $result['message'] ?? 'Lỗi không xác định',
                'raw'        => $result,
            ];
        } catch (\Exception $e) {
            Log::error('MoMo payment request failed', ['error' => $e->getMessage()]);
            return [
                'success'    => false,
                'pay_url'    => null,
                'message'    => 'Không thể kết nối cổng thanh toán MoMo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Xác minh MAC từ MoMo IPN / callback
     */
    public function verifyMAC(array $data): bool
    {
        $receivedSignature = $data['signature'] ?? '';

        // Các field dùng để verify (theo tài liệu MoMo v2)
        $rawSignature = implode('&', [
            'accessKey='      . $this->accessKey,
            'amount='         . ($data['amount'] ?? ''),
            'extraData='      . ($data['extraData'] ?? ''),
            'message='        . ($data['message'] ?? ''),
            'orderId='        . ($data['orderId'] ?? ''),
            'orderInfo='      . ($data['orderInfo'] ?? ''),
            'orderType='      . ($data['orderType'] ?? ''),
            'partnerCode='    . ($data['partnerCode'] ?? ''),
            'payType='        . ($data['payType'] ?? ''),
            'requestId='      . ($data['requestId'] ?? ''),
            'responseTime='   . ($data['responseTime'] ?? ''),
            'resultCode='     . ($data['resultCode'] ?? ''),
            'transId='        . ($data['transId'] ?? ''),
        ]);

        $expectedSignature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Parse callback / IPN từ MoMo
     */
    public function parseCallback(array $data): array
    {
        $resultCode = (int) ($data['resultCode'] ?? -1);

        return [
            'success'       => $resultCode === 0,
            'order_id'      => $data['orderId'] ?? '',
            'request_id'    => $data['requestId'] ?? '',
            'amount'        => (float) ($data['amount'] ?? 0),
            'trans_id'      => $data['transId'] ?? '',
            'pay_type'      => $data['payType'] ?? '',       // qr | webApp | credit | napas
            'result_code'   => $resultCode,
            'message'       => $data['message'] ?? $this->getResultMessage($resultCode),
            'response_time' => $data['responseTime'] ?? '',
            'extra_data'    => isset($data['extraData']) ? json_decode(base64_decode($data['extraData']), true) ?? [] : [],
            'raw'           => $data,
        ];
    }

    /**
     * Tạo request hoàn tiền MoMo
     */
    public function buildRefundRequest(array $params): array
    {
        $requestId   = $params['request_id'] ?? 'REFUND_' . $params['order_id'] . '_' . time();
        $orderId     = $params['order_id'];
        $amount      = (string) (int) $params['amount'];
        $transId     = (string) ($params['trans_id'] ?? '');
        $description = $params['description'] ?? 'Hoàn tiền đơn hàng ' . $orderId;

        $rawSignature = implode('&', [
            'accessKey='   . $this->accessKey,
            'amount='      . $amount,
            'description=' . $description,
            'orderId='     . $orderId,
            'partnerCode=' . $this->partnerCode,
            'requestId='   . $requestId,
            'transId='     . $transId,
        ]);

        $signature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        $body = [
            'partnerCode' => $this->partnerCode,
            'orderId'     => $orderId,
            'requestId'   => $requestId,
            'amount'      => $amount,
            'transId'     => $transId,
            'lang'        => 'vi',
            'description' => $description,
            'signature'   => $signature,
        ];

        try {
            $refundEndpoint = str_replace('/create', '/refund', $this->endpoint);
            $response = Http::timeout(30)->post($refundEndpoint, $body);
            $result   = $response->json();

            return [
                'success'    => ($result['resultCode'] ?? -1) === 0,
                'result_code'=> $result['resultCode'] ?? -1,
                'message'    => $result['message'] ?? 'Lỗi không xác định',
                'raw'        => $result,
            ];
        } catch (\Exception $e) {
            Log::error('MoMo refund failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mã kết quả MoMo → message tiếng Việt
     */
    public function getResultMessage(int $code): string
    {
        return match ($code) {
            0    => 'Giao dịch thành công',
            9000 => 'Giao dịch được cấp phép (Giao dịch cho NotifyURL)',
            8000 => 'Giao dịch đang chờ xác nhận',
            7000 => 'Giao dịch đang được xử lý',
            1000 => 'Giao dịch được khởi tạo, chờ người dùng xác nhận',
            11   => 'Truy cập bị từ chối',
            12   => 'Phiên bản API không được hỗ trợ',
            13   => 'Xác thực merchant thất bại',
            20   => 'Request sai định dạng',
            21   => 'Số tiền không hợp lệ',
            22   => 'orderId không hợp lệ',
            40   => 'RequestId bị trùng',
            41   => 'OrderId bị trùng',
            42   => 'OrderId không tồn tại',
            43   => 'Yêu cầu bị từ chối vì xung đột',
            1001 => 'Thanh toán thất bại do tài khoản không đủ số dư',
            1002 => 'Giao dịch bị từ chối do nhà phát hành',
            1003 => 'Giao dịch bị hủy sau khi hết thời gian chờ',
            1004 => 'Số tiền vượt quá hạn mức thanh toán',
            1005 => 'URL thanh toán hết hạn hoặc đã được thanh toán',
            1006 => 'Giao dịch bị từ chối vì người dùng thao tác sai',
            1007 => 'Tài khoản MoMo không tồn tại hoặc bị khóa',
            1026 => 'Bị hạn chế theo quy định chính sách',
            2001 => 'Giao dịch hoàn tiền thất bại vì giao dịch gốc không tìm thấy',
            2007 => 'Giao dịch hoàn tiền thất bại vì số lần hoàn tiền vượt quá giới hạn',
            2005 => 'Giao dịch hoàn tiền thất bại vì số tiền vượt quá số tiền giao dịch ban đầu',
            default => "Lỗi không xác định (mã: {$code})",
        };
    }
}