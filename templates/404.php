<!DOCTYPE html>
<html lang="<?php echo getCurrentLang() === 'en_US' ? 'en' : 'zh-CN'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?php echo lang('error'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .error-container {
            text-align: center;
            padding: 40px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .error-title {
            font-size: 28px;
            margin-bottom: 16px;
        }
        .error-message {
            font-size: 16px;
            opacity: 0.8;
            margin-bottom: 32px;
        }
        .btn-home {
            display: inline-block;
            padding: 14px 32px;
            background: #f39c12;
            color: #fff;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title"><?php echo lang('error'); ?></h1>
        <p class="error-message">
            <?php echo getCurrentLang() === 'en_US' 
                ? 'The page you are looking for does not exist.' 
                : '您访问的页面不存在。'; ?>
        </p>
        <a href="/" class="btn-home">
            <?php echo lang('nav_home'); ?>
        </a>
    </div>
</body>
</html>
