# CSCA Bridge - 来华留学备考平台

> **一站式CSCA考试培训与留学服务平台**
> 
> 网站: https://cscabridge.com
> 
> 环境: WAMP/LAMP (Apache + PHP 8.2 + MySQL 8.0)

---

## 📋 项目概述

CSCA Bridge是面向计划赴中国攻读本科学位的国际学生设计的标准化考试备考平台。平台提供"（基础测评）→学习→测评→申请"的一站式闭环服务。

### 核心功能

| 模块 | 功能描述 |
|------|----------|
| 🎯 **测评系统** | 基础定位测评、阶段能力测评、全真模拟考试 |
| 📚 **题库系统** | 按科目/章节/难度筛选、顺序练习、随机练习、错题本 |
| 🎬 **视频课程** | 高清视频课程、断点续播、学习进度追踪 |
| 💳 **支付系统** | Stripe国际支付、订单管理、退款处理 |
| 🏫 **机构管理** | 机构入驻、学员绑定、分账结算 |
| 📊 **数据统计** | 学习进度、成绩分析、能力雷达图 |

---

## 🚀 快速开始

### 环境要求

- **操作系统**: Windows 10/11 (WAMP) 或 Linux (LAMP)
- **Web服务器**: Apache 2.4+
- **PHP版本**: 8.2+
- **数据库**: MySQL 8.0+
- **缓存**: Redis 7.0 (可选)

### 安装步骤

1. **克隆项目到Web根目录**
```bash
cd /var/www/html
git clone https://github.com/cscabridge/platform.git cscabridge
```

2. **创建数据库**
```bash
mysql -u root -p < database/cscabridge_database.sql
```

3. **配置数据库连接**
```bash
cp config/database.php.example config/database.php
# 编辑 config/database.php 设置数据库连接信息
```

4. **设置目录权限**
```bash
chmod 755 /var/www/html/cscabridge
chmod -R 777 uploads/
chmod -R 777 logs/
chmod -R 777 cache/
```

5. **配置Apache重写规则**
确保Apache已启用mod_rewrite模块，.htaccess文件已包含重写规则。

6. **访问网站**
打开浏览器访问: http://localhost/cscabridge

---

## 📁 目录结构

```
/cscabridge/
├── config/                 # 配置文件
│   ├── database.php       # 数据库配置
│   ├── app.php            # 应用配置
│   ├── payment.php        # 支付配置
│   └── ...
├── includes/              # 公共函数/核心库
│   ├── Database.php       # 数据库连接类
│   ├── functions.php      # 公共函数库
│   └── autoload.php       # 自动加载器
├── classes/               # 业务类
│   ├── models/            # 数据模型
│   └── services/          # 业务服务
├── lang/                  # 多语言文件
│   ├── zh_CN/             # 简体中文
│   └── en_US/             # 英文
├── assets/                # 静态资源
│   ├── css/               # 样式文件
│   ├── js/                # JavaScript文件
│   └── images/            # 图片资源
├── student/               # 学生端
│   ├── controllers/       # 控制器
│   └── views/             # 视图
├── institution/           # 机构后台
├── admin/                 # 平台管理后台
├── api/                   # API接口
├── templates/             # 视图模板
├── uploads/               # 上传文件
├── logs/                  # 日志文件
├── cache/                 # 缓存文件
└── database/              # 数据库相关
```

---

## 🗄️ 数据库设计

### 核心数据表 (24个)

| 分类 | 表名 | 说明 |
|------|------|------|
| **用户系统** | users | 用户主表 |
| | user_profiles | 用户扩展信息 |
| | sessions | 用户会话 |
| **机构系统** | institutions | 机构信息 |
| | institution_admins | 机构管理员 |
| **题库系统** | subjects | 科目表 |
| | knowledge_points | 知识点 |
| | questions | 题目表 |
| | question_options | 题目选项 |
| **测评系统** | assessments | 测评配置 |
| | user_assessments | 用户测评记录 |
| | user_answers | 用户答题详情 |
| | tier_mappings | 高校档位映射 |
| **课程系统** | courses | 课程表 |
| | videos | 视频表 |
| | user_learning_records | 学习记录 |
| **订单系统** | orders | 订单表 |
| | order_items | 订单明细 |
| | payments | 支付记录 |
| | settlements | 机构分账 |

---

## 🌐 多语言支持

平台支持中英文双语切换，语言文件位于 `/lang/` 目录。

### 切换语言

- URL参数: `?lang=en_US` 或 `?lang=zh_CN`
- 会话存储: 语言设置保存在Session中

---

## 💳 支付集成

### Stripe配置

1. 在 [Stripe Dashboard](https://dashboard.stripe.com) 获取API密钥
2. 配置 `config/payment.php`:
```php
'public_key' => 'pk_live_your_public_key',
'secret_key' => 'sk_live_your_secret_key',
'webhook_secret' => 'whsec_your_webhook_secret',
```
3. 配置Webhook端点: `https://cscabridge.com/api/webhooks/stripe`

---

## 🔐 安全特性

- ✅ CSRF保护
- ✅ XSS过滤
- ✅ SQL注入防护（预处理语句）
- ✅ 密码加密（bcrypt）
- ✅ 会话安全
- ✅ HTTPS强制（生产环境）
- ✅ 点击劫持防护
- ✅ 安全响应头

---

## 📊 性能优化

- 数据库读写分离支持
- Redis缓存热点数据
- 静态资源CDN加速
- 页面缓存机制
- Gzip压缩
- 浏览器缓存策略

---

## 📝 开发规范

### 代码风格
- 遵循 PSR-12 代码规范
- 使用有意义的变量名
- 添加必要的注释

### 文件命名
- 类文件: 大驼峰命名法 (UserController.php)
- 函数文件: 小写下划线 (common.php)
- 视图文件: 小写下划线 (login.php)

---

## 🤝 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

---

## 📄 许可证

本项目采用 MIT 许可证 - 详见 [LICENSE](LICENSE) 文件

---

## 📞 联系我们

- 网站: https://cscabridge.com
- 邮箱: support@cscabridge.com
- 电话: +86 xxx xxxx xxxx

---

**© 2024 CSCA Bridge. All Rights Reserved.**
