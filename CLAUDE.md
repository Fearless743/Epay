# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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

## Plugin Development Reference

To create a new payment plugin:

1. Create `plugins/{name}/{name}_plugin.php` with class `\\{name}_plugin`
2. Define a static `$info` array with: `name`, `showname`, `type`, `config` (array of field definitions), `submit` (callable)
3. Create `plugins/{name}/inc/` for any supporting files
4. The plugin will be auto-discovered by `\lib\Plugin::getList()`
