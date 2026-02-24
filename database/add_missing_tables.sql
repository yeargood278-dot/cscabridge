-- =====================================================
-- CSCA Bridge - 添加缺失的表
-- 用于注册和登录功能
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. 角色表
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
    `name` VARCHAR(50) NOT NULL COMMENT '角色标识名',
    `display_name` VARCHAR(100) NOT NULL COMMENT '角色显示名称',
    `description` TEXT COMMENT '角色描述',
    `permissions` JSON DEFAULT NULL COMMENT '权限列表（JSON格式）',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- 插入默认角色
INSERT INTO `roles` (`name`, `display_name`, `description`) VALUES
('student', '学员', '普通学员用户'),
('institution_admin', '机构管理员', '机构管理员'),
('platform_admin', '平台管理员', '平台超级管理员')
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- =====================================================
-- 2. 用户角色关联表
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_roles` (`user_id`, `role_id`),
    KEY `idx_user_roles_role_id` (`role_id`),
    CONSTRAINT `fk_user_roles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';

-- =====================================================
-- 3. 用户订阅表
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '订阅ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `plan_type` ENUM('free', 'basic', 'premium', 'enterprise') NOT NULL DEFAULT 'free' COMMENT '套餐类型',
    `status` ENUM('active', 'expired', 'cancelled', 'pending') NOT NULL DEFAULT 'active' COMMENT '订阅状态',
    `starts_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '开始时间',
    `expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT '过期时间',
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL COMMENT '取消时间',
    `payment_method` VARCHAR(50) DEFAULT NULL COMMENT '支付方式',
    `transaction_id` VARCHAR(255) DEFAULT NULL COMMENT '交易ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_subscriptions_user_id` (`user_id`),
    KEY `idx_user_subscriptions_status` (`status`),
    KEY `idx_user_subscriptions_plan_type` (`plan_type`),
    CONSTRAINT `fk_user_subscriptions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户订阅表';

-- =====================================================
-- 4. 修改users表添加必要字段
-- =====================================================
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `login_attempts` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录尝试次数' AFTER `last_login_ip`,
ADD COLUMN IF NOT EXISTS `locked_until` TIMESTAMP NULL DEFAULT NULL COMMENT '锁定截止时间' AFTER `login_attempts`,
ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(255) DEFAULT NULL COMMENT '记住登录令牌' AFTER `locked_until`;

-- =====================================================
-- 5. 修改auth_provider枚举值支持更多方式
-- =====================================================
ALTER TABLE `users` 
MODIFY COLUMN `auth_provider` ENUM('email', 'phone', 'google', 'wechat', 'facebook', 'twitter') NOT NULL DEFAULT 'email' COMMENT '认证方式';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 使用说明
-- =====================================================
-- 执行此SQL文件创建缺失的表：
-- mysql -u root -p cscabridge < add_missing_tables.sql
-- =====================================================
