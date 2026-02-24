<?php
/**
 * CSCA Bridge - 邮箱验证页面
 */

session_start();

require_once __DIR__ . '/../../../includes/functions.php';

// 语言切换
if (isset($_GET['lang']) && in_array($_GET['lang'], ['zh_CN', 'en_US'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'zh_CN';
$langFile = __DIR__ . '/../../../lang/' . $currentLang . '/auth.php';
if (!file_exists($langFile)) {
    $langFile = __DIR__ . '/../../../lang/zh_CN/auth.php';
}
$lang = require $langFile;

// 检查是否有待验证的注册数据
if (!isset($_SESSION['register_data']) || !isset($_SESSION['email_verify_email'])) {
    header('Location: /student/views/auth/register.php');
    exit;
}

$errors = [];
$success = '';

// 处理验证表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['verification_code'] ?? '');
    
    if (empty($code)) {
        $errors[] = $lang['verify_email_error'];
    } elseif (strlen($code) !== 6 || !ctype_digit($code)) {
        $errors[] = $lang['verify_email_error'];
    } else {
        // 验证验证码
        if (isset($_SESSION['email_verify_code']) && 
            isset($_SESSION['email_verify_expires']) &&
            $_SESSION['email_verify_expires'] > time() &&
            $_SESSION['email_verify_code'] === $code) {
            
            // 验证成功，创建用户
            try {
                require_once __DIR__ . '/../../../includes/Database.php';
                $db = Database::getInstance();
                $registerData = $_SESSION['register_data'];
                
                // 创建用户
                $userId = $db->insert('users', [
                    'email' => $registerData['email'],
                    'password_hash' => $registerData['password'],
                    'auth_provider' => 'email',
                    'role' => 'student',
                    'status' => 1,
                    'nickname' => $registerData['nickname'],
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                
                // 创建用户扩展信息
                $db->insert('user_profiles', [
                    'user_id' => $userId,
                    'nickname' => $registerData['nickname'],
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
                
                // 清除session数据
                unset($_SESSION['register_data']);
                unset($_SESSION['email_verify_code']);
                unset($_SESSION['email_verify_email']);
                unset($_SESSION['email_verify_expires']);
                
                // 设置登录会话
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $registerData['email'];
                $_SESSION['user_role'] = 'student';
                $_SESSION['user_name'] = $registerData['nickname'];
                
                $success = $lang['verify_email_success'];
                
                // 3秒后重定向
                header('Refresh: 3; URL=/student/dashboard/');
                
            } catch (Exception $e) {
                $errors[] = $lang['error_system'];
                logMessage("Email verification error: " . $e->getMessage(), 'error', 'register');
            }
        } else {
            $errors[] = $lang['verify_email_error'];
        }
    }
}

// 获取邮箱（脱敏显示）
$email = $_SESSION['email_verify_email'] ?? '';
$maskedEmail = maskEmail($email);

function maskEmail(string $email): string
{
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    
    $name = $parts[0];
    $domain = $parts[1];
    
    $nameLength = strlen($name);
    if ($nameLength <= 2) {
        $maskedName = str_repeat('*', $nameLength);
    } else {
        $maskedName = substr($name, 0, 2) . str_repeat('*', $nameLength - 2);
    }
    
    return $maskedName . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang === 'en_US' ? 'en' : 'zh-CN'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['verify_email_title']; ?> - <?php echo $lang['site_name']; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+SC:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-dark: #1e3a5f;
            --primary-light: #2c5282;
            --accent-orange: #f39c12;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --error: #ef4444;
            --success: #10b981;
            --radius-md: 12px;
            --radius-lg: 16px;
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
        }
        
        .verify-container {
            width: 100%;
            max-width: 440px;
        }
        
        .verify-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 48px;
            box-shadow: var(--shadow-xl);
            text-align: center;
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
            margin-bottom: 24px;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 12px;
        }
        
        .form-subtitle {
            font-size: 14px;
            color: var(--gray-500);
            margin-bottom: 32px;
        }
        
        .email-display {
            background: var(--gray-100);
            padding: 12px 20px;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 24px;
            display: inline-block;
        }
        
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
        
        .code-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 24px;
        }
        
        .code-input {
            width: 50px; height: 56px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            color: var(--gray-800);
            transition: all 0.3s ease;
        }
        
        .code-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent-orange), #e67e22);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(243, 156, 18, 0.4);
        }
        
        .resend-section {
            font-size: 14px;
            color: var(--gray-500);
        }
        
        .resend-link {
            color: var(--primary-light);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        
        .resend-link:hover { text-decoration: underline; }
        
        .resend-timer { color: var(--gray-400); }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            color: var(--gray-500);
            font-size: 14px;
            text-decoration: none;
        }
        
        .back-link:hover { color: var(--primary-light); }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="logo-icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            
            <h1 class="form-title"><?php echo $lang['verify_email_title']; ?></h1>
            <p class="form-subtitle"><?php echo $lang['verify_email_subtitle']; ?></p>
            
            <div class="email-display">
                <i class="fas fa-envelope" style="margin-right: 8px; color: var(--primary-light);"></i>
                <?php echo htmlspecialchars($maskedEmail); ?>
            </div>
            
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
            
            <?php if (!$success): ?>
            <form method="POST" action="" id="verifyForm">
                <div class="code-inputs">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                </div>
                
                <input type="hidden" name="verification_code" id="fullCode">
                
                <button type="submit" class="btn-submit">
                    <?php echo $lang['verify_email_button']; ?>
                </button>
            </form>
            
            <div class="resend-section">
                <span id="resendTimer">60s</span>
                <span class="resend-timer"> <?php echo $lang['verify_email_resend_timer']; ?></span>
                <br>
                <a class="resend-link" id="resendLink" style="display: none;" onclick="resendCode()">
                    <?php echo $lang['verify_email_resend']; ?>
                </a>
            </div>
            <?php endif; ?>
            
            <a href="/student/views/auth/register.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span><?php echo $lang['back_to_home']; ?></span>
            </a>
        </div>
    </div>
    
    <script>
        // 验证码输入自动跳转
        const codeInputs = document.querySelectorAll('.code-input');
        const fullCodeInput = document.getElementById('fullCode');
        
        codeInputs.forEach((input, index) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1) {
                    if (index < codeInputs.length - 1) {
                        codeInputs[index + 1].focus();
                    }
                }
                updateFullCode();
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    codeInputs[index - 1].focus();
                }
            });
            
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').slice(0, 6);
                if (/^\d+$/.test(pasteData)) {
                    pasteData.split('').forEach((char, i) => {
                        if (codeInputs[i]) codeInputs[i].value = char;
                    });
                    updateFullCode();
                }
            });
        });
        
        function updateFullCode() {
            const code = Array.from(codeInputs).map(input => input.value).join('');
            fullCodeInput.value = code;
        }
        
        // 倒计时
        let timeLeft = 60;
        const timerElement = document.getElementById('resendTimer');
        const resendLink = document.getElementById('resendLink');
        
        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft + 's';
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerElement.parentElement.style.display = 'none';
                resendLink.style.display = 'inline';
            }
        }, 1000);
        
        function resendCode() {
            alert('<?php echo $currentLang === 'en_US' ? 'Verification code resent!' : '验证码已重新发送！'; ?>');
            location.reload();
        }
        
        document.getElementById('verifyForm').addEventListener('submit', updateFullCode);
    </script>
</body>
</html>
