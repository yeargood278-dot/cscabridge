<?php
/**
 * CSCA Bridge - 首页模板
 * 加载静态HTML首页
 */

// 获取当前语言
$currentLang = getCurrentLang();
$langPrefix = $currentLang === 'en_US' ? 'en' : 'zh';

// 读取首页HTML文件
$homeFile = ROOT_PATH . '/index.html';

if (file_exists($homeFile)) {
    // 读取并输出首页内容
    $content = file_get_contents($homeFile);
    
    // 根据语言设置调整页面
    if ($currentLang === 'en_US') {
        // 设置英文为默认显示
        $content = str_replace('<html lang="zh-CN">', '<html lang="en">', $content);
    }
    
    echo $content;
} else {
    // 如果首页文件不存在，显示错误
    echo '<h1>Homepage not found</h1>';
}
