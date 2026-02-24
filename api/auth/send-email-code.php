<?php
/**
 * CSCA Bridge - 发送邮箱验证码 API
 * 支持邮箱验证码发送
 */

session_start();

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$type = $input['type'] ?? 'register';

// 验证CSRF Token
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? '';
if (!empty($csrfToken) && !validateCsrfToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// 增强版邮箱格式验证
function validateEmailFormat(string $email): array {
    $result = ['valid' => false, 'message' => ''];
    
    if (empty($email)) {
        $result['message'] = 'Email address is required';
        return $result;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result['message'] = 'Invalid email format';
        return $result;
    }
    
    $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    if (!preg_match($pattern, $email)) {
        $result['message'] = 'Invalid email format';
        return $result;
    }
    
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        $result['message'] = 'Invalid email format';
        return $result;
    }
    
    list($localPart, $domain) = $parts;
    
    if (strlen($localPart) > 64) {
        $result['message'] = 'Email local part too long';
        return $result;
    }
    
    if (strlen($domain) > 255) {
        $result['message'] = 'Email domain too long';
        return $result;
    }
    
    $domainParts = explode('.', $domain);
    $tld = end($domainParts);
    if (strlen($tld) < 2) {
        $result['message'] = 'Invalid email domain suffix';
        return $result;
    }
    
    $result['valid'] = true;
    return $result;
}

// 验证邮箱格式
$emailValidation = validateEmailFormat($email);
if (!$emailValidation['valid']) {
    echo json_encode(['success' => false, 'message' => $emailValidation['message']]);
    exit;
}

// 检查发送间隔
$lastSendKey = 'email_code_last_send_' . md5($email);
if (isset($_SESSION[$lastSendKey]) && $_SESSION[$lastSendKey] > time() - 60) {
    $waitSeconds = 60 - (time() - $_SESSION[$lastSendKey]);
    echo json_encode([
        'success' => false,
        'message' => "Please wait {$waitSeconds} seconds before requesting a new code",
        'wait_seconds' => $waitSeconds
    ]);
    exit;
}

// 生成6位验证码
$code = sprintf('%06d', random_int(0, 999999));

// 保存到session
$_SESSION['email_verify_code'] = $code;
$_SESSION['email_verify_email'] = $email;
$_SESSION['email_verify_expires'] = time() + 600;
$_SESSION[$lastSendKey] = time();

// 记录日志
if (function_exists('logMessage')) {
    logMessage("Email code generated for {$email}: {$code}", 'info', 'email');
}

// 检测是否为开发环境
$isDevelopment = (getenv('APP_ENV') === 'development') || 
                 ($_SERVER['HTTP_HOST'] === 'localhost') || 
                 ($_SERVER['HTTP_HOST'] === '127.0.0.1');

// 开发环境：直接返回验证码
if ($isDevelopment) {
    echo json_encode([
        'success' => true,
        'message' => 'Verification code generated (development mode)',
        'code' => $code,
        'mode' => 'development'
    ]);
    exit;
}

// 生产环境：尝试发送邮件
$emailSent = sendVerificationEmail($email, $code);

if ($emailSent) {
    echo json_encode(['success' => true, 'message' => 'Verification code sent to your email']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again later.']);
}

/**
 * 发送验证邮件
 */
function sendVerificationEmail(string $to, string $code): bool {
    $subject = 'CSCA Bridge - Email Verification Code';
    
    $from = getenv('SMTP_FROM') ?: 'noreply@cscabridge.com';
    $fromName = getenv('SMTP_FROM_NAME') ?: 'CSCA Bridge';
    
    $htmlMessage = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1e3a5f, #2c5282); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .code { font-size: 32px; font-weight: bold; color: #f39c12; text-align: center; padding: 20px; background: white; border-radius: 8px; margin: 20px 0; letter-spacing: 8px; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CSCA Bridge</h1>
            <p>Email Verification</p>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Thank you for registering with CSCA Bridge. Please use the following verification code to complete your registration:</p>
            <div class="code">' . $code . '</div>
            <p>This code will expire in <strong>5 minutes</strong>.</p>
            <p>If you did not request this code, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' CSCA Bridge. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    $headers = "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $result = mail($to, $subject, $htmlMessage, $headers);
    
    if (function_exists('logMessage')) {
        logMessage("Email sent to {$to} with code {$code}. Result: " . ($result ? 'success' : 'failed'), 'info', 'email');
    }
    
    return $result;
}
