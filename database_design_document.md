# CSCA在线学习与考试平台 - 数据库设计文档

## 项目信息

| 项目 | 内容 |
|------|------|
| 网站域名 | cscabridge.com |
| 数据库 | MySQL 8.0 |
| 字符集 | UTF-8mb4 |
| 存储引擎 | InnoDB |
| 创建日期 | 2024年 |

---

## 一、数据库设计概述

### 1.1 设计原则

1. **三范式规范**：遵循数据库设计三范式，确保数据一致性和减少冗余
2. **性能优化**：合理使用索引，支持高并发查询
3. **扩展性**：预留扩展字段，支持JSON类型存储动态数据
4. **安全性**：支持软删除，保护数据完整性
5. **国际化**：UTF-8mb4编码，支持多语言内容

### 1.2 表结构总览

| 序号 | 表名 | 说明 | 所属模块 |
|------|------|------|----------|
| 1 | users | 用户主表 | 用户系统 |
| 2 | user_profiles | 用户扩展信息表 | 用户系统 |
| 3 | sessions | 用户会话表 | 用户系统 |
| 4 | institutions | 机构表 | 机构系统 |
| 5 | institution_admins | 机构管理员关联表 | 机构系统 |
| 6 | subjects | 科目表 | 题库系统 |
| 7 | knowledge_points | 知识点表 | 题库系统 |
| 8 | questions | 题目表 | 题库系统 |
| 9 | question_options | 题目选项表 | 题库系统 |
| 10 | assessments | 测评配置表 | 测评系统 |
| 11 | assessment_questions | 测评题目关联表 | 测评系统 |
| 12 | universities | 高校信息表 | 测评系统 |
| 13 | tier_mappings | 高校档位映射表 | 测评系统 |
| 14 | user_assessments | 用户测评记录表 | 测评系统 |
| 15 | user_answers | 用户答题详情表 | 测评系统 |
| 16 | user_wrong_answers | 用户错题本表 | 测评系统 |
| 17 | courses | 课程表 | 课程系统 |
| 18 | videos | 视频表 | 课程系统 |
| 19 | user_learning_records | 用户学习记录表 | 课程系统 |
| 20 | orders | 订单表 | 支付系统 |
| 21 | order_items | 订单明细表 | 支付系统 |
| 22 | payments | 支付记录表 | 支付系统 |
| 23 | settlements | 机构分账表 | 支付系统 |
| 24 | settings | 系统配置表 | 系统配置 |

---

## 二、用户系统

### 2.1 users（用户主表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 用户ID，主键，自增 |
| email | VARCHAR(255) | 邮箱地址，唯一索引 |
| password_hash | VARCHAR(255) | 密码哈希（OAuth用户可为空） |
| auth_provider | ENUM | 认证方式：email/google |
| auth_provider_id | VARCHAR(255) | 第三方认证ID |
| role | ENUM | 用户角色：student/institution_admin/platform_admin |
| status | TINYINT | 状态：0-禁用，1-正常，2-待验证 |
| institution_id | BIGINT UNSIGNED | 所属机构ID |
| invitation_code | VARCHAR(32) | 绑定的邀请码 |
| email_verified_at | TIMESTAMP | 邮箱验证时间 |
| last_login_at | TIMESTAMP | 最后登录时间 |
| last_login_ip | VARCHAR(45) | 最后登录IP |
| created_at | TIMESTAMP | 创建时间 |
| updated_at | TIMESTAMP | 更新时间 |
| deleted_at | TIMESTAMP | 软删除时间 |

**索引说明**：
- 主键：id
- 唯一索引：email, (auth_provider, auth_provider_id)
- 普通索引：role, status, institution_id, created_at

### 2.2 user_profiles（用户扩展信息表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 记录ID，主键 |
| user_id | BIGINT UNSIGNED | 用户ID，外键，唯一 |
| nickname | VARCHAR(50) | 昵称 |
| real_name | VARCHAR(50) | 真实姓名 |
| avatar_url | VARCHAR(500) | 头像URL |
| phone | VARCHAR(20) | 手机号 |
| gender | TINYINT | 性别：0-保密，1-男，2-女 |
| birth_date | DATE | 出生日期 |
| country | VARCHAR(50) | 国家 |
| city | VARCHAR(100) | 城市 |
| school_name | VARCHAR(200) | 学校名称 |
| grade | VARCHAR(20) | 年级 |
| target_university | VARCHAR(200) | 目标大学 |
| target_major | VARCHAR(100) | 目标专业 |
| bio | TEXT | 个人简介 |

### 2.3 sessions（用户会话表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 会话ID，主键 |
| user_id | BIGINT UNSIGNED | 用户ID，外键 |
| session_token | VARCHAR(255) | 会话令牌，唯一索引 |
| refresh_token | VARCHAR(255) | 刷新令牌 |
| device_type | ENUM | 设备类型：web/ios/android/other |
| device_info | VARCHAR(500) | 设备信息 |
| ip_address | VARCHAR(45) | IP地址 |
| expires_at | TIMESTAMP | 过期时间 |
| last_activity_at | TIMESTAMP | 最后活动时间 |

---

## 三、机构系统

### 3.1 institutions（机构表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 机构ID，主键 |
| name | VARCHAR(200) | 机构名称，唯一索引 |
| name_en | VARCHAR(200) | 机构英文名称 |
| type | ENUM | 机构类型：large/medium/small/studio |
| status | TINYINT | 状态：0-待审核，1-已通过，2-已拒绝，3-已停用 |
| logo_url | VARCHAR(500) | 机构Logo |
| description | TEXT | 机构简介 |
| website | VARCHAR(255) | 官方网站 |
| country | VARCHAR(50) | 所在国家 |
| city | VARCHAR(100) | 所在城市 |
| address | VARCHAR(500) | 详细地址 |
| contact_name | VARCHAR(50) | 联系人姓名 |
| contact_phone | VARCHAR(20) | 联系人电话 |
| contact_email | VARCHAR(255) | 联系人邮箱 |
| business_license | VARCHAR(500) | 营业执照URL |
| invitation_code | VARCHAR(32) | 机构邀请码，唯一索引 |
| revenue_share_rate | DECIMAL(5,2) | 分账比例（百分比，机构获得） |
| total_students | INT UNSIGNED | 学员总数 |
| total_revenue | DECIMAL(15,2) | 总营收金额 |
| settled_amount | DECIMAL(15,2) | 已结算金额 |
| pending_settlement | DECIMAL(15,2) | 待结算金额 |

### 3.2 institution_admins（机构管理员关联表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 记录ID，主键 |
| institution_id | BIGINT UNSIGNED | 机构ID，外键 |
| user_id | BIGINT UNSIGNED | 用户ID，外键 |
| role | ENUM | 机构内角色：owner/admin/teacher |
| permissions | JSON | 权限配置（JSON格式） |
| is_primary | TINYINT | 是否主管理员 |
| status | TINYINT | 状态：0-禁用，1-正常 |

---

## 四、题库系统

### 4.1 subjects（科目表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | INT UNSIGNED | 科目ID，主键 |
| code | VARCHAR(20) | 科目代码，唯一索引 |
| name | VARCHAR(50) | 科目名称 |
| name_en | VARCHAR(50) | 科目英文名称 |
| description | TEXT | 科目描述 |
| icon_url | VARCHAR(500) | 图标URL |
| sort_order | INT | 排序顺序 |
| status | TINYINT | 状态：0-禁用，1-启用 |

**初始化数据**：
- math（数学）
- physics（物理）
- chemistry（化学）
- chinese（语文）
- english（英语）

### 4.2 knowledge_points（知识点表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 知识点ID，主键 |
| subject_id | INT UNSIGNED | 所属科目ID，外键 |
| parent_id | BIGINT UNSIGNED | 父知识点ID（支持多级） |
| name | VARCHAR(100) | 知识点名称 |
| description | TEXT | 知识点描述 |
| difficulty_level | TINYINT | 难度等级：1-5 |
| importance_level | TINYINT | 重要程度：1-5 |
| sort_order | INT | 排序顺序 |
| status | TINYINT | 状态：0-禁用，1-启用 |

### 4.3 questions（题目表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 题目ID，主键 |
| subject_id | INT UNSIGNED | 所属科目ID，外键 |
| type | ENUM | 题型：single_choice/multiple_choice/fill_blank/essay |
| difficulty_level | TINYINT | 难度等级：1-5 |
| content | TEXT | 题目内容 |
| content_html | TEXT | 题目内容（HTML格式） |
| analysis | TEXT | 题目解析 |
| analysis_html | TEXT | 题目解析（HTML格式） |
| answer | TEXT | 参考答案（问答题使用） |
| score | DECIMAL(5,2) | 题目分值 |
| knowledge_point_ids | JSON | 关联知识点ID数组 |
| tags | JSON | 标签数组 |
| source | VARCHAR(200) | 题目来源 |
| usage_count | INT UNSIGNED | 使用次数 |
| correct_count | INT UNSIGNED | 正确次数 |
| wrong_count | INT UNSIGNED | 错误次数 |
| correct_rate | DECIMAL(5,2) | 正确率 |
| status | TINYINT | 状态：0-禁用，1-启用，2-待审核 |

### 4.4 question_options（题目选项表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 选项ID，主键 |
| question_id | BIGINT UNSIGNED | 所属题目ID，外键 |
| option_key | CHAR(1) | 选项标识：A/B/C/D/E... |
| content | TEXT | 选项内容 |
| content_html | TEXT | 选项内容（HTML格式） |
| is_correct | TINYINT | 是否为正确答案 |
| sort_order | INT | 排序顺序 |

---

## 五、测评系统

### 5.1 assessments（测评配置表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 测评ID，主键 |
| title | VARCHAR(200) | 测评标题 |
| type | ENUM | 测评类型：placement（基础定位）/stage（阶段能力）/mock_exam（全真模拟） |
| subject_id | INT UNSIGNED | 关联科目ID（综合测评为空） |
| description | TEXT | 测评描述 |
| instructions | TEXT | 测评说明 |
| duration_minutes | INT UNSIGNED | 测评时长（分钟） |
| total_questions | INT UNSIGNED | 题目总数 |
| total_score | DECIMAL(8,2) | 总分 |
| passing_score | DECIMAL(8,2) | 及格分数 |
| difficulty_distribution | JSON | 难度分布配置 |
| knowledge_point_weights | JSON | 知识点权重配置 |
| allow_pause | TINYINT | 是否允许暂停 |
| max_attempts | INT UNSIGNED | 最大尝试次数 |
| show_answer_after | ENUM | 答案显示时机 |
| is_public | TINYINT | 是否公开 |
| price | DECIMAL(10,2) | 价格 |
| status | TINYINT | 状态：0-禁用，1-启用，2-草稿 |

### 5.2 user_assessments（用户测评记录表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 记录ID，主键 |
| user_id | BIGINT UNSIGNED | 用户ID，外键 |
| assessment_id | BIGINT UNSIGNED | 测评ID，外键 |
| attempt_number | INT UNSIGNED | 尝试次数 |
| status | ENUM | 状态：in_progress/paused/completed/expired/abandoned |
| start_time | TIMESTAMP | 开始时间 |
| end_time | TIMESTAMP | 结束时间 |
| time_spent_seconds | INT UNSIGNED | 实际用时（秒） |
| current_question_index | INT UNSIGNED | 当前题目索引 |
| total_score | DECIMAL(8,2) | 总分 |
| obtained_score | DECIMAL(8,2) | 获得分数 |
| correct_count | INT UNSIGNED | 正确题数 |
| wrong_count | INT UNSIGNED | 错误题数 |
| unanswered_count | INT UNSIGNED | 未答题数 |
| tier_result | ENUM | 档位结果：A/B/C/D |
| ability_analysis | JSON | 能力分析数据（雷达图） |
| knowledge_point_analysis | JSON | 知识点掌握分析 |
| ranking_percentile | DECIMAL(5,2) | 排名百分比 |
| certificate_url | VARCHAR(500) | 证书URL |

### 5.3 tier_mappings（高校档位映射表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | INT UNSIGNED | 映射ID，主键 |
| assessment_type | ENUM | 测评类型 |
| subject_id | INT UNSIGNED | 科目ID（综合测评为空） |
| min_score | DECIMAL(8,2) | 最低分数 |
| max_score | DECIMAL(8,2) | 最高分数 |
| tier | ENUM | 对应档位：A/B/C/D |
| description | VARCHAR(500) | 档位说明 |
| recommended_universities | JSON | 推荐高校ID列表 |

---

## 六、课程系统

### 6.1 courses（课程表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 课程ID，主键 |
| title | VARCHAR(200) | 课程标题 |
| subtitle | VARCHAR(500) | 课程副标题 |
| description | TEXT | 课程描述 |
| subject_id | INT UNSIGNED | 关联科目ID |
| cover_image | VARCHAR(500) | 封面图片 |
| institution_id | BIGINT UNSIGNED | 所属机构ID |
| teacher_name | VARCHAR(50) | 主讲教师名称 |
| teacher_avatar | VARCHAR(500) | 教师头像 |
| teacher_bio | TEXT | 教师简介 |
| difficulty_level | TINYINT | 难度等级：1-5 |
| target_audience | VARCHAR(500) | 目标受众 |
| learning_objectives | JSON | 学习目标 |
| prerequisites | TEXT | 先修要求 |
| total_chapters | INT UNSIGNED | 总章节数 |
| total_videos | INT UNSIGNED | 总视频数 |
| total_duration_seconds | INT UNSIGNED | 总时长（秒） |
| price | DECIMAL(10,2) | 价格 |
| original_price | DECIMAL(10,2) | 原价 |
| is_free | TINYINT | 是否免费 |
| is_public | TINYINT | 是否公开 |
| status | TINYINT | 状态：0-草稿，1-已发布，2-已下架 |
| published_at | TIMESTAMP | 发布时间 |
| enrollment_count | INT UNSIGNED | 报名人数 |
| rating | DECIMAL(2,1) | 评分（1-5） |
| rating_count | INT UNSIGNED | 评分人数 |

### 6.2 videos（视频表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 视频ID，主键 |
| course_id | BIGINT UNSIGNED | 所属课程ID，外键 |
| title | VARCHAR(200) | 视频标题 |
| description | TEXT | 视频描述 |
| video_url | VARCHAR(500) | 视频URL |
| video_key | VARCHAR(255) | 视频存储Key |
| thumbnail_url | VARCHAR(500) | 缩略图URL |
| duration_seconds | INT UNSIGNED | 视频时长（秒） |
| file_size | BIGINT UNSIGNED | 文件大小（字节） |
| resolution | VARCHAR(20) | 分辨率 |
| format | VARCHAR(20) | 视频格式 |
| sequence_number | INT UNSIGNED | 序号 |
| is_free_preview | TINYINT | 是否免费试看 |
| view_count | INT UNSIGNED | 观看次数 |

### 6.3 user_learning_records（用户学习记录表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 记录ID，主键 |
| user_id | BIGINT UNSIGNED | 用户ID，外键 |
| course_id | BIGINT UNSIGNED | 课程ID，外键 |
| video_id | BIGINT UNSIGNED | 视频ID，外键 |
| progress_seconds | INT UNSIGNED | 当前进度（秒） |
| duration_seconds | INT UNSIGNED | 视频总时长（秒） |
| progress_percentage | DECIMAL(5,2) | 进度百分比 |
| is_completed | TINYINT | 是否完成 |
| completed_at | TIMESTAMP | 完成时间 |
| last_position | INT UNSIGNED | 上次观看位置（秒） |
| watch_count | INT UNSIGNED | 观看次数 |
| last_watched_at | TIMESTAMP | 最后观看时间 |

---

## 七、支付系统

### 7.1 orders（订单表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 订单ID，主键 |
| order_no | VARCHAR(32) | 订单编号，唯一索引 |
| user_id | BIGINT UNSIGNED | 用户ID，外键 |
| institution_id | BIGINT UNSIGNED | 关联机构ID |
| type | ENUM | 订单类型：assessment/course/membership/package |
| title | VARCHAR(200) | 订单标题 |
| total_amount | DECIMAL(12,2) | 订单总金额 |
| discount_amount | DECIMAL(12,2) | 优惠金额 |
| payable_amount | DECIMAL(12,2) | 应付金额 |
| currency | VARCHAR(3) | 货币代码，默认USD |
| status | ENUM | 订单状态：pending/paid/processing/completed/cancelled/refunded/partial_refunded |
| paid_at | TIMESTAMP | 支付时间 |
| coupon_code | VARCHAR(50) | 使用的优惠券码 |

### 7.2 payments（支付记录表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 支付记录ID，主键 |
| order_id | BIGINT UNSIGNED | 订单ID，外键 |
| order_no | VARCHAR(32) | 订单编号 |
| payment_no | VARCHAR(64) | 支付流水号，唯一索引 |
| payment_provider | ENUM | 支付渠道：stripe/paypal/other |
| payment_intent_id | VARCHAR(255) | Stripe Payment Intent ID |
| charge_id | VARCHAR(255) | Stripe Charge ID |
| amount | DECIMAL(12,2) | 支付金额 |
| currency | VARCHAR(3) | 货币代码 |
| status | ENUM | 支付状态：pending/processing/succeeded/failed/cancelled/refunded/disputed |
| receipt_url | VARCHAR(500) | 收据URL |
| card_brand | VARCHAR(50) | 卡品牌 |
| card_last4 | VARCHAR(4) | 卡号后四位 |
| card_country | VARCHAR(2) | 卡所属国家 |

### 7.3 settlements（机构分账表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | BIGINT UNSIGNED | 分账记录ID，主键 |
| settlement_no | VARCHAR(32) | 分账编号，唯一索引 |
| institution_id | BIGINT UNSIGNED | 机构ID，外键 |
| period_start | DATE | 结算周期开始 |
| period_end | DATE | 结算周期结束 |
| total_orders | INT UNSIGNED | 订单数量 |
| total_amount | DECIMAL(15,2) | 总金额 |
| platform_fee | DECIMAL(15,2) | 平台服务费 |
| institution_amount | DECIMAL(15,2) | 机构应得金额 |
| tax_amount | DECIMAL(15,2) | 税费 |
| net_amount | DECIMAL(15,2) | 实际结算金额 |
| status | ENUM | 结算状态：pending/processing/completed/failed |
| paid_at | TIMESTAMP | 付款时间 |

---

## 八、系统配置

### 8.1 settings（系统配置表）

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | INT UNSIGNED | 配置ID，主键 |
| category | VARCHAR(50) | 配置分类 |
| key | VARCHAR(100) | 配置键 |
| value | TEXT | 配置值 |
| value_type | ENUM | 值类型：string/integer/float/boolean/json/array |
| description | VARCHAR(500) | 配置说明 |
| is_editable | TINYINT | 是否可编辑 |
| is_visible | TINYINT | 是否可见 |
| sort_order | INT | 排序顺序 |

**初始化配置**：
- system.site_name: CSCA Bridge
- system.site_description: CSCA在线学习与考试平台
- payment.stripe_public_key: Stripe公钥
- payment.currency: USD
- institution.default_revenue_share: 70
- assessment.default_duration: 60

---

## 九、数据库ER图关系

```
users (1) ----< (N) user_profiles
users (1) ----< (N) sessions
users (1) ----< (N) institution_admins
users (1) ----< (N) user_assessments
users (1) ----< (N) user_learning_records
users (1) ----< (N) orders
users (1) ----< (N) user_wrong_answers

institutions (1) ----< (N) institution_admins
institutions (1) ----< (N) courses
institutions (1) ----< (N) orders
institutions (1) ----< (N) settlements

subjects (1) ----< (N) knowledge_points
subjects (1) ----< (N) questions
subjects (1) ----< (N) assessments
subjects (1) ----< (N) courses

knowledge_points (1) ----< (N) knowledge_points (自关联)

questions (1) ----< (N) question_options
questions (1) ----< (N) assessment_questions
questions (1) ----< (N) user_answers
questions (1) ----< (N) user_wrong_answers

assessments (1) ----< (N) assessment_questions
assessments (1) ----< (N) user_assessments

courses (1) ----< (N) videos
courses (1) ----< (N) user_learning_records

videos (1) ----< (N) user_learning_records

user_assessments (1) ----< (N) user_answers

orders (1) ----< (N) order_items
orders (1) ----< (N) payments
```

---

## 十、使用说明

### 10.1 导入数据库

```bash
mysql -u root -p < cscabridge_database.sql
```

### 10.2 创建数据库用户

```sql
CREATE USER 'csca_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON cscabridge.* TO 'csca_user'@'localhost';
FLUSH PRIVILEGES;
```

### 10.3 性能优化建议

1. **定期维护索引**：使用 `OPTIMIZE TABLE` 命令
2. **分区表**：对于大数据量的表（如 user_answers）考虑分区
3. **读写分离**：配置主从复制，分担读压力
4. **缓存策略**：热点数据使用 Redis 缓存

---

**文档版本**: 1.0  
**最后更新**: 2024年
