-- =====================================================
-- CSCA Bridge - 添加手机号支持（国际格式）
-- 手机号格式: +国家代码手机号（如 +8613800138000）
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. 修改users表支持国际手机号
-- =====================================================

-- 添加手机号字段（支持国际格式）
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20) DEFAULT NULL COMMENT '国际手机号，格式: +国家代码手机号' AFTER `email`,
ADD COLUMN IF NOT EXISTS `last_login_method` VARCHAR(20) DEFAULT NULL COMMENT '最后登录方式: email, phone, google, wechat, facebook, twitter' AFTER `last_login_ip`;

-- 添加手机号索引
CREATE INDEX IF NOT EXISTS `idx_users_phone` ON `users` (`phone`);

-- =====================================================
-- 2. 修改user_profiles表
-- =====================================================

ALTER TABLE `user_profiles` 
ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20) DEFAULT NULL COMMENT '国际手机号' AFTER `user_id`;

-- =====================================================
-- 3. 添加系统配置
-- =====================================================

INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
-- OAuth配置
('oauth_google_enabled', '0', 'boolean', '启用Google登录'),
('oauth_wechat_enabled', '0', 'boolean', '启用微信登录（需企业资质）'),
('oauth_facebook_enabled', '0', 'boolean', '启用Facebook登录'),
('oauth_twitter_enabled', '0', 'boolean', '启用Twitter登录'),

-- 短信服务配置
('sms_provider', '', 'string', '短信服务商: aliyun, tencent, twilio, nexmo'),
('sms_enabled', '0', 'boolean', '启用短信验证码'),

-- 注册配置
('register_email_enabled', '1', 'boolean', '启用邮箱注册'),
('register_phone_enabled', '1', 'boolean', '启用手机号注册'),
('register_oauth_enabled', '1', 'boolean', '启用OAuth注册'),

-- 安全配置
('login_max_attempts', '5', 'number', '登录最大尝试次数'),
('login_lock_duration', '15', 'number', '登录锁定时间（分钟）'),
('verify_code_expires', '300', 'number', '验证码过期时间（秒）')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 使用说明
-- =====================================================
-- 国际手机号格式示例：
-- 中国: +8613800138000
-- 美国: +14155552671
-- 英国: +447700900123
-- 日本: +819012345678
-- =====================================================
