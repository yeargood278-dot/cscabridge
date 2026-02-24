<?php
/**
 * CSCA Bridge - 平台管理后台入口
 * 严格的权限控制，仅允许平台管理员访问
 */

session_start();

// 加载必要文件
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: /student/views/auth/login.php?redirect=/admin/');
    exit;
}

// 检查用户角色（必须是平台管理员）
if ($_SESSION['user_role'] !== 'platform_admin') {
    http_response_code(403);
    die('Access Denied: Insufficient permissions');
}

// 额外的安全检查：验证IP地址（可选）
$allowedIps = getenv('ADMIN_ALLOWED_IPS') ? explode(',', getenv('ADMIN_ALLOWED_IPS')) : [];
if (!empty($allowedIps) && !in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
    http_response_code(403);
    die('Access Denied: IP not allowed');
}

// 记录管理员访问日志
try {
    $db = Database::getInstance();
    $db->insert('security_logs', [
        'user_id' => $_SESSION['user_id'],
        'action' => 'admin_access',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'details' => json_encode(['page' => 'admin_dashboard']),
        'risk_level' => 'low',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    error_log('Failed to log admin access: ' . $e->getMessage());
}

// 获取统计数据
try {
    $db = Database::getInstance();
    
    // 用户统计
    $userStats = $db->fetchOne("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_users,
            SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students,
            SUM(CASE WHEN role = 'institution_admin' THEN 1 ELSE 0 END) as institutions
        FROM users 
        WHERE deleted_at IS NULL
    ");
    
    // 订单统计
    $orderStats = $db->fetchOne("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
            SUM(CASE WHEN status = 'paid' THEN payable_amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN DATE(created_at) = CURDATE() AND status = 'paid' THEN payable_amount ELSE 0 END) as today_revenue
        FROM orders
        WHERE deleted_at IS NULL
    ");
    
    // 课程统计
    $courseStats = $db->fetchOne("
        SELECT 
            COUNT(*) as total_courses,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_courses,
            SUM(enrollment_count) as total_enrollments
        FROM courses
        WHERE deleted_at IS NULL
    ");
    
    // 最近登录
    $recentLogins = $db->fetchAll("
        SELECT u.email, u.nickname, u.last_login_at, u.last_login_ip, u.role
        FROM users u
        WHERE u.last_login_at IS NOT NULL AND u.deleted_at IS NULL
        ORDER BY u.last_login_at DESC
        LIMIT 10
    ");
    
} catch (Exception $e) {
    error_log('Admin dashboard stats error: ' . $e->getMessage());
    $userStats = ['total_users' => 0, 'today_users' => 0, 'students' => 0, 'institutions' => 0];
    $orderStats = ['total_orders' => 0, 'today_orders' => 0, 'total_revenue' => 0, 'today_revenue' => 0];
    $courseStats = ['total_courses' => 0, 'active_courses' => 0, 'total_enrollments' => 0];
    $recentLogins = [];
}

$currentLang = $_SESSION['lang'] ?? 'zh_CN';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang === 'en_US' ? 'en' : 'zh-CN'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - CSCA Bridge</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+SC:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-dark: #1e3a5f;
            --primary-light: #2c5282;
            --accent-orange: #f39c12;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --sidebar-width: 260px;
            --header-height: 64px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', 'Noto Sans SC', sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
        }
        
        /* 侧边栏 */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--primary-dark);
            color: #fff;
            overflow-y: auto;
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-logo-icon {
            width: 40px;
            height: 40px;
            background: var(--accent-orange);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
        }
        
        .sidebar-logo-text {
            font-size: 18px;
            font-weight: 600;
        }
        
        .sidebar-user {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-user-avatar {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .sidebar-user-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .sidebar-user-role {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 24px;
        }
        
        .nav-section-title {
            padding: 0 20px;
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.5;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: var(--accent-orange);
        }
        
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        
        /* 主内容区 */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        
        .header {
            height: var(--header-height);
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .header-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .header-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: none;
            background: var(--gray-100);
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .header-btn:hover {
            background: var(--gray-200);
        }
        
        .header-btn .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 18px;
            height: 18px;
            background: var(--error);
            color: #fff;
            font-size: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .content {
            padding: 24px;
        }
        
        /* 统计卡片 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .stat-title {
            font-size: 14px;
            color: var(--gray-600);
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .stat-icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-icon.green { background: #d1fae5; color: #059669; }
        .stat-icon.orange { background: #fef3c7; color: #d97706; }
        .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 4px;
        }
        
        .stat-change {
            font-size: 13px;
            color: var(--success);
        }
        
        .stat-change.negative {
            color: var(--error);
        }
        
        /* 内容卡片 */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* 表格 */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        
        .data-table th {
            font-weight: 600;
            color: var(--gray-600);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .data-table td {
            border-bottom: 1px solid var(--gray-100);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--gray-200);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .role-badge.admin {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .role-badge.student {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .role-badge.institution {
            background: #fef3c7;
            color: #d97706;
        }
        
        /* 响应式 */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- 侧边栏 -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">C</div>
                <div class="sidebar-logo-text">Admin Panel</div>
            </div>
        </div>
        
        <div class="sidebar-user">
            <div class="sidebar-user-info">
                <div class="sidebar-user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                    <div class="sidebar-user-role">Platform Administrator</div>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="/admin/" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/admin/users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="/admin/institutions.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    <span>Institutions</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Content</div>
                <a href="/admin/courses.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Courses</span>
                </a>
                <a href="/admin/questions.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Questions</span>
                </a>
                <a href="/admin/exams.php" class="nav-item">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Exams</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Finance</div>
                <a href="/admin/orders.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
                <a href="/admin/finance.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Finance</span>
                </a>
                <a href="/admin/settlements.php" class="nav-item">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Settlements</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">System</div>
                <a href="/admin/settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="/admin/logs.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Logs</span>
                </a>
                <a href="/student/views/auth/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- 主内容 -->
    <main class="main-content">
        <header class="header">
            <h1 class="header-title">Dashboard</h1>
            <div class="header-actions">
                <button class="header-btn">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </button>
                <button class="header-btn">
                    <i class="fas fa-envelope"></i>
                </button>
                <button class="header-btn" onclick="toggleLang()">
                    <i class="fas fa-globe"></i>
                </button>
            </div>
        </header>
        
        <div class="content">
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Users</span>
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($userStats['total_users'] ?? 0); ?></div>
                    <div class="stat-change">+<?php echo $userStats['today_users'] ?? 0; ?> today</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Revenue</span>
                        <div class="stat-icon green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">$<?php echo number_format($orderStats['total_revenue'] ?? 0, 2); ?></div>
                    <div class="stat-change">+$<?php echo number_format($orderStats['today_revenue'] ?? 0, 2); ?> today</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Active Courses</span>
                        <div class="stat-icon orange">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($courseStats['active_courses'] ?? 0); ?></div>
                    <div class="stat-change"><?php echo number_format($courseStats['total_enrollments'] ?? 0); ?> enrollments</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Today's Orders</span>
                        <div class="stat-icon purple">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($orderStats['today_orders'] ?? 0); ?></div>
                    <div class="stat-change">of <?php echo number_format($orderStats['total_orders'] ?? 0); ?> total</div>
                </div>
            </div>
            
            <!-- 内容网格 -->
            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Logins</h3>
                        <a href="/admin/users.php" style="font-size: 14px; color: var(--primary-light);">View All</a>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Login Time</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogins as $login): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <span><?php echo htmlspecialchars($login['nickname'] ?: $login['email']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge <?php echo $login['role'] === 'platform_admin' ? 'admin' : ($login['role'] === 'institution_admin' ? 'institution' : 'student'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $login['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, H:i', strtotime($login['last_login_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($login['last_login_ip']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <a href="/admin/users.php?action=create" class="btn-action" style="padding: 12px; background: var(--gray-100); border-radius: 8px; text-decoration: none; color: var(--gray-700); display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-user-plus" style="color: var(--primary-light);"></i>
                                <span>Add New User</span>
                            </a>
                            <a href="/admin/courses.php?action=create" class="btn-action" style="padding: 12px; background: var(--gray-100); border-radius: 8px; text-decoration: none; color: var(--gray-700); display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-plus-circle" style="color: var(--success);"></i>
                                <span>Create Course</span>
                            </a>
                            <a href="/admin/questions.php?action=import" class="btn-action" style="padding: 12px; background: var(--gray-100); border-radius: 8px; text-decoration: none; color: var(--gray-700); display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-file-import" style="color: var(--accent-orange);"></i>
                                <span>Import Questions</span>
                            </a>
                            <a href="/admin/settings.php" class="btn-action" style="padding: 12px; background: var(--gray-100); border-radius: 8px; text-decoration: none; color: var(--gray-700); display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-cog" style="color: var(--gray-500);"></i>
                                <span>System Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        function toggleLang() {
            const currentLang = '<?php echo $currentLang; ?>';
            const newLang = currentLang === 'zh_CN' ? 'en_US' : 'zh_CN';
            window.location.href = '?lang=' + newLang;
        }
    </script>
</body>
</html>
