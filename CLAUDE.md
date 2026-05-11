# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## ⚠️ 重要：每次提交代码前必须阅读此文件

在执行 git commit、git push、发布版本等操作前，必须先阅读本文档中的「Release & Update System」章节，严格按照其中的流程执行。

## Project Overview

彩虹易支付 (Epay) — an open-source PHP payment gateway system supporting Alipay, WeChat Pay, QQ Wallet, UnionPay, USDT, and more. Developed by 郑州追梦网络科技有限公司. Requires PHP >= 7.4 and MySQL.

## Build & Deployment

There is **no build system, test framework, or linting tool**. This is a traditional PHP application deployed directly to a web server (Nginx + PHP-FPM). There is no `composer install` step for development — dependencies are pre-vendored in `includes/vendor/`.

- **Installation**: Navigate to `/install/` in a browser. Requires `install/install.lock` to be absent for fresh install, then placed after setup.
- **Database upgrades**: `/install/update.php` when `DB_VERSION` in `includes/common.php` is newer than the stored `conf['version']`.

## Architecture

### Request Routing (Nginx)

Nginx rewrite rules (see `nginx.txt`) map clean URLs to entry points:

| URL Pattern | Entry Point | Purpose |
|---|---|---|
| `/pay/{plugin}/{trade_no}/` | `pay.php` | Payment processing (plugin dispatch) |
| `/submit.php` | `submit.php` | Payment order creation |
| `/api/{action}` | `api.php` | Merchant API (query, settle, order, refund) |
| `/{mod}.html` | `index.php` | Frontend pages (template system) |
| `/doc/{name}.html` | `index.php?doc=` | API documentation pages |

### Bootstrap Chain

Almost every entry point starts with:

```php
require './includes/common.php';
```

`includes/common.php` handles:
1. Constants (`VERSION`, `DB_VERSION`, `SYSTEM_ROOT`, `ROOT`, `PLUGIN_ROOT`, `TEMPLATE_ROOT`)
2. Autoloader registration (`includes/autoloader.php` — PSR-0-like, maps `lib\*` to `includes/lib/*.php`)
3. Optional bot/spider blocking (`includes/txprotect.php`, loaded when `$is_defend = true`)
4. Database config from `config.php` → `lib\PdoHelper` instance (`$DB`)
5. Cache initialization (`$CACHE = new \lib\Cache()`, fetches all `pre_config` rows into `$conf`)
6. Helper functions (`includes/functions.php`) and session/auth (`includes/member.php`)
7. Composer vendor autoloader (`includes/vendor/autoload.php`)
8. RSA key pair auto-generation if missing
9. CDN public path selection based on `$conf['cdnpublic']`

### Three Main Areas

- **`user/`** — Merchant dashboard (login, order management, settlement, settings)
- **`admin/`** — Admin dashboard (site config, user/channel/order management)
- **Root files** — Payment-facing pages (pay, submit, cashdesk, API)

### Core Library Classes (`includes/lib/`)

All in the `lib` namespace:

| Class | Responsibility |
|---|---|
| `Payment` | Sign generation/verification (MD5 or RSA-SHA256), payment page output (`echoDefault`) |
| `Plugin` | Plugin discovery, loading, and payment dispatch (`loadForPay`) |
| `Channel` | Payment channel retrieval with per-user config override |
| `Order` | Order creation, status updates, refund logic |
| `ApiHelper` | API endpoint dispatch for `/api/{action}` via `api/` subdirectory |
| `PdoHelper` | PDO wrapper for all DB operations |
| `Cache` | Reads all `pre_config` into memory |
| `Template` | Template file resolution for frontend and doc pages |
| `Transfer` | Payout/transfer logic |
| `RiskCheck` | Risk control checks |
| `MsgNotice` | Notification dispatch (email, SMS, etc.) |

### Plugin System

Plugins live in `plugins/{name}/`:

```
plugins/
  {name}/
    {name}_plugin.php    # Main plugin class (\{name}_plugin) with static $info array
    inc/                  # Plugin-specific includes
```

- Plugin class discovery: `\lib\Plugin::getConfig($name)` includes the file and reads the static `$info` property.
- Payment dispatch: `\lib\Plugin::loadForPay($s)` parses the URL `{plugin}/{trade_no}/`, loads the order, resolves the channel, and calls the plugin's payment method.
- Each plugin's `$info` defines: `name`, `showname`, `type`, `config` (form fields), `submit` (handler method reference).

### Database Schema

All tables use the `pre_` prefix (configurable via `$dbconfig['dbqz']` in `config.php`). Key tables:

| Table | Purpose |
|---|---|
| `pre_config` | System key-value settings |
| `pre_user` | Merchant accounts |
| `pre_group` | User groups with channel/rate config |
| `pre_order` | Payment orders (key: `trade_no` char(19)) |
| `pre_channel` | Payment channel definitions |
| `pre_subchannel` | Channel sub-configurations per user |
| `pre_type` | Payment types (alipay, wxpay, etc.) |
| `pre_settle` | Settlement/payout records |
| `pre_transfer` | Transfer transactions |
| `pre_domain` | Custom merchant domains |

### API Authentication

The submit/payment API supports two signing methods (set per merchant via `keytype`):
- **MD5**: `sign = md5(sorted_params + merchant_key)`
- **RSA**: `sign = base64(openssl_sign(sorted_params, private_key, SHA256))`

Signing logic is in `\lib\Payment::makeSign()` and `verifySign()`.

### Key Global Variables

After `common.php` loads, these are available everywhere:
- `$DB` — PDO database helper instance
- `$conf` — All system config as associative array
- `$CACHE` — Cache instance
- `$cdnpublic` — CDN base URL for static assets
- `$siteurl` — Current site base URL
- `$date` — Current datetime string

## Security Notes

- `install/install.lock` must exist after installation (blocks re-install)
- `plugins/` and `includes/` directories are denied direct access in Nginx
- `$is_defend = true` triggers bot/spider IP and User-Agent blocking via `txprotect.php`
- Admin passwords are checked for weakness at login
- CSRF tokens are used in login forms
- The `pre_blacklist` table stores blocked IPs/accounts

## Release & Update System

### 发布新版本（必须严格按顺序执行）

当用户说「发布」「推送更新」「发版」「bump version」时，必须执行以下完整流程，不可跳步：

```bash
# 1. 修改 includes/common.php 中的 VERSION 常量（+1）
# 2. git add 相关文件
# 3. git commit -m "具体改动描述"（写清楚改了什么，不要只写 bump version）
# 4. git tag v{VERSION}
# 5. git push origin main --tags
```

⚠️ 单独执行 `git push` 不算发布。必须更新 VERSION、提交、打 tag、推送四步都完成才算发布成功。

⚠️ commit message 必须写明本次变更的实质内容，不能只写 "bump version"。格式示例：`feat(chuangyupay): 重构轮询为批量列表匹配模式，使用系统订单号匹配`

### GitHub Actions Release Workflow

`.github/workflows/release.yml` — triggered on push of `v*` tags:

1. **全量安装包**: `Epay-{tag}.zip` / `.tar.gz`（排除 `.git`、`.github`、`config.php`、`install.lock` 等）
2. **增量更新包**: `Epay-update-{tag}.zip` / `.tar.gz`（仅包含上一个 tag 到当前 tag 之间变更的文件）

发布流程：
```bash
# 1. 修改 includes/common.php 中的 VERSION 常量（必须与 tag 数字一致）
# 2. 提交并推送（commit message 写明具体改动内容）
git add -A && git commit -m "具体改动描述"
git tag v{VERSION}
git push origin main --tags
```

### 后台一键更新 (`admin/ajax_update.php`)

- **检查更新**: 调用 GitHub Releases API 获取所有 release，与本地 `VERSION` 常量比较
- **一键更新**: 逐版本下载增量更新包，解压覆盖（保留 `config.php`），自动更新 `VERSION` 常量和数据库
- **代理支持**: 使用系统「中转代理」设置（`conf['proxy']`）访问 GitHub API，解决国内服务器无法直连 GitHub 的问题
- **`proxyGitHub()`**: 独立的 curl 函数，支持 FOLLOWLOCATION（跟随 GitHub asset 302 重定向）和系统代理配置

## Plugin Development Reference

To create a new payment plugin:

1. Create `plugins/{name}/{name}_plugin.php` with class `\\{name}_plugin`
2. Define a static `$info` array with: `name`, `showname`, `type`, `config` (array of field definitions), `submit` (callable)
3. Create `plugins/{name}/inc/` for any supporting files
4. The plugin will be auto-discovered by `\lib\Plugin::getList()`
