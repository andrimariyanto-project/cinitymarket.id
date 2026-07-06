<?php
// ============================================
// cinitymarket.id - Tripay Payment Integration
// ============================================

class Tripay {
    private string $merchantCode;
    private string $apiKey;
    private string $privateKey;
    private bool $isProduction;
    private string $baseUrl;

    public function __construct() {
        $this->merchantCode = TRIPAY_MERCHANT_CODE;
        $this->apiKey       = TRIPAY_API_KEY;
        $this->privateKey   = TRIPAY_PRIVATE_KEY;
        $this->isProduction = TRIPAY_IS_PRODUCTION;
        $this->baseUrl      = $this->isProduction
            ? 'https://tripay.co.id/api/'
            : 'https://tripay.co.id/api-sandbox/';
    }

    private function request(string $endpoint, string $method = 'GET', array $data = []): array {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $result = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error) return ['success' => false, 'message' => $error];
        $decoded = json_decode($result, true);
        return $decoded ?? ['success' => false, 'message' => 'Invalid response'];
    }

    /** Get available payment channels */
    public function getChannels(): array {
        return $this->request('merchant/payment-channel');
    }

    /** Get payment channel fees */
    public function getFeeCalculation(string $code, float $amount): array {
        return $this->request('merchant/fee-calculator?' . http_build_query([
            'code'   => $code,
            'amount' => $amount
        ]));
    }

    /** Create closed transaction */
    public function createTransaction(array $orderData): array {
        $signature = hash_hmac('sha256',
            $this->merchantCode . $orderData['merchant_ref'] . $orderData['amount'],
            $this->privateKey
        );

        $payload = [
            'method'            => $orderData['payment_method'],
            'merchant_ref'      => $orderData['merchant_ref'],
            'amount'            => $orderData['amount'],
            'customer_name'     => $orderData['customer_name'],
            'customer_email'    => $orderData['customer_email'],
            'customer_phone'    => $orderData['customer_phone'],
            'order_items'       => $orderData['order_items'],
            'callback_url'      => APP_URL . '/api/payment-callback.php',
            'return_url'        => APP_URL . '/buyer/order-detail.php?order=' . $orderData['merchant_ref'],
            'expired_time'      => time() + (ORDER_EXPIRE_HOURS * 3600),
            'signature'         => $signature
        ];

        return $this->request('transaction/create', 'POST', $payload);
    }

    /** Get transaction detail */
    public function getTransaction(string $reference): array {
        return $this->request('transaction/detail?' . http_build_query(['reference' => $reference]));
    }

    /** Validate callback signature */
    public function validateCallback(array $payload): bool {
        $callbackSignature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
        $signature = hash_hmac('sha256', json_encode($payload), $this->privateKey);
        return hash_equals($signature, $callbackSignature);
    }
}
