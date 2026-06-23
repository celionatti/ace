<?php

namespace Ace;

use Exception;

class PaystackService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
        $this->publicKey = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';

        if (empty($this->secretKey)) {
            // Log warning or throw exception if secret key is empty in production
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                throw new Exception("Paystack Secret Key is missing in .env config", 500);
            }
        }
    }

    /**
     * Initialize a Paystack transaction
     * 
     * @param string $email Customer email address
     * @param float $amount Amount in local currency (Naira, GHS, etc.) - will be converted to kobo
     * @param string $callbackUrl URL to redirect to after successful payment
     * @param string|null $reference Optional unique transaction reference
     * @return array Contains 'authorization_url', 'access_code', and 'reference'
     */
    public function initializeTransaction(string $email, float $amount, string $callbackUrl, ?string $reference = null): array
    {
        $url = $this->baseUrl . "/transaction/initialize";

        // Paystack expects amount in Kobo (or minor units)
        $amountInKobo = (int)round($amount * 100);

        if (!$reference) {
            $reference = 'PAY_' . uniqid() . '_' . time();
        }

        $fields = [
            'email' => $email,
            'amount' => $amountInKobo,
            'callback_url' => $callbackUrl,
            'reference' => $reference
        ];

        $response = $this->makeRequest('POST', $url, $fields);

        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'];
        }

        throw new Exception("Paystack Initialization Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Verify a Paystack transaction reference
     */
    public function verifyTransaction(string $reference): array
    {
        $url = $this->baseUrl . "/transaction/verify/" . rawurlencode($reference);
        $response = $this->makeRequest('GET', $url);

        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'];
        }

        throw new Exception("Paystack Verification Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Validate a Paystack Webhook Signature to ensure request authenticity
     */
    public function validateWebhook(string $payload, string $signatureHeader): bool
    {
        $calculatedSignature = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($calculatedSignature, $signatureHeader);
    }

    /**
     * Helper to perform HTTP request via cURL
     */
    private function makeRequest(string $method, string $url, array $fields = []): array
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $headers = [
            "Authorization: Bearer " . $this->secretKey,
            "Cache-Control: no-cache",
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
            throw new Exception("Invalid response received from payment gateway.", 500);
        }

        return $decoded;
    }
}

