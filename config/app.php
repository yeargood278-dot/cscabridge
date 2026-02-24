<?php
/**
 * CSCA Bridge - 应用基础配置文件
 * 网站: cscabridge.com
 */

return [
    // 应用基本信息
    'name' => 'CSCA Bridge',
    'name_en' => 'CSCA Bridge',
    'name_zh' => 'CSCA来华留学备考平台',
    'version' => '1.0.0',
    'env' => getenv('APP_ENV') ?: 'production', // development, production
    
    // 网站URL
    'url' => getenv('APP_URL') ?: 'https://cscabridge.com',
    'url_cn' => 'https://cscabridge.com',
    'url_en' => 'https://cscabridge.com/en',
    
    // 时区设置
    'timezone' => 'Asia/Shanghai',
    
    // 语言设置
    'locale' => 'zh_CN',
    'fallback_locale' => 'en_US',
    'available_locales' => ['zh_CN', 'en_US'],
    
    // 调试模式
    'debug' => getenv('APP_DEBUG') === 'true' ? true : false,
    
    // 日志配置
    'log' => [
        'enabled' => true,
        'level' => 'debug', // debug, info, warning, error
        'path' => __DIR__ . '/../logs/',
        'max_files' => 30,
    ],
    
    // 会话配置
    'session' => [
        'driver' => 'file', // file, redis, database
        'lifetime' => 120, // 分钟
        'expire_on_close' => false,
        'encrypt' => true,
        'files' => __DIR__ . '/../cache/session/',
        'connection' => null,
        'table' => 'sessions',
        'lottery' => [2, 100],
        'cookie' => 'cscabridge_session',
        'path' => '/',
        'domain' => null,
        'secure' => true,
        'http_only' => true,
        'same_site' => 'lax',
    ],
    
    // 缓存配置
    'cache' => [
        'default' => 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../cache/data/',
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
            ],
        ],
        'prefix' => 'cscabridge_',
    ],
    
    // 分页配置
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],
    
    // 上传配置
    'upload' => [
        'max_size' => 104857600, // 100MB
        'allowed_extensions' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
        ],
        'path' => __DIR__ . '/../uploads/',
    ],
    
    // 安全配置
    'security' => [
        'csrf_protection' => true,
        'xss_protection' => true,
        'clickjacking_protection' => true,
        'content_security_policy' => true,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15分钟
    ],
];
