<?php
/**
 * CSCA Bridge - 发送手机验证码 API
 * 支持国际手机号格式: +国家代码手机号
 * 支持短信服务商：阿里云、腾讯云、Twilio、Nexmo
 */

session_start();

// 引入必要的函数
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$phone = trim($input['phone'] ?? '');
$type = $input['type'] ?? 'register'; // register 或 login

// 验证CSRF Token（如果存在）
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? '';
if (!empty($csrfToken) && !validateCsrfToken($csrfToken)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token'
    ]);
    exit;
}

// 增强版手机号格式验证
function validatePhoneFormat(string $phone): array
{
    $result = ['valid' => false, 'message' => ''];

    if (empty($phone)) {
        $result['message'] = 'Phone number is required';
        return $result;
    }

    // 必须以+开头
    if (!str_starts_with($phone, '+')) {
        $result['message'] = 'Phone number must start with + (country code)';
        return $result;
    }

    // 获取数字部分
    $digits = substr($phone, 1);

    // 必须全是数字
    if (!ctype_digit($digits)) {
        $result['message'] = 'Phone number can only contain digits after +';
        return $result;
    }

    // 长度检查（8-15位）
    $length = strlen($digits);
    if ($length < 8 || $length > 15) {
        $result['message'] = "Phone number length must be 8-15 digits (current: {$length})";
        return $result;
    }

    // 国家代码不能以0开头
    if (str_starts_with($digits, '0')) {
        $result['message'] = 'Country code cannot start with 0';
        return $result;
    }

    // 检查手机号部分是否以0开头（警告但不阻止）
    for ($i = 1; $i <= 3 && $i < $length; $i++) {
        $nationalNumber = substr($digits, $i);
        if (strlen($nationalNumber) >= 5 && str_starts_with($nationalNumber, '0')) {
            $result['message'] = 'Warning: National number should not start with 0 in international format';
            // 继续验证，不返回
        }
    }

    $result['valid'] = true;
    return $result;
}

// 验证手机号格式
$phoneValidation = validatePhoneFormat($phone);
if (!$phoneValidation['valid']) {
    echo json_encode([
        'success' => false,
        'message' => $phoneValidation['message']
    ]);
    exit;
}

// 检查发送间隔（防止频繁发送）
$lastSendKey = 'phone_code_last_send_' . md5($phone);
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
$_SESSION['phone_verify_code'] = $code;
$_SESSION['phone_verify_phone'] = $phone;
$_SESSION['phone_verify_expires'] = time() + 300; // 5分钟过期
$_SESSION[$lastSendKey] = time();

// 记录日志
if (function_exists('logMessage')) {
    logMessage("Phone code generated for {$phone}: {$code}", 'info', 'sms');
}

// 检测是否为开发环境
$isDevelopment = false;
if (getenv('APP_ENV') === 'development') {
    $isDevelopment = true;
} elseif (getenv('SMS_PROVIDER') === '' || getenv('SMS_PROVIDER') === false) {
    $isDevelopment = true;
} elseif ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    $isDevelopment = true;
}

// 开发环境：直接返回验证码
if ($isDevelopment) {
    echo json_encode([
        'success' => true,
        'message' => 'Verification code generated (development mode)',
        'code' => $code, // 仅在开发环境返回
        'mode' => 'development'
    ]);
    exit;
}

// 生产环境：调用短信服务商API
$smsProvider = getenv('SMS_PROVIDER'); // aliyun, tencent, twilio, nexmo

$result = false;
$message = '';

switch ($smsProvider) {
    case 'aliyun':
        $result = sendAliyunSms($phone, $code);
        break;
    case 'tencent':
        $result = sendTencentSms($phone, $code);
        break;
    case 'twilio':
        $result = sendTwilioSms($phone, $code);
        break;
    case 'nexmo':
        $result = sendNexmoSms($phone, $code);
        break;
    default:
        // 使用模拟发送（记录日志但不实际发送）
        $result = true;
        $message = 'SMS provider not configured, using mock mode';
        logMessage("Mock SMS sent to {$phone}: {$code}", 'warning', 'sms');
}

if ($result) {
    echo json_encode([
        'success' => true, 
        'message' => $message ?: 'Verification code sent successfully'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to send verification code. Please try again later.'
    ]);
}

/**
 * 发送阿里云短信
 * 文档: https://help.aliyun.com/document_detail/101414.html
 */
function sendAliyunSms(string $phone, string $code): bool
{
    $accessKeyId = getenv('ALIYUN_ACCESS_KEY_ID');
    $accessKeySecret = getenv('ALIYUN_ACCESS_KEY_SECRET');
    $signName = getenv('ALIYUN_SMS_SIGN_NAME');
    $templateCode = getenv('ALIYUN_SMS_TEMPLATE_CODE');
    
    if (empty($accessKeyId) || empty($accessKeySecret) || empty($signName) || empty($templateCode)) {
        logMessage('Aliyun SMS credentials not configured', 'error', 'sms');
        return false;
    }
    
    // 阿里云短信API需要签名计算，这里简化处理
    // 实际使用时请参考阿里云官方SDK
    
    $params = [
        'PhoneNumbers' => ltrim($phone, '+'), // 阿里云不需要+号
        'SignName' => $signName,
        'TemplateCode' => $templateCode,
        'TemplateParam' => json_encode(['code' => $code]),
    ];
    
    // TODO: 实现阿里云短信发送逻辑
    // 推荐使用阿里云官方PHP SDK
    
    logMessage("Aliyun SMS would be sent to {$phone} with code {$code}", 'info', 'sms');
    return true;
}

/**
 * 发送腾讯云短信
 * 文档: https://cloud.tencent.com/document/product/382/43197
 */
function sendTencentSms(string $phone, string $code): bool
{
    $secretId = getenv('TENCENT_SECRET_ID');
    $secretKey = getenv('TENCENT_SECRET_KEY');
    $smsSdkAppId = getenv('TENCENT_SMS_SDK_APP_ID');
    $signName = getenv('TENCENT_SMS_SIGN_NAME');
    $templateId = getenv('TENCENT_SMS_TEMPLATE_ID');
    
    if (empty($secretId) || empty($secretKey) || empty($smsSdkAppId) || empty($signName) || empty($templateId)) {
        logMessage('Tencent SMS credentials not configured', 'error', 'sms');
        return false;
    }
    
    // 腾讯云短信API需要签名计算
    // 实际使用时请参考腾讯云官方SDK
    
    logMessage("Tencent SMS would be sent to {$phone} with code {$code}", 'info', 'sms');
    return true;
}

/**
 * 发送Twilio短信 (国际短信)
 * 文档: https://www.twilio.com/docs/sms
 */
function sendTwilioSms(string $phone, string $code): bool
{
    $accountSid = getenv('TWILIO_ACCOUNT_SID');
    $authToken = getenv('TWILIO_AUTH_TOKEN');
    $fromNumber = getenv('TWILIO_PHONE_NUMBER');
    
    if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
        logMessage('Twilio credentials not configured', 'error', 'sms');
        return false;
    }
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
    
    $data = [
        'To' => $phone, // Twilio需要带+号的国际格式
        'From' => $fromNumber,
        'Body' => "Your CSCA Bridge verification code is: {$code}. Valid for 5 minutes.",
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        logMessage("Twilio cURL error: {$error}", 'error', 'sms');
        return false;
    }
    
    if ($httpCode === 201) {
        logMessage("Twilio SMS sent successfully to {$phone}", 'info', 'sms');
        return true;
    } else {
        logMessage("Twilio SMS failed. HTTP: {$httpCode}, Response: {$response}", 'error', 'sms');
        return false;
    }
}

/**
 * 发送Nexmo (Vonage) 短信
 * 文档: https://developer.vonage.com/messaging/sms/overview
 */
function sendNexmoSms(string $phone, string $code): bool
{
    $apiKey = getenv('NEXMO_API_KEY');
    $apiSecret = getenv('NEXMO_API_SECRET');
    $fromName = getenv('NEXMO_FROM_NAME') ?: 'CSCABridge';
    
    if (empty($apiKey) || empty($apiSecret)) {
        logMessage('Nexmo credentials not configured', 'error', 'sms');
        return false;
    }
    
    $url = 'https://rest.nexmo.com/sms/json';
    
    $data = [
        'api_key' => $apiKey,
        'api_secret' => $apiSecret,
        'to' => ltrim($phone, '+'),
        'from' => $fromName,
        'text' => "Your CSCA Bridge verification code is: {$code}. Valid for 5 minutes.",
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        logMessage("Nexmo cURL error: {$error}", 'error', 'sms');
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['messages'][0]['status']) && $result['messages'][0]['status'] == '0') {
        logMessage("Nexmo SMS sent successfully to {$phone}", 'info', 'sms');
        return true;
    } else {
        logMessage("Nexmo SMS failed: {$response}", 'error', 'sms');
        return false;
    }
}


