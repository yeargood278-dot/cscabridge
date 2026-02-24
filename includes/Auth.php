<?php
/**
 * CSCA Bridge - 认证与权限控制类
 * 提供统一的认证、授权和权限检查功能
 */

class Auth
{
    private static ?array $currentUser = null;
    private static ?array $userPermissions = null;
    
    /**
     * 检查用户是否已登录
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * 获取当前登录用户
     */
    public static function user(): ?array
    {
        if (self::$currentUser === null && self::check()) {
            try {
                $db = Database::getInstance();
                self::$currentUser = $db->fetchOne(
                    "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
                    [$_SESSION['user_id']]
                );
            } catch (Exception $e) {
                error_log('Auth user error: ' . $e->getMessage());
            }
        }
        return self::$currentUser;
    }
    
    /**
     * 获取当前用户ID
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * 检查用户角色
     */
    public static function hasRole(string $role): bool
    {
        if (!self::check()) {
            return false;
        }
        
        // 直接检查session中的角色
        if ($_SESSION['user_role'] === $role) {
            return true;
        }
        
        // 从数据库检查角色
        try {
            $db = Database::getInstance();
            $hasRole = $db->fetchOne("
                SELECT 1 FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = ? AND r.name = ?
                LIMIT 1
            ", [$_SESSION['user_id'], $role]);
            
            return $hasRole !== null;
        } catch (Exception $e) {
            error_log('Auth hasRole error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查用户是否有指定权限
     */
    public static function hasPermission(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }
        
        // 平台管理员拥有所有权限
        if ($_SESSION['user_role'] === 'platform_admin') {
            return true;
        }
        
        // 检查缓存的权限
        if (self::$userPermissions === null) {
            self::$userPermissions = self::getUserPermissions();
        }
        
        return in_array($permission, self::$userPermissions);
    }
    
    /**
     * 检查用户是否有多个权限中的任意一个
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 检查用户是否拥有所有指定权限
     */
    public static function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!self::hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 获取用户所有权限
     */
    public static function getUserPermissions(): array
    {
        if (!self::check()) {
            return [];
        }
        
        try {
            $db = Database::getInstance();
            $permissions = $db->fetchAll("
                SELECT DISTINCT p.name 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ?
            ", [$_SESSION['user_id']]);
            
            return array_column($permissions, 'name');
        } catch (Exception $e) {
            error_log('Auth getUserPermissions error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 检查用户是否可以访问资源
     */
    public static function canAccessResource(string $resourceType, int $resourceId, string $permission = 'view'): bool
    {
        if (!self::check()) {
            return false;
        }
        
        $user = self::user();
        if (!$user) {
            return false;
        }
        
        // 平台管理员可以访问所有资源
        if ($user['role'] === 'platform_admin') {
            return true;
        }
        
        // 检查特定权限
        $permissionName = $resourceType . '.' . $permission;
        if (!self::hasPermission($permissionName)) {
            return false;
        }
        
        // 检查具体的资源访问权限
        try {
            $db = Database::getInstance();
            $access = $db->fetchOne("
                SELECT 1 FROM user_resource_permissions
                WHERE user_id = ? AND resource_type = ? AND resource_id = ? AND permission_type = ?
                AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1
            ", [$user['id'], $resourceType, $resourceId, $permission]);
            
            return $access !== null;
        } catch (Exception $e) {
            error_log('Auth canAccessResource error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查用户是否为付费用户
     */
    public static function isPaidUser(): bool
    {
        if (!self::check()) {
            return false;
        }
        
        try {
            $db = Database::getInstance();
            $subscription = $db->fetchOne("
                SELECT 1 FROM user_subscriptions
                WHERE user_id = ? AND status = 'active' 
                AND plan_type IN ('basic', 'premium', 'vip')
                AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1
            ", [$_SESSION['user_id']]);
            
            return $subscription !== null;
        } catch (Exception $e) {
            error_log('Auth isPaidUser error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查用户订阅类型
     */
    public static function getSubscriptionType(): string
    {
        if (!self::check()) {
            return 'guest';
        }
        
        try {
            $db = Database::getInstance();
            $subscription = $db->fetchOne("
                SELECT plan_type FROM user_subscriptions
                WHERE user_id = ? AND status = 'active'
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY 
                    CASE plan_type
                        WHEN 'vip' THEN 1
                        WHEN 'premium' THEN 2
                        WHEN 'basic' THEN 3
                        ELSE 4
                    END
                LIMIT 1
            ", [$_SESSION['user_id']]);
            
            return $subscription ? $subscription['plan_type'] : 'free';
        } catch (Exception $e) {
            error_log('Auth getSubscriptionType error: ' . $e->getMessage());
            return 'free';
        }
    }
    
    /**
     * 要求用户登录
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /student/views/auth/login.php');
            exit;
        }
    }
    
    /**
     * 要求特定角色
     */
    public static function requireRole(string $role): void
    {
        self::requireAuth();
        
        if (!self::hasRole($role)) {
            http_response_code(403);
            die('Access Denied: Insufficient permissions');
        }
    }
    
    /**
     * 要求特定权限
     */
    public static function requirePermission(string $permission): void
    {
        self::requireAuth();
        
        if (!self::hasPermission($permission)) {
            http_response_code(403);
            die('Access Denied: Missing required permission');
        }
    }
    
    /**
     * 要求付费用户
     */
    public static function requirePaidUser(): void
    {
        self::requireAuth();
        
        if (!self::isPaidUser()) {
            header('Location: /student/payment/upgrade.php');
            exit;
        }
    }
    
    /**
     * 登录用户
     */
    public static function login(array $user, bool $remember = false): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['nickname'] ?: $user['email'];
        
        // 更新最后登录时间
        try {
            $db = Database::getInstance();
            $db->update('users', [
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'login_attempts' => 0,
                'locked_until' => null,
            ], 'id = ?', [$user['id']]);
        } catch (Exception $e) {
            error_log('Auth login error: ' . $e->getMessage());
        }
        
        // 记住我功能
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 30 * 24 * 3600, '/', '', true, true);
            
            try {
                $db = Database::getInstance();
                $db->update('users', [
                    'remember_token' => password_hash($token, PASSWORD_DEFAULT)
                ], 'id = ?', [$user['id']]);
            } catch (Exception $e) {
                error_log('Auth remember me error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * 登出用户
     */
    public static function logout(): void
    {
        // 清除记住我cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            
            // 清除数据库中的token
            if (isset($_SESSION['user_id'])) {
                try {
                    $db = Database::getInstance();
                    $db->update('users', [
                        'remember_token' => null
                    ], 'id = ?', [$_SESSION['user_id']]);
                } catch (Exception $e) {
                    error_log('Auth logout error: ' . $e->getMessage());
                }
            }
        }
        
        // 清除session
        session_destroy();
    }
    
    /**
     * 检查登录尝试次数
     */
    public static function checkLoginAttempts(string $email): array
    {
        try {
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT login_attempts, locked_until FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                return ['allowed' => true, 'message' => ''];
            }
            
            // 检查账户是否被锁定
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
                return [
                    'allowed' => false,
                    'message' => "Account locked. Please try again in {$remaining} minutes."
                ];
            }
            
            // 检查失败次数
            if ($user['login_attempts'] >= 5) {
                // 锁定账户15分钟
                $db->update('users', [
                    'locked_until' => date('Y-m-d H:i:s', time() + 900)
                ], 'email = ?', [$email]);
                
                return [
                    'allowed' => false,
                    'message' => 'Too many failed attempts. Account locked for 15 minutes.'
                ];
            }
            
            return ['allowed' => true, 'message' => ''];
        } catch (Exception $e) {
            error_log('Auth checkLoginAttempts error: ' . $e->getMessage());
            return ['allowed' => true, 'message' => ''];
        }
    }
    
    /**
     * 增加登录失败次数
     */
    public static function incrementLoginAttempts(string $email): void
    {
        try {
            $db = Database::getInstance();
            $db->query("
                UPDATE users 
                SET login_attempts = login_attempts + 1 
                WHERE email = ?
            ", [$email]);
        } catch (Exception $e) {
            error_log('Auth incrementLoginAttempts error: ' . $e->getMessage());
        }
    }
    
    /**
     * 从记住我cookie自动登录
     */
    public static function attemptRememberMeLogin(): bool
    {
        if (self::check() || !isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        
        try {
            $db = Database::getInstance();
            $users = $db->fetchAll(
                "SELECT * FROM users WHERE remember_token IS NOT NULL AND deleted_at IS NULL"
            );
            
            foreach ($users as $user) {
                if (password_verify($token, $user['remember_token'])) {
                    self::login($user);
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log('Auth remember me login error: ' . $e->getMessage());
        }
        
        return false;
    }
}
