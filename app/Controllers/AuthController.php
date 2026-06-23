<?php

namespace App\Controllers;

use Ace\Application;
use Ace\Controller;
use Ace\Request;
use App\Models\User;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\GuestMiddleware;

class AuthController extends Controller
{
    public function __construct()
    {
        // Apply GuestMiddleware to auth routes so logged-in users cannot access them
        $this->registerMiddleware(new GuestMiddleware([
            'loginView', 'login', 'registerView', 'register',
            'forgotPasswordView', 'forgotPassword', 'resetPasswordView', 'resetPassword'
        ]));
        
        // Apply AuthMiddleware to profile route so guests cannot access it
        $this->registerMiddleware(new AuthMiddleware(['profile']));

        // Apply CsrfMiddleware to authentication POST requests
        $this->registerMiddleware(new \App\Middlewares\CsrfMiddleware([
            'login', 'register', 'forgotPassword', 'resetPassword'
        ]));
    }

    /**
     * Render Login View
     */
    public function loginView(Request $request): string
    {
        $user = new User();
        return $this->render('login', [
            'model' => $user
        ]);
    }

    /**
     * Handle Login POST request
     */
    public function login(Request $request)
    {
        $user = new User();
        $data = $request->getBody();
        $user->loadData($data);

        // Simple validation rules check
        if (empty($data['email'])) {
            $user->addError('email', 'Email is required');
        }
        if (empty($data['password'])) {
            $user->addError('password', 'Password is required');
        }

        if (empty($user->errors)) {
            // Find user in database
            $dbUser = User::findOne(['email' => $data['email']]);
            if ($dbUser && password_verify($data['password'], $dbUser->password)) {
                $remember = !empty($data['remember']);
                Application::$app->login($dbUser, $remember);
                Application::$app->session->setFlash('success', 'Logged in successfully!');
                Application::$app->response->redirect('/profile');
            } else {
                $user->addError('email', 'Invalid email or password');
            }
        }

        return $this->render('login', [
            'model' => $user
        ]);
    }

    /**
     * Render Registration View
     */
    public function registerView(Request $request): string
    {
        $user = new User();
        return $this->render('register', [
            'model' => $user
        ]);
    }

    /**
     * Handle Registration POST request
     */
    public function register(Request $request)
    {
        $user = new User();
        $user->loadData($request->getBody());

        if ($user->validate() && $user->save()) {
            Application::$app->session->setFlash('success', 'Registration successful! Please login.');
            Application::$app->response->redirect('/login');
        }

        return $this->render('register', [
            'model' => $user
        ]);
    }

    /**
     * Handle Logout Request
     */
    public function logout(Request $request): void
    {
        Application::$app->logout();
        Application::$app->session->setFlash('success', 'Logged out successfully.');
        Application::$app->response->redirect('/');
    }

    /**
     * Render Profile View
     */
    public function profile(Request $request): string
    {
        return $this->render('profile', [
            'user' => Application::$app->user
        ]);
    }

    /**
     * Render Forgot Password View
     */
    public function forgotPasswordView(Request $request): string
    {
        return $this->render('forgot_password');
    }

    /**
     * Handle Forgot Password POST request
     */
    public function forgotPassword(Request $request)
    {
        $data = $request->getBody();
        $email = $data['email'] ?? '';

        if (empty($email)) {
            Application::$app->session->setFlash('error', 'Please enter your email address.');
            return $this->render('forgot_password');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Application::$app->session->setFlash('error', 'Please enter a valid email address.');
            return $this->render('forgot_password');
        }

        $user = User::findOne(['email' => $email]);
        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $db = Application::$app->db;
            if ($db) {
                // Delete previous resets for this email
                $stmt = $db->prepare("DELETE FROM password_resets WHERE email = :email");
                $stmt->execute(['email' => $email]);

                // Save new reset token
                $stmt = $db->prepare("INSERT INTO password_resets (email, token) VALUES (:email, :token)");
                $stmt->execute(['email' => $email, 'token' => $tokenHash]);
            }

            // Send password reset email
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $resetLink = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/reset-password?token=' . $token . '&email=' . urlencode($email);
            
            // Try sending the email
            $mailSent = \Ace\Mail::send(
                $email, 
                'Reset Your Password', 
                'emails/reset_password', 
                [
                    'name' => $user->name,
                    'resetLink' => $resetLink
                ]
            );

            if (!$mailSent) {
                // Fallback for development if SMTP is not configured
                Application::$app->session->setFlash('warning', 'A password reset link was generated, but the email could not be sent. Link: <a href="' . $resetLink . '">' . $resetLink . '</a>');
                return $this->render('forgot_password');
            }
        }

        // Always show success to prevent email harvesting
        Application::$app->session->setFlash('success', 'If an account exists with that email, we have sent a password reset link.');
        return $this->render('forgot_password');
    }

    /**
     * Render Reset Password View
     */
    public function resetPasswordView(Request $request): string
    {
        $data = $request->getBody();
        $token = $data['token'] ?? '';
        $email = $data['email'] ?? '';

        if (empty($token) || empty($email)) {
            Application::$app->session->setFlash('error', 'Invalid password reset request.');
            Application::$app->response->redirect('/login');
            return '';
        }

        // Check if token is valid (last 1 hour)
        $tokenHash = hash('sha256', $token);
        $db = Application::$app->db;
        $valid = false;
        if ($db) {
            $stmt = $db->prepare("
                SELECT * FROM password_resets 
                WHERE email = :email AND token = :token AND created_at > NOW() - INTERVAL 1 HOUR
                LIMIT 1
            ");
            $stmt->execute(['email' => $email, 'token' => $tokenHash]);
            $valid = (bool)$stmt->fetch();
        }

        if (!$valid) {
            Application::$app->session->setFlash('error', 'This password reset link is invalid or has expired.');
            Application::$app->response->redirect('/login');
            return '';
        }

        $user = new User();
        return $this->render('reset_password', [
            'token' => $token,
            'email' => $email,
            'model' => $user
        ]);
    }

    /**
     * Handle Reset Password POST request
     */
    public function resetPassword(Request $request)
    {
        $data = $request->getBody();
        $token = $data['token'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['passwordConfirm'] ?? '';

        if (empty($token) || empty($email)) {
            Application::$app->session->setFlash('error', 'Invalid password reset request.');
            Application::$app->response->redirect('/login');
            return '';
        }

        // Verify token again
        $tokenHash = hash('sha256', $token);
        $db = Application::$app->db;
        $validRecord = null;
        if ($db) {
            $stmt = $db->prepare("
                SELECT * FROM password_resets 
                WHERE email = :email AND token = :token AND created_at > NOW() - INTERVAL 1 HOUR
                LIMIT 1
            ");
            $stmt->execute(['email' => $email, 'token' => $tokenHash]);
            $validRecord = $stmt->fetch();
        }

        $user = new User();
        
        if (!$validRecord) {
            Application::$app->session->setFlash('error', 'This password reset link is invalid or has expired.');
            Application::$app->response->redirect('/login');
            return '';
        }

        // Validate password
        if (empty($password)) {
            $user->addError('password', 'Password is required');
        } elseif (strlen($password) < 6) {
            $user->addError('password', 'Password must be at least 6 characters');
        }

        if (empty($passwordConfirm)) {
            $user->addError('passwordConfirm', 'Confirm password is required');
        } elseif ($password !== $passwordConfirm) {
            $user->addError('passwordConfirm', 'Passwords do not match');
        }

        if (empty($user->errors)) {
            // Update password in users table
            $dbUser = User::findOne(['email' => $email]);
            if ($dbUser) {
                $dbUser->password = $password;
                if ($dbUser->save()) {
                    // Delete the token
                    $stmt = $db->prepare("DELETE FROM password_resets WHERE email = :email");
                    $stmt->execute(['email' => $email]);

                    Application::$app->session->setFlash('success', 'Your password has been successfully reset! Please login.');
                    Application::$app->response->redirect('/login');
                    return '';
                }
            }
            Application::$app->session->setFlash('error', 'An error occurred. Please try again.');
        }

        return $this->render('reset_password', [
            'token' => $token,
            'email' => $email,
            'model' => $user
        ]);
    }
}

