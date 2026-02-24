-- =====================================================
-- CSCA Bridge - OAuth 和权限管理数据库更新
-- 添加OAuth支持和用户权限控制
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. 修改用户表支持OAuth
-- =====================================================

-- 添加OAuth相关字段到users表
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `auth_provider` ENUM('email', 'google', 'wechat', 'facebook', 'twitter') 
    NOT NULL DEFAULT 'email' COMMENT '认证方式' AFTER `password_hash`,
ADD COLUMN IF NOT EXISTS `auth_provider_id` VARCHAR(255) 
    DEFAULT NULL COMMENT '第三方认证ID' AFTER `auth_provider`,
ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(255) 
    DEFAULT NULL COMMENT '记住我令牌' AFTER `auth_provider_id`,
ADD COLUMN IF NOT EXISTS `email_verify_code` VARCHAR(10) 
    DEFAULT NULL COMMENT '邮箱验证码' AFTER `email_verified_at`,
ADD COLUMN IF NOT EXISTS `email_verify_expires` TIMESTAMP 
    NULL DEFAULT NULL COMMENT '验证码过期时间' AFTER `email_verify_code`,
ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(255) 
    DEFAULT NULL COMMENT '密码重置令牌' AFTER `email_verify_expires`,
ADD COLUMN IF NOT EXISTS `password_reset_expires` TIMESTAMP 
    NULL DEFAULT NULL COMMENT '重置令牌过期时间' AFTER `password_reset_token`,
ADD COLUMN IF NOT EXISTS `login_attempts` INT UNSIGNED 
    NOT NULL DEFAULT 0 COMMENT '登录失败次数' AFTER `password_reset_expires`,
ADD COLUMN IF NOT EXISTS `locked_until` TIMESTAMP 
    NULL DEFAULT NULL COMMENT '账户锁定截止时间' AFTER `login_attempts`;

-- 添加索引
CREATE INDEX IF NOT EXISTS `idx_users_auth_provider` ON `users` (`auth_provider`);
CREATE INDEX IF NOT EXISTS `idx_users_auth_provider_id` ON `users` (`auth_provider`, `auth_provider_id`);
CREATE INDEX IF NOT EXISTS `idx_users_remember_token` ON `users` (`remember_token`);

-- =====================================================
-- 2. 创建用户权限表
-- =====================================================

-- 权限表
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '权限ID',
    `name` VARCHAR(100) NOT NULL COMMENT '权限标识',
    `display_name` VARCHAR(200) NOT NULL COMMENT '显示名称',
    `description` TEXT COMMENT '权限描述',
    `module` VARCHAR(50) NOT NULL COMMENT '所属模块',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permissions_name` (`name`),
    KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限表';

-- 角色表
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
    `name` VARCHAR(50) NOT NULL COMMENT '角色标识',
    `display_name` VARCHAR(100) NOT NULL COMMENT '显示名称',
    `description` TEXT COMMENT '角色描述',
    `is_system` TINYINT NOT NULL DEFAULT 0 COMMENT '是否系统角色',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- 角色权限关联表
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `permission_id` INT UNSIGNED NOT NULL COMMENT '权限ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_role_permissions` (`role_id`, `permission_id`),
    KEY `idx_rp_permission_id` (`permission_id`),
    CONSTRAINT `fk_rp_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

-- 用户角色关联表
CREATE TABLE IF NOT EXISTS `user_roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_roles` (`user_id`, `role_id`),
    KEY `idx_ur_role_id` (`role_id`),
    CONSTRAINT `fk_ur_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ur_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';

-- =====================================================
-- 3. 创建用户订阅/付费表
-- =====================================================

-- 用户订阅表
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '订阅ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `plan_type` ENUM('free', 'basic', 'premium', 'vip') NOT NULL DEFAULT 'free' COMMENT '订阅类型',
    `status` ENUM('active', 'expired', 'cancelled', 'pending') NOT NULL DEFAULT 'active' COMMENT '状态',
    `starts_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '开始时间',
    `expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT '过期时间',
    `payment_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联支付ID',
    `auto_renew` TINYINT NOT NULL DEFAULT 0 COMMENT '是否自动续费',
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL COMMENT '取消时间',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_subscriptions_user_id` (`user_id`),
    KEY `idx_subscriptions_status` (`status`),
    KEY `idx_subscriptions_expires` (`expires_at`),
    CONSTRAINT `fk_subscriptions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户订阅表';

-- 用户资源访问权限表
CREATE TABLE IF NOT EXISTS `user_resource_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `resource_type` ENUM('video', 'document', 'course', 'assessment') NOT NULL COMMENT '资源类型',
    `resource_id` BIGINT UNSIGNED NOT NULL COMMENT '资源ID',
    `permission_type` ENUM('view', 'download', 'stream') NOT NULL DEFAULT 'view' COMMENT '权限类型',
    `granted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '授权时间',
    `expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT '过期时间',
    `granted_by` ENUM('subscription', 'purchase', 'admin', 'promotion') NOT NULL COMMENT '授权方式',
    `order_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联订单ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_resource_permissions` (`user_id`, `resource_type`, `resource_id`, `permission_type`),
    KEY `idx_urp_resource` (`resource_type`, `resource_id`),
    KEY `idx_urp_expires` (`expires_at`),
    CONSTRAINT `fk_urp_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户资源访问权限表';

-- =====================================================
-- 4. 初始化角色和权限数据
-- =====================================================

-- 插入系统角色
INSERT INTO `roles` (`name`, `display_name`, `description`, `is_system`) VALUES
('student', '学员', '普通学员用户', 1),
('premium_student', '付费学员', '付费订阅学员', 1),
('institution_admin', '机构管理员', '培训机构管理员', 1),
('platform_admin', '平台管理员', '平台超级管理员', 1),
('content_manager', '内容管理员', '管理课程和题库内容', 0),
('finance_manager', '财务管理员', '管理订单和财务', 0)
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- 插入权限列表
INSERT INTO `permissions` (`name`, `display_name`, `description`, `module`) VALUES
-- 用户管理权限
('user.view', '查看用户', '查看用户信息', 'user'),
('user.create', '创建用户', '创建新用户', 'user'),
('user.edit', '编辑用户', '编辑用户信息', 'user'),
('user.delete', '删除用户', '删除用户', 'user'),
('user.manage_roles', '管理角色', '分配用户角色', 'user'),

-- 课程管理权限
('course.view', '查看课程', '查看课程列表和详情', 'course'),
('course.create', '创建课程', '创建新课程', 'course'),
('course.edit', '编辑课程', '编辑课程内容', 'course'),
('course.delete', '删除课程', '删除课程', 'course'),
('course.publish', '发布课程', '发布/下架课程', 'course'),

-- 视频管理权限
('video.view', '查看视频', '观看视频', 'video'),
('video.upload', '上传视频', '上传视频文件', 'video'),
('video.edit', '编辑视频', '编辑视频信息', 'video'),
('video.delete', '删除视频', '删除视频', 'video'),
('video.download', '下载视频', '下载视频文件', 'video'),
('video.stream', '流媒体播放', '播放视频流', 'video'),

-- 题库管理权限
('question.view', '查看题目', '查看题目列表', 'question'),
('question.create', '创建题目', '创建新题目', 'question'),
('question.edit', '编辑题目', '编辑题目内容', 'question'),
('question.delete', '删除题目', '删除题目', 'question'),
('question.import', '导入题目', '批量导入题目', 'question'),

-- 考试管理权限
('exam.view', '查看考试', '查看考试记录', 'exam'),
('exam.create', '创建考试', '创建新考试', 'exam'),
('exam.manage', '管理考试', '管理考试配置', 'exam'),
('exam.grade', '批改试卷', '批改考试试卷', 'exam'),

-- 订单管理权限
('order.view', '查看订单', '查看订单列表', 'order'),
('order.manage', '管理订单', '管理订单状态', 'order'),
('order.refund', '处理退款', '处理退款申请', 'order'),

-- 财务管理权限
('finance.view', '查看财务', '查看财务数据', 'finance'),
('finance.settlements', '结算管理', '管理机构结算', 'finance'),
('finance.statistics', '财务统计', '查看财务报表', 'finance'),

-- 系统管理权限
('system.settings', '系统设置', '管理系统配置', 'system'),
('system.logs', '查看日志', '查看系统日志', 'system'),
('system.backup', '数据备份', '备份和恢复数据', 'system')
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- 为角色分配权限
-- 平台管理员拥有所有权限
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE name = 'platform_admin'),
    id 
FROM permissions
ON DUPLICATE KEY UPDATE role_id = role_id;

-- 付费学员权限
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE name = 'premium_student'),
    id 
FROM permissions 
WHERE name IN ('course.view', 'video.view', 'video.stream', 'video.download', 'question.view', 'exam.view')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- 普通学员权限
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE name = 'student'),
    id 
FROM permissions 
WHERE name IN ('course.view', 'video.view', 'video.stream', 'question.view', 'exam.view')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- =====================================================
-- 5. 创建安全审计日志表
-- =====================================================

CREATE TABLE IF NOT EXISTS `security_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '用户ID',
    `action` VARCHAR(100) NOT NULL COMMENT '操作类型',
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT '用户代理',
    `details` JSON DEFAULT NULL COMMENT '详细信息',
    `risk_level` ENUM('low', 'medium', 'high') DEFAULT 'low' COMMENT '风险等级',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_security_logs_user_id` (`user_id`),
    KEY `idx_security_logs_action` (`action`),
    KEY `idx_security_logs_ip` (`ip_address`),
    KEY `idx_security_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='安全审计日志表';

-- =====================================================
-- 6. 创建API访问令牌表（用于移动端和第三方接入）
-- =====================================================

CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '令牌ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `token` VARCHAR(255) NOT NULL COMMENT '访问令牌',
    `refresh_token` VARCHAR(255) DEFAULT NULL COMMENT '刷新令牌',
    `scopes` JSON DEFAULT NULL COMMENT '权限范围',
    `expires_at` TIMESTAMP NOT NULL COMMENT '过期时间',
    `last_used_at` TIMESTAMP NULL DEFAULT NULL COMMENT '最后使用时间',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_api_tokens_token` (`token`),
    KEY `idx_api_tokens_user_id` (`user_id`),
    KEY `idx_api_tokens_expires` (`expires_at`),
    CONSTRAINT `fk_api_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API访问令牌表';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 数据库更新完成
-- =====================================================
