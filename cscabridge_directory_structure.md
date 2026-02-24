# CSCA在线学习与考试平台 - 网站目录结构

> **项目信息**
> - 域名：cscabridge.com
> - 环境：WAMP/LAMP (Apache + PHP 8.2 + MySQL 8.0)
> - 目标用户：来华留学备考CSCA考试的国际学生

---

## 目录结构总览

```
/cscabridge/                          # 网站根目录
│
├── .htaccess                         # Apache重写规则配置文件
├── index.php                         # 网站入口文件
├── api.php                           # API接口统一入口
├── robots.txt                        # 搜索引擎爬虫规则
├── sitemap.xml                       # 网站地图
│
├── /config/                          # 【配置文件目录】
│   ├── database.php                  # 数据库连接配置
│   ├── app.php                       # 应用基础配置
│   ├── payment.php                   # 支付配置(Stripe密钥等)
│   ├── email.php                     # 邮件SMTP配置
│   ├── storage.php                   # 存储配置(本地/云存储)
│   ├── security.php                  # 安全配置(加密密钥、IP白名单等)
│   ├── routes.php                    # 路由配置
│   └── constants.php                 # 常量定义
│
├── /includes/                        # 【公共函数/核心库目录】
│   ├── /functions/                   # 公共函数库
│   │   ├── common.php                # 通用函数(字符串处理、数组操作等)
│   │   ├── auth.php                  # 认证相关函数
│   │   ├── validate.php              # 数据验证函数
│   │   ├── security.php              # 安全相关函数(加密、XSS过滤等)
│   │   ├── http.php                  # HTTP请求处理函数
│   │   ├── file.php                  # 文件操作函数
│   │   ├── time.php                  # 时间日期处理函数
│   │   └── payment.php               # 支付相关函数
│   │
│   ├── /core/                        # 核心类库
│   │   ├── App.php                   # 应用核心类
│   │   ├── Router.php                # 路由处理类
│   │   ├── Request.php               # HTTP请求处理类
│   │   ├── Response.php              # HTTP响应处理类
│   │   ├── Session.php               # Session管理类
│   │   ├── Cache.php                 # 缓存管理类
│   │   └── Hook.php                  # 钩子/事件系统
│   │
│   └── autoload.php                  # 自动加载文件
│
├── /classes/                         # 【数据库操作/业务模型类目录】
│   ├── /models/                      # 数据模型类(MVC-M)
│   │   ├── User.php                  # 用户模型
│   │   ├── Student.php               # 学员模型
│   │   ├── Institution.php           # 机构模型
│   │   ├── Question.php              # 题目模型
│   │   ├── QuestionBank.php          # 题库模型
│   │   ├── Exam.php                  # 考试/测评模型
│   │   ├── ExamRecord.php            # 考试记录模型
│   │   ├── Course.php                # 课程模型
│   │   ├── Video.php                 # 视频模型
│   │   ├── Order.php                 # 订单模型
│   │   ├── Payment.php               # 支付记录模型
│   │   ├── WrongBook.php             # 错题本模型
│   │   ├── Notice.php                # 公告/通知模型
│   │   └── BaseModel.php             # 基础模型类(所有模型继承)
│   │
│   ├── /services/                    # 业务逻辑服务类
│   │   ├── AuthService.php           # 认证服务
│   │   ├── ExamService.php           # 考试服务
│   │   ├── QuestionService.php       # 题库服务
│   │   ├── CourseService.php         # 课程服务
│   │   ├── PaymentService.php        # 支付服务
│   │   ├── UserService.php           # 用户服务
│   │   ├── StatisticsService.php     # 统计服务
│   │   └── NotificationService.php   # 通知服务
│   │
│   └── /traits/                      # 代码复用Traits
│       ├── SoftDelete.php            # 软删除Trait
│       ├── Timestamp.php             # 时间戳Trait
│       └── Loggable.php              # 日志记录Trait
│
├── /lang/                            # 【多语言文件目录】
│   ├── /zh_CN/                       # 简体中文
│   │   ├── common.php                # 通用语言包
│   │   ├── auth.php                  # 认证相关
│   │   ├── exam.php                  # 考试测评相关
│   │   ├── course.php                # 课程相关
│   │   ├── payment.php               # 支付相关
│   │   ├── user.php                  # 用户相关
│   │   ├── institution.php           # 机构相关
│   │   ├── validation.php            # 验证提示
│   │   └── errors.php                # 错误提示
│   │
│   ├── /en_US/                       # 英文(美国)
│   │   ├── common.php
│   │   ├── auth.php
│   │   ├── exam.php
│   │   ├── course.php
│   │   ├── payment.php
│   │   ├── user.php
│   │   ├── institution.php
│   │   ├── validation.php
│   │   └── errors.php
│   │
│   └── Language.php                  # 语言切换处理类
│
├── /assets/                          # 【静态资源目录】(Web可访问)
│   ├── /css/                         # 样式文件
│   │   ├── /student/                 # 学生端样式
│   │   │   ├── main.css
│   │   │   ├── exam.css
│   │   │   ├── course.css
│   │   │   └── responsive.css
│   │   │
│   │   ├── /institution/             # 机构后台样式
│   │   │   ├── dashboard.css
│   │   │   └── admin.css
│   │   │
│   │   ├── /admin/                   # 平台后台样式
│   │   │   ├── dashboard.css
│   │   │   └── system.css
│   │   │
│   │   ├── /common/                  # 公共样式
│   │   │   ├── bootstrap.min.css
│   │   │   ├── font-awesome.min.css
│   │   │   └── components.css
│   │   │
│   │   └── /plugins/                 # 第三方插件样式
│   │       ├── select2.min.css
│   │       ├── datetimepicker.css
│   │       └── videojs.min.css
│   │
│   ├── /js/                          # JavaScript文件
│   │   ├── /student/                 # 学生端脚本
│   │   │   ├── main.js
│   │   │   ├── exam.js               # 考试相关
│   │   │   ├── practice.js           # 练习相关
│   │   │   ├── course.js             # 课程播放相关
│   │   │   └── payment.js            # 支付相关
│   │   │
│   │   ├── /institution/             # 机构后台脚本
│   │   │   ├── dashboard.js
│   │   │   ├── students.js           # 学员管理
│   │   │   └── reports.js            # 报表统计
│   │   │
│   │   ├── /admin/                   # 平台后台脚本
│   │   │   ├── dashboard.js
│   │   │   ├── system.js
│   │   │   └── charts.js             # 图表统计
│   │   │
│   │   ├── /common/                  # 公共脚本
│   │   │   ├── jquery.min.js
│   │   │   ├── bootstrap.min.js
│   │   │   ├── vue.min.js
│   │   │   ├── axios.min.js
│   │   │   ├── utils.js              # 工具函数
│   │   │   └── api.js                # API请求封装
│   │   │
│   │   └── /plugins/                 # 第三方插件脚本
│   │       ├── select2.min.js
│   │       ├── datetimepicker.js
│   │       ├── video.min.js          # Video.js播放器
│   │       ├── chart.min.js          # Chart.js图表
│   │       └── mathjax.min.js        # 数学公式渲染
│   │
│   ├── /images/                      # 图片资源
│   │   ├── /logo/                    # Logo相关
│   │   │   ├── logo.png
│   │   │   ├── logo-white.png
│   │   │   └── favicon.ico
│   │   │
│   │   ├── /icons/                   # 图标
│   │   │   ├── exam-icon.png
│   │   │   ├── course-icon.png
│   │   │   └── practice-icon.png
│   │   │
│   │   ├── /banners/                 # 横幅广告
│   │   │   ├── home-banner.jpg
│   │   │   └── course-banner.jpg
│   │   │
│   │   ├── /avatars/                 # 默认头像
│   │   │   ├── default-student.png
│   │   │   └── default-institution.png
│   │   │
│   │   └── /subjects/                # 科目图标
│   │       ├── math.png
│   │       ├── physics.png
│   │       └── chemistry.png
│   │
│   ├── /fonts/                       # 字体文件
│   │   ├── /fontawesome/             # FontAwesome字体
│   │   └── /custom/                  # 自定义字体
│   │
│   └── /libs/                        # 前端第三方库(未压缩版)
│       ├── /bootstrap/
│       ├── /vue/
│       └── /element-ui/
│
├── /uploads/                         # 【上传文件目录】(需设置权限)
│   ├── /videos/                      # 视频文件
│   │   ├── /courses/                 # 课程视频
│   │   │   ├── /course_001/          # 按课程ID分目录
│   │   │   │   ├── 01_intro.mp4
│   │   │   │   └── 02_chapter1.mp4
│   │   │   └── /course_002/
│   │   │
│   │   └── /previews/                # 视频预览/试看
│   │
│   ├── /images/                      # 上传的图片
│   │   ├── /avatars/                 # 用户头像
│   │   │   ├── /students/
│   │   │   └── /institutions/
│   │   │
│   │   ├── /courses/                 # 课程封面
│   │   │   └── /thumbs/              # 缩略图
│   │   │
│   │   ├── /questions/               # 题目配图
│   │   │   └── /thumbs/
│   │   │
│   │   ├── /institutions/            # 机构Logo/资质
│   │   │   ├── /logos/
│   │   │   └── /certificates/        # 资质证书
│   │   │
│   │   └── /banners/                 # 运营banner
│   │
│   ├── /documents/                   # 文档文件
│   │   └── /course_materials/        # 课程资料
│   │
│   ├── /exports/                     # 导出文件
│   │   ├── /exam_results/            # 考试成绩导出
│   │   └── /reports/                 # 统计报表导出
│   │
│   └── .htaccess                     # 禁止直接访问敏感文件
│
├── /logs/                            # 【日志目录】(需设置权限)
│   ├── /app/                         # 应用日志
│   ├── /error/                       # 错误日志
│   ├── /sql/                         # SQL执行日志
│   ├── /payment/                     # 支付日志
│   ├── /login/                       # 登录日志
│   └── /api/                         # API请求日志
│
├── /cache/                           # 【缓存目录】(需设置权限)
│   ├── /data/                        # 数据缓存
│   ├── /view/                        # 视图缓存
│   ├── /session/                     # Session文件
│   └── /compiled/                    # 编译缓存
│
├── /database/                        # 【数据库相关】
│   ├── /migrations/                  # 数据库迁移文件
│   ├── /seeds/                       # 数据填充文件
│   ├── /backups/                     # 数据库备份
│   └── schema.sql                    # 完整数据库结构
│
├── /templates/                       # 【视图模板目录】(MVC-V)
│   ├── /layouts/                     # 布局模板
│   │   ├── /student/                 # 学生端布局
│   │   │   ├── main.php              # 主布局
│   │   │   ├── blank.php             # 空白布局(登录页等)
│   │   │   └── exam.php              # 考试专用布局
│   │   │
│   │   ├── /institution/             # 机构后台布局
│   │   │   ├── dashboard.php
│   │   │   └── auth.php
│   │   │
│   │   └── /admin/                   # 平台后台布局
│   │       ├── dashboard.php
│   │       └── auth.php
│   │
│   ├── /components/                  # 公共组件模板
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── sidebar.php
│   │   ├── pagination.php
│   │   ├── modal.php
│   │   ├── alert.php
│   │   └── loading.php
│   │
│   └── /emails/                      # 邮件模板
│       ├── welcome.php
│       ├── password_reset.php
│       ├── exam_reminder.php
│       └── payment_receipt.php
│
├── /student/                         # 【学生端前台】(MVC-C)
│   ├── index.php                     # 首页控制器
│   ├── /controllers/                 # 控制器
│   │   ├── AuthController.php        # 认证相关
│   │   ├── DashboardController.php   # 个人中心
│   │   ├── ExamController.php        # 考试测评
│   │   ├── PracticeController.php    # 练习
│   │   ├── CourseController.php      # 课程学习
│   │   ├── PaymentController.php     # 支付
│   │   ├── WrongBookController.php   # 错题本
│   │   ├── NoticeController.php      # 通知公告
│   │   └── ProfileController.php     # 个人资料
│   │
│   ├── /views/                       # 视图文件
│   │   ├── /auth/                    # 认证页面
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── forgot_password.php
│   │   │   └── reset_password.php
│   │   │
│   │   ├── /dashboard/               # 个人中心
│   │   │   ├── index.php             # 学习中心首页
│   │   │   ├── my_courses.php        # 我的课程
│   │   │   ├── my_exams.php          # 我的考试
│   │   │   ├── progress.php          # 学习进度
│   │   │   └── settings.php          # 账号设置
│   │   │
│   │   ├── /exam/                    # 考试模块
│   │   │   ├── index.php             # 考试首页
│   │   │   ├── placement.php         # 基础定位测评
│   │   │   ├── stage.php             # 阶段能力测评
│   │   │   ├── mock.php              # 全真模拟
│   │   │   ├── start.php             # 开始考试页面
│   │   │   ├── doing.php             # 考试中页面
│   │   │   ├── result.php            # 成绩结果
│   │   │   └── review.php            # 错题回顾
│   │   │
│   │   ├── /practice/                # 练习模块
│   │   │   ├── index.php
│   │   │   ├── by_chapter.php        # 章节练习
│   │   │   ├── by_subject.php        # 科目练习
│   │   │   ├── random.php            # 随机练习
│   │   │   └── wrong_book.php        # 错题本
│   │   │
│   │   ├── /course/                  # 课程模块
│   │   │   ├── index.php             # 课程列表
│   │   │   ├── detail.php            # 课程详情
│   │   │   ├── play.php              # 视频播放
│   │   │   └── category.php          # 课程分类
│   │   │
│   │   ├── /payment/                 # 支付模块
│   │   │   ├── checkout.php          # 结算页面
│   │   │   ├── success.php           # 支付成功
│   │   │   ├── cancel.php            # 支付取消
│   │   │   └── orders.php            # 订单列表
│   │   │
│   │   ├── /notice/                  # 通知公告
│   │   │   ├── list.php
│   │   │   └── detail.php
│   │   │
│   │   └── /common/                  # 公共页面
│   │       ├── about.php
│   │       ├── contact.php
│   │       ├── faq.php
│   │       └── help.php
│   │
│   └── .htaccess                     # 路由重写配置
│
├── /institution/                     # 【机构管理后台】
│   ├── index.php                     # 机构后台入口
│   ├── /controllers/                 # 机构后台控制器
│   │   ├── AuthController.php        # 机构登录
│   │   ├── DashboardController.php   # 机构仪表盘
│   │   ├── StudentController.php     # 学员管理
│   │   ├── ExamController.php        # 考试管理
│   │   ├── ReportController.php      # 报表统计
│   │   ├── FinanceController.php     # 财务管理
│   │   ├── ProfileController.php     # 机构资料
│   │   └── SettingController.php     # 设置
│   │
│   ├── /views/                       # 机构后台视图
│   │   ├── /auth/
│   │   │   └── login.php
│   │   │
│   │   ├── /dashboard/
│   │   │   └── index.php             # 机构仪表盘
│   │   │
│   │   ├── /student/
│   │   │   ├── list.php              # 学员列表
│   │   │   ├── bind.php              # 学员绑定
│   │   │   ├── import.php            # 批量导入
│   │   │   └── detail.php            # 学员详情
│   │   │
│   │   ├── /exam/
│   │   │   ├── list.php              # 考试记录
│   │   │   ├── arrange.php           # 安排考试
│   │   │   └── results.php           # 成绩查看
│   │   │
│   │   ├── /report/
│   │   │   ├── overview.php          # 总览报表
│   │   │   ├── student_progress.php  # 学员进度
│   │   │   └── exam_analysis.php     # 考试分析
│   │   │
│   │   ├── /finance/
│   │   │   ├── overview.php          # 财务概览
│   │   │   ├── transactions.php      # 交易记录
│   │   │   └── settlement.php        # 结算管理
│   │   │
│   │   ├── /profile/
│   │   │   ├── info.php              # 基本信息
│   │   │   ├── certificate.php       # 资质认证
│   │   │   └── contact.php           # 联系方式
│   │   │
│   │   └── /setting/
│   │       ├── account.php
│   │       └── notification.php
│   │
│   └── .htaccess
│
├── /admin/                           # 【平台管理后台】
│   ├── index.php                     # 后台入口
│   ├── /controllers/                 # 后台控制器
│   │   ├── AuthController.php        # 管理员登录
│   │   ├── DashboardController.php   # 管理仪表盘
│   │   ├── UserController.php        # 用户管理
│   │   ├── InstitutionController.php # 机构管理
│   │   ├── QuestionController.php    # 题库管理
│   │   ├── CourseController.php      # 课程管理
│   │   ├── ExamController.php        # 考试管理
│   │   ├── OrderController.php       # 订单管理
│   │   ├── FinanceController.php     # 财务管理
│   │   ├── ContentController.php     # 内容管理
│   │   ├── SystemController.php      # 系统设置
│   │   └── ReportController.php      # 数据统计
│   │
│   ├── /views/                       # 后台视图
│   │   ├── /auth/
│   │   │   └── login.php
│   │   │
│   │   ├── /dashboard/
│   │   │   └── index.php             # 管理仪表盘
│   │   │
│   │   ├── /user/
│   │   │   ├── students.php          # 学员管理
│   │   │   ├── admins.php            # 管理员管理
│   │   │   └── roles.php             # 角色权限
│   │   │
│   │   ├── /institution/
│   │   │   ├── list.php              # 机构列表
│   │   │   ├── pending.php           # 待审核机构
│   │   │   ├── detail.php            # 机构详情
│   │   │   └── audit.php             # 审核页面
│   │   │
│   │   ├── /question/
│   │   │   ├── list.php              # 题目列表
│   │   │   ├── add.php               # 添加题目
│   │   │   ├── edit.php              # 编辑题目
│   │   │   ├── import.php            # 批量导入
│   │   │   ├── categories.php        # 分类管理
│   │   │   └── tags.php              # 标签管理
│   │   │
│   │   ├── /course/
│   │   │   ├── list.php              # 课程列表
│   │   │   ├── add.php               # 添加课程
│   │   │   ├── edit.php              # 编辑课程
│   │   │   ├── videos.php            # 视频管理
│   │   │   └── categories.php        # 分类管理
│   │   │
│   │   ├── /exam/
│   │   │   ├── templates.php         # 试卷模板
│   │   │   ├── records.php           # 考试记录
│   │   │   └── settings.php          # 考试设置
│   │   │
│   │   ├── /order/
│   │   │   ├── list.php              # 订单列表
│   │   │   ├── detail.php            # 订单详情
│   │   │   └── refund.php            # 退款处理
│   │   │
│   │   ├── /finance/
│   │   │   ├── overview.php          # 财务概览
│   │   │   ├── settlements.php       # 结算管理
│   │   │   └── statistics.php        # 收支统计
│   │   │
│   │   ├── /content/
│   │   │   ├── notices.php           # 公告管理
│   │   │   ├── banners.php           # Banner管理
│   │   │   ├── pages.php             # 页面管理
│   │   │   └── faq.php               # FAQ管理
│   │   │
│   │   ├── /system/
│   │   │   ├── basic.php             # 基础设置
│   │   │   ├── payment.php           # 支付设置
│   │   │   ├── email.php             # 邮件设置
│   │   │   ├── security.php          # 安全设置
│   │   │   └── logs.php              # 日志查看
│   │   │
│   │   └── /report/
│   │       ├── user_stats.php        # 用户统计
│   │       ├── exam_stats.php        # 考试统计
│   │       ├── revenue_stats.php     # 收入统计
│   │       └── institution_stats.php # 机构统计
│   │
│   └── .htaccess
│
├── /api/                             # 【API接口目录】
│   ├── /v1/                          # API版本1
│   │   ├── /auth/                    # 认证接口
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── logout.php
│   │   │   ├── refresh.php
│   │   │   └── forgot_password.php
│   │   │
│   │   ├── /user/                    # 用户接口
│   │   │   ├── profile.php
│   │   │   ├── update.php
│   │   │   ├── avatar.php
│   │   │   └── password.php
│   │   │
│   │   ├── /exam/                    # 考试接口
│   │   │   ├── list.php              # 考试列表
│   │   │   ├── start.php             # 开始考试
│   │   │   ├── submit.php            # 提交答案
│   │   │   ├── save_progress.php     # 保存进度
│   │   │   ├── result.php            # 获取成绩
│   │   │   └── time.php              # 获取服务器时间
│   │   │
│   │   ├── /question/                # 题目接口
│   │   │   ├── list.php
│   │   │   ├── detail.php
│   │   │   └── search.php
│   │   │
│   │   ├── /practice/                # 练习接口
│   │   │   ├── chapters.php          # 章节列表
│   │   │   ├── questions.php         # 获取题目
│   │   │   ├── submit.php            # 提交答案
│   │   │   └── wrong_book.php        # 错题本操作
│   │   │
│   │   ├── /course/                  # 课程接口
│   │   │   ├── list.php
│   │   │   ├── detail.php
│   │   │   ├── videos.php
│   │   │   ├── progress.php          # 学习进度
│   │   │   └── notes.php             # 学习笔记
│   │   │
│   │   ├── /payment/                 # 支付接口
│   │   │   ├── create_order.php      # 创建订单
│   │   │   ├── stripe_intent.php     # Stripe支付意图
│   │   │   ├── webhook.php           # Stripe回调
│   │   │   ├── cancel.php
│   │   │   └── orders.php            # 订单列表
│   │   │
│   │   ├── /notice/                  # 通知接口
│   │   │   ├── list.php
│   │   │   ├── detail.php
│   │   │   └── unread.php            # 未读数量
│   │   │
│   │   └── /institution/             # 机构接口
│   │       ├── dashboard.php         # 机构仪表盘数据
│   │       ├── students.php          # 学员数据
│   │       └── reports.php           # 报表数据
│   │
│   ├── /webhooks/                    # 第三方Webhook
│   │   ├── stripe.php                # Stripe支付回调
│   │   └── sendgrid.php              # 邮件发送回调
│   │
│   └── .htaccess
│
├── /vendor/                          # 【Composer依赖目录】
│   └── (由Composer管理)
│
├── /tests/                           # 【测试目录】
│   ├── /unit/                        # 单元测试
│   ├── /integration/                 # 集成测试
│   └── /e2e/                         # 端到端测试
│
├── /docs/                            # 【项目文档】
│   ├── api_documentation.md          # API接口文档
│   ├── database_design.md            # 数据库设计文档
│   ├── deployment_guide.md           # 部署指南
│   ├── development_guide.md          # 开发规范
│   └── user_manual.md                # 用户手册
│
└── /scripts/                         # 【脚本工具】
    ├── /cron/                        # 定时任务脚本
    │   ├── daily_report.php          # 每日报表
    │   ├── settlement.php            # 结算处理
    │   └── cleanup.php               # 数据清理
    │
    ├── /deploy/                      # 部署脚本
    │   └── deploy.sh
    │
    └── /maintenance/                 # 维护脚本
        ├── backup.php                # 数据库备份
        └── optimize.php              # 数据库优化
```

---

## 目录说明详解

### 1. 核心配置目录 `/config/`

| 文件 | 用途 |
|------|------|
| `database.php` | MySQL数据库连接配置(主机、端口、用户名、密码、数据库名) |
| `app.php` | 应用基础配置(时区、调试模式、URL模式、分页设置等) |
| `payment.php` | Stripe支付配置(公钥、私钥、Webhook密钥、货币设置) |
| `email.php` | SMTP邮件服务器配置 |
| `storage.php` | 文件存储配置(本地/阿里云OSS/AWS S3) |
| `security.php` | 安全配置(加密密钥、CSRF令牌、IP白名单、登录限制) |
| `routes.php` | URL路由规则配置 |
| `constants.php` | 全局常量定义(状态码、错误码等) |

### 2. 公共函数目录 `/includes/`

| 子目录/文件 | 用途 |
|-------------|------|
| `/functions/` | 全局公共函数库，按功能分类 |
| `/core/` | 框架核心类库，提供基础功能支持 |
| `autoload.php` | PSR-4自动加载配置 |

### 3. 数据模型目录 `/classes/`

| 子目录 | 用途 |
|--------|------|
| `/models/` | MVC中的Model层，负责数据库CRUD操作 |
| `/services/` | 业务逻辑服务层，封装复杂业务逻辑 |
| `/traits/` | PHP Trait，实现代码复用 |

### 4. 多语言目录 `/lang/`

| 子目录 | 用途 |
|--------|------|
| `/zh_CN/` | 简体中文语言包 |
| `/en_US/` | 英文(美国)语言包 |
| `Language.php` | 语言切换处理类，支持自动检测和手动切换 |

### 5. 静态资源目录 `/assets/`

| 子目录 | 用途 |
|--------|------|
| `/css/` | 样式表文件，按模块分子目录 |
| `/js/` | JavaScript文件，按模块分子目录 |
| `/images/` | 图片资源(Logo、图标、Banner等) |
| `/fonts/` | 字体文件 |
| `/libs/` | 第三方前端库源码 |

### 6. 上传文件目录 `/uploads/`

**⚠️ 安全提示：此目录需要设置适当的访问权限**

| 子目录 | 用途 |
|--------|------|
| `/videos/` | 课程视频文件，按课程ID分目录存储 |
| `/images/` | 用户上传的图片(头像、封面、题目配图等) |
| `/documents/` | 课程资料文档 |
| `/exports/` | 系统导出的文件(成绩、报表) |

### 7. 日志目录 `/logs/`

| 子目录 | 用途 |
|--------|------|
| `/app/` | 应用运行日志 |
| `/error/` | PHP错误日志 |
| `/sql/` | SQL执行日志(调试用) |
| `/payment/` | 支付相关日志 |
| `/login/` | 用户登录日志 |
| `/api/` | API请求日志 |

### 8. 视图模板目录 `/templates/`

| 子目录 | 用途 |
|--------|------|
| `/layouts/` | 页面布局模板，定义整体页面结构 |
| `/components/` | 可复用的页面组件(头部、底部、分页等) |
| `/emails/` | 邮件HTML模板 |

### 9. 学生端前台 `/student/`

| 子目录 | 用途 |
|--------|------|
| `/controllers/` | MVC控制器，处理用户请求 |
| `/views/` | 视图文件，渲染HTML页面 |

**主要功能模块：**
- 用户认证(登录/注册/找回密码)
- 个人中心(学习中心/我的课程/学习进度)
- 考试测评(定位测评/阶段测评/全真模拟)
- 练习系统(章节练习/随机练习/错题本)
- 视频课程(课程列表/视频播放)
- 支付系统(订单/支付)

### 10. 机构后台 `/institution/`

| 子目录 | 用途 |
|--------|------|
| `/controllers/` | 机构后台控制器 |
| `/views/` | 机构后台视图 |

**主要功能模块：**
- 机构仪表盘(数据统计概览)
- 学员管理(列表/绑定/导入)
- 考试管理(安排考试/查看成绩)
- 报表统计(学员进度/考试分析)
- 财务管理(交易记录/结算管理)
- 机构资料(信息维护/资质认证)

### 11. 平台管理后台 `/admin/`

| 子目录 | 用途 |
|--------|------|
| `/controllers/` | 平台后台控制器 |
| `/views/` | 平台后台视图 |

**主要功能模块：**
- 管理仪表盘(平台数据总览)
- 用户管理(学员/管理员/角色权限)
- 机构管理(审核/管理)
- 题库管理(题目CRUD/分类/导入)
- 课程管理(课程CRUD/视频/分类)
- 订单管理(订单列表/退款处理)
- 财务管理(结算/统计)
- 内容管理(公告/Banner/页面/FAQ)
- 系统设置(基础/支付/邮件/安全)
- 数据统计(多维度的数据报表)

### 12. API接口目录 `/api/`

| 子目录 | 用途 |
|--------|------|
| `/v1/` | API版本1接口 |
| `/webhooks/` | 第三方Webhook接收端点 |

**API分类：**
- 认证接口：登录/注册/登出/Token刷新
- 用户接口：个人信息/修改/头像/密码
- 考试接口：考试列表/开始/提交/保存进度/成绩
- 题目接口：题目列表/详情/搜索
- 练习接口：章节/题目获取/提交/错题本
- 课程接口：课程列表/详情/视频/进度/笔记
- 支付接口：创建订单/Stripe意图/Webhook
- 通知接口：通知列表/详情/未读数
- 机构接口：仪表盘/学员/报表数据

### 13. 其他重要目录

| 目录 | 用途 |
|------|------|
| `/vendor/` | Composer管理的第三方依赖包 |
| `/tests/` | 单元测试、集成测试、E2E测试 |
| `/docs/` | 项目文档(API文档/数据库设计/部署指南等) |
| `/scripts/` | 定时任务/部署脚本/维护脚本 |
| `/cache/` | 缓存文件(Session/视图/数据缓存) |
| `/database/` | 数据库迁移/种子/备份文件 |

---

## 安全建议

### 1. 目录权限设置

```bash
# 755 - 所有者可读写执行，其他用户只读执行
chmod 755 /cscabridge/

# 777 - 上传目录需要可写权限
chmod 777 /cscabridge/uploads/
chmod 777 /cscabridge/logs/
chmod 777 /cscabridge/cache/

# 644 - 配置文件只读
chmod 644 /cscabridge/config/*.php
```

### 2. .htaccess安全配置

```apache
# 禁止访问敏感目录
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # 禁止直接访问config、includes、classes、logs、cache目录
    RewriteRule ^(config|includes|classes|logs|cache|vendor)/ - [F,L]
    
    # 禁止访问.开头的隐藏文件
    RewriteRule (^|/)\. - [F,L]
    
    # 禁止访问特定文件类型
    <FilesMatch "\.(sql|log|ini|conf|md)$">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>

# 禁用目录浏览
Options -Indexes

# 设置默认字符集
AddDefaultCharset UTF-8
```

### 3. 数据库安全

- 使用预处理语句防止SQL注入
- 数据库用户权限最小化原则
- 敏感数据加密存储(密码、支付信息)
- 定期备份数据库

---

## URL路由设计

### 学生端路由

| URL | 功能 |
|-----|------|
| `/` | 首页 |
| `/login` | 登录 |
| `/register` | 注册 |
| `/dashboard` | 学习中心 |
| `/exam` | 考试首页 |
| `/exam/placement` | 基础定位测评 |
| `/exam/mock` | 全真模拟 |
| `/practice` | 练习首页 |
| `/practice/chapter` | 章节练习 |
| `/practice/wrong-book` | 错题本 |
| `/course` | 课程列表 |
| `/course/123` | 课程详情(ID=123) |
| `/course/123/play` | 视频播放 |
| `/payment/checkout` | 结算页面 |
| `/payment/orders` | 订单列表 |

### 机构后台路由

| URL | 功能 |
|-----|------|
| `/institution/` | 机构仪表盘 |
| `/institution/login` | 机构登录 |
| `/institution/students` | 学员管理 |
| `/institution/exams` | 考试管理 |
| `/institution/reports` | 报表统计 |
| `/institution/finance` | 财务管理 |

### 平台后台路由

| URL | 功能 |
|-----|------|
| `/admin/` | 管理仪表盘 |
| `/admin/login` | 管理员登录 |
| `/admin/users` | 用户管理 |
| `/admin/institutions` | 机构管理 |
| `/admin/questions` | 题库管理 |
| `/admin/courses` | 课程管理 |
| `/admin/orders` | 订单管理 |
| `/admin/finance` | 财务管理 |
| `/admin/system` | 系统设置 |

### API路由

| URL | 功能 |
|-----|------|
| `/api/v1/auth/login` | 用户登录 |
| `/api/v1/exam/start` | 开始考试 |
| `/api/v1/exam/submit` | 提交答案 |
| `/api/v1/payment/create_order` | 创建订单 |
| `/api/webhooks/stripe` | Stripe回调 |

---

## 文件命名规范

1. **类文件**：使用大驼峰命名法，如 `UserController.php`, `ExamService.php`
2. **函数文件**：使用小写下划线命名法，如 `common.php`, `validate.php`
3. **视图文件**：使用小写下划线命名法，如 `login.php`, `my_courses.php`
4. **配置文件**：使用小写下划线命名法，如 `database.php`, `payment.php`
5. **语言文件**：使用小写下划线命名法，如 `common.php`, `errors.php`

---

## 开发环境建议

### 推荐IDE/编辑器
- PHPStorm (推荐)
- VS Code + PHP插件

### 推荐开发工具
- **版本控制**：Git
- **依赖管理**：Composer
- **数据库工具**：phpMyAdmin / Navicat / TablePlus
- **API测试**：Postman
- **代码规范**：PHP_CodeSniffer

### 推荐PHP扩展
- `pdo_mysql` - MySQL数据库连接
- `gd` / `imagick` - 图像处理
- `mbstring` - 多字节字符串处理
- `openssl` - 加密功能
- `curl` - HTTP请求
- `json` - JSON处理
- `fileinfo` - 文件类型检测
- `zip` - 压缩文件处理

---

## 部署检查清单

- [ ] 配置数据库连接信息
- [ ] 配置Stripe支付密钥
- [ ] 配置SMTP邮件服务器
- [ ] 设置目录权限(uploads, logs, cache)
- [ ] 配置Apache重写规则
- [ ] 配置SSL证书(HTTPS)
- [ ] 设置定时任务(cron)
- [ ] 配置错误日志记录
- [ ] 关闭调试模式(生产环境)
- [ ] 配置Web应用防火墙(WAF)

---

*文档版本: 1.0*  
*最后更新: 2024年*  
*作者: CSCA技术团队*
