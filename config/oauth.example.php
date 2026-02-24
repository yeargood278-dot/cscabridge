<?php
/**
 * CSCA Bridge - OAuth 配置示例
 * 
 * 使用说明：
 * 1. 复制此文件为 oauth.php
 * 2. 填入您的实际配置
 * 3. 确保回调地址正确配置
 * 
 * 国际用户推荐使用 Google / Facebook / Twitter
 * 微信需要中国大陆企业资质认证
 */

return [
    // =====================================================
    // Google OAuth 配置 (推荐，配置最简单)
    // =====================================================
    // 申请地址: https://console.cloud.google.com/apis/credentials
    // 需要启用 API: Google+ API, Google People API
    'google' => [
        'enabled' => true,
        'client_id' => 'your-google-client-id.apps.googleusercontent.com',
        'client_secret' => 'your-google-client-secret',
        'redirect_uri' => 'https://yourdomain.com/api/auth/google.php',
        'scopes' => ['openid', 'email', 'profile'],
    ],
    
    // =====================================================
    // 微信OAuth配置 (需要中国大陆企业资质认证)
    // =====================================================
    // 申请地址: https://open.weixin.qq.com/
    // 注意: 个人开发者无法申请，需要企业资质
    'wechat' => [
        'enabled' => false, // 默认禁用，需要企业资质
        'app_id' => 'your-wechat-app-id',
        'app_secret' => 'your-wechat-app-secret',
        'redirect_uri' => 'https://yourdomain.com/api/auth/wechat.php',
        'scopes' => 'snsapi_login',
    ],
    
    // =====================================================
    // Facebook OAuth 配置
    // =====================================================
    // 申请地址: https://developers.facebook.com/apps/
    // 需要: 应用编号(App ID) 和 应用密钥(App Secret)
    'facebook' => [
        'enabled' => true,
        'app_id' => 'your-facebook-app-id',
        'app_secret' => 'your-facebook-app-secret',
        'redirect_uri' => 'https://yourdomain.com/api/auth/facebook.php',
        'scopes' => ['email', 'public_profile'],
    ],
    
    // =====================================================
    // Twitter OAuth 2.0 配置
    // =====================================================
    // 申请地址: https://developer.twitter.com/en/portal/projects-and-apps
    // 注意: Twitter 使用 OAuth 2.0，不是 OAuth 1.0a
    'twitter' => [
        'enabled' => true,
        'client_id' => 'your-twitter-client-id',
        'client_secret' => 'your-twitter-client-secret',
        'redirect_uri' => 'https://yourdomain.com/api/auth/twitter.php',
        'scopes' => ['tweet.read', 'users.read'],
    ],
];
