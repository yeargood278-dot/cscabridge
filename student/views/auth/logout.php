<?php
/**
 * CSCA Bridge - 用户登出
 */

session_start();

require_once __DIR__ . '/../../../includes/Auth.php';

// 执行登出
Auth::logout();

// 重定向到首页
header('Location: /');
exit;
