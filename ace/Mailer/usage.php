<?php

namespace App\Services;

use App\Mail\AdvancedMailer;

/**
 * Example service class to demonstrate AdvancedMailer usage
 */
class MailService
{
    /**
     * @var AdvancedMailer
     */
    private $mailer;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Load configuration from environment or configuration file
        $config = [
            'environment' => getenv('APP_ENV') ?: 'development',
            'from_email' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@example.com',
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'Your Application',
            'smtp_host' => getenv('MAIL_HOST') ?: 'smtp.example.com',
            'smtp_port' => getenv('MAIL_PORT') ?: 587,
            'smtp_username' => getenv('MAIL_USERNAME') ?: '',
            'smtp_password' => getenv('MAIL_PASSWORD') ?: '',
            'smtp_encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
            'template_dir' => __DIR__ . '/../resources/views/emails',
            'log_file' => __DIR__ . '/../storage/logs/mail.log',
            'development_recipients' => ['dev@example.com'],
        ];

        // Initialize mailer
        $this->mailer = new AdvancedMailer($config);
    }

    /**
     * Send welcome email to a new user
     *
     * @param string $email User email
     * @param string $name User name
     * @return bool Success status
     */
    public function sendWelcomeEmail(string $email, string $name): bool
    {
        $subject = 'Welcome to ' . getenv('APP_NAME');

        // Data for template
        $templateData = [
            'name' => $name,
            'message' => '<p>Thank you for registering with our application. We\'re excited to have you on board!</p>',
            'callToAction' => 'Get Started',
            'callToActionUrl' => getenv('APP_URL') . '/dashboard',
            'companyName' => getenv('APP_NAME'),
            'unsubscribeUrl' => getenv('APP_URL') . '/preferences/notifications'
        ];

        return $this->mailer->sendTemplate($email, $subject, 'welcome', $templateData);
    }

    /**
     * Send password reset email
     *
     * @param string $email User email
     * @param string $name User name
     * @param string $token Reset token
     * @return bool Success status
     */
    public function sendPasswordResetEmail(string $email, string $name, string $token): bool
    {
        $subject = 'Password Reset Request';

        $resetUrl = getenv('APP_URL') . '/reset-password?token=' . urlencode($token);

        // Data for template
        $templateData = [
            'name' => $name,
            'message' => '<p>We received a request to reset your password. Click the button below to set a new password:</p>',
            'callToAction' => 'Reset Password',
            'callToActionUrl' => $resetUrl,
            'companyName' => getenv('APP_NAME')
        ];

        // Add note about token expiration
        $templateData['message'] .= '<p>This link will expire in 60 minutes. If you didn\'t request a password reset, please ignore this email.</p>';

        return $this->mailer->sendTemplate($email, $subject, 'password-reset', $templateData);
    }

    /**
     * Send a notification with attachment
     *
     * @param string $email User email
     * @param string $name User name
     * @param string $subject Email subject
     * @param string $message Email message
     * @param string $attachmentPath Path to attachment
     * @return bool Success status
     */
    public function sendNotificationWithAttachment(string $email, string $name, string $subject, string $message, string $attachmentPath): bool
    {
        // Data for template
        $templateData = [
            'name' => $name,
            'message' => '<p>' . htmlspecialchars($message) . '</p>',
            'companyName' => getenv('APP_NAME')
        ];

        // Add attachment
        $this->mailer->addAttachment($attachmentPath);

        return $this->mailer->sendTemplate($email, $subject, 'notification', $templateData);
    }

    /**
     * Queue a batch of emails
     *
     * @param array $emails Array of [email, name, subject, message]
     * @return int Number of queued emails
     */
    public function queueBatchEmails(array $emails): int
    {
        // Enable queue mode
        $this->mailer->useQueue(true);

        $count = 0;
        foreach ($emails as $emailData) {
            $templateData = [
                'name' => $emailData['name'],
                'message' => '<p>' . htmlspecialchars($emailData['message']) . '</p>',
                'companyName' => getenv('APP_NAME')
            ];

            $result = $this->mailer->sendTemplate(
                $emailData['email'],
                $emailData['subject'],
                'notification',
                $templateData
            );

            if ($result) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Process the email queue
     *
     * @param int $batchSize Number of emails to process
     * @return array Results [success_count, fail_count]
     */
    public function processQueue(int $batchSize = 50): array
    {
        return $this->mailer->processQueue($batchSize);
    }

    /**
     * Send a test email to verify configuration
     *
     * @param string $testEmail Email to send test to
     * @return bool Success status
     */
    public function sendTestEmail(string $testEmail): bool
    {
        $subject = 'Test Email from ' . getenv('APP_NAME');

        $templateData = [
            'name' => 'Administrator',
            'message' => '<p>This is a test email to verify that your email configuration is working correctly.</p>',
            'companyName' => getenv('APP_NAME')
        ];

        return $this->mailer->sendTemplate($testEmail, $subject, 'notification', $templateData);
    }

    /**
     * Get emails sent in development mode
     *
     * @return array
     */
    public function getDevMailbox(): array
    {
        return $this->mailer->getMailbox();
    }

    /**
     * Test SMTP connection
     *
     * @return bool Connection successful
     */
    public function testSmtpConnection(): bool
    {
        return $this->mailer->testSmtpConnection();
    }
}