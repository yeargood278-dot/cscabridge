<?php
/**
 * CSCA Bridge - 微信 OAuth 登录/注册
 * 需要申请微信开放平台接入权限
 */

session_start();

require_once __DIR__ . '/../../includes/Database.php';

// 微信OAuth配置
$appId = getenv('WECHAT_APP_ID');
$appSecret = getenv('WECHAT_APP_SECRET');

// 检查配置是否有效
$isConfigured = !empty($appId) && !empty($appSecret) &&
                strpos($appId, 'your-') === false &&
                strpos($appSecret, 'your-') === false &&
                strlen($appId) > 10 && strlen($appSecret) > 10;

// 检测是否为开发环境
$isDevelopment = (getenv('APP_ENV') === 'development') ||
                 ($_SERVER['HTTP_HOST'] === 'localhost') ||
                 ($_SERVER['HTTP_HOST'] === '127.0.0.1');

// 配置未设置时，显示开发模式模拟登录页面
if (!$isConfigured) {
    if ($isDevelopment) {
        // 开发模式：显示模拟登录页面
        showDevModePage('wechat');
        exit;
    } else {
        // 生产环境：显示错误页面
        showErrorPage(
            'WeChat Login Not Configured',
            'WeChat OAuth is not configured. Please contact the administrator.',
            '微信登录未配置，请联系管理员。'
        );
        exit;
    }
}

$wechatConfig = [
    'app_id' => $appId,
    'app_secret' => $appSecret,
    'redirect_uri' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/api/auth/wechat.php',
    'auth_url' => 'https://open.weixin.qq.com/connect/qrconnect',
    'token_url' => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    'userinfo_url' => 'https://api.weixin.qq.com/sns/userinfo',
];

// 步骤1: 获取授权码
if (!isset($_GET['code'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    
    $params = [
        'appid' => $wechatConfig['app_id'],
        'redirect_uri' => $wechatConfig['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'snsapi_login', // 网页授权
        'state' => $state,
    ];
    
    $authUrl = $wechatConfig['auth_url'] . '?' . http_build_query($params) . '#wechat_redirect';
    header('Location: ' . $authUrl);
    exit;
}

// 验证state
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Invalid state parameter');
}

// 步骤2: 用授权码换取访问令牌
$code = $_GET['code'];

$tokenUrl = $wechatConfig['token_url'] . '?' . http_build_query([
    'appid' => $wechatConfig['app_id'],
    'secret' => $wechatConfig['app_secret'],
    'code' => $code,
    'grant_type' => 'authorization_code',
]);

$tokenResponse = file_get_contents($tokenUrl);
$tokenInfo = json_decode($tokenResponse, true);

if (isset($tokenInfo['errcode'])) {
    error_log('WeChat OAuth error: ' . $tokenResponse);
    header('Location: /student/views/auth/login.php?error=oauth_failed');
    exit;
}

if (!isset($tokenInfo['access_token'])) {
    header('Location: /student/views/auth/login.php?error=oauth_failed');
    exit;
}

// 步骤3: 获取用户信息
$userinfoUrl = $wechatConfig['userinfo_url'] . '?' . http_build_query([
    'access_token' => $tokenInfo['access_token'],
    'openid' => $tokenInfo['openid'],
    'lang' => 'zh_CN',
]);

$userResponse = file_get_contents($userinfoUrl);
$userInfo = json_decode($userResponse, true);

if (isset($userInfo['errcode']) || !isset($userInfo['openid'])) {
    error_log('WeChat user info error: ' . $userResponse);
    header('Location: /student/views/auth/login.php?error=oauth_failed');
    exit;
}

// 处理用户登录/注册
try {
    $db = Database::getInstance();
    
    // 微信用户使用 unionid 或 openid 作为唯一标识
    $providerId = $userInfo['unionid'] ?? $userInfo['openid'];
    
    // 检查用户是否已存在（通过provider_id）
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE auth_provider_id = ? AND auth_provider = 'wechat' AND deleted_at IS NULL",
        [$providerId]
    );
    
    if (!$user && isset($userInfo['email']) && !empty($userInfo['email'])) {
        // 如果通过provider_id没找到，但提供了邮箱，检查邮箱
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$userInfo['email']]
        );
    }
    
    if ($user) {
        // 更新用户信息
        $updateData = [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ];
        
        // 如果之前不是微信登录，更新provider信息
        if ($user['auth_provider'] !== 'wechat') {
            $updateData['auth_provider'] = 'wechat';
            $updateData['auth_provider_id'] = $providerId;
        }
        
        $db->update('users', $updateData, 'id = ?', [$user['id']]);
        
        // 更新用户资料
        if (isset($userInfo['nickname']) || isset($userInfo['headimgurl'])) {
            $profileData = [];
            if (isset($userInfo['nickname'])) {
                $profileData['nickname'] = $userInfo['nickname'];
            }
            if (isset($userInfo['headimgurl'])) {
                $profileData['avatar_url'] = $userInfo['headimgurl'];
            }
            if (!empty($profileData)) {
                $db->update('user_profiles', $profileData, 'user_id = ?', [$user['id']]);
            }
        }
        
        // 设置会话
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['nickname'] ?: $userInfo['nickname'] ?: 'WeChat User';
        
        logOAuthSuccess($user['email'] ?? $providerId, 'wechat');
        
        $redirect = $user['role'] === 'platform_admin' ? '/admin/' : '/student/dashboard/';
        header('Location: ' . $redirect);
        exit;
    } else {
        // 新用户注册
        // 微信可能不提供邮箱，需要生成一个临时邮箱
        $email = $userInfo['email'] ?? 'wechat_' . substr($providerId, -8) . '@cscabridge.temp';
        $nickname = $userInfo['nickname'] ?: 'WeChat User';
        
        $randomPassword = bin2hex(random_bytes(16));
        
        $userId = $db->insert('users', [
            'email' => $email,
            'password_hash' => password_hash($randomPassword, PASSWORD_BCRYPT),
            'auth_provider' => 'wechat',
            'auth_provider_id' => $providerId,
            'role' => 'student',
            'status' => 1,
            'nickname' => $nickname,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 创建用户扩展信息
        $db->insert('user_profiles', [
            'user_id' => $userId,
            'nickname' => $nickname,
            'avatar_url' => $userInfo['headimgurl'] ?? null,
            'gender' => isset($userInfo['sex']) ? ($userInfo['sex'] == 1 ? 'male' : 'female') : null,
            'country' => $userInfo['country'] ?? null,
            'city' => $userInfo['city'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 设置会话
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'student';
        $_SESSION['user_name'] = $nickname;
        
        logOAuthSuccess($email, 'wechat', true);
        
        header('Location: /student/dashboard/');
        exit;
    }
} catch (Exception $e) {
    error_log('WeChat OAuth processing error: ' . $e->getMessage());
    header('Location: /student/views/auth/login.php?error=oauth_failed');
    exit;
}

function logOAuthSuccess(string $identifier, string $provider, bool $isNew = false): void
{
    $logDir = __DIR__ . '/../../logs/oauth/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . date('Y-m-d') . '.log';
    $action = $isNew ? 'registered' : 'logged in';
    $logEntry = sprintf("[%s] [%s] User %s %s via %s%s",
        date('Y-m-d H:i:s'),
        'SUCCESS',
        $identifier,
        $action,
        $provider,
        PHP_EOL
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * 显示错误页面
 */
function showErrorPage(string $title, string $messageEn, string $messageZh): void
{
    $currentLang = $_SESSION['lang'] ?? 'zh_CN';
    $message = $currentLang === 'zh_CN' ? $messageZh : $messageEn;
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> - CSCA Bridge</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Noto Sans SC', sans-serif;
                background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
            }
            .error-container {
                background: white;
                padding: 48px;
                border-radius: 16px;
                box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 400px;
            }
            .error-icon {
                font-size: 64px;
                color: #ef4444;
                margin-bottom: 24px;
            }
            .error-title {
                font-size: 24px;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 16px;
            }
            .error-message {
                color: #64748b;
                margin-bottom: 24px;
                line-height: 1.6;
            }
            .btn-back {
                display: inline-block;
                padding: 12px 24px;
                background: #f39c12;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            .btn-back:hover {
                background: #e67e22;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title"><?php echo $title; ?></h1>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
            <a href="/student/views/auth/login.php" class="btn-back">Back to Login / 返回登录</a>
        </div>
    </body>
    </html>
    <?php
}

/**
 * 显示开发模式模拟登录页面
 */
function showDevModePage(string $provider): void
{
    $currentLang = $_SESSION['lang'] ?? 'zh_CN';
    $isZh = $currentLang === 'zh_CN';
    $providerName = $isZh ? '微信' : 'WeChat';

    // 处理模拟登录表单提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dev_login'])) {
        $email = $_POST['dev_email'] ?? 'dev_test@example.com';
        $name = $_POST['dev_name'] ?? 'Dev Test User';

        // 模拟OAuth登录流程
        processDevModeLogin($email, $name, 'wechat');
        exit;
    }

    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $isZh ? 'zh-CN' : 'en'; ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $isZh ? '开发模式 - ' . $providerName . ' 模拟登录' : 'Dev Mode - ' . $providerName . ' Mock Login'; ?> - CSCA Bridge</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Noto Sans SC', sans-serif;
                background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .dev-container {
                background: white;
                padding: 48px;
                border-radius: 16px;
                box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
                max-width: 480px;
                width: 100%;
            }
            .dev-badge {
                display: inline-block;
                background: #f39c12;
                color: white;
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                margin-bottom: 24px;
                text-transform: uppercase;
            }
            .dev-icon {
                width: 64px;
                height: 64px;
                background: linear-gradient(135deg, #07c160, #00b057);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
                font-size: 36px;
                color: white;
            }
            .dev-title {
                font-size: 24px;
                font-weight: 600;
                color: #1e293b;
                text-align: center;
                margin-bottom: 12px;
            }
            .dev-subtitle {
                color: #64748b;
                text-align: center;
                margin-bottom: 32px;
                line-height: 1.6;
            }
            .dev-form-group {
                margin-bottom: 20px;
            }
            .dev-label {
                display: block;
                font-size: 14px;
                font-weight: 500;
                color: #374151;
                margin-bottom: 8px;
            }
            .dev-input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                font-size: 15px;
                transition: all 0.3s ease;
            }
            .dev-input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            .dev-btn {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #07c160, #00b057);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 8px;
            }
            .dev-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(7, 193, 96, 0.3);
            }
            .dev-notice {
                background: #fef3c7;
                border: 1px solid #fbbf24;
                border-radius: 8px;
                padding: 16px;
                margin-bottom: 24px;
                font-size: 14px;
                color: #92400e;
            }
            .dev-notice strong {
                display: block;
                margin-bottom: 8px;
            }
            .dev-back {
                text-align: center;
                margin-top: 24px;
            }
            .dev-back a {
                color: #3b82f6;
                text-decoration: none;
                font-size: 14px;
            }
            .dev-back a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="dev-container">
            <div class="dev-badge"><?php echo $isZh ? '开发模式' : 'Development Mode'; ?></div>
            <div class="dev-icon">微</div>
            <h1 class="dev-title"><?php echo $isZh ? $providerName . ' 模拟登录' : $providerName . ' Mock Login'; ?></h1>
            <p class="dev-subtitle">
                <?php echo $isZh
                    ? '微信 OAuth 未配置。这是开发模式下的模拟登录页面，用于测试登录流程。'
                    : 'WeChat OAuth is not configured. This is a mock login page for development testing.';
                ?>
            </p>

            <div class="dev-notice">
                <strong><?php echo $isZh ? '提示' : 'Notice'; ?></strong>
                <?php echo $isZh
                    ? '点击下方按钮即可模拟成功登录。在生产环境中，请配置正确的微信开放平台凭据。'
                    : 'Click the button below to simulate a successful login. Configure proper WeChat Open Platform credentials for production.';
                ?>
            </div>

            <form method="POST" action="">
                <div class="dev-form-group">
                    <label class="dev-label"><?php echo $isZh ? '模拟邮箱' : 'Mock Email'; ?></label>
                    <input type="email" name="dev_email" class="dev-input"
                           value="wechat_dev@example.com" required>
                </div>
                <div class="dev-form-group">
                    <label class="dev-label"><?php echo $isZh ? '模拟昵称' : 'Mock Nickname'; ?></label>
                    <input type="text" name="dev_name" class="dev-input"
                           value="微信测试用户" required>
                </div>
                <button type="submit" name="dev_login" class="dev-btn">
                    <?php echo $isZh ? '模拟登录' : 'Simulate Login'; ?>
                </button>
            </form>

            <div class="dev-back">
                <a href="/student/views/auth/login.php">
                    <?php echo $isZh ? '返回登录页面' : 'Back to Login'; ?>
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * 处理开发模式登录
 */
function processDevModeLogin(string $email, string $name, string $provider): void
{
    require_once __DIR__ . '/../../includes/Database.php';

    try {
        $db = Database::getInstance();

        // 检查用户是否已存在
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$email]
        );

        if ($user) {
            // 更新登录信息
            $db->update('users', [
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'last_login_method' => $provider . '_dev',
            ], 'id = ?', [$user['id']]);

            // 设置会话
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['nickname'] ?: $name;

            $redirect = $user['role'] === 'platform_admin' ? '/admin/' : '/student/dashboard/';
        } else {
            // 创建新用户
            $nickname = $name;
            $randomPassword = bin2hex(random_bytes(16));

            $userId = $db->insert('users', [
                'email' => $email,
                'password_hash' => password_hash($randomPassword, PASSWORD_BCRYPT),
                'auth_provider' => $provider . '_dev',
                'auth_provider_id' => 'dev_' . uniqid(),
                'role' => 'student',
                'status' => 1,
                'nickname' => $nickname,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'last_login_method' => $provider . '_dev',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // 创建用户资料
            $db->insert('user_profiles', [
                'user_id' => $userId,
                'nickname' => $name,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // 分配默认角色
            $defaultRole = $db->fetchOne("SELECT id FROM roles WHERE name = 'student'");
            if ($defaultRole) {
                $db->insert('user_roles', [
                    'user_id' => $userId,
                    'role_id' => $defaultRole['id'],
                ]);
            }

            // 创建免费订阅
            $db->insert('user_subscriptions', [
                'user_id' => $userId,
                'plan_type' => 'free',
                'status' => 'active',
                'starts_at' => date('Y-m-d H:i:s'),
            ]);

            // 设置会话
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'student';
            $_SESSION['user_name'] = $nickname;

            $redirect = '/student/dashboard/';
        }

        // 重定向
        header('Location: ' . $redirect);
        exit;

    } catch (Exception $e) {
        // 显示错误
        echo '<p style="color: red; text-align: center;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p style="text-align: center;"><a href="/student/views/auth/login.php">Back to Login</a></p>';
        exit;
    }
}
