<?php
/**
 * CSCA Bridge - 网站入口文件
 * 网站: cscabridge.com
 * 功能: 路由分发、初始化、语言切换
 */

// 定义根目录常量
define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('LANG_PATH', ROOT_PATH . '/lang');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// 加载自动加载器
require_once INCLUDES_PATH . '/autoload.php';

// 加载配置
$config = require CONFIG_PATH . '/app.php';

// 设置时区
date_default_timezone_set($config['timezone']);

// 设置错误报告
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// 启动会话
session_name($config['session']['cookie']);
session_start();

// 语言切换处理
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? $config['locale'];
if (!in_array($lang, $config['available_locales'])) {
    $lang = $config['locale'];
}
$_SESSION['lang'] = $lang;

// 加载语言包
$translations = require LANG_PATH . '/' . $lang . '/common.php';

// 简单的路由处理
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// 路由映射
$routes = [
    '' => 'home',
    'home' => 'home',
    'login' => 'login',
    'register' => 'register',
    'courses' => 'courses',
    'practice' => 'practice',
    'exam' => 'exam',
    'universities' => 'universities',
    'about' => 'about',
    'contact' => 'contact',
];

// 获取路由
$route = $routes[$uri] ?? 'home';

// 根据路由加载对应页面
switch ($route) {
    case 'home':
        // 显示首页HTML
        include ROOT_PATH . '/templates/home.php';
        break;
    case 'login':
        include ROOT_PATH . '/student/views/auth/login.php';
        break;
    case 'register':
        include ROOT_PATH . '/student/views/auth/register.php';
        break;
    default:
        // 404页面
        http_response_code(404);
        include ROOT_PATH . '/templates/404.php';
}
