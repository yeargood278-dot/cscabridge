# CSCA Bridge - OAuth 配置指南

## 概述

本系统支持以下OAuth登录方式：
- Google (推荐，配置最简单)
- Facebook
- Twitter (X)
- 微信 (需要企业资质)

## 快速配置

### 1. Google OAuth 配置

1. 访问 [Google Cloud Console](https://console.cloud.google.com/)
2. 创建新项目或选择现有项目
3. 启用 **Google+ API** 和 **Google People API**
4. 进入 **Credentials** → **Create Credentials** → **OAuth client ID**
5. 配置授权回调地址: `https://yourdomain.com/api/auth/google.php`
6. 获取 Client ID 和 Client Secret

**环境变量配置:**
```bash
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
```

### 2. Facebook OAuth 配置

1. 访问 [Facebook Developers](https://developers.facebook.com/)
2. 创建新应用
3. 添加 **Facebook Login** 产品
4. 配置有效 OAuth 跳转 URI: `https://yourdomain.com/api/auth/facebook.php`
5. 获取应用编号和应用密钥

**环境变量配置:**
```bash
FACEBOOK_APP_ID=your-app-id
FACEBOOK_APP_SECRET=your-app-secret
```

### 3. Twitter OAuth 配置

1. 访问 [Twitter Developer Portal](https://developer.twitter.com/)
2. 创建新项目
3. 配置用户认证设置
4. 设置回调 URL: `https://yourdomain.com/api/auth/twitter.php`
5. 获取 Client ID 和 Client Secret

**环境变量配置:**
```bash
TWITTER_CLIENT_ID=your-client-id
TWITTER_CLIENT_SECRET=your-client-secret
```

### 4. 微信 OAuth 配置 (需要企业资质)

1. 访问 [微信开放平台](https://open.weixin.qq.com/)
2. 注册开发者账号并完成企业认证
3. 创建网站应用
4. 配置授权回调域: `yourdomain.com`
5. 获取 AppID 和 AppSecret

**环境变量配置:**
```bash
WECHAT_APP_ID=your-app-id
WECHAT_APP_SECRET=your-app-secret
```

## 环境变量配置方式

### 方式1: Apache .htaccess (推荐用于共享主机)

在 `.htaccess` 文件中添加:
```apache
SetEnv GOOGLE_CLIENT_ID your-client-id
SetEnv GOOGLE_CLIENT_SECRET your-client-secret
SetEnv FACEBOOK_APP_ID your-app-id
SetEnv FACEBOOK_APP_SECRET your-app-secret
SetEnv TWITTER_CLIENT_ID your-client-id
SetEnv TWITTER_CLIENT_SECRET your-client-secret
```

### 方式2: PHP 配置文件

创建 `config/oauth.php`:
```php
<?php
putenv('GOOGLE_CLIENT_ID=your-client-id');
putenv('GOOGLE_CLIENT_SECRET=your-client-secret');
// ... 其他配置
```

### 方式3: 服务器环境变量

在服务器配置中设置环境变量:

**Nginx:**
```nginx
fastcgi_param GOOGLE_CLIENT_ID your-client-id;
fastcgi_param GOOGLE_CLIENT_SECRET your-client-secret;
```

## 测试配置

配置完成后，访问登录页面:
- 如果OAuth未配置，社交登录按钮会显示为禁用状态
- 如果配置正确，点击按钮会跳转到对应平台的授权页面

## 常见问题

### "OAuth client was not found" (Google)
- 检查 Client ID 是否正确
- 确保没有多余的空格
- 确认已启用 Google+ API

### "AppID参数错误" (微信)
- 微信需要企业认证，个人开发者无法使用
- 检查 AppID 是否正确
- 确认授权回调域配置正确

### "应用编号无效" (Facebook)
- 检查应用编号是否正确
- 确认应用已发布（不是开发模式）
- 检查有效 OAuth 跳转 URI 配置

## 安全建议

1. 不要在代码中硬编码密钥
2. 使用 HTTPS 进行 OAuth 回调
3. 定期轮换密钥
4. 限制 OAuth 回调域名
5. 启用状态验证（state parameter）

## 开发环境测试

在开发环境中，系统会显示"社交登录功能尚未配置"的提示，这是正常的。如需测试:

1. 配置真实的 OAuth 密钥
2. 或使用邮箱/手机号注册登录进行测试
