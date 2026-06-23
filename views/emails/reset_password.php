<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f5f7;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e1e4e8;
        }
        .header {
            background-color: #6366f1;
            background-image: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #ffffff;
            text-align: center;
            padding: 40px 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .content {
            padding: 40px 30px;
            color: #333333;
            line-height: 1.6;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            background-color: #6366f1;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.35);
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 13px;
            color: #6c757d;
            border-top: 1px solid #e1e4e8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MiniMVC Password Recovery</h1>
        </div>
        <div class="content">
            <p>Hello {{ $name }},</p>
            <p>We received a request to reset your password. You can reset your password by clicking the button below:</p>
            <div class="btn-container">
                <a href="{{ $resetLink }}" class="btn">Reset Password</a>
            </div>
            <p>If you did not request a password reset, no further action is required.</p>
            <p>This password reset link will expire in 1 hour.</p>
            <p>Regards,<br>MiniMVC Team</p>
        </div>
        <div class="footer">
            <p>This is an automated message, please do not reply directly to this email.</p>
            <p>&copy; <?php echo date('Y'); ?> MiniMVC Framework.</p>
        </div>
    </div>
</body>
</html>

