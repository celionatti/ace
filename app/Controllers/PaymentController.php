<?php

namespace App\Controllers;

use Ace\Application;
use Ace\Controller;
use Ace\Request;
use Ace\PaystackService;
use Ace\StripeService;
use Ace\FlutterwaveService;
use App\Models\Transaction;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use Exception;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Require auth for payment forms and all payment actions
        $this->registerMiddleware(new AuthMiddleware(['payView', 'initialize', 'callback', 'stripeInitialize', 'stripeCallback', 'flutterwaveInitialize', 'flutterwaveCallback']));
        
        // Protect POST/initialize endpoints with CsrfMiddleware
        $this->registerMiddleware(new CsrfMiddleware(['initialize', 'stripeInitialize', 'flutterwaveInitialize']));
    }

    /**
     * Render Payment Page showing logs and option to fund account
     */
    public function payView(Request $request): string
    {
        $transactions = [];
        if (!Application::isGuest()) {
            $transactions = Transaction::find(['user_id' => Application::$app->user->id]);
        }

        return $this->render('pay', [
            'user' => Application::$app->user,
            'transactions' => $transactions
        ]);
    }

    /**
     * Dynamic base url generator for callback routes
     */
    private function getCallbackUrl(string $path): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseDir = dirname($scriptName);
        $baseDir = str_replace('\\', '/', $baseDir);
        return $protocol . $host . rtrim($baseDir, '/') . $path;
    }

    /**
     * Initialize Paystack Payment
     */
    public function initialize(Request $request)
    {
        $body = $request->getBody();
        $amount = (float)($body['amount'] ?? 0);
        $email = $body['email'] ?? '';

        if ($amount <= 0 || empty($email)) {
            Application::$app->session->setFlash('error', 'Please provide a valid email and amount.');
            Application::$app->response->redirect('/pay');
        }

        try {
            $paystack = new PaystackService();
            $reference = 'PAY_' . uniqid() . '_' . time();
            $callbackUrl = $this->getCallbackUrl('/pay/callback');

            $paystackData = $paystack->initializeTransaction($email, $amount, $callbackUrl, $reference);

            $transaction = new Transaction();
            $transaction->user_id = Application::$app->user->id ?? null;
            $transaction->reference = $reference;
            $transaction->amount = $amount;
            $transaction->status = 'pending';
            $transaction->email = $email;
            $transaction->save();

            Application::$app->response->redirect($paystackData['authorization_url']);
        } catch (Exception $e) {
            Application::$app->session->setFlash('error', 'Paystack initialization failed: ' . $e->getMessage());
            Application::$app->response->redirect('/pay');
        }
    }

    /**
     * Handle Paystack Callback redirect
     */
    public function callback(Request $request)
    {
        $params = $request->getBody();
        $reference = $params['reference'] ?? null;

        if (!$reference) {
            Application::$app->session->setFlash('error', 'No payment reference found.');
            Application::$app->response->redirect('/pay');
        }

        try {
            $paystack = new PaystackService();
            $verification = $paystack->verifyTransaction($reference);
            $transaction = Transaction::findOne(['reference' => $reference]);

            if ($transaction) {
                if ($verification['status'] === 'success') {
                    $transaction->status = 'success';
                    Application::$app->session->setFlash('success', 'Payment via Paystack was successful!');
                } else {
                    $transaction->status = 'failed';
                    Application::$app->session->setFlash('error', 'Payment verification reports failure.');
                }
                $transaction->save();
            }
        } catch (Exception $e) {
            Application::$app->session->setFlash('error', 'Paystack verification failed: ' . $e->getMessage());
        }

        Application::$app->response->redirect('/pay');
    }

    /**
     * Initialize Stripe Checkout Session
     */
    public function stripeInitialize(Request $request)
    {
        $body = $request->getBody();
        $amount = (float)($body['amount'] ?? 0);
        $email = $body['email'] ?? '';

        if ($amount <= 0 || empty($email)) {
            Application::$app->session->setFlash('error', 'Please provide a valid email and amount.');
            Application::$app->response->redirect('/pay');
        }

        try {
            $stripe = new StripeService();
            $reference = 'STRIPE_' . uniqid() . '_' . time();
            $successUrl = $this->getCallbackUrl('/pay/stripe/callback');
            $cancelUrl = $this->getCallbackUrl('/pay');

            $stripeData = $stripe->initializeCheckout($email, $amount, $successUrl, $cancelUrl, $reference);

            $transaction = new Transaction();
            $transaction->user_id = Application::$app->user->id ?? null;
            $transaction->reference = $reference;
            $transaction->amount = $amount;
            $transaction->status = 'pending';
            $transaction->email = $email;
            $transaction->save();

            Application::$app->response->redirect($stripeData['checkout_url']);
        } catch (Exception $e) {
            Application::$app->session->setFlash('error', 'Stripe initialization failed: ' . $e->getMessage());
            Application::$app->response->redirect('/pay');
        }
    }

    /**
     * Handle Stripe Checkout Callback
     */
    public function stripeCallback(Request $request)
    {
        $params = $request->getBody();
        $sessionId = $params['session_id'] ?? null;
        $reference = $params['reference'] ?? null;

        if (!$sessionId || !$reference) {
            Application::$app->session->setFlash('error', 'Stripe payment verification params missing.');
            Application::$app->response->redirect('/pay');
        }

        try {
            $stripe = new StripeService();
            $sessionData = $stripe->verifySession($sessionId);
            $transaction = Transaction::findOne(['reference' => $reference]);

            if ($transaction) {
                if ($sessionData['payment_status'] === 'paid') {
                    $transaction->status = 'success';
                    Application::$app->session->setFlash('success', 'Payment via Stripe was successful!');
                } else {
                    $transaction->status = 'failed';
                    Application::$app->session->setFlash('error', 'Stripe checkout reports unpaid status.');
                }
                $transaction->save();
            }
        } catch (Exception $e) {
            Application::$app->session->setFlash('error', 'Stripe session verification failed: ' . $e->getMessage());
        }

        Application::$app->response->redirect('/pay');
    }

    /**
     * Initialize Flutterwave Payment
     */
    public function flutterwaveInitialize(Request $request)
    {
        $body = $request->getBody();
        $amount = (float)($body['amount'] ?? 0);
        $email = $body['email'] ?? '';

        if ($amount <= 0 || empty($email)) {
            Application::$app->session->setFlash('error', 'Please provide a valid email and amount.');
            Application::$app->response->redirect('/pay');
        }

        try {
            $flutterwave = new FlutterwaveService();
            $reference = 'FLW_' . uniqid() . '_' . time();
            $redirectUrl = $this->getCallbackUrl('/pay/flutterwave/callback');

            $checkoutUrl = $flutterwave->initializePayment($email, $amount, $redirectUrl, $reference);

            $transaction = new Transaction();
            $transaction->user_id = Application::$app->user->id ?? null;
            $transaction->reference = $reference;
            $transaction->amount = $amount;
            $transaction->status = 'pending';
            $transaction->email = $email;
            $transaction->save();

            Application::$app->response->redirect($checkoutUrl);
        } catch (Exception $e) {
            Application::$app->session->setFlash('error', 'Flutterwave initialization failed: ' . $e->getMessage());
            Application::$app->response->redirect('/pay');
        }
    }

    /**
     * Handle Flutterwave Callback
     */
    public function flutterwaveCallback(Request $request)
    {
        $params = $request->getBody();
        $status = $params['status'] ?? '';
        $txRef = $params['tx_ref'] ?? '';
        $transactionId = $params['transaction_id'] ?? '';

        if (!$txRef || !$transactionId) {
            Application::$app->session->setFlash('error', 'Flutterwave payment verification parameters missing.');
            Application::$app->response->redirect('/pay');
        }

        try {
            $flutterwave = new FlutterwaveService();
            $verificationData = $flutterwave->verifyTransaction($transactionId);
            $transaction = Transaction::findOne(['reference' => $txRef]);

            if ($transaction) {
                if ($verificationData['status'] === 'successful' && $verificationData['amount'] >= $transaction->amount) {
                    $transaction->status = 'success';
                    Application::$app->session->setFlash('success', 'Payment via Flutterwave was successful!');
                } else {
                    $transaction->status = 'failed';
                    Application::$app->session->setFlash('error', 'Flutterwave transaction verification failed.');
                }
                $transaction->save();
            }
        } catch (Exception $e) {
            Application::$app->session->setFlash('error', 'Flutterwave verification failed: ' . $e->getMessage());
        }

        Application::$app->response->redirect('/pay');
    }

    /**
     * Handle Paystack Webhook
     */
    public function webhook(Request $request): void
    {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

        if (empty($payload) || empty($signature)) {
            Application::$app->response->json(['status' => 'error', 'message' => 'Empty payload or signature'], 400);
        }

        try {
            $paystack = new PaystackService();
            $isValid = $paystack->validateWebhook($payload, $signature);

            if (!$isValid) {
                Application::$app->response->json(['status' => 'error', 'message' => 'Signature mismatch'], 400);
            }

            $event = json_decode($payload, true);
            if (isset($event['event']) && $event['event'] === 'charge.success') {
                $data = $event['data'];
                $reference = $data['reference'];
                $status = $data['status'];

                $transaction = Transaction::findOne(['reference' => $reference]);
                if ($transaction && $transaction->status !== 'success') {
                    $transaction->status = $status;
                    $transaction->save();
                }
            }

            Application::$app->response->json(['status' => 'success'], 200);
        } catch (Exception $e) {
            Application::$app->response->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}

