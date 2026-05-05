<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MoMoService
{
    private string $partnerCode;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private string $redirectUrl;
    private string $ipnUrl;
    private string $requestType;

    public function __construct()
    {
        $env = config('momo.environment', 'test');
        $this->partnerCode = config('momo.partner_code');
        $this->accessKey = config('momo.access_key');
        $this->secretKey = config('momo.secret_key');
        $this->endpoint = config('momo.endpoint.' . $env);
        $this->redirectUrl = config('momo.redirect_url');
        $this->ipnUrl = config('momo.ipn_url');
        $this->requestType = config('momo.request_type');
    }

    /**
     * Tạo request thanh toán MoMo
     *
     * @param string $orderId Mã đơn hàng (duy nhất)
     * @param int $amount Số tiền (VND)
     * @param string $orderInfo Thông tin đơn hàng
     * @param string $userId ID của user (để MoMo bind)
     * @return array Thông tin thanh toán bao gồm payUrl để redirect
     */
    public function createPayment(string $orderId, int $amount, string $orderInfo, string $userId): array
    {
        $requestId = (string) time() . Str::random(6);
        $extraData = base64_encode(json_encode(['user_id' => $userId]));

        // Build dữ liệu để tạo signature
        $rawData = sprintf(
            'accessKey=%s&amount=%d&extraData=%s&ipnUrl=%s&orderId=%s&orderInfo=%s&partnerCode=%s&redirectUrl=%s&requestId=%s&requestType=%s',
            $this->accessKey,
            $amount,
            $extraData,
            $this->ipnUrl,
            $orderId,
            $orderInfo,
            $this->partnerCode,
            $this->redirectUrl,
            $requestId,
            $this->requestType
        );

        $signature = hash_hmac('sha256', $rawData, $this->secretKey);

        $payload = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => 'DTU LingoAI',
            'storeId' => 'DTU_LingoAI_Store',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $this->redirectUrl,
            'ipnUrl' => $this->ipnUrl,
            'extraData' => $extraData,
            'requestType' => $this->requestType,
            'signature' => $signature,
            'lang' => 'vi',
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json; charset=UTF-8',
        ])->timeout(30)->post($this->endpoint . '/v2/gateway/api/create', $payload);

        $result = $response->json();

        if (isset($result['resultCode']) && $result['resultCode'] === 0) {
            return [
                'success' => true,
                'payUrl' => $result['payUrl'] ?? null,
                'deeplink' => $result['deeplink'] ?? null,
                'qrCodeUrl' => $result['qrCodeUrl'] ?? null,
                'orderId' => $orderId,
                'requestId' => $requestId,
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Không thể tạo yêu cầu thanh toán MoMo',
            'resultCode' => $result['resultCode'] ?? null,
        ];
    }

    /**
     * Xác minh signature từ callback/IPN của MoMo
     *
     * @param array $data Dữ liệu nhận được từ MoMo
     * @return bool
     */
    public function verifySignature(array $data): bool
    {
        // Các trường cần thiết cho signature verification
        $requiredFields = ['partnerCode', 'orderId', 'requestId', 'amount', 'orderInfo', 'orderType', 'transId', 'resultCode'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        // Build raw data theo thứ tự alphabet
        $rawData = sprintf(
            'accessKey=%s&amount=%d&extraData=%s&message=%s&orderId=%s&orderInfo=%s&orderType=%s&partnerClientId=%s&partnerCode=%s&payType=%s&requestId=%s&responseTime=%s&resultCode=%d&transId=%d',
            $this->accessKey,
            $data['amount'],
            $data['extraData'] ?? '',
            $data['message'] ?? '',
            $data['orderId'],
            $data['orderInfo'],
            $data['orderType'],
            $data['partnerClientId'] ?? '',
            $data['partnerCode'],
            $data['payType'] ?? '',
            $data['requestId'],
            $data['responseTime'] ?? 0,
            $data['resultCode'],
            $data['transId']
        );

        $signature = hash_hmac('sha256', $rawData, $this->secretKey);

        return isset($data['signature']) && hash_equals($signature, $data['signature']);
    }

    /**
     * Xử lý kết quả thanh toán từ redirect URL
     *
     * @param array $params Dữ liệu từ query string redirect
     * @return array Kết quả xử lý
     */
    public function handleRedirect(array $params): array
    {
        if (!isset($params['resultCode'])) {
            return [
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'status' => 'that-bai',
            ];
        }

        $resultCode = (int) $params['resultCode'];

        if ($resultCode === 0) {
            return [
                'success' => true,
                'message' => 'Thanh toán thành công',
                'status' => 'thanh-cong',
                'orderId' => $params['orderId'] ?? null,
                'transId' => $params['transId'] ?? null,
            ];
        }

        $errorMessages = [
            1001 => 'Giao dịch đang được xử lý',
            1002 => 'Giao dịch bị từ chối',
            1003 => 'Giao dịch bị hủy',
            1006 => 'Giao dịch bị giả mạo',
            1010 => 'Giao dịch không tồn tại',
            1023 => 'Số dư không đủ',
        ];

        return [
            'success' => false,
            'message' => $errorMessages[$resultCode] ?? 'Thanh toán thất bại',
            'status' => 'that-bai',
            'resultCode' => $resultCode,
        ];
    }

    /**
     * Xử lý IPN (Instant Payment Notification) từ MoMo
     *
     * @param array $data Dữ liệu IPN
     * @return array Kết quả xử lý
     */
    public function handleIpn(array $data): array
    {
        // Verify signature
        if (!$this->verifySignature($data)) {
            return [
                'success' => false,
                'message' => 'Signature không hợp lệ',
            ];
        }

        $resultCode = (int) $data['resultCode'];

        if ($resultCode === 0) {
            return [
                'success' => true,
                'message' => 'Thanh toán thành công',
                'status' => 'thanh-cong',
                'orderId' => $data['orderId'] ?? null,
                'transId' => $data['transId'] ?? null,
                'amount' => $data['amount'] ?? 0,
                'extraData' => $data['extraData'] ?? '',
            ];
        }

        return [
            'success' => false,
            'message' => 'Thanh toán thất bại hoặc đang xử lý',
            'status' => 'that-bai',
            'resultCode' => $resultCode,
        ];
    }

    /**
     * Lấy thông tin cấu hình endpoint
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Kiểm tra môi trường
     */
    public function isTestEnvironment(): bool
    {
        return config('momo.environment') === 'test';
    }
}
