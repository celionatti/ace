<?php

namespace Ace;

use Exception;

class FlutterwaveService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl = 'https://api.flutterwave.com/v3';

    public function __construct()
    {
        $this->secretKey = $_ENV['FLUTTERWAVE_SECRET_KEY'] ?? '';
        $this->publicKey = $_ENV['FLUTTERWAVE_PUBLIC_KEY'] ?? '';

        if (empty($this->secretKey) && ($_ENV['APP_ENV'] ?? 'development') === 'production') {
            throw new Exception("Flutterwave Secret Key is missing in .env config", 500);
        }
    }

    /**
     * Initialize a Flutterwave Standard Payment
     * 
     * @param string $email Customer email address
     * @param float $amount Amount to charge
     * @param string $redirectUrl Redirect URL on completion
     * @param string $reference Local unique transaction reference
     * @return string Redirect checkout URL returned from Flutterwave
     */
    public function initializePayment(string $email, float $amount, string $redirectUrl, string $reference): string
    {
        $url = $this->baseUrl . "/payments";

        $fields = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => 'NGN', // Default to NGN (or others)
            'redirect_url' => $redirectUrl,
            'customer' => [
                'email' => $email,
                'name' => 'Customer (' . $email . ')'
            ],
            'customizations' => [
                'title' => 'Fund Account',
                'description' => 'Account Fund Deposit'
            ]
        ];

        $response = $this->makeRequest('POST', $url, $fields);

        if (isset($response['status']) && $response['status'] === 'success') {
            return $response['data']['link'];
        }

        throw new Exception("Flutterwave Initialization Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Verify a Flutterwave Transaction ID
     */
    public function verifyTransaction(string $transactionId): array
    {
        $url = $this->baseUrl . "/transactions/" . rawurlencode($transactionId) . "/verify";
        $response = $this->makeRequest('GET', $url);

        if (isset($response['status']) && $response['status'] === 'success') {
            return $response['data'];
        }

        throw new Exception("Flutterwave Verification Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Helper to perform HTTP request via cURL with Bearer Token Authorization
     */
    private function makeRequest(string $method, string $url, array $fields = []): array
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $headers = [
            "Authorization: Bearer " . $this->secretKey,
            "Content-Type: application/json"
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error_msg, 500);
        }

        curl_close($ch);

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new Exception("Invalid response received from Flutterwave API.", 500);
        }

        return $decoded;
    }
}

