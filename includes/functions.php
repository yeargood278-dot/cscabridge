<?php
/**
 * CSCA Bridge - 公共函数库
 */

/**
 * 生成随机字符串
 */
function generateRandomString(int $length = 16): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * 生成订单编号
 */
function generateOrderNo(): string
{
    return 'CSCA' . date('Ymd') . strtoupper(substr(uniqid(), -8));
}

/**
 * 生成邀请码
 */
function generateInvitationCode(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * 格式化金额
 */
function formatMoney(float $amount, string $currency = 'USD'): string
{
    $symbols = [
        'USD' => '$',
        'CNY' => '¥',
        'EUR' => '€',
        'GBP' => '£',
    ];
    
    $symbol = $symbols[$currency] ?? '$';
    return $symbol . number_format($amount, 2);
}

/**
 * 格式化时间
 */
function formatTime(int $seconds): string
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%02d:%02d', $minutes, $secs);
}

/**
 * 格式化日期
 */
function formatDate(string $date, string $format = 'Y-m-d H:i'): string
{
    return date($format, strtotime($date));
}

/**
 * 获取客户端IP
 */
function getClientIp(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * 发送JSON响应
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * API成功响应
 */
function apiSuccess(array $data = [], string $message = 'Success'): void
{
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => time(),
    ]);
}

/**
 * API错误响应
 */
function apiError(string $message = 'Error', int $code = 400, array $errors = []): void
{
    jsonResponse([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
        'code' => $code,
        'timestamp' => time(),
    ], $code);
}

/**
 * 验证邮箱格式
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 验证密码强度
 */
function isValidPassword(string $password): array
{
    $result = [
        'valid' => true,
        'errors' => [],
    ];
    
    if (strlen($password) < 8) {
        $result['valid'] = false;
        $result['errors'][] = 'Password must be at least 8 characters';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $result['valid'] = false;
        $result['errors'][] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $result['valid'] = false;
        $result['errors'][] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $result['valid'] = false;
        $result['errors'][] = 'Password must contain at least one number';
    }
    
    return $result;
}

/**
 * 生成验证码
 */
function generateCaptchaCode(int $length = 5): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * 生成CSRF令牌
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证CSRF令牌
 */
function validateCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 记录日志
 */
function logMessage(string $message, string $level = 'info', string $channel = 'app'): void
{
    $logDir = __DIR__ . '/../logs/' . $channel . '/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . date('Y-m-d') . '.log';
    $logEntry = sprintf(
        "[%s] [%s] %s%s",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        PHP_EOL
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * 缓存数据
 */
function cacheSet(string $key, $value, int $ttl = 3600): bool
{
    $cacheDir = __DIR__ . '/../cache/data/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . md5($key) . '.cache';
    $data = [
        'expires' => time() + $ttl,
        'value' => $value,
    ];
    
    return file_put_contents($cacheFile, serialize($data), LOCK_EX) !== false;
}

/**
 * 获取缓存数据
 */
function cacheGet(string $key, $default = null)
{
    $cacheFile = __DIR__ . '/../cache/data/' . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return $default;
    }
    
    $data = unserialize(file_get_contents($cacheFile));
    
    if ($data['expires'] < time()) {
        unlink($cacheFile);
        return $default;
    }
    
    return $data['value'];
}

/**
 * 清除缓存
 */
function cacheDelete(string $key): bool
{
    $cacheFile = __DIR__ . '/../cache/data/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    return false;
}
