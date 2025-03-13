<?php

declare(strict_types=1);

namespace Ace\Mailer;


class Mailer
{
    /**
     * @var array Mail configuration
     */
    private $config;

    /**
     * @var string Current environment (development or production)
     */
    private $environment;

    /**
     * @var array Collection of sent emails (for testing in development)
     */
    private $mailbox = [];

    /**
     * @var array Default mail headers
     */
    private $defaultHeaders = [];

    /**
     * @var array SMTP settings for production
     */
    private $smtpSettings = [];

    /**
     * @var array Attachments to send
     */
    private $attachments = [];

    /**
     * @var string Mail template directory
     */
    private $templateDir;

    /**
     * @var array Email queue for batch sending
     */
    private $queue = [];

    /**
     * @var bool Whether to use queuing for emails
     */
    private $useQueue = false;

    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct(array $config = [])
    {
        // Set defaults
        $defaultConfig = [
            'environment' => 'development', // development or production
            'from_email' => 'no-reply@example.com',
            'from_name' => 'Ace Mailer',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls', // tls or ssl
            'smtp_auth' => true,
            'template_dir' => __DIR__ . '/templates',
            'log_file' => __DIR__ . '/logs/mail.log',
            'use_queue' => false,
            'development_recipients' => [], // Override recipients in development
        ];

        $this->config = array_merge($defaultConfig, $config);
        $this->environment = $this->config['environment'];
        $this->templateDir = $this->config['template_dir'];
        $this->useQueue = $this->config['use_queue'];

        // Set up SMTP settings
        $this->smtpSettings = [
            'host' => $this->config['smtp_host'],
            'port' => $this->config['smtp_port'],
            'username' => $this->config['smtp_username'],
            'password' => $this->config['smtp_password'],
            'encryption' => $this->config['smtp_encryption'],
            'auth' => $this->config['smtp_auth'],
        ];

        // Set default headers
        $this->defaultHeaders = [
            'From' => $this->formatAddress($this->config['from_email'], $this->config['from_name']),
            'X-Mailer' => 'Advanced PHP Mailer',
            'Content-Type' => 'text/html; charset=UTF-8',
        ];

        // Create log directory if it doesn't exist
        $logDir = dirname($this->config['log_file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Set the environment
     *
     * @param string $environment 'development' or 'production'
     * @return self
     */
    public function setEnvironment(string $environment): self
    {
        if (!in_array($environment, ['development', 'production'])) {
            throw new \InvalidArgumentException("Environment must be 'development' or 'production'");
        }

        $this->environment = $environment;
        return $this;
    }

    /**
     * Format email address with name
     *
     * @param string $email Email address
     * @param string|null $name Name (optional)
     * @return string
     */
    private function formatAddress(string $email, ?string $name = null): string
    {
        if (empty($name)) {
            return $email;
        }

        // Encode the name to handle special characters
        $encodedName = '=?UTF-8?B?' . base64_encode($name) . '?=';
        return "{$encodedName} <{$email}>";
    }

    /**
     * Send an email
     *
     * @param string|array $to Recipient(s)
     * @param string $subject Email subject
     * @param string $body Email body
     * @param array $headers Additional headers
     * @return bool Success status
     */
    public function send($to, string $subject, string $body, array $headers = []): bool
    {
        // Prepare recipients
        $recipients = $this->prepareRecipients($to);

        // Prepare headers
        $allHeaders = array_merge($this->defaultHeaders, $headers);
        $headerString = $this->formatHeaders($allHeaders);

        // Create email data
        $emailData = [
            'to' => $recipients,
            'subject' => $subject,
            'body' => $body,
            'headers' => $allHeaders,
            'attachments' => $this->attachments,
            'date' => date('Y-m-d H:i:s'),
        ];

        // If queuing is enabled, add to queue
        if ($this->useQueue) {
            $this->queue[] = $emailData;
            $this->logMessage("Email queued to: " . implode(', ', $recipients));
            // Reset attachments for next email
            $this->attachments = [];
            return true;
        }

        // Handle based on environment
        if ($this->environment === 'development') {
            return $this->sendInDevelopment($emailData);
        } else {
            return $this->sendInProduction($emailData);
        }
    }

    /**
     * Prepare recipients
     *
     * @param string|array $to Recipient(s)
     * @return array
     */
    private function prepareRecipients($to): array
    {
        // Convert string to array
        if (!is_array($to)) {
            $to = [$to];
        }

        // In development, override recipients if configured
        if ($this->environment === 'development' && !empty($this->config['development_recipients'])) {
            return $this->config['development_recipients'];
        }

        return $to;
    }

    /**
     * Format headers for email
     *
     * @param array $headers Headers
     * @return string
     */
    private function formatHeaders(array $headers): string
    {
        $headerStrings = [];
        foreach ($headers as $name => $value) {
            $headerStrings[] = "{$name}: {$value}";
        }
        return implode("\r\n", $headerStrings);
    }

    /**
     * Send email in development environment
     *
     * @param array $emailData Email data
     * @return bool
     */
    private function sendInDevelopment(array $emailData): bool
    {
        // Store in mailbox for testing
        $this->mailbox[] = $emailData;

        // Log the email
        $this->logMessage("Development email sent to: " . implode(', ', $emailData['to']));

        // Reset attachments for next email
        $this->attachments = [];

        return true;
    }

    /**
     * Send email in production environment
     *
     * @param array $emailData Email data
     * @return bool
     */
    private function sendInProduction(array $emailData): bool
    {
        try {
            // Use SMTP to send the email
            $success = $this->sendSmtp($emailData);

            if ($success) {
                $this->logMessage("Production email sent to: " . implode(', ', $emailData['to']));
            } else {
                $this->logMessage("Failed to send production email to: " . implode(', ', $emailData['to']), 'ERROR');
            }

            // Reset attachments for next email
            $this->attachments = [];

            return $success;
        } catch (\Exception $e) {
            $this->logMessage("Exception sending email: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Send email using SMTP
     *
     * @param array $emailData Email data
     * @return bool
     */
    private function sendSmtp(array $emailData): bool
    {
        // Create connection
        $smtp = fsockopen(
            ($this->smtpSettings['encryption'] === 'ssl' ? 'ssl://' : '') . $this->smtpSettings['host'],
            $this->smtpSettings['port'],
            $errno,
            $errstr,
            30
        );

        if (!$smtp) {
            $this->logMessage("SMTP connection failed: {$errstr} ({$errno})", 'ERROR');
            return false;
        }

        // Set timeout
        stream_set_timeout($smtp, 30);

        // Read greeting
        $this->getSmtpResponse($smtp);

        // Say hello
        fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $this->getSmtpResponse($smtp);

        // Start TLS if needed
        if ($this->smtpSettings['encryption'] === 'tls') {
            fputs($smtp, "STARTTLS\r\n");
            $this->getSmtpResponse($smtp);
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // Say hello again after TLS
            fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $this->getSmtpResponse($smtp);
        }

        // Authenticate if needed
        if ($this->smtpSettings['auth']) {
            fputs($smtp, "AUTH LOGIN\r\n");
            $this->getSmtpResponse($smtp);

            fputs($smtp, base64_encode($this->smtpSettings['username']) . "\r\n");
            $this->getSmtpResponse($smtp);

            fputs($smtp, base64_encode($this->smtpSettings['password']) . "\r\n");
            $this->getSmtpResponse($smtp);
        }

        // Set from
        $from = $this->config['from_email'];
        fputs($smtp, "MAIL FROM:<{$from}>\r\n");
        $this->getSmtpResponse($smtp);

        // Add recipients
        foreach ($emailData['to'] as $recipient) {
            fputs($smtp, "RCPT TO:<{$recipient}>\r\n");
            $this->getSmtpResponse($smtp);
        }

        // Start data
        fputs($smtp, "DATA\r\n");
        $this->getSmtpResponse($smtp);

        // Assemble email
        $email = "Subject: {$emailData['subject']}\r\n";

        // Add headers
        foreach ($emailData['headers'] as $name => $value) {
            $email .= "{$name}: {$value}\r\n";
        }

        // Add date
        $email .= "Date: " . date('r') . "\r\n";

        // Add boundary for attachments if needed
        $boundary = null;
        if (!empty($emailData['attachments'])) {
            $boundary = md5(time());
            $email .= "MIME-Version: 1.0\r\n";
            $email .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
            $email .= "\r\n--{$boundary}\r\n";
            $email .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $email .= chunk_split(base64_encode($emailData['body']));

            // Add attachments
            foreach ($emailData['attachments'] as $attachment) {
                $email .= "\r\n--{$boundary}\r\n";
                $email .= "Content-Type: {$attachment['type']}; name=\"{$attachment['name']}\"\r\n";
                $email .= "Content-Transfer-Encoding: base64\r\n";
                $email .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n\r\n";
                $email .= chunk_split(base64_encode($attachment['content']));
            }

            $email .= "\r\n--{$boundary}--\r\n";
        } else {
            // Add body without attachments
            $email .= "\r\n" . $emailData['body'];
        }

        // End message
        fputs($smtp, $email . "\r\n.\r\n");
        $this->getSmtpResponse($smtp);

        // Close connection
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);

        return true;
    }

    /**
     * Get SMTP response
     *
     * @param resource $smtp SMTP connection
     * @return string
     */
    private function getSmtpResponse($smtp): string
    {
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            // If line doesn't start with a dash, it's the last line
            if (substr($line, 3, 1) !== '-') {
                break;
            }
        }

        $code = substr($response, 0, 3);
        if ($code >= 400) {
            $this->logMessage("SMTP Error: {$response}", 'ERROR');
            throw new \Exception("SMTP Error: {$response}");
        }

        return $response;
    }

    /**
     * Add an attachment
     *
     * @param string $path File path
     * @param string|null $name Custom filename (optional)
     * @return self
     */
    public function addAttachment(string $path, ?string $name = null): self
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("File not found: {$path}");
        }

        $content = file_get_contents($path);
        $filename = $name ?? basename($path);
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        $this->attachments[] = [
            'name' => $filename,
            'content' => $content,
            'type' => $mime,
        ];

        return $this;
    }

    /**
     * Add attachment from string
     *
     * @param string $content File content
     * @param string $name Filename
     * @param string $mime MIME type
     * @return self
     */
    public function addAttachmentFromString(string $content, string $name, string $mime = 'application/octet-stream'): self
    {
        $this->attachments[] = [
            'name' => $name,
            'content' => $content,
            'type' => $mime,
        ];

        return $this;
    }

    /**
     * Send email using a template
     *
     * @param string|array $to Recipient(s)
     * @param string $subject Email subject
     * @param string $template Template name
     * @param array $data Data for template variables
     * @param array $headers Additional headers
     * @return bool Success status
     */
    public function sendTemplate($to, string $subject, string $template, array $data = [], array $headers = []): bool
    {
        $templatePath = $this->templateDir . '/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException("Template not found: {$template}");
        }

        // Extract data to variables for use in template
        extract($data);

        // Start output buffer
        ob_start();
        include $templatePath;
        $body = ob_get_clean();

        // Send the email
        return $this->send($to, $subject, $body, $headers);
    }

    /**
     * Get sent emails (for testing in development)
     *
     * @return array
     */
    public function getMailbox(): array
    {
        return $this->mailbox;
    }

    /**
     * Clear sent emails
     *
     * @return self
     */
    public function clearMailbox(): self
    {
        $this->mailbox = [];
        return $this;
    }

    /**
     * Enable or disable queue mode
     *
     * @param bool $useQueue Whether to use queue
     * @return self
     */
    public function useQueue(bool $useQueue = true): self
    {
        $this->useQueue = $useQueue;
        return $this;
    }

    /**
     * Get current queue
     *
     * @return array
     */
    public function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * Process the queue
     *
     * @param int $batchSize Number of emails to process at once (0 = all)
     * @return array Results for each email [success_count, fail_count]
     */
    public function processQueue(int $batchSize = 0): array
    {
        if (empty($this->queue)) {
            return [0, 0];
        }

        // Determine how many emails to process
        $count = $batchSize > 0 ? min($batchSize, count($this->queue)) : count($this->queue);
        $successCount = 0;
        $failCount = 0;

        // Process emails
        for ($i = 0; $i < $count; $i++) {
            $emailData = array_shift($this->queue);
            $this->attachments = $emailData['attachments'];
            $result = $this->send(
                $emailData['to'],
                $emailData['subject'],
                $emailData['body'],
                $emailData['headers']
            );

            if ($result) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [$successCount, $failCount];
    }

    /**
     * Set the "From" address
     *
     * @param string $email Email address
     * @param string|null $name Name (optional)
     * @return self
     */
    public function setFrom(string $email, ?string $name = null): self
    {
        $this->defaultHeaders['From'] = $this->formatAddress($email, $name);
        return $this;
    }

    /**
     * Add a "CC" recipient
     *
     * @param string $email Email address
     * @param string|null $name Name (optional)
     * @return self
     */
    public function addCc(string $email, ?string $name = null): self
    {
        $address = $this->formatAddress($email, $name);

        // Initialize CC header if it doesn't exist
        if (!isset($this->defaultHeaders['Cc'])) {
            $this->defaultHeaders['Cc'] = $address;
        } else {
            $this->defaultHeaders['Cc'] .= ', ' . $address;
        }

        return $this;
    }

    /**
     * Add a "BCC" recipient
     *
     * @param string $email Email address
     * @param string|null $name Name (optional)
     * @return self
     */
    public function addBcc(string $email, ?string $name = null): self
    {
        $address = $this->formatAddress($email, $name);

        // Initialize BCC header if it doesn't exist
        if (!isset($this->defaultHeaders['Bcc'])) {
            $this->defaultHeaders['Bcc'] = $address;
        } else {
            $this->defaultHeaders['Bcc'] .= ', ' . $address;
        }

        return $this;
    }

    /**
     * Add reply-to address
     *
     * @param string $email Email address
     * @param string|null $name Name (optional)
     * @return self
     */
    public function setReplyTo(string $email, ?string $name = null): self
    {
        $this->defaultHeaders['Reply-To'] = $this->formatAddress($email, $name);
        return $this;
    }

    /**
     * Log a message
     *
     * @param string $message Message to log
     * @param string $level Log level
     * @return void
     */
    private function logMessage(string $message, string $level = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        file_put_contents($this->config['log_file'], $logMessage, FILE_APPEND);
    }

    /**
     * Test the SMTP connection
     *
     * @return bool Connection successful
     */
    public function testSmtpConnection(): bool
    {
        try {
            $smtp = fsockopen(
                ($this->smtpSettings['encryption'] === 'ssl' ? 'ssl://' : '') . $this->smtpSettings['host'],
                $this->smtpSettings['port'],
                $errno,
                $errstr,
                10
            );

            if (!$smtp) {
                $this->logMessage("SMTP connection test failed: {$errstr} ({$errno})", 'ERROR');
                return false;
            }

            // Close connection
            fclose($smtp);
            $this->logMessage("SMTP connection test successful", 'INFO');
            return true;

        } catch (\Exception $e) {
            $this->logMessage("SMTP connection test exception: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Set content type
     *
     * @param string $contentType Content type (text/html or text/plain)
     * @return self
     */
    public function setContentType(string $contentType): self
    {
        $validTypes = ['text/html', 'text/plain'];
        if (!in_array($contentType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid content type. Must be one of: " . implode(', ', $validTypes));
        }

        $this->defaultHeaders['Content-Type'] = $contentType . '; charset=UTF-8';
        return $this;
    }
}