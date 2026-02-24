<?php
/**
 * CSCA Bridge - 用户注册页面
 * 支持邮箱注册、手机号注册（国际格式）
 * 手机号格式：+国家代码手机号（如 +8613800138000）
 */

session_start();

require_once __DIR__ . '/../../../includes/functions.php';

// 语言切换处理
if (isset($_GET['lang']) && in_array($_GET['lang'], ['zh_CN', 'en_US'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $currentUrl);
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'zh_CN';
$langPrefix = $currentLang === 'en_US' ? 'en' : 'zh';

$langFile = __DIR__ . '/../../../lang/' . $currentLang . '/auth.php';
if (!file_exists($langFile)) {
    $langFile = __DIR__ . '/../../../lang/zh_CN/auth.php';
}
$lang = require $langFile;

if (isset($_SESSION['user_id'])) {
    header('Location: /student/dashboard/');
    exit;
}

$csrfToken = generateCsrfToken();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = $lang['error_csrf'];
    } else {
        $registerType = $_POST['register_type'] ?? 'email';
        
        if ($registerType === 'email') {
            processEmailRegister($_POST, $lang, $errors, $success);
        } elseif ($registerType === 'phone') {
            processPhoneRegister($_POST, $lang, $errors, $success);
        }
    }
}

/**
 * 验证国际手机号格式（增强版）
 */
function validateInternationalPhone(string $phone): array
{
    $result = ['valid' => false, 'message' => ''];

    $phone = trim($phone);

    if (empty($phone) || !str_starts_with($phone, '+')) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '手机号必须以+开头' : 'Phone number must start with +';
        return $result;
    }

    $digits = substr($phone, 1);

    if (!ctype_digit($digits)) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '+号后只能包含数字' : 'Only digits allowed after +';
        return $result;
    }

    $length = strlen($digits);
    if ($length < 8 || $length > 15) {
        $result['message'] = ($GLOBALS['currentLang'] === 'zh_CN' ? '手机号长度应为8-15位（当前：' : 'Phone length should be 8-15 digits (current: ') . $length . ')';
        return $result;
    }

    if (str_starts_with($digits, '0')) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '国家代码不能以0开头' : 'Country code cannot start with 0';
        return $result;
    }

    $result['valid'] = true;
    return $result;
}

/**
 * 增强版邮箱格式验证
 */
function validateEmailFormatEnhanced(string $email): array
{
    $result = ['valid' => false, 'message' => ''];
    
    $email = trim($email);
    
    if (empty($email)) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '邮箱地址不能为空' : 'Email address is required';
        return $result;
    }
    
    // 基本格式验证
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '邮箱格式不正确' : 'Invalid email format';
        return $result;
    }
    
    // 更严格的正则验证
    $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    if (!preg_match($pattern, $email)) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '邮箱格式不正确' : 'Invalid email format';
        return $result;
    }
    
    // 检查域名部分
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '邮箱格式不正确' : 'Invalid email format';
        return $result;
    }
    
    list($localPart, $domain) = $parts;
    
    // 检查本地部分长度
    if (strlen($localPart) > 64) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '邮箱用户名过长' : 'Email local part too long';
        return $result;
    }
    
    // 检查域名是否有效
    if (strlen($domain) > 255) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '邮箱域名过长' : 'Email domain too long';
        return $result;
    }
    
    // 检查域名是否有有效的TLD
    $domainParts = explode('.', $domain);
    $tld = end($domainParts);
    if (strlen($tld) < 2) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '邮箱域名后缀无效' : 'Invalid email domain suffix';
        return $result;
    }
    
    // 常见一次性邮箱域名列表（可选：用于阻止临时邮箱）
    $disposableDomains = ['tempmail.com', '10minutemail.com', 'guerrillamail.com', 'mailinator.com'];
    if (in_array(strtolower($domain), $disposableDomains)) {
        $result['message'] = $GLOBALS['currentLang'] === 'zh_CN' ? '不允许使用临时邮箱' : 'Disposable email addresses are not allowed';
        return $result;
    }
    
    $result['valid'] = true;
    $result['email'] = $email;
    return $result;
}

/**
 * 处理邮箱注册（发送验证码）
 */
function processEmailRegister(array $post, array $lang, array &$errors, string &$success): void
{
    $email = trim($post['email'] ?? '');
    $emailCode = trim($post['email_code'] ?? '');
    $password = $post['password'] ?? '';
    $passwordConfirm = $post['password_confirm'] ?? '';
    $nickname = trim($post['nickname'] ?? '');
    $agreeTerms = isset($post['agree_terms']);
    
    // 验证邮箱格式
    $emailValidation = validateEmailFormatEnhanced($email);
    if (!$emailValidation['valid']) {
        $errors[] = $emailValidation['message'];
    }
    
    // 验证验证码
    if (empty($emailCode) || strlen($emailCode) !== 6 || !ctype_digit($emailCode)) {
        $errors[] = $GLOBALS['currentLang'] === 'zh_CN' ? '请输入6位数字验证码' : 'Please enter 6-digit verification code';
    }
    
    // 验证密码
    if (empty($password)) {
        $errors[] = $lang['error_password_required'];
    } else {
        $pwdValidation = validatePassword($password);
        if (!$pwdValidation['valid']) {
            $errors = array_merge($errors, $pwdValidation['errors']);
        }
    }
    
    if ($password !== $passwordConfirm) {
        $errors[] = $lang['error_password_mismatch'];
    }
    
    if (!$agreeTerms) {
        $errors[] = $lang['error_agree_terms'];
    }
    
    if (empty($errors)) {
        // 验证邮箱验证码
        if (!isset($_SESSION['email_verify_code']) || 
            !isset($_SESSION['email_verify_email']) ||
            $_SESSION['email_verify_email'] !== $email ||
            $_SESSION['email_verify_code'] !== $emailCode ||
            $_SESSION['email_verify_expires'] < time()) {
            $errors[] = $GLOBALS['currentLang'] === 'zh_CN' ? '验证码错误或已过期' : 'Invalid or expired verification code';
            return;
        }
        
        try {
            require_once __DIR__ . '/../../../includes/Database.php';
                        // 检查邮箱是否已存在
            $existing = Database::fetchOne(
                "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL",
                [$email]
            );
            
            if ($existing) {
                $errors[] = $lang['error_email_exists'];
                return;
            }
            
            // 创建用户
            $nickname = $nickname ?: substr($email, 0, strpos($email, '@'));
            
            $userId = Database::insert('users', [
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'auth_provider' => 'email',
                'role' => 'student',
                'status' => 1,
                'nickname' => $nickname,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 创建用户资料（如果表存在）
            try {
                Database::insert('user_profiles', [
                    'user_id' => $userId,
                    'nickname' => $nickname,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {
                logMessage("user_profiles insert skipped: " . $e->getMessage(), 'warning', 'register');
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
                logMessage("user_roles insert skipped: " . $e->getMessage(), 'warning', 'register');
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
                logMessage("user_subscriptions insert skipped: " . $e->getMessage(), 'warning', 'register');
            }
            
            // 清除验证码session
            unset($_SESSION['email_verify_code']);
            unset($_SESSION['email_verify_email']);
            unset($_SESSION['email_verify_expires']);
            
            // 设置登录状态
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'student';
            $_SESSION['user_name'] = $nickname;
            
            $success = $lang['register_success'];
            header('Location: /student/dashboard/');
            exit;
            
        } catch (Exception $e) {
            $errors[] = $lang['error_system'];
            logMessage("Email register error: " . $e->getMessage(), 'error', 'register');
        }
    }
}

/**
 * 处理手机注册
 */
function processPhoneRegister(array $post, array $lang, array &$errors, string &$success): void
{
    $phone = trim($post['phone'] ?? '');
    $phoneCode = trim($post['phone_code'] ?? '');
    $nickname = trim($post['nickname'] ?? '');
    $agreeTerms = isset($post['agree_terms']);

    $phoneValidation = validateInternationalPhone($phone);
    if (!$phoneValidation['valid']) {
        $errors[] = $phoneValidation['message'];
    }
    
    if (empty($phoneCode) || strlen($phoneCode) !== 6 || !ctype_digit($phoneCode)) {
        $errors[] = $GLOBALS['currentLang'] === 'zh_CN' ? '请输入6位数字验证码' : 'Please enter 6-digit verification code';
    }
    
    if (!$agreeTerms) {
        $errors[] = $lang['error_agree_terms'];
    }
    
    if (empty($errors)) {
        // 验证手机验证码
        if (!isset($_SESSION['phone_verify_code']) || 
            !isset($_SESSION['phone_verify_phone']) ||
            $_SESSION['phone_verify_phone'] !== $phone ||
            $_SESSION['phone_verify_code'] !== $phoneCode ||
            $_SESSION['phone_verify_expires'] < time()) {
            $errors[] = $GLOBALS['currentLang'] === 'zh_CN' ? '验证码错误或已过期' : 'Invalid or expired verification code';
            return;
        }
        
        try {
            require_once __DIR__ . '/../../../includes/Database.php';
                        $existing = Database::fetchOne(
                "SELECT id FROM users WHERE phone = ? AND deleted_at IS NULL",
                [$phone]
            );
            
            if ($existing) {
                $errors[] = $GLOBALS['currentLang'] === 'zh_CN' ? '该手机号已注册' : 'This phone number is already registered';
                return;
            }
            
            $nickname = $nickname ?: 'User_' . substr($phone, -4);
            
            $userId = Database::insert('users', [
                'phone' => $phone,
                'auth_provider' => 'phone',
                'role' => 'student',
                'status' => 1,
                'nickname' => $nickname,
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 创建用户资料（如果表存在）
            try {
                Database::insert('user_profiles', [
                    'user_id' => $userId,
                    'phone' => $phone,
                    'nickname' => $nickname,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {
                logMessage("user_profiles insert skipped: " . $e->getMessage(), 'warning', 'register');
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
                logMessage("user_roles insert skipped: " . $e->getMessage(), 'warning', 'register');
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
                logMessage("user_subscriptions insert skipped: " . $e->getMessage(), 'warning', 'register');
            }
            
            unset($_SESSION['phone_verify_code']);
            unset($_SESSION['phone_verify_phone']);
            unset($_SESSION['phone_verify_expires']);
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $phone;
            $_SESSION['user_role'] = 'student';
            $_SESSION['user_name'] = $nickname;
            
            $success = $lang['register_success'];
            header('Location: /student/dashboard/');
            exit;
            
        } catch (Exception $e) {
            $errors[] = $lang['error_system'];
            logMessage("Phone register error: " . $e->getMessage(), 'error', 'register');
        }
    }
}

function validatePassword(string $password): array
{
    global $lang;
    $result = ['valid' => true, 'errors' => []];
    
    if (strlen($password) < 8) {
        $result['valid'] = false;
        $result['errors'][] = $lang['error_password_length'] ?? ($GLOBALS['currentLang'] === 'zh_CN' ? '密码至少需要8个字符' : 'Password must be at least 8 characters');
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $result['valid'] = false;
        $result['errors'][] = $lang['error_password_uppercase'] ?? ($GLOBALS['currentLang'] === 'zh_CN' ? '密码需要包含大写字母' : 'Password must contain uppercase letter');
    }
    if (!preg_match('/[a-z]/', $password)) {
        $result['valid'] = false;
        $result['errors'][] = $lang['error_password_lowercase'] ?? ($GLOBALS['currentLang'] === 'zh_CN' ? '密码需要包含小写字母' : 'Password must contain lowercase letter');
    }
    if (!preg_match('/[0-9]/', $password)) {
        $result['valid'] = false;
        $result['errors'][] = $lang['error_password_number'] ?? ($GLOBALS['currentLang'] === 'zh_CN' ? '密码需要包含数字' : 'Password must contain number');
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $result['valid'] = false;
        $result['errors'][] = $lang['error_password_special'] ?? ($GLOBALS['currentLang'] === 'zh_CN' ? '密码需要包含特殊字符' : 'Password must contain special character');
    }
    
    return $result;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $langPrefix === 'en' ? 'en' : 'zh-CN'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <title><?php echo $lang['register_title'] ?? ($currentLang === 'zh_CN' ? '注册' : 'Register'); ?> - CSCA Bridge</title>
    
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
            font-family: 'Inter', 'Noto Sans SC', sans-serif;
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
        
        .register-container {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 1;
        }
        
        .register-card {
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
        
        .register-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: var(--gray-100);
            padding: 4px;
            border-radius: var(--radius-md);
        }
        
        .register-tab {
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
        
        .register-tab.active {
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
        
        .alert ul { margin: 0; padding-left: 20px; }
        .alert li { margin-bottom: 4px; }
        .alert li:last-child { margin-bottom: 0; }
        
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
        
        .input-hint.error { color: var(--error); }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        
        .password-strength-bar.weak { width: 33%; background: var(--error); }
        .password-strength-bar.medium { width: 66%; background: var(--accent-orange); }
        .password-strength-bar.strong { width: 100%; background: var(--success); }
        
        .password-hint {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 6px;
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
        
        .code-group {
            display: flex;
            gap: 12px;
        }
        
        .code-input { flex: 1; }
        
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
        
        .terms-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 24px;
        }
        
        .terms-group input[type="checkbox"] {
            width: 18px; height: 18px;
            margin-top: 2px;
            accent-color: var(--primary-light);
        }
        
        .terms-group label {
            font-size: 14px;
            color: var(--gray-600);
            line-height: 1.5;
        }
        
        .terms-group a {
            color: var(--primary-light);
            text-decoration: none;
        }
        
        .terms-group a:hover { text-decoration: underline; }
        
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
        
        .form-footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
            margin-top: 24px;
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
            .register-card { padding: 32px 24px; }
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
    
    <div class="register-container">
        <div class="register-card">
            <div class="logo">
                <div class="logo-icon">C</div>
                <div class="logo-text">CSCA<span>Bridge</span></div>
            </div>
            
            <h1 class="form-title"><?php echo $currentLang === 'zh_CN' ? '创建账户' : 'Create Account'; ?></h1>
            <p class="form-subtitle"><?php echo $currentLang === 'zh_CN' ? '选择注册方式，开始您的学习之旅' : 'Choose a registration method to start your learning journey'; ?></p>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- 注册方式切换 -->
            <div class="register-tabs">
                <button type="button" class="register-tab active" onclick="switchTab('email')">
                    <i class="fas fa-envelope"></i> <?php echo $currentLang === 'zh_CN' ? '邮箱注册' : 'Email Register'; ?>
                </button>
                <button type="button" class="register-tab" onclick="switchTab('phone')">
                    <i class="fas fa-mobile-alt"></i> <?php echo $currentLang === 'zh_CN' ? '手机注册' : 'Phone Register'; ?>
                </button>
            </div>
            
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="register_type" id="registerType" value="email">
                
                <!-- 邮箱注册 -->
                <div id="emailTab" class="tab-content active">
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $currentLang === 'zh_CN' ? '邮箱地址' : 'Email Address'; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'zh_CN' ? '请输入您的邮箱' : 'Enter your email'; ?>" 
                                   id="emailInput" onblur="validateEmailFormat()">
                        </div>
                        <div class="input-hint" id="emailHint"></div>
                    </div>
                    
                    <!-- 邮箱验证码 -->
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $currentLang === 'zh_CN' ? '邮箱验证码' : 'Email Verification Code'; ?></label>
                        <div class="code-group">
                            <div class="form-input-wrapper code-input">
                                <i class="fas fa-shield-alt"></i>
                                <input type="text" name="email_code" class="form-input" 
                                       placeholder="<?php echo $currentLang === 'zh_CN' ? '请输入6位验证码' : 'Enter 6-digit code'; ?>" 
                                       maxlength="6" id="emailCodeInput">
                            </div>
                            <button type="button" class="btn-send-code" id="sendEmailCodeBtn" onclick="sendEmailCode()">
                                <?php echo $currentLang === 'zh_CN' ? '获取验证码' : 'Get Code'; ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo $currentLang === 'zh_CN' ? '昵称' : 'Nickname'; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="nickname" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'zh_CN' ? '请输入昵称（选填）' : 'Enter nickname (optional)'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $currentLang === 'zh_CN' ? '密码' : 'Password'; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'zh_CN' ? '请设置密码' : 'Set your password'; ?>" 
                                   id="passwordInput" oninput="checkPasswordStrength()">
                            <button type="button" class="password-toggle" onclick="togglePassword('passwordInput', 'toggleIcon1')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-hint"><?php echo $currentLang === 'zh_CN' ? '密码需包含8位以上，大小写字母、数字和特殊字符' : 'Password must be 8+ chars with uppercase, lowercase, number and special char'; ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $currentLang === 'zh_CN' ? '确认密码' : 'Confirm Password'; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password_confirm" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'zh_CN' ? '请再次输入密码' : 'Confirm your password'; ?>" 
                                   id="passwordConfirmInput">
                            <button type="button" class="password-toggle" onclick="togglePassword('passwordConfirmInput', 'toggleIcon2')">
                                <i class="fas fa-eye" id="toggleIcon2"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 手机号注册 -->
                <div id="phoneTab" class="tab-content">
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $currentLang === 'zh_CN' ? '手机号码' : 'Phone Number'; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-globe"></i>
                            <input type="tel" name="phone" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'zh_CN' ? '格式：+8613800138000' : 'Format: +8613800138000'; ?>" 
                                   id="phoneInput" oninput="validatePhoneFormat()" onblur="validatePhoneFormat()">
                        </div>
                        <div class="input-hint" id="phoneHint">
                            <i class="fas fa-info-circle"></i> <?php echo $currentLang === 'zh_CN' ? '请输入国际格式手机号（+国家代码手机号）' : 'Enter international phone format (+country code + number)'; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label form-label-required"><?php echo $currentLang === 'zh_CN' ? '手机验证码' : 'Phone Verification Code'; ?></label>
                        <div class="code-group">
                            <div class="form-input-wrapper code-input">
                                <i class="fas fa-shield-alt"></i>
                                <input type="text" name="phone_code" class="form-input" 
                                       placeholder="<?php echo $currentLang === 'zh_CN' ? '请输入6位验证码' : 'Enter 6-digit code'; ?>" 
                                       maxlength="6">
                            </div>
                            <button type="button" class="btn-send-code" id="sendPhoneCodeBtn" onclick="sendPhoneCode()">
                                <?php echo $currentLang === 'zh_CN' ? '获取验证码' : 'Get Code'; ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo $currentLang === 'zh_CN' ? '昵称' : 'Nickname'; ?></label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="nickname" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'zh_CN' ? '请输入昵称（选填）' : 'Enter nickname (optional)'; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="terms-group">
                    <input type="checkbox" name="agree_terms" id="agreeTerms" required>
                    <label for="agreeTerms">
                        <?php echo $currentLang === 'zh_CN' ? '我已阅读并同意' : 'I have read and agree to the'; ?> 
                        <a href="/terms" target="_blank"><?php echo $currentLang === 'zh_CN' ? '服务条款' : 'Terms of Service'; ?></a> 
                        <?php echo $currentLang === 'zh_CN' ? '和' : 'and'; ?> 
                        <a href="/privacy" target="_blank"><?php echo $currentLang === 'zh_CN' ? '隐私政策' : 'Privacy Policy'; ?></a>
                    </label>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i> <?php echo $currentLang === 'zh_CN' ? '立即注册' : 'Register Now'; ?>
                </button>
            </form>
            
            <div class="form-footer">
                <p>
                    <?php echo $currentLang === 'zh_CN' ? '已有账户？' : 'Already have an account?'; ?>
                    <a href="/cscabridge_kimi/student/views/auth/login.php"><?php echo $currentLang === 'zh_CN' ? '立即登录' : 'Login Now'; ?></a>
                </p>
            </div>
        </div>
        
        <div class="back-home">
            <a href="/">
                <i class="fas fa-arrow-left"></i> <?php echo $currentLang === 'zh_CN' ? '返回首页' : 'Back to Home'; ?>
            </a>
        </div>
    </div>
    
    <script>
        // 切换注册方式
        function switchTab(tab) {
            document.querySelectorAll('.register-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tab + 'Tab').classList.add('active');
            document.getElementById('registerType').value = tab;
        }
        
        // 切换密码可见性
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
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
        
        // 检查密码强度
        function checkPasswordStrength() {
            const password = document.getElementById('passwordInput').value;
            const strengthBar = document.getElementById('strengthBar');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) strengthBar.classList.add('weak');
            else if (strength <= 4) strengthBar.classList.add('medium');
            else strengthBar.classList.add('strong');
        }
        
        // 增强版邮箱格式验证
        function validateEmailFormat() {
            const emailInput = document.getElementById('emailInput');
            const emailHint = document.getElementById('emailHint');
            const email = emailInput.value.trim();
            const sendBtn = document.getElementById('sendEmailCodeBtn');
            
            // 增强版邮箱正则
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            
            if (email === '') {
                emailInput.classList.remove('valid', 'invalid');
                emailHint.innerHTML = '';
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }
            
            // 检查基本格式
            if (!emailRegex.test(email)) {
                emailInput.classList.add('invalid');
                emailInput.classList.remove('valid');
                emailHint.innerHTML = '<i class="fas fa-exclamation-circle" style="color: var(--error);"></i> <?php echo $currentLang === "zh_CN" ? "邮箱格式不正确" : "Invalid email format"; ?>';
                emailHint.classList.add('error');
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }
            
            // 检查域名部分
            const parts = email.split('@');
            if (parts.length !== 2) {
                emailInput.classList.add('invalid');
                emailInput.classList.remove('valid');
                emailHint.innerHTML = '<i class="fas fa-exclamation-circle" style="color: var(--error);"></i> <?php echo $currentLang === "zh_CN" ? "邮箱格式不正确" : "Invalid email format"; ?>';
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }
            
            const domain = parts[1];
            const domainParts = domain.split('.');
            const tld = domainParts[domainParts.length - 1];
            
            if (tld.length < 2) {
                emailInput.classList.add('invalid');
                emailInput.classList.remove('valid');
                emailHint.innerHTML = '<i class="fas fa-exclamation-circle" style="color: var(--error);"></i> <?php echo $currentLang === "zh_CN" ? "邮箱域名后缀无效" : "Invalid email domain suffix"; ?>';
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }
            
            // 格式正确
            emailInput.classList.add('valid');
            emailInput.classList.remove('invalid');
            emailHint.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> <?php echo $currentLang === "zh_CN" ? "邮箱格式正确" : "Valid email format"; ?>';
            emailHint.classList.remove('error');
            if (sendBtn) sendBtn.disabled = false;
            return true;
        }
        
        // 验证国际手机号格式
        function validatePhoneFormat() {
            const phoneInput = document.getElementById('phoneInput');
            const phoneHint = document.getElementById('phoneHint');
            const phone = phoneInput.value.trim();
            const sendBtn = document.getElementById('sendPhoneCodeBtn');

            if (phone === '') {
                phoneInput.classList.remove('valid', 'invalid');
                phoneHint.innerHTML = '<i class="fas fa-info-circle"></i> <?php echo $currentLang === "zh_CN" ? "请输入国际格式手机号（+国家代码手机号）" : "Enter international phone format (+country code + number)"; ?>';
                phoneHint.classList.remove('error');
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }

            if (!phone.startsWith('+')) {
                phoneInput.classList.add('invalid');
                phoneInput.classList.remove('valid');
                phoneHint.innerHTML = '<i class="fas fa-exclamation-circle"></i> <?php echo $currentLang === "zh_CN" ? "手机号必须以+开头" : "Phone must start with +"; ?>';
                phoneHint.classList.add('error');
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }

            const digits = phone.substring(1);

            if (!/^\d+$/.test(digits)) {
                phoneInput.classList.add('invalid');
                phoneInput.classList.remove('valid');
                phoneHint.innerHTML = '<i class="fas fa-exclamation-circle"></i> <?php echo $currentLang === "zh_CN" ? "+号后只能包含数字" : "Only digits allowed after +"; ?>';
                phoneHint.classList.add('error');
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }

            if (digits.length < 8 || digits.length > 15) {
                phoneInput.classList.add('invalid');
                phoneInput.classList.remove('valid');
                phoneHint.innerHTML = '<i class="fas fa-exclamation-circle"></i> <?php echo $currentLang === "zh_CN" ? "手机号长度应为8-15位" : "Phone length should be 8-15 digits"; ?>';
                phoneHint.classList.add('error');
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }

            if (digits.startsWith('0')) {
                phoneInput.classList.add('invalid');
                phoneInput.classList.remove('valid');
                phoneHint.innerHTML = '<i class="fas fa-exclamation-circle"></i> <?php echo $currentLang === "zh_CN" ? "国家代码不能以0开头" : "Country code cannot start with 0"; ?>';
                phoneHint.classList.add('error');
                if (sendBtn) sendBtn.disabled = true;
                return false;
            }

            phoneInput.classList.add('valid');
            phoneInput.classList.remove('invalid');
            phoneHint.innerHTML = '<i class="fas fa-check-circle"></i> <?php echo $currentLang === "zh_CN" ? "手机号格式正确" : "Valid phone format"; ?>';
            phoneHint.classList.remove('error');
            if (sendBtn) sendBtn.disabled = false;
            return true;
        }

        // 页面加载时禁用发送验证码按钮
        document.addEventListener('DOMContentLoaded', function() {
            const emailSendBtn = document.getElementById('sendEmailCodeBtn');
            const phoneSendBtn = document.getElementById('sendPhoneCodeBtn');
            if (emailSendBtn) emailSendBtn.disabled = true;
            if (phoneSendBtn) phoneSendBtn.disabled = true;
        });
        
        // 发送邮箱验证码
        function sendEmailCode() {
            const emailInput = document.getElementById('emailInput');
            const email = emailInput.value.trim();
            const btn = document.getElementById('sendEmailCodeBtn');

            if (!validateEmailFormat()) {
                alert('<?php echo $currentLang === "zh_CN" ? "请输入正确的邮箱地址" : "Please enter a valid email address"; ?>');
                emailInput.focus();
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $currentLang === "zh_CN" ? "发送中..." : "Sending..."; ?>';

            fetch('/cscabridge_kimi/api/auth/send-email-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ email: email, type: 'register' })
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('Network response was not ok');
                }
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    let seconds = 60;
                    btn.innerHTML = seconds + 's';

                    const timer = setInterval(() => {
                        seconds--;
                        btn.innerHTML = seconds + 's';
                        if (seconds <= 0) {
                            clearInterval(timer);
                            btn.disabled = false;
                            btn.innerHTML = '<?php echo $currentLang === "zh_CN" ? "获取验证码" : "Get Code"; ?>';
                        }
                    }, 1000);

                    if (data.code) {
                        alert('<?php echo $currentLang === "zh_CN" ? "验证码已发送（开发模式）\\n验证码: " : "Verification code sent (Dev Mode)\\nCode: "; ?>' + data.code);
                    } else {
                        alert('<?php echo $currentLang === "zh_CN" ? "验证码已发送到您的邮箱" : "Verification code sent to your email"; ?>');
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<?php echo $currentLang === "zh_CN" ? "获取验证码" : "Get Code"; ?>';
                    alert(data.message || '<?php echo $currentLang === "zh_CN" ? "发送失败，请稍后重试" : "Failed to send, please try again later"; ?>');
                }
            })
            .catch(err => {
                console.error('Send code error:', err);
                btn.disabled = false;
                btn.innerHTML = '<?php echo $currentLang === "zh_CN" ? "获取验证码" : "Get Code"; ?>';
                alert('<?php echo $currentLang === "zh_CN" ? "发送失败，请稍后重试" : "Failed to send, please try again later"; ?>');
            });
        }
        
        // 发送手机验证码
        function sendPhoneCode() {
            const phoneInput = document.getElementById('phoneInput');
            const phone = phoneInput.value.trim();
            const btn = document.getElementById('sendPhoneCodeBtn');

            if (!validatePhoneFormat()) {
                alert('<?php echo $currentLang === "zh_CN" ? "请输入正确的手机号格式" : "Please enter a valid phone number format"; ?>');
                phoneInput.focus();
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $currentLang === "zh_CN" ? "发送中..." : "Sending..."; ?>';

            fetch('/cscabridge_kimi/api/auth/send-phone-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ phone: phone, type: 'register' })
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('Network response was not ok');
                }
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    let seconds = 60;
                    btn.innerHTML = seconds + 's';

                    const timer = setInterval(() => {
                        seconds--;
                        btn.innerHTML = seconds + 's';
                        if (seconds <= 0) {
                            clearInterval(timer);
                            btn.disabled = false;
                            btn.innerHTML = '<?php echo $currentLang === "zh_CN" ? "获取验证码" : "Get Code"; ?>';
                        }
                    }, 1000);

                    if (data.code) {
                        alert('<?php echo $currentLang === "zh_CN" ? "验证码已发送（开发模式）\\n验证码: " : "Verification code sent (Dev Mode)\\nCode: "; ?>' + data.code);
                    } else {
                        alert('<?php echo $currentLang === "zh_CN" ? "验证码已发送" : "Verification code sent"; ?>');
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<?php echo $currentLang === "zh_CN" ? "获取验证码" : "Get Code"; ?>';
                    alert(data.message || '<?php echo $currentLang === "zh_CN" ? "发送失败，请稍后重试" : "Failed to send, please try again later"; ?>');
                }
            })
            .catch(err => {
                console.error('Send code error:', err);
                btn.disabled = false;
                btn.innerHTML = '<?php echo $currentLang === "zh_CN" ? "获取验证码" : "Get Code"; ?>';
                alert('<?php echo $currentLang === "zh_CN" ? "发送失败，请稍后重试" : "Failed to send, please try again later"; ?>');
            });
        }
        
        // 表单提交验证
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const registerType = document.getElementById('registerType').value;
            
            if (registerType === 'email') {
                const email = document.getElementById('emailInput').value.trim();
                const emailCode = document.getElementById('emailCodeInput').value.trim();
                const password = document.getElementById('passwordInput').value;
                const passwordConfirm = document.getElementById('passwordConfirmInput').value;
                
                if (!validateEmailFormat()) {
                    e.preventDefault();
                    alert('<?php echo $currentLang === "zh_CN" ? "请输入正确的邮箱地址" : "Please enter a valid email address"; ?>');
                    return false;
                }
                
                if (emailCode.length !== 6 || !/^\d+$/.test(emailCode)) {
                    e.preventDefault();
                    alert('<?php echo $currentLang === "zh_CN" ? "请输入6位数字验证码" : "Please enter 6-digit verification code"; ?>');
                    return false;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('<?php echo $currentLang === "zh_CN" ? "密码至少需要8个字符" : "Password must be at least 8 characters"; ?>');
                    return false;
                }
                
                if (password !== passwordConfirm) {
                    e.preventDefault();
                    alert('<?php echo $currentLang === "zh_CN" ? "两次输入的密码不一致" : "Passwords do not match"; ?>');
                    return false;
                }
            } else if (registerType === 'phone') {
                if (!validatePhoneFormat()) {
                    e.preventDefault();
                    alert('<?php echo $currentLang === "zh_CN" ? "请输入正确的手机号格式" : "Please enter a valid phone number format"; ?>');
                    return false;
                }
            }
        });
    </script>
</body>
</html>
