<?php

namespace Ace;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    /**
     * Send an email using a view template for the body.
     *
     * @param string|array $to Single email address or array of addresses
     * @param string $subject The email subject
     * @param string $view The view template path for the HTML body
     * @param array $params Variables to pass to the view
     * @return bool True if sent, false otherwise
     */
    public static function send(string|array $to, string $subject, string $view, array $params = []): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST', '127.0.0.1');
            $mail->SMTPAuth   = !empty(env('MAIL_USERNAME'));
            if ($mail->SMTPAuth) {
                $mail->Username = env('MAIL_USERNAME');
                $mail->Password = env('MAIL_PASSWORD');
            }
            
            $encryption = env('MAIL_ENCRYPTION', '');
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->Port = (int) env('MAIL_PORT', 2525);

            // Recipients
            $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@localhost');
            $fromName = env('MAIL_FROM_NAME', 'Application');
            $mail->setFrom($fromAddress, $fromName);

            if (is_array($to)) {
                foreach ($to as $address) {
                    $mail->addAddress($address);
                }
            } else {
                $mail->addAddress($to);
            }

            // Render view content for the body
            $body = Application::$app->view->render($view, $params);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (Exception $e) {
            Logger::error("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}

