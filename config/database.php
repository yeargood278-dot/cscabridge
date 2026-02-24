<?php
/**
 * CSCA Bridge - 数据库配置文件
 * 网站: cscabridge.com
 * 环境: WAMP/LAMP (Apache + PHP 8.2 + MySQL 8.0)
 */

return [
    // 默认数据库连接
    'default' => 'mysql',
    
    // 数据库连接配置
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'cscabridge',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            // 连接选项
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ],
        ],
        
        // 只读从库配置（用于读写分离）
        'mysql_read' => [
            'driver' => 'mysql',
            'host' => getenv('DB_READ_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'cscabridge',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
    ],
    
    // Redis缓存配置
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'password' => getenv('REDIS_PASS') ?: null,
        'database' => 0,
    ],
];
