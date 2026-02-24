<?php
/**
 * CSCA Bridge - 短信服务配置示例（国际版）
 * 
 * 支持服务商:
 * - 阿里云短信: https://www.aliyun.com/product/sms (中国大陆)
 * - 腾讯云短信: https://cloud.tencent.com/product/sms (中国大陆)
 * - Twilio: https://www.twilio.com/ (国际推荐)
 * - Nexmo (Vonage): https://www.vonage.com/ (国际)
 * 
 * 国际用户推荐使用 Twilio 或 Nexmo
 */

return [
    // 默认短信服务商
    'provider' => 'twilio', // aliyun, tencent, twilio, nexmo
    
    // =====================================================
    // 阿里云短信配置 (中国大陆)
    // =====================================================
    'aliyun' => [
        'access_key_id' => 'your-access-key-id',
        'access_key_secret' => 'your-access-key-secret',
        'sign_name' => 'CSCABridge', // 短信签名，需在阿里云审核通过
        'template_code' => 'SMS_xxxxxx', // 验证码模板CODE
        // 模板内容示例: 您的验证码是${code}，5分钟内有效。
    ],
    
    // =====================================================
    // 腾讯云短信配置 (中国大陆)
    // =====================================================
    'tencent' => [
        'secret_id' => 'your-secret-id',
        'secret_key' => 'your-secret-key',
        'sms_sdk_app_id' => 'your-sdk-app-id',
        'sign_name' => 'CSCABridge',
        'template_id' => 'your-template-id',
    ],
    
    // =====================================================
    // Twilio配置 (国际短信 - 推荐)
    // =====================================================
    // 注册: https://www.twilio.com/try-twilio
    // 控制台: https://console.twilio.com/
    'twilio' => [
        'account_sid' => 'your-account-sid', // 从控制台获取
        'auth_token' => 'your-auth-token',   // 从控制台获取
        'phone_number' => '+1234567890',     // 你的Twilio号码
    ],
    
    // =====================================================
    // Nexmo (Vonage) 配置 (国际短信)
    // =====================================================
    // 注册: https://dashboard.nexmo.com/sign-up
    'nexmo' => [
        'api_key' => 'your-api-key',
        'api_secret' => 'your-api-secret',
        'from_name' => 'CSCABridge', // 发送方名称
    ],
    
    // =====================================================
    // 验证码设置
    // =====================================================
    'code' => [
        'length' => 6,           // 验证码长度
        'expires' => 300,        // 过期时间（秒）
        'interval' => 60,        // 发送间隔（秒）
        'max_daily' => 10,       // 每日最大发送次数
    ],
];
