<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $subject; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4a7aff;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666666;
        }
        .button {
            display: inline-block;
            background-color: #4a7aff;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $subject; ?></h1>
        </div>
        <div class="content">
            <p>Hello <?php echo htmlspecialchars($name); ?>,</p>
            
            <?php echo $message; ?>
            
            <?php if (isset($callToAction) && isset($callToActionUrl)): ?>
            <p>
                <a href="<?php echo htmlspecialchars($callToActionUrl); ?>" class="button">
                    <?php echo htmlspecialchars($callToAction); ?>
                </a>
            </p>
            <?php endif; ?>
            
            <p>Best regards,<br>
            <?php echo htmlspecialchars($companyName); ?> Team</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyName); ?>. All rights reserved.</p>
            <?php if (isset($unsubscribeUrl)): ?>
            <p><a href="<?php echo htmlspecialchars($unsubscribeUrl); ?>">Unsubscribe</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>