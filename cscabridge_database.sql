-- =====================================================
-- CSCA在线学习与考试平台数据库设计
-- 网站域名: cscabridge.com
-- 数据库版本: MySQL 8.0
-- 字符集: UTF-8mb4
-- 创建日期: 2024年
-- =====================================================

-- 设置数据库字符集
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. 用户系统表
-- =====================================================

-- 用户主表
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
    `email` VARCHAR(255) NOT NULL COMMENT '邮箱地址',
    `password_hash` VARCHAR(255) DEFAULT NULL COMMENT '密码哈希（OAuth用户可为空）',
    `auth_provider` ENUM('email', 'google') NOT NULL DEFAULT 'email' COMMENT '认证方式',
    `auth_provider_id` VARCHAR(255) DEFAULT NULL COMMENT '第三方认证ID',
    `role` ENUM('student', 'institution_admin', 'platform_admin') NOT NULL DEFAULT 'student' COMMENT '用户角色',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-正常，2-待验证',
    `institution_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '所属机构ID',
    `invitation_code` VARCHAR(32) DEFAULT NULL COMMENT '绑定的邀请码',
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT '邮箱验证时间',
    `last_login_at` TIMESTAMP NULL DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(45) DEFAULT NULL COMMENT '最后登录IP',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    UNIQUE KEY `uk_users_auth_provider` (`auth_provider`, `auth_provider_id`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_status` (`status`),
    KEY `idx_users_institution` (`institution_id`),
    KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户主表';

-- 用户扩展信息表
CREATE TABLE `user_profiles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
    `real_name` VARCHAR(50) DEFAULT NULL COMMENT '真实姓名',
    `avatar_url` VARCHAR(500) DEFAULT NULL COMMENT '头像URL',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
    `gender` TINYINT DEFAULT NULL COMMENT '性别：0-保密，1-男，2-女',
    `birth_date` DATE DEFAULT NULL COMMENT '出生日期',
    `country` VARCHAR(50) DEFAULT NULL COMMENT '国家',
    `city` VARCHAR(100) DEFAULT NULL COMMENT '城市',
    `school_name` VARCHAR(200) DEFAULT NULL COMMENT '学校名称',
    `grade` VARCHAR(20) DEFAULT NULL COMMENT '年级',
    `target_university` VARCHAR(200) DEFAULT NULL COMMENT '目标大学',
    `target_major` VARCHAR(100) DEFAULT NULL COMMENT '目标专业',
    `bio` TEXT COMMENT '个人简介',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_profiles_user_id` (`user_id`),
    KEY `idx_user_profiles_phone` (`phone`),
    KEY `idx_user_profiles_country` (`country`),
    CONSTRAINT `fk_user_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户扩展信息表';

-- 用户会话表
CREATE TABLE `sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '会话ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `session_token` VARCHAR(255) NOT NULL COMMENT '会话令牌',
    `refresh_token` VARCHAR(255) DEFAULT NULL COMMENT '刷新令牌',
    `device_type` ENUM('web', 'ios', 'android', 'other') DEFAULT 'web' COMMENT '设备类型',
    `device_info` VARCHAR(500) DEFAULT NULL COMMENT '设备信息',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
    `expires_at` TIMESTAMP NOT NULL COMMENT '过期时间',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `last_activity_at` TIMESTAMP NULL DEFAULT NULL COMMENT '最后活动时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sessions_token` (`session_token`),
    KEY `idx_sessions_user_id` (`user_id`),
    KEY `idx_sessions_expires_at` (`expires_at`),
    CONSTRAINT `fk_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户会话表';

-- =====================================================
-- 2. 机构系统表
-- =====================================================

-- 机构表
CREATE TABLE `institutions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '机构ID',
    `name` VARCHAR(200) NOT NULL COMMENT '机构名称',
    `name_en` VARCHAR(200) DEFAULT NULL COMMENT '机构英文名称',
    `type` ENUM('large', 'medium', 'small', 'studio') NOT NULL COMMENT '机构类型：大型/中型/小型/工作室',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态：0-待审核，1-已通过，2-已拒绝，3-已停用',
    `logo_url` VARCHAR(500) DEFAULT NULL COMMENT '机构Logo',
    `description` TEXT COMMENT '机构简介',
    `website` VARCHAR(255) DEFAULT NULL COMMENT '官方网站',
    `country` VARCHAR(50) DEFAULT NULL COMMENT '所在国家',
    `city` VARCHAR(100) DEFAULT NULL COMMENT '所在城市',
    `address` VARCHAR(500) DEFAULT NULL COMMENT '详细地址',
    `contact_name` VARCHAR(50) DEFAULT NULL COMMENT '联系人姓名',
    `contact_phone` VARCHAR(20) DEFAULT NULL COMMENT '联系人电话',
    `contact_email` VARCHAR(255) DEFAULT NULL COMMENT '联系人邮箱',
    `business_license` VARCHAR(500) DEFAULT NULL COMMENT '营业执照URL',
    `invitation_code` VARCHAR(32) NOT NULL COMMENT '机构邀请码',
    `revenue_share_rate` DECIMAL(5,2) NOT NULL DEFAULT 70.00 COMMENT '分账比例（百分比，机构获得）',
    `total_students` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '学员总数',
    `total_revenue` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '总营收金额',
    `settled_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '已结算金额',
    `pending_settlement` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '待结算金额',
    `admin_user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '机构管理员用户ID',
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL COMMENT '审核时间',
    `reviewed_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '审核人ID',
    `review_remark` TEXT COMMENT '审核备注',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_institutions_invitation_code` (`invitation_code`),
    UNIQUE KEY `uk_institutions_name` (`name`),
    KEY `idx_institutions_type` (`type`),
    KEY `idx_institutions_status` (`status`),
    KEY `idx_institutions_country` (`country`),
    KEY `idx_institutions_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='机构表';

-- 机构管理员关联表
CREATE TABLE `institution_admins` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `institution_id` BIGINT UNSIGNED NOT NULL COMMENT '机构ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `role` ENUM('owner', 'admin', 'teacher') NOT NULL DEFAULT 'admin' COMMENT '机构内角色',
    `permissions` JSON DEFAULT NULL COMMENT '权限配置（JSON格式）',
    `is_primary` TINYINT NOT NULL DEFAULT 0 COMMENT '是否主管理员：0-否，1-是',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-正常',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_institution_admins` (`institution_id`, `user_id`),
    KEY `idx_institution_admins_user` (`user_id`),
    KEY `idx_institution_admins_role` (`role`),
    CONSTRAINT `fk_ia_institution_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ia_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='机构管理员关联表';

-- =====================================================
-- 3. 题库系统表
-- =====================================================

-- 科目表
CREATE TABLE `subjects` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '科目ID',
    `code` VARCHAR(20) NOT NULL COMMENT '科目代码',
    `name` VARCHAR(50) NOT NULL COMMENT '科目名称',
    `name_en` VARCHAR(50) DEFAULT NULL COMMENT '科目英文名称',
    `description` TEXT COMMENT '科目描述',
    `icon_url` VARCHAR(500) DEFAULT NULL COMMENT '图标URL',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序顺序',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_subjects_code` (`code`),
    KEY `idx_subjects_status` (`status`),
    KEY `idx_subjects_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='科目表';

-- 知识点表
CREATE TABLE `knowledge_points` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '知识点ID',
    `subject_id` INT UNSIGNED NOT NULL COMMENT '所属科目ID',
    `parent_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '父知识点ID（支持多级）',
    `name` VARCHAR(100) NOT NULL COMMENT '知识点名称',
    `description` TEXT COMMENT '知识点描述',
    `difficulty_level` TINYINT DEFAULT NULL COMMENT '难度等级：1-5',
    `importance_level` TINYINT DEFAULT NULL COMMENT '重要程度：1-5',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序顺序',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_kp_subject_id` (`subject_id`),
    KEY `idx_kp_parent_id` (`parent_id`),
    KEY `idx_kp_difficulty` (`difficulty_level`),
    KEY `idx_kp_importance` (`importance_level`),
    CONSTRAINT `fk_kp_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kp_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `knowledge_points` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识点表';

-- 题目表
CREATE TABLE `questions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '题目ID',
    `subject_id` INT UNSIGNED NOT NULL COMMENT '所属科目ID',
    `type` ENUM('single_choice', 'multiple_choice', 'fill_blank', 'essay') NOT NULL COMMENT '题型',
    `difficulty_level` TINYINT NOT NULL DEFAULT 3 COMMENT '难度等级：1-5',
    `content` TEXT NOT NULL COMMENT '题目内容',
    `content_html` TEXT COMMENT '题目内容（HTML格式）',
    `analysis` TEXT COMMENT '题目解析',
    `analysis_html` TEXT COMMENT '题目解析（HTML格式）',
    `answer` TEXT COMMENT '参考答案（问答题使用）',
    `score` DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT '题目分值',
    `knowledge_point_ids` JSON COMMENT '关联知识点ID数组',
    `tags` JSON COMMENT '标签数组',
    `source` VARCHAR(200) DEFAULT NULL COMMENT '题目来源',
    `usage_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数',
    `correct_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '正确次数',
    `wrong_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '错误次数',
    `correct_rate` DECIMAL(5,2) DEFAULT NULL COMMENT '正确率',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用，2-待审核',
    `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `reviewed_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '审核人ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_questions_subject` (`subject_id`),
    KEY `idx_questions_type` (`type`),
    KEY `idx_questions_difficulty` (`difficulty_level`),
    KEY `idx_questions_status` (`status`),
    KEY `idx_questions_created_by` (`created_by`),
    KEY `idx_questions_created_at` (`created_at`),
    FULLTEXT KEY `ft_questions_content` (`content`),
    CONSTRAINT `fk_questions_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='题目表';

-- 题目选项表
CREATE TABLE `question_options` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '选项ID',
    `question_id` BIGINT UNSIGNED NOT NULL COMMENT '所属题目ID',
    `option_key` CHAR(1) NOT NULL COMMENT '选项标识：A/B/C/D/E...',
    `content` TEXT NOT NULL COMMENT '选项内容',
    `content_html` TEXT COMMENT '选项内容（HTML格式）',
    `is_correct` TINYINT NOT NULL DEFAULT 0 COMMENT '是否为正确答案：0-否，1-是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序顺序',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_question_options` (`question_id`, `option_key`),
    KEY `idx_qo_is_correct` (`is_correct`),
    CONSTRAINT `fk_qo_question_id` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='题目选项表';

-- =====================================================
-- 4. 测评系统表
-- =====================================================

-- 测评配置表
CREATE TABLE `assessments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '测评ID',
    `title` VARCHAR(200) NOT NULL COMMENT '测评标题',
    `type` ENUM('placement', 'stage', 'mock_exam') NOT NULL COMMENT '测评类型：基础定位/阶段能力/全真模拟',
    `subject_id` INT UNSIGNED DEFAULT NULL COMMENT '关联科目ID（综合测评为空）',
    `description` TEXT COMMENT '测评描述',
    `instructions` TEXT COMMENT '测评说明',
    `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 60 COMMENT '测评时长（分钟）',
    `total_questions` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目总数',
    `total_score` DECIMAL(8,2) NOT NULL DEFAULT 100.00 COMMENT '总分',
    `passing_score` DECIMAL(8,2) DEFAULT NULL COMMENT '及格分数',
    `difficulty_distribution` JSON COMMENT '难度分布配置',
    `knowledge_point_weights` JSON COMMENT '知识点权重配置',
    `allow_pause` TINYINT NOT NULL DEFAULT 1 COMMENT '是否允许暂停：0-否，1-是',
    `max_attempts` INT UNSIGNED DEFAULT NULL COMMENT '最大尝试次数（NULL表示不限）',
    `show_answer_after` ENUM('never', 'immediate', 'after_submit', 'after_deadline') DEFAULT 'after_submit' COMMENT '答案显示时机',
    `is_public` TINYINT NOT NULL DEFAULT 1 COMMENT '是否公开：0-否，1-是',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用，2-草稿',
    `start_time` TIMESTAMP NULL DEFAULT NULL COMMENT '开始时间',
    `end_time` TIMESTAMP NULL DEFAULT NULL COMMENT '结束时间',
    `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_assessments_type` (`type`),
    KEY `idx_assessments_subject` (`subject_id`),
    KEY `idx_assessments_status` (`status`),
    KEY `idx_assessments_is_public` (`is_public`),
    KEY `idx_assessments_price` (`price`),
    KEY `idx_assessments_created_at` (`created_at`),
    CONSTRAINT `fk_assessments_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='测评配置表';

-- 测评题目关联表
CREATE TABLE `assessment_questions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `assessment_id` BIGINT UNSIGNED NOT NULL COMMENT '测评ID',
    `question_id` BIGINT UNSIGNED NOT NULL COMMENT '题目ID',
    `sequence_number` INT UNSIGNED NOT NULL COMMENT '题目序号',
    `score` DECIMAL(5,2) NOT NULL COMMENT '该题分值',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_assessment_questions_seq` (`assessment_id`, `sequence_number`),
    UNIQUE KEY `uk_assessment_questions_q` (`assessment_id`, `question_id`),
    KEY `idx_aq_question_id` (`question_id`),
    CONSTRAINT `fk_aq_assessment_id` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_aq_question_id` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='测评题目关联表';

-- 高校信息表
CREATE TABLE `universities` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '高校ID',
    `name` VARCHAR(200) NOT NULL COMMENT '高校名称',
    `name_en` VARCHAR(200) DEFAULT NULL COMMENT '高校英文名称',
    `country` VARCHAR(50) NOT NULL COMMENT '所在国家',
    `ranking` INT UNSIGNED DEFAULT NULL COMMENT '综合排名',
    `tier` ENUM('A', 'B', 'C', 'D') DEFAULT NULL COMMENT '档位',
    `logo_url` VARCHAR(500) DEFAULT NULL COMMENT '校徽URL',
    `website` VARCHAR(255) DEFAULT NULL COMMENT '官方网站',
    `description` TEXT COMMENT '高校简介',
    `admission_requirements` TEXT COMMENT '录取要求',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_universities_country` (`country`),
    KEY `idx_universities_tier` (`tier`),
    KEY `idx_universities_ranking` (`ranking`),
    KEY `idx_universities_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='高校信息表';

-- 高校档位映射表
CREATE TABLE `tier_mappings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '映射ID',
    `assessment_type` ENUM('placement', 'stage', 'mock_exam') NOT NULL COMMENT '测评类型',
    `subject_id` INT UNSIGNED DEFAULT NULL COMMENT '科目ID（综合测评为空）',
    `min_score` DECIMAL(8,2) NOT NULL COMMENT '最低分数',
    `max_score` DECIMAL(8,2) NOT NULL COMMENT '最高分数',
    `tier` ENUM('A', 'B', 'C', 'D') NOT NULL COMMENT '对应档位',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '档位说明',
    `recommended_universities` JSON COMMENT '推荐高校ID列表',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_tm_assessment_type` (`assessment_type`),
    KEY `idx_tm_subject` (`subject_id`),
    KEY `idx_tm_tier` (`tier`),
    KEY `idx_tm_score_range` (`min_score`, `max_score`),
    CONSTRAINT `fk_tm_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='高校档位映射表';

-- 用户测评记录表
CREATE TABLE `user_assessments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `assessment_id` BIGINT UNSIGNED NOT NULL COMMENT '测评ID',
    `attempt_number` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '尝试次数',
    `status` ENUM('in_progress', 'paused', 'completed', 'expired', 'abandoned') NOT NULL DEFAULT 'in_progress' COMMENT '状态',
    `start_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '开始时间',
    `end_time` TIMESTAMP NULL DEFAULT NULL COMMENT '结束时间',
    `time_spent_seconds` INT UNSIGNED DEFAULT NULL COMMENT '实际用时（秒）',
    `current_question_index` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前题目索引',
    `total_score` DECIMAL(8,2) DEFAULT NULL COMMENT '总分',
    `obtained_score` DECIMAL(8,2) DEFAULT NULL COMMENT '获得分数',
    `correct_count` INT UNSIGNED DEFAULT NULL COMMENT '正确题数',
    `wrong_count` INT UNSIGNED DEFAULT NULL COMMENT '错误题数',
    `unanswered_count` INT UNSIGNED DEFAULT NULL COMMENT '未答题数',
    `tier_result` ENUM('A', 'B', 'C', 'D') DEFAULT NULL COMMENT '档位结果',
    `ability_analysis` JSON COMMENT '能力分析数据（雷达图）',
    `knowledge_point_analysis` JSON COMMENT '知识点掌握分析',
    `ranking_percentile` DECIMAL(5,2) DEFAULT NULL COMMENT '排名百分比',
    `certificate_url` VARCHAR(500) DEFAULT NULL COMMENT '证书URL',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
    `device_info` VARCHAR(500) DEFAULT NULL COMMENT '设备信息',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_assessments_attempt` (`user_id`, `assessment_id`, `attempt_number`),
    KEY `idx_ua_assessment_id` (`assessment_id`),
    KEY `idx_ua_status` (`status`),
    KEY `idx_ua_start_time` (`start_time`),
    KEY `idx_ua_tier_result` (`tier_result`),
    KEY `idx_ua_created_at` (`created_at`),
    CONSTRAINT `fk_ua_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ua_assessment_id` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户测评记录表';

-- 用户答题详情表
CREATE TABLE `user_answers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_assessment_id` BIGINT UNSIGNED NOT NULL COMMENT '用户测评记录ID',
    `question_id` BIGINT UNSIGNED NOT NULL COMMENT '题目ID',
    `sequence_number` INT UNSIGNED NOT NULL COMMENT '题目序号',
    `answer` TEXT COMMENT '用户答案',
    `answer_options` JSON COMMENT '选择的选项（选择题）',
    `is_correct` TINYINT DEFAULT NULL COMMENT '是否正确：0-否，1-是',
    `score` DECIMAL(5,2) DEFAULT NULL COMMENT '得分',
    `time_spent_seconds` INT UNSIGNED DEFAULT NULL COMMENT '答题用时（秒）',
    `answered_at` TIMESTAMP NULL DEFAULT NULL COMMENT '答题时间',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_answers` (`user_assessment_id`, `question_id`),
    KEY `idx_ua_question_id` (`question_id`),
    KEY `idx_ua_is_correct` (`is_correct`),
    KEY `idx_ua_answered_at` (`answered_at`),
    CONSTRAINT `fk_ua_user_assessment_id` FOREIGN KEY (`user_assessment_id`) REFERENCES `user_assessments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ua_question_id` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户答题详情表';

-- 用户错题本表
CREATE TABLE `user_wrong_answers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `question_id` BIGINT UNSIGNED NOT NULL COMMENT '题目ID',
    `user_assessment_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联测评记录ID',
    `wrong_answer` TEXT COMMENT '错误答案',
    `wrong_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '错误次数',
    `last_wrong_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后错误时间',
    `is_mastered` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已掌握：0-否，1-是',
    `mastered_at` TIMESTAMP NULL DEFAULT NULL COMMENT '掌握时间',
    `notes` TEXT COMMENT '用户笔记',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_wrong_answers` (`user_id`, `question_id`),
    KEY `idx_uwa_question_id` (`question_id`),
    KEY `idx_uwa_is_mastered` (`is_mastered`),
    KEY `idx_uwa_last_wrong_at` (`last_wrong_at`),
    CONSTRAINT `fk_uwa_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_uwa_question_id` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_uwa_user_assessment_id` FOREIGN KEY (`user_assessment_id`) REFERENCES `user_assessments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户错题本表';

-- =====================================================
-- 5. 视频课程系统表
-- =====================================================

-- 课程表
CREATE TABLE `courses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '课程ID',
    `title` VARCHAR(200) NOT NULL COMMENT '课程标题',
    `subtitle` VARCHAR(500) DEFAULT NULL COMMENT '课程副标题',
    `description` TEXT COMMENT '课程描述',
    `subject_id` INT UNSIGNED DEFAULT NULL COMMENT '关联科目ID',
    `cover_image` VARCHAR(500) DEFAULT NULL COMMENT '封面图片',
    `institution_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '所属机构ID',
    `teacher_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '主讲教师ID',
    `teacher_name` VARCHAR(50) DEFAULT NULL COMMENT '主讲教师名称',
    `teacher_avatar` VARCHAR(500) DEFAULT NULL COMMENT '教师头像',
    `teacher_bio` TEXT COMMENT '教师简介',
    `difficulty_level` TINYINT DEFAULT 3 COMMENT '难度等级：1-5',
    `target_audience` VARCHAR(500) DEFAULT NULL COMMENT '目标受众',
    `learning_objectives` JSON COMMENT '学习目标',
    `prerequisites` TEXT COMMENT '先修要求',
    `total_chapters` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总章节数',
    `total_videos` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总视频数',
    `total_duration_seconds` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总时长（秒）',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
    `original_price` DECIMAL(10,2) DEFAULT NULL COMMENT '原价',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否免费：0-否，1-是',
    `is_public` TINYINT NOT NULL DEFAULT 1 COMMENT '是否公开：0-否，1-是',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态：0-草稿，1-已发布，2-已下架',
    `published_at` TIMESTAMP NULL DEFAULT NULL COMMENT '发布时间',
    `enrollment_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '报名人数',
    `rating` DECIMAL(2,1) DEFAULT NULL COMMENT '评分（1-5）',
    `rating_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '评分人数',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序顺序',
    `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_courses_subject` (`subject_id`),
    KEY `idx_courses_institution` (`institution_id`),
    KEY `idx_courses_status` (`status`),
    KEY `idx_courses_is_public` (`is_public`),
    KEY `idx_courses_is_free` (`is_free`),
    KEY `idx_courses_price` (`price`),
    KEY `idx_courses_difficulty` (`difficulty_level`),
    KEY `idx_courses_published_at` (`published_at`),
    KEY `idx_courses_sort_order` (`sort_order`),
    FULLTEXT KEY `ft_courses_title` (`title`, `subtitle`),
    CONSTRAINT `fk_courses_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_courses_institution_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程表';

-- 视频表
CREATE TABLE `videos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '视频ID',
    `course_id` BIGINT UNSIGNED NOT NULL COMMENT '所属课程ID',
    `chapter_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '所属章节ID（预留）',
    `title` VARCHAR(200) NOT NULL COMMENT '视频标题',
    `description` TEXT COMMENT '视频描述',
    `video_url` VARCHAR(500) NOT NULL COMMENT '视频URL',
    `video_key` VARCHAR(255) DEFAULT NULL COMMENT '视频存储Key',
    `thumbnail_url` VARCHAR(500) DEFAULT NULL COMMENT '缩略图URL',
    `duration_seconds` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '视频时长（秒）',
    `file_size` BIGINT UNSIGNED DEFAULT NULL COMMENT '文件大小（字节）',
    `resolution` VARCHAR(20) DEFAULT NULL COMMENT '分辨率',
    `format` VARCHAR(20) DEFAULT NULL COMMENT '视频格式',
    `sequence_number` INT UNSIGNED NOT NULL COMMENT '序号',
    `is_free_preview` TINYINT NOT NULL DEFAULT 0 COMMENT '是否免费试看：0-否，1-是',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '观看次数',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_videos_course` (`course_id`),
    KEY `idx_videos_chapter` (`chapter_id`),
    KEY `idx_videos_status` (`status`),
    KEY `idx_videos_sequence` (`sequence_number`),
    KEY `idx_videos_is_free_preview` (`is_free_preview`),
    CONSTRAINT `fk_videos_course_id` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='视频表';

-- 用户学习记录表
CREATE TABLE `user_learning_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `course_id` BIGINT UNSIGNED NOT NULL COMMENT '课程ID',
    `video_id` BIGINT UNSIGNED NOT NULL COMMENT '视频ID',
    `progress_seconds` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前进度（秒）',
    `duration_seconds` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '视频总时长（秒）',
    `progress_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '进度百分比',
    `is_completed` TINYINT NOT NULL DEFAULT 0 COMMENT '是否完成：0-否，1-是',
    `completed_at` TIMESTAMP NULL DEFAULT NULL COMMENT '完成时间',
    `last_position` INT UNSIGNED DEFAULT 0 COMMENT '上次观看位置（秒）',
    `watch_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '观看次数',
    `last_watched_at` TIMESTAMP NULL DEFAULT NULL COMMENT '最后观看时间',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
    `device_type` ENUM('web', 'ios', 'android', 'other') DEFAULT 'web' COMMENT '设备类型',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_learning_records` (`user_id`, `video_id`),
    KEY `idx_ulr_course_id` (`course_id`),
    KEY `idx_ulr_is_completed` (`is_completed`),
    KEY `idx_ulr_last_watched` (`last_watched_at`),
    KEY `idx_ulr_created_at` (`created_at`),
    CONSTRAINT `fk_ulr_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ulr_course_id` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ulr_video_id` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户学习记录表';

-- =====================================================
-- 6. 订单支付系统表
-- =====================================================

-- 订单表
CREATE TABLE `orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '订单ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '订单编号',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `institution_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联机构ID',
    `type` ENUM('assessment', 'course', 'membership', 'package') NOT NULL COMMENT '订单类型',
    `title` VARCHAR(200) NOT NULL COMMENT '订单标题',
    `description` TEXT COMMENT '订单描述',
    `total_amount` DECIMAL(12,2) NOT NULL COMMENT '订单总金额',
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
    `payable_amount` DECIMAL(12,2) NOT NULL COMMENT '应付金额',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD' COMMENT '货币代码',
    `status` ENUM('pending', 'paid', 'processing', 'completed', 'cancelled', 'refunded', 'partial_refunded') NOT NULL DEFAULT 'pending' COMMENT '订单状态',
    `paid_at` TIMESTAMP NULL DEFAULT NULL COMMENT '支付时间',
    `completed_at` TIMESTAMP NULL DEFAULT NULL COMMENT '完成时间',
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL COMMENT '取消时间',
    `cancel_reason` VARCHAR(500) DEFAULT NULL COMMENT '取消原因',
    `coupon_code` VARCHAR(50) DEFAULT NULL COMMENT '使用的优惠券码',
    `coupon_discount` DECIMAL(12,2) DEFAULT NULL COMMENT '优惠券优惠金额',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT '下单IP',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT '用户代理',
    `notes` TEXT COMMENT '备注',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_orders_order_no` (`order_no`),
    KEY `idx_orders_user_id` (`user_id`),
    KEY `idx_orders_institution_id` (`institution_id`),
    KEY `idx_orders_type` (`type`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_paid_at` (`paid_at`),
    KEY `idx_orders_created_at` (`created_at`),
    CONSTRAINT `fk_orders_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_orders_institution_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单表';

-- 订单明细表
CREATE TABLE `order_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '明细ID',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `item_type` ENUM('assessment', 'course', 'video', 'membership', 'package') NOT NULL COMMENT '商品类型',
    `item_id` BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    `item_name` VARCHAR(200) NOT NULL COMMENT '商品名称',
    `item_description` TEXT COMMENT '商品描述',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `unit_price` DECIMAL(12,2) NOT NULL COMMENT '单价',
    `total_price` DECIMAL(12,2) NOT NULL COMMENT '总价',
    `discount_price` DECIMAL(12,2) DEFAULT NULL COMMENT '折扣价',
    `institution_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '所属机构ID',
    `institution_share` DECIMAL(12,2) DEFAULT NULL COMMENT '机构分账金额',
    `platform_share` DECIMAL(12,2) DEFAULT NULL COMMENT '平台分账金额',
    `status` ENUM('pending', 'delivered', 'refunded') NOT NULL DEFAULT 'pending' COMMENT '明细状态',
    `delivered_at` TIMESTAMP NULL DEFAULT NULL COMMENT '交付时间',
    `refund_amount` DECIMAL(12,2) DEFAULT NULL COMMENT '退款金额',
    `refund_reason` VARCHAR(500) DEFAULT NULL COMMENT '退款原因',
    `refunded_at` TIMESTAMP NULL DEFAULT NULL COMMENT '退款时间',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_oi_order_id` (`order_id`),
    KEY `idx_oi_item_type` (`item_type`),
    KEY `idx_oi_item_id` (`item_id`),
    KEY `idx_oi_institution_id` (`institution_id`),
    KEY `idx_oi_status` (`status`),
    CONSTRAINT `fk_oi_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_oi_institution_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单明细表';

-- 支付记录表
CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '支付记录ID',
    `order_id` BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '订单编号',
    `payment_no` VARCHAR(64) NOT NULL COMMENT '支付流水号',
    `payment_provider` ENUM('stripe', 'paypal', 'other') NOT NULL DEFAULT 'stripe' COMMENT '支付渠道',
    `payment_method` VARCHAR(50) DEFAULT NULL COMMENT '支付方式',
    `payment_intent_id` VARCHAR(255) DEFAULT NULL COMMENT 'Stripe Payment Intent ID',
    `charge_id` VARCHAR(255) DEFAULT NULL COMMENT 'Stripe Charge ID',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '支付金额',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD' COMMENT '货币代码',
    `status` ENUM('pending', 'processing', 'succeeded', 'failed', 'cancelled', 'refunded', 'disputed') NOT NULL DEFAULT 'pending' COMMENT '支付状态',
    `error_code` VARCHAR(100) DEFAULT NULL COMMENT '错误代码',
    `error_message` TEXT COMMENT '错误信息',
    `receipt_url` VARCHAR(500) DEFAULT NULL COMMENT '收据URL',
    `receipt_email` VARCHAR(255) DEFAULT NULL COMMENT '收据邮箱',
    `card_brand` VARCHAR(50) DEFAULT NULL COMMENT '卡品牌',
    `card_last4` VARCHAR(4) DEFAULT NULL COMMENT '卡号后四位',
    `card_country` VARCHAR(2) DEFAULT NULL COMMENT '卡所属国家',
    `billing_details` JSON COMMENT '账单详情',
    `metadata` JSON COMMENT '元数据',
    `processed_at` TIMESTAMP NULL DEFAULT NULL COMMENT '处理时间',
    `refunded_at` TIMESTAMP NULL DEFAULT NULL COMMENT '退款时间',
    `refund_amount` DECIMAL(12,2) DEFAULT NULL COMMENT '退款金额',
    `refund_reason` VARCHAR(500) DEFAULT NULL COMMENT '退款原因',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payments_payment_no` (`payment_no`),
    UNIQUE KEY `uk_payments_intent_id` (`payment_intent_id`),
    KEY `idx_payments_order_id` (`order_id`),
    KEY `idx_payments_order_no` (`order_no`),
    KEY `idx_payments_provider` (`payment_provider`),
    KEY `idx_payments_status` (`status`),
    KEY `idx_payments_created_at` (`created_at`),
    CONSTRAINT `fk_payments_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付记录表';

-- 机构分账表
CREATE TABLE `settlements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分账记录ID',
    `settlement_no` VARCHAR(32) NOT NULL COMMENT '分账编号',
    `institution_id` BIGINT UNSIGNED NOT NULL COMMENT '机构ID',
    `period_start` DATE NOT NULL COMMENT '结算周期开始',
    `period_end` DATE NOT NULL COMMENT '结算周期结束',
    `total_orders` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数量',
    `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '总金额',
    `platform_fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '平台服务费',
    `institution_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '机构应得金额',
    `tax_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT '税费',
    `net_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '实际结算金额',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD' COMMENT '货币代码',
    `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending' COMMENT '结算状态',
    `payment_method` VARCHAR(50) DEFAULT NULL COMMENT '付款方式',
    `payment_reference` VARCHAR(255) DEFAULT NULL COMMENT '付款参考号',
    `paid_at` TIMESTAMP NULL DEFAULT NULL COMMENT '付款时间',
    `transaction_receipt` VARCHAR(500) DEFAULT NULL COMMENT '交易凭证',
    `notes` TEXT COMMENT '备注',
    `processed_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '处理人ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settlements_no` (`settlement_no`),
    KEY `idx_settlements_institution` (`institution_id`),
    KEY `idx_settlements_period` (`period_start`, `period_end`),
    KEY `idx_settlements_status` (`status`),
    KEY `idx_settlements_paid_at` (`paid_at`),
    CONSTRAINT `fk_settlements_institution_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='机构分账表';

-- =====================================================
-- 7. 辅助表
-- =====================================================

-- 系统配置表
CREATE TABLE `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `category` VARCHAR(50) NOT NULL COMMENT '配置分类',
    `key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `value` TEXT COMMENT '配置值',
    `value_type` ENUM('string', 'integer', 'float', 'boolean', 'json', 'array') NOT NULL DEFAULT 'string' COMMENT '值类型',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '配置说明',
    `is_editable` TINYINT NOT NULL DEFAULT 1 COMMENT '是否可编辑：0-否，1-是',
    `is_visible` TINYINT NOT NULL DEFAULT 1 COMMENT '是否可见：0-否，1-是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序顺序',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`category`, `key`),
    KEY `idx_settings_category` (`category`),
    KEY `idx_settings_is_editable` (`is_editable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- =====================================================
-- 8. 初始化数据
-- =====================================================

-- 初始化科目数据
INSERT INTO `subjects` (`code`, `name`, `name_en`, `description`, `sort_order`, `status`) VALUES
('math', '数学', 'Mathematics', '数学科目，包含代数、几何、微积分等内容', 1, 1),
('physics', '物理', 'Physics', '物理科目，包含力学、电磁学、光学等内容', 2, 1),
('chemistry', '化学', 'Chemistry', '化学科目，包含有机化学、无机化学等内容', 3, 1),
('chinese', '语文', 'Chinese', '语文科目，包含阅读理解、写作等内容', 4, 1),
('english', '英语', 'English', '英语科目，包含听力、阅读、写作等内容', 5, 1);

-- 初始化系统配置
INSERT INTO `settings` (`category`, `key`, `value`, `value_type`, `description`, `is_editable`, `sort_order`) VALUES
('system', 'site_name', 'CSCA Bridge', 'string', '网站名称', 1, 1),
('system', 'site_logo', '', 'string', '网站Logo URL', 1, 2),
('system', 'site_description', 'CSCA在线学习与考试平台', 'string', '网站描述', 1, 3),
('payment', 'stripe_public_key', '', 'string', 'Stripe公钥', 1, 1),
('payment', 'stripe_secret_key', '', 'string', 'Stripe密钥', 1, 2),
('payment', 'currency', 'USD', 'string', '默认货币', 1, 3),
('institution', 'default_revenue_share', '70', 'integer', '默认机构分账比例（%）', 1, 1),
('institution', 'min_withdrawal_amount', '100', 'float', '最低提现金额', 1, 2),
('assessment', 'default_duration', '60', 'integer', '默认测评时长（分钟）', 1, 1),
('assessment', 'max_pause_count', '3', 'integer', '最大暂停次数', 1, 2),
('email', 'smtp_host', '', 'string', 'SMTP服务器地址', 1, 1),
('email', 'smtp_port', '587', 'integer', 'SMTP端口', 1, 2),
('email', 'smtp_username', '', 'string', 'SMTP用户名', 1, 3),
('email', 'smtp_password', '', 'string', 'SMTP密码', 1, 4),
('oauth', 'google_client_id', '', 'string', 'Google OAuth Client ID', 1, 1),
('oauth', 'google_client_secret', '', 'string', 'Google OAuth Client Secret', 1, 2);

-- 初始化高校档位映射示例数据
INSERT INTO `tier_mappings` (`assessment_type`, `subject_id`, `min_score`, `max_score`, `tier`, `description`, `status`) VALUES
('placement', NULL, 90.00, 100.00, 'A', '优秀，可冲击顶尖高校', 1),
('placement', NULL, 80.00, 89.99, 'B', '良好，可冲击一流高校', 1),
('placement', NULL, 70.00, 79.99, 'C', '中等，可冲击普通高校', 1),
('placement', NULL, 0.00, 69.99, 'D', '需加强，建议系统学习', 1);

-- 恢复外键检查
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 数据库设计完成
-- =====================================================
