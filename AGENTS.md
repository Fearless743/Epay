# Repository Guidelines

## Project Overview

彩虹易支付系统（Epay）是基于 PHP 的开源免签约支付平台，支持支付宝、微信、QQ 钱包、银联等 60+ 支付渠道的统一接入。

## Project Structure & Module Organization

```
Epay/
├── config.php          # 数据库与站点配置
├── api.php             # 对外支付 API 接口
├── gateway.php         # 支付网关回调入口
├── pay.php / submit.php / submit2.php  # 下单与支付发起
├── cashier.php         # 收银台页面
├── cron.php            # 定时任务（对账、清理等）
├── mapi.php            # 移动端 API
├── admin/              # 后台管理面板（ajax_*.php 为接口，其余为页面）
├── user/               # 用户中心（商户自助操作）
├── paypage/            # 前台支付页面
├── includes/           # 核心库
│   ├── lib/            # 业务类库（Payment、Order、Plugin、Channel 等）
│   ├── lang/           # 国际化语言包（zh.php / en.php）
│   ├── pages/          # 后台公共页面片段
│   ├── vendor/         # Composer 依赖（自动加载）
│   └── composer.json   # PHP 依赖声明
├── plugins/            # 支付插件目录（64 个插件，每个一个目录）
│   └── <plugin>/       # 单个插件，含支付配置与回调逻辑
├── template/           # 前台模板主题（default, index1–index10）
├── assets/             # 静态资源（css/js/img/fonts/vendor）
└── install/            # 安装脚本与 SQL
```

## Build, Test, and Development Commands

```bash
# 安装 Composer 依赖
composer install          # 在 includes/ 目录下执行

# 本地开发服务器（PHP 内置）
php -S 0.0.0.0:8080 -t .

# 数据库初始化
mysql -u root -p < install/install.sql
```

项目无构建工具链，为纯 PHP 应用，直接部署至 Web 服务器即可运行。

## Coding Style & Naming Conventions

- **缩进**：4 空格，不使用 Tab
- **PHP 文件**：小写加连字符或下划线（如 `ajax_order.php`），插件目录名小写（如 `bepusdt`）
- **类名**：PascalCase（如 `Payment.php`、`Plugin.php`）
- **函数/方法**：camelCase（如 `getOrderInfo`）
- **SQL**：表名前缀统一，字段名使用下划线命名
- **前端模板**：保持 HTML/PHP 混编风格，与现有 template 结构一致
- **插件命名**：目录名即插件标识，与 `pay_plugin` 表记录对应

## Testing Guidelines

项目当前无自动化测试框架。提交代码前请手动验证：

- 核心支付流程（下单 → 回调 → 订单状态更新）
- 后台管理关键操作（登录、订单查询、结算）
- 涉及数据库变更时，同步更新 `install/install.sql` 或提供 `update*.sql` 增量脚本

## Commit & Pull Request Guidelines

- **Commit 格式**：`<type>(<scope>): <描述>`，如 `feat(plugin): 新增抖音支付插件`
- **常用 type**：`feat`、`fix`、`update`、`security`、`docs`
- **语言**：描述使用中文或英文均可，保持与项目历史风格一致
- **PR 要求**：说明变更内容与影响范围；涉及支付渠道时标注测试的支付方式；涉及数据库变更时附带 SQL 脚本

## Security & Configuration Tips

- `config.php` 包含数据库凭据，**禁止提交真实密码**到版本控制
- 支付插件涉及密钥与证书，注意 `.gitignore` 敏感文件
- RSA 签名验证位于 `includes/lib/Payment.php`，修改支付逻辑时务必保持签名校验完整性
- 回调接口（`gateway.php`、`plugins/*/notify.php`）必须验证签名，防止伪造回调

## Plugin Development

- 插件位于 `plugins/<name>/`，需在后台「支付插件」中启用
- 核心接口参考 `includes/lib/Plugin.php`
- 插件需实现支付下单、异步通知、查询订单等方法
- 参考现有插件（如 `plugins/epay/`）作为开发模板
