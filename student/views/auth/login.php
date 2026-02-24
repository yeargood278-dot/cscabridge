<?php
/**
 * CSCA Bridge - 用户登录页面
 * 支持中英文切换、多种登录方式、验证码
 * 手机号格式：+国家代码手机号（如 +8613800138000）
 */

session_start();

// 加载公共函数
require_once __DIR__ . '/../../../includes/functions.php';

// 语言切换处理
if (isset($_GET['lang']) && in_array($_GET['lang'], ['zh_CN', 'en_US'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $currentUrl);
    exit;
}

// 获取当前语言
$currentLang = $_SESSION['lang'] ?? 'zh_CN';
$langPrefix = $currentLang === 'en_US' ? 'en' : 'zh';

// 加载语言包
$langFile = __DIR__ . '/../../../lang/' . $currentLang . '/auth.php';
if (!file_exists($langFile)) {
    $langFile = __DIR__ . '/../../../lang/zh_CN/auth.php';
}
$lang = require $langFile;

// 如果已登录，重定向
if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['user_role'] === 'platform_admin' ? '/admin/' : '/student/dashboard/';
    header('Location: ' . $redirect);
    exit;
}

// 生成CSRF令牌
$csrfToken = generateCsrfToken();

// 生成验证码
$captchaCode = generateCaptchaCode();
$_SESSION['captcha_code'] = $captchaCode;

// 错误和成功消息
$errors = [];
$success = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = $lang['error_csrf'];
    } else {
        $loginType = $_POST['login_type'] ?? 'email';
        $captcha = $_POST['captcha'] ?? '';
        $remember = isset($_POST['remember']);
        
        // 验证验证码
        if (empty($captcha) || strtoupper($captcha) !== ($_SESSION['captcha_code'] ?? '')) {
            $errors[] = $lang['error_captcha_invalid'];
        }
        
        if (empty($errors)) {
            if ($loginType === 'email') {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    $errors[] = $lang['error_empty_fields'];
                } elseif (!isValidEmail($email)) {
                    $errors[] = $lang['error_invalid_email'];
                } else {
                    processEmailLogin($email, $password, $remember, $lang, $errors);
                }
            } elseif ($loginType === 'phone') {
                $phone = trim($_POST['phone'] ?? '');
                $phoneCode = $_POST['phone_code'] ?? '';
                
                if (empty($phone) || empty($phoneCode)) {
                    $errors[] = $lang['error_empty_fields'];
                } elseif (!validateInternationalPhone($phone)) {
                    $errors[] = $lang['error_invalid_phone_format'];
                } else {
                    processPhoneLogin($phone, $phoneCode, $remember, $lang, $errors);
                }
            }
        }
    }
    
    // 重新生成验证码
    $captchaCode = generateCaptchaCode();
    $_SESSION['captcha_code'] = $captchaCode;
}

/**
 * 验证国际手机号格式
 * 格式: +国家代码手机号（如 +8613800138000, +14155552671）
 */
function validateInternationalPhone(string $phone): bool
{
    // 必须以+开头，后面跟国家代码(1-3位)和手机号(至少7位)
    // 总长度在 8-15 位之间（不含+号）
    return preg_match('/^\+[1-9]\d{1,14}$/', $phone) === 1;
}

/**
 * 处理邮箱登录
 */
function processEmailLogin(string $email, string $password, bool $remember, array $lang, array &$errors): void
{
    try {
        require_once __DIR__ . '/../../../includes/Database.php';
        
        $attempts = Database::fetchOne(
            "SELECT login_attempts, locked_until FROM users WHERE email = ? AND deleted_at IS NULL",
            [$email]
        );
        
        if ($attempts && $attempts['locked_until'] && strtotime($attempts['locked_until']) > time()) {
            $remaining = ceil((strtotime($attempts['locked_until']) - time()) / 60);
            $errors[] = str_replace(':minutes', $remaining, $lang['error_account_locked']);
            return;
        }
        
        $user = Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND status = 1 AND deleted_at IS NULL",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            completeLogin($user, $remember, 'email');
        } else {
            Database::query("UPDATE users SET login_attempts = login_attempts + 1 WHERE email = ?", [$email]);
            if (($attempts['login_attempts'] ?? 0) >= 4) {
                Database::query("UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email = ?", [$email]);
            }
            $errors[] = $lang['error_invalid_credentials'];
            logMessage("Failed login attempt for email: {$email}", 'warning', 'login');
        }
    } catch (Exception $e) {
        $errors[] = $lang['error_system'];
        logMessage("Login error: " . $e->getMessage(), 'error', 'login');
    }
}

/**
 * 处理手机号登录
 */
function processPhoneLogin(string $phone, string $code, bool $remember, array $lang, array &$errors): void
{
    if (!isset($_SESSION['phone_verify_code']) || 
        !isset($_SESSION['phone_verify_phone']) ||
        !isset($_SESSION['phone_verify_expires']) ||
        $_SESSION['phone_verify_expires'] < time() ||
        $_SESSION['phone_verify_phone'] !== $phone ||
        $_SESSION['phone_verify_code'] !== $code) {
        $errors[] = $lang['error_phone_code_invalid'];
        return;
    }
    
    try {
        require_once __DIR__ . '/../../../includes/Database.php';
        
        $user = Database::fetchOne(
            "SELECT * FROM users WHERE phone = ? AND deleted_at IS NULL",
            [$phone]
        );
        
        if ($user) {
            completeLogin($user, $remember, 'phone');
        } else {
            // 手机号未注册，自动创建账户
            $userId = Database::insert('users', [
                'phone' => $phone,
                'auth_provider' => 'phone',
                'role' => 'student',
                'status' => 1,
                'nickname' => 'User_' . substr($phone, -4),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 创建用户资料（如果表存在）
            try {
                Database::insert('user_profiles', [
                    'user_id' => $userId,
                    'phone' => $phone,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {
                logMessage("user_profiles insert skipped: " . $e->getMessage(), 'warning', 'login');
            }
            
            // 分配默认角色（如果表存在）
            try {
                $defaultRole = Database::fetchOne("SELECT id FROM roles WHERE name = 'student'");
                if ($defaultRole) {
                    Database::insert('user_roles', [
                        'user_id' => $userId,
                        'role_id' => $defaultRole['id'],
                    ]);
                }
            } catch (Exception $e) {
                logMessage("user_roles insert skipped: " . $e->getMessage(), 'warning', 'login');
            }
            
            // 创建免费订阅（如果表存在）
            try {
                Database::insert('user_subscriptions', [
                    'user_id' => $userId,
                    'plan_type' => 'free',
                    'status' => 'active',
                    'starts_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {
                logMessage("user_subscriptions insert skipped: " . $e->getMessage(), 'warning', 'login');
            }
            
            $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            completeLogin($user, $remember, 'phone');
        }
        
        unset($_SESSION['phone_verify_code']);
        unset($_SESSION['phone_verify_phone']);
        unset($_SESSION['phone_verify_expires']);
        
    } catch (Exception $e) {
        $errors[] = $lang['error_system'];
        logMessage("Phone login error: " . $e->getMessage(), 'error', 'login');
    }
}

/**
 * 完成登录流程
 */
function completeLogin(array $user, bool $remember, string $loginMethod): void
{
    global $lang;
    
    require_once __DIR__ . '/../../../includes/Database.php';
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'] ?? $user['phone'] ?? '';
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['nickname'] ?? $user['email'] ?? $user['phone'] ?? 'User';
    
    Database::update('users', [
        'last_login_at' => date('Y-m-d H:i:s'),
        'last_login_ip' => getClientIp(),
        'last_login_method' => $loginMethod,
        'login_attempts' => 0,
        'locked_until' => null,
    ], 'id = ?', [$user['id']]);
    
    if ($remember) {
        $rememberToken = generateRandomString(32);
        setcookie('remember_token', $rememberToken, time() + 30 * 24 * 3600, '/', '', true, true);
        Database::update('users', [
            'remember_token' => password_hash($rememberToken, PASSWORD_DEFAULT)
        ], 'id = ?', [$user['id']]);
    }
    
    unset($_SESSION['captcha_code']);
    logMessage("User {$user['id']} logged in successfully via {$loginMethod}", 'info', 'login');
    
    $redirect = $user['role'] === 'platform_admin' ? '/admin/' : '/student/dashboard/';
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $langPrefix === 'en' ? 'en' : 'zh-CN'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <title><?php echo $lang['login_title']; ?> - <?php echo $lang['site_name']; ?></title>
    <meta name="description" content="<?php echo $lang['login_description']; ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+SC:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-dark: #1e3a5f;
            --primary-light: #2c5282;
            --accent-orange: #f39c12;
            --accent-orange-hover: #e67e22;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --error: #ef4444;
            --success: #10b981;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-full: 9999px;
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', 'Noto Sans SC', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        
        .bg-decoration {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        
        .bg-decoration::before, .bg-decoration::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.05;
        }
        
        .bg-decoration::before {
            width: 600px; height: 600px;
            background: var(--accent-orange);
            top: -200px; right: -200px;
        }
        
        .bg-decoration::after {
            width: 400px; height: 400px;
            background: var(--white);
            bottom: -100px; left: -100px;
        }
        
        .lang-switch {
            position: fixed;
            top: 24px; right: 24px;
            z-index: 100;
        }
        
        .lang-switch-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-full);
            color: var(--white);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .lang-switch-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .login-container {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 48px;
            box-shadow: var(--shadow-xl);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(30, 58, 95, 0.3);
        }
        
        .logo-text {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-dark);
        }
        
        .logo-text span { color: var(--accent-orange); }
        
        .logo-subtitle {
            font-size: 14px;
            color: var(--gray-500);
            margin-top: 8px;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--gray-800);
            text-align: center;
            margin-bottom: 8px;
        }
        
        .form-subtitle {
            font-size: 14px;
            color: var(--gray-500);
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: var(--gray-100);
            padding: 4px;
            border-radius: var(--radius-md);
        }
        
        .login-tab {
            flex: 1;
            padding: 10px 16px;
            border: none;
            background: transparent;
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-radius: var(--radius-sm);
            transition: all 0.3s ease;
        }
        
        .login-tab.active {
            background: var(--white);
            color: var(--primary-dark);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .alert {
            padding: 14px 16px;
            border-radius: var(--radius-md);
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fef2f2;
            color: var(--error);
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: var(--success);
            border: 1px solid #bbf7d0;
        }
        
        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 8px;
        }
        
        .form-label-required::after {
            content: ' *';
            color: var(--error);
        }
        
        .form-input-wrapper { position: relative; }
        
        .form-input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 18px;
        }
        
        .form-input-wrapper:focus-within i { color: var(--primary-light); }
        
        .form-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 15px;
            color: var(--gray-800);
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
        }
        
        .form-input.valid { border-color: var(--success); }
        .form-input.invalid { border-color: var(--error); }
        
        .input-hint {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 6px;
        }
        
        .input-hint.error {
            color: var(--error);
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 4px;
            font-size: 18px;
        }
        
        .captcha-group {
            display: flex;
            gap: 12px;
        }
        
        .captcha-input { flex: 1; }
        
        .captcha-image {
            width: 140px; height: 50px;
            background: var(--gray-100);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Courier New', monospace;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-dark);
            letter-spacing: 4px;
            cursor: pointer;
            user-select: none;
            border: 2px solid var(--gray-200);
        }
        
        .phone-code-group {
            display: flex;
            gap: 12px;
        }
        
        .phone-code-input { flex: 1; }
        
        .btn-send-code {
            padding: 14px 20px;
            background: var(--primary-light);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-send-code:hover { background: var(--primary-dark); }
        
        .btn-send-code:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }
        
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: var(--primary-light);
        }
        
        .remember-me span {
            font-size: 14px;
            color: var(--gray-600);
        }
        
        .forgot-password {
            font-size: 14px;
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password:hover { text-decoration: underline; }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent-orange), var(--accent-orange-hover));
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(243, 156, 18, 0.4);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 28px 0;
            color: var(--gray-400);
            font-size: 14px;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }
        
        .divider span { padding: 0 16px; }
        
        .social-login {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .btn-social {
            padding: 14px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            background: var(--white);
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-700);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-social:hover {
            border-color: var(--primary-light);
            background: var(--gray-50);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .btn-social i { font-size: 20px; }
        .btn-social.google i { color: #ea4335; }
        .btn-social.wechat i { color: #07c160; }
        .btn-social.facebook i { color: #1877f2; }
        .btn-social.twitter i { color: #1da1f2; }
        
        .form-footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
        }
        
        .form-footer p {
            font-size: 14px;
            color: var(--gray-600);
        }
        
        .form-footer a {
            color: var(--primary-light);
            font-weight: 600;
            text-decoration: none;
        }
        
        .form-footer a:hover { text-decoration: underline; }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            text-decoration: none;
        }
        
        .back-home a:hover { color: var(--white); }
        
        @media (max-width: 480px) {
            .login-card { padding: 32px 24px; }
            .social-login { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="bg-decoration"></div>
    
    <div class="lang-switch">
        <a href="?lang=<?php echo $currentLang === 'zh_CN' ? 'en_US' : 'zh_CN'; ?>" class="lang-switch-btn">
            <i class="fas fa-globe"></i>
            <span><?php echo $currentLang === 'zh_CN' ? 'English' : '中文'; ?></span>
        </a>
    </div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon">C</div>
                <div class="logo-text">CSCA<span>Bridge</span></div>
                <div class="logo-subtitle"><?php echo $lang['site_slogan']; ?></div>
            </div>
            
            <h1 class="form-title"><?php echo $lang['login_title']; ?></h1>
            <p class="form-subtitle"><?php echo $lang['login_subtitle']; ?></p>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- 登录方式切换 -->
            <div class="login-tabs">
                <button type="button" class="login-tab active" onclick="switchTab('email')">
                    <i class="fas fa-envelope"></i> <?php echo $lang['email_login']; ?>
                </button>
                <button type="button" class="login-tab" onclick="switchTab('phone')">
                    <i class="fas fa-mobile-alt"></i> <?php echo $lang['phone_login']; ?>
                </button>
            </div>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="login_type" id="loginType" value="email">
                
                <!-- 邮箱登录 -->
                <div id="emailTab" class="tab-content active">
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $lang['email']; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-input" 
                                   placeholder="<?php echo $lang['email_placeholder']; ?>" 
                                   autocomplete="email">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $lang['password']; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-input" 
                                   placeholder="<?php echo $lang['password_placeholder']; ?>" 
                                   autocomplete="current-password" id="passwordInput">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 手机号登录 -->
                <div id="phoneTab" class="tab-content">
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $lang['phone']; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-globe"></i>
                            <input type="tel" name="phone" class="form-input" 
                                   placeholder="<?php echo $lang['phone_placeholder']; ?>" 
                                   id="phoneInput" oninput="validatePhoneFormat()" onblur="validatePhoneFormat()">
                        </div>
                        <div class="input-hint" id="phoneHint">
                            <i class="fas fa-info-circle"></i> <?php echo $lang['phone_format_hint']; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $lang['phone_code']; ?></label>
                        <div class="phone-code-group">
                            <div class="form-input-wrapper phone-code-input">
                                <i class="fas fa-shield-alt"></i>
                                <input type="text" name="phone_code" class="form-input" 
                                       placeholder="<?php echo $lang['phone_code_placeholder']; ?>" 
                                       maxlength="6">
                            </div>
                            <button type="button" class="btn-send-code" id="sendCodeBtn" onclick="sendPhoneCode()">
                                <?php echo $lang['send_code']; ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 验证码 -->
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo $lang['captcha']; ?></label>
                    <div class="captcha-group">
                        <div class="form-input-wrapper captcha-input">
                            <i class="fas fa-shield-alt"></i>
                            <input type="text" name="captcha" class="form-input" 
                                   placeholder="<?php echo $lang['captcha_placeholder']; ?>" 
                                   maxlength="5" autocomplete="off">
                        </div>
                        <div class="captcha-image" onclick="refreshCaptcha()" title="<?php echo $lang['captcha_refresh']; ?>">
                            <?php echo $captchaCode; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1">
                        <span><?php echo $lang['remember_me']; ?></span>
                    </label>
                    <a href="/forgot-password" class="forgot-password"><?php echo $lang['forgot_password']; ?></a>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i> <?php echo $lang['login_button']; ?>
                </button>
            </form>
            
            <div class="divider">
                <span><?php echo $lang['or_login_with']; ?></span>
            </div>
            
            <!-- OAuth 社交登录 -->
            <div class="social-login">
                <a href="/api/auth/google.php" class="btn-social google">
                    <i class="fab fa-google"></i> Google
                </a>
                <a href="/api/auth/wechat.php" class="btn-social wechat">
                    <i class="fab fa-weixin"></i> <?php echo $lang['wechat']; ?>
                </a>
                <a href="/api/auth/facebook.php" class="btn-social facebook">
                    <i class="fab fa-facebook-f"></i> Facebook
                </a>
                <a href="/api/auth/twitter.php" class="btn-social twitter">
                    <i class="fab fa-twitter"></i> Twitter
                </a>
            </div>
            
            <div class="form-footer">
                <p>
                    <?php echo $lang['no_account']; ?>
                    <a href="/student/views/auth/register.php"><?php echo $lang['register_now']; ?></a>
                </p>
            </div>
        </div>
        
        <div class="back-home">
            <a href="/">
                <i class="fas fa-arrow-left"></i> <?php echo $lang['back_to_home']; ?>
            </a>
        </div>
    </div>
    
    <script>
        // 切换登录方式
        function switchTab(tab) {
            document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tab + 'Tab').classList.add('active');
            document.getElementById('loginType').value = tab;
        }
        
        // 切换密码可见性
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // 刷新验证码
        function refreshCaptcha() {
            location.reload();
        }
        
        // 验证国际手机号格式
        // 格式: +国家代码手机号（如 +8613800138000, +14155552671）
        function validatePhoneFormat() {
            const phoneInput = document.getElementById('phoneInput');
            const phoneHint = document.getElementById('phoneHint');
            const phone = phoneInput.value.trim();
            
            // 国际手机号正则: +开头，后面跟1-15位数字
            const phoneRegex = /^\+[1-9]\d{1,14}$/;
            
            if (phone === '') {
                phoneInput.classList.remove('valid', 'invalid');
                phoneHint.innerHTML = '<i class="fas fa-info-circle"></i> <?php echo $lang['phone_format_hint']; ?>';
                phoneHint.classList.remove('error');
                return false;
            }
            
            if (phoneRegex.test(phone)) {
                phoneInput.classList.add('valid');
                phoneInput.classList.remove('invalid');
                phoneHint.innerHTML = '<i class="fas fa-check-circle"></i> <?php echo $lang['phone_format_valid']; ?>';
                phoneHint.classList.remove('error');
                return true;
            } else {
                phoneInput.classList.add('invalid');
                phoneInput.classList.remove('valid');
                phoneHint.innerHTML = '<i class="fas fa-exclamation-circle"></i> <?php echo $lang['phone_format_invalid']; ?>';
                phoneHint.classList.add('error');
                return false;
            }
        }
        
        // 发送手机验证码
        function sendPhoneCode() {
            const phone = document.getElementById('phoneInput').value.trim();
            const btn = document.getElementById('sendCodeBtn');
            
            if (!validatePhoneFormat()) {
                alert('<?php echo $lang['error_invalid_phone_format']; ?>');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('/api/auth/send-phone-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ phone: phone, type: 'login' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let seconds = 60;
                    btn.textContent = seconds + 's';
                    
                    const timer = setInterval(() => {
                        seconds--;
                        btn.textContent = seconds + 's';
                        if (seconds <= 0) {
                            clearInterval(timer);
                            btn.disabled = false;
                            btn.textContent = '<?php echo $lang['send_code']; ?>';
                        }
                    }, 1000);
                    
                    alert(data.code ? 
                        '<?php echo $lang['phone_code_sent']; ?> (Dev: ' + data.code + ')' : 
                        '<?php echo $lang['phone_code_sent']; ?>'
                    );
                } else {
                    btn.disabled = false;
                    btn.textContent = '<?php echo $lang['send_code']; ?>';
                    alert(data.message || '<?php echo $lang['error_system']; ?>');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.disabled = false;
                btn.textContent = '<?php echo $lang['send_code']; ?>';
                alert('<?php echo $lang['error_system']; ?>');
            });
        }
        
        // 表单提交验证
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginType = document.getElementById('loginType').value;
            
            if (loginType === 'phone') {
                if (!validatePhoneFormat()) {
                    e.preventDefault();
                    alert('<?php echo $lang['error_invalid_phone_format']; ?>');
                    return false;
                }
            }
        });
    </script>
</body>
</html>
