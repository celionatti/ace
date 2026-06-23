<?php

namespace Ace;

use Exception;

class StripeService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->secretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        $this->publicKey = $_ENV['STRIPE_PUBLIC_KEY'] ?? '';

        if (empty($this->secretKey) && ($_ENV['APP_ENV'] ?? 'development') === 'production') {
            throw new Exception("Stripe Secret Key is missing in .env config", 500);
        }
    }

    /**
     * Initialize a Stripe Checkout Session
     * 
     * @param string $email Customer email address
     * @param float $amount Amount in USD (Stripe uses currency units)
     * @param string $successUrl Redirect URL on success
     * @param string $cancelUrl Redirect URL on cancellation
     * @param string $reference Local unique transaction reference
     * @return array Contains 'checkout_url' and 'session_id'
     */
    public function initializeCheckout(string $email, float $amount, string $successUrl, string $cancelUrl, string $reference): array
    {
        $url = $this->baseUrl . "/checkout/sessions";
        $amountInCents = (int)round($amount * 100);

        // Parameters in URL-encoded form data format
        $fields = [
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Account Fund Deposit (Ref: ' . $reference . ')',
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}&reference=' . urlencode($reference),
            'cancel_url' => $cancelUrl . '?reference=' . urlencode($reference),
            'customer_email' => $email,
            'client_reference_id' => $reference
        ];

        // Format fields for application/x-www-form-urlencoded
        $postQuery = http_build_query($fields);
        
        $response = $this->makeRequest('POST', $url, $postQuery);

        if (isset($response['id'])) {
            return [
                'checkout_url' => $response['url'],
                'session_id' => $response['id'],
            ];
        }

        throw new Exception("Stripe Session Initialization Failed: " . ($response['error']['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Verify a Stripe Checkout Session status
     */
    public function verifySession(string $sessionId): array
    {
        $url = $this->baseUrl . "/checkout/sessions/" . rawurlencode($sessionId);
        $response = $this->makeRequest('GET', $url);

        if (isset($response['id'])) {
            return $response;
        }

        throw new Exception("Stripe Session Verification Failed: " . ($response['error']['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Helper to perform HTTP request via cURL with Basic Authentication
     */
    private function makeRequest(string $method, string $url, ?string $postFields = null): array
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        // Basic Auth: Stripe Secret Key as Username, empty Password
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey . ":");

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }

        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error_msg, 500);
        }

        curl_close($ch);

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new Exception("Invalid response received from Stripe API.", 500);
        }

        return $decoded;
    }
}

