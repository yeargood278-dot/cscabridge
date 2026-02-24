<?php
/**
 * CSCA Bridge - 支付配置文件
 * 支持Stripe国际支付
 */

return [
    // 默认支付渠道
    'default' => 'stripe',
    
    // Stripe配置
    'stripe' => [
        'enabled' => true,
        'test_mode' => getenv('STRIPE_TEST_MODE') === 'true' ? true : false,
        
        // API密钥
        'public_key' => getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_your_public_key',
        'secret_key' => getenv('STRIPE_SECRET_KEY') ?: 'sk_test_your_secret_key',
        'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_your_webhook_secret',
        
        // 支付设置
        'currency' => 'usd', // 默认货币
        'supported_currencies' => ['usd', 'cny', 'eur', 'gbp', 'jpy', 'aud', 'cad'],
        
        // 支付方式
        'payment_methods' => [
            'card' => true,
            'alipay' => false, // P1阶段支持
            'wechat_pay' => false, // P1阶段支持
        ],
        
        // 结账页面设置
        'checkout' => [
            'success_url' => '/payment/success',
            'cancel_url' => '/payment/cancel',
            'allow_promotion_codes' => true,
            'collect_tax_id' => true,
        ],
        
        // Webhook事件
        'webhook_events' => [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'charge.refunded',
            'checkout.session.completed',
        ],
    ],
    
    // 货币设置
    'currencies' => [
        'usd' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimals' => 2,
        ],
        'cny' => [
            'name' => 'Chinese Yuan',
            'symbol' => '¥',
            'decimals' => 2,
        ],
        'eur' => [
            'name' => 'Euro',
            'symbol' => '€',
            'decimals' => 2,
        ],
    ],
    
    // 订单设置
    'order' => [
        'prefix' => 'CSCA',
        'expire_minutes' => 30, // 订单过期时间
        'auto_cancel' => true,
    ],
    
    // 退款设置
    'refund' => [
        'allow_partial' => true,
        'max_days' => 30, // 最大退款天数
        'auto_approve_amount' => 50.00, // 自动批准金额阈值
    ],
    
    // 分账设置
    'revenue_share' => [
        'platform_rate' => 30, // 平台分成比例(%)
        'institution_rate' => 70, // 机构分成比例(%)
    ],
];
