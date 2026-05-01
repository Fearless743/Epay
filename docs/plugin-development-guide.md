# 彩虹易支付系统 插件开发指南

## 概述

彩虹易支付系统采用插件化架构，支持灵活扩展各种支付方式。每个支付插件都是一个独立的目录，位于 `plugins/` 目录下，遵循特定的命名和结构规范。

## 插件结构

每个插件应该具有以下基本结构：

```
plugins/yourplugin/
├── yourplugin_plugin.php      # 主插件文件（必需）
├── inc/                       # 包含目录（可选）
│   ├── config.php             # 配置文件（常见）
│   └── ...                    # 其他辅助文件
└── cert/                      # 证书目录（某些支付方式需要）
    └── ...
```

## 命名规范

1. **插件目录名称**：必须是小写英文，且与插件类名保持一致
2. **主插件文件**：必须命名为 `{目录名称}_plugin.php`
3. **插件类名**：必须是 `{目录名称}_plugin`（首字母小写，后续单词首字母大写）

例如：
- 目录：`alipay`
- 文件：`alipay_plugin.php`
- 类名：`alipay_plugin`

## 插件主文件结构

每个插件主文件必须包含以下基本结构：

```php
<?php

class yourplugin_plugin
{
    // 插件信息数组
    static public $info = [
        // 基本信息
        'name'        => 'yourplugin', // 必须与目录名称一致
        'showname'    => '您的插件显示名称',
        'author'      => '插件作者',
        'link'        => '插件作者链接（官网）',
        
        // 支持的支付方式
        'types'       => ['yourplugin'], // 支付方式标识数组
        'transtypes'  => ['yourplugin','bank'], // 转账方式标识数组
        
        // 配置输入字段
        'inputs' => [
            'fieldname' => [
                'name' => '字段显示名称',
                'type' => 'input|textarea|select', // 输入类型
                'note' => '字段说明（可选）',
            ],
            // ... 更多字段
        ],
        
        // 其他可选配置
        'select' => [
            'value' => '显示名称', // 用于选择已开启的功能
        ],
        'note' => '<p>使用说明HTML内容</p>',
        'bindwxmp' => true, // 是否支持绑定微信公众号（微信专用）
        'bindwxa' => true,  // 是否支持绑定微信小程序（微信专用）
    ];

    // 插件方法实现
    static public function submit(){ /* ... */ }
    static public function mapi(){ /* ... */ }
    // ... 其他必需方法
}
```

## 必需方法

插件应该实现以下核心方法（根据支付方式而定）：

### 1. `submit()`
处理前端提交的支付请求，返回支付跳转或表单数据

**返回格式**：
- `['type'=>'jump','url'=>'支付链接']` - 页面跳转
- `['type'=>'qrcode','page'=>'模板名称','url'=>'二维码链接']` - 二维码支付
- `['type'=>'html','data'=>'HTML内容']` - 直接返回HTML
- `['type'=>'error','msg'=>'错误信息']` - 错误返回

### 2. `mapi()`
处理后台API调用，支持不同的支付方式（app、jsapi、scan等）

### 3. 根据支付方式的其他方法
- `qrcodepc()` - PC端二维码支付
- `submitpc()` - PC端表单支付
- `submitwap()` - WAP网站支付
- `jsapipay()` - JSAPI支付
- `jspay()` - JSP支付
- `scanpay()` - 扫码支付
- `apppay()` - APP支付
- 以及转账相关方法：`transfer()`, `transfer_query()`, `transfer_proof()`, `balance_query()`

## 全局变量和常量

在插件方法中可以使用以下全局变量：

- `$siteurl` - 网站根URL
- `$channel` - 当前渠道/插件配置信息
- `$order` - 订单信息数组
- `$ordername` - 订单名称
- `$sitename` - 网站名称
- `$conf` - 系统配置
- `$clientip` - 客户端IP
- `$device` - 设备类型（mobile/pc）
- `$mdevice` - 移动设备类型

常用常量：
- `PAY_ROOT` - 支付根目录路径
- `PLUGIN_ROOT` - 插件根目录路径
- `TRADE_NO` - 交易号

## 配置访问

插件配置通过 `$channel` 数组访问：
- `$channel['appid']` - 应用ID
- `$channel['appkey']` - 应用密钥/公钥
- `$channel['appsecret']` - 应用密钥/私钥
- `$channel['appmchid']` - 商户ID
- `$channel['appurl']` - 应用URL

## 使用SDK

大多数官方插件使用对应支付方式的官方SDK。SDK通常放置在：
- `includes/vendor/` - 第三方SDK库
- 插件目录下的 `inc/` 或 vendor 目录

例如，支付宝插件使用：
```php
require(PAY_ROOT.'inc/config.php'); // 系统支付配置
$aop = new \Alipay\AlipayTradeService($alipay_config);
```

## 最佳实践

1. **错误处理**：所有方法应该捕获异常并返回友好的错误信息
2. **安全验证**：验证所有输入数据，特别是回调通知
3. **日志记录**：使用系统日志功能记录关键操作
4. **配置隔离**：将敏密信息（如私钥）存储在插件目录下，避免提交到仓库
5. **兼容性**：考虑不同设备类型（mobile/pc）和支付场景
6. **文档完善**：在 `$info['note']` 中提供清晰的配置说明

## 示例：最小插件

这里是一个最小的插件示例（实际支付功能需要完整实现）：

```php
<?php

class minipay_plugin
{
    static public $info = [
        'name'        => 'minipay',
        'showname'    => '最小支付插件',
        'author'      => '开发者',
        'link'        => 'https://example.com',
        'types'       => ['minipay'],
        'transtypes'  => ['minipay','bank'],
        'inputs' => [
            'appid' => [
                'name' => '应用ID',
                'type' => 'input',
                'note' => '请填写正确的应用ID',
            ],
            'appsecret' => [
                'name' => '应用密钥',
                'type' => 'textarea',
                'note' => '应用密钥或私钥',
            ],
        ],
    ];

    static public function submit(){
        global $siteurl, $channel, $order, $ordername;
        
        // 这里应该实现实际的支付逻辑
        // 例如：调用支付网关SDK生成支付链接
        
        // 返回跳转到支付页面
        return ['type'=>'jump','url'=>$siteurl.'pay/minipay/'.TRADE_NO.'/'];
    }

    static public function mapi(){
        global $siteurl, $channel;
        
        // 处理API请求
        return ['type'=>'jump','url'=>$siteurl.'pay/minipay-api/'.TRADE_NO.'/'];
    }
}
```

## 插件安装和启用

1. 将插件目录上传到 `plugins/` 目录
2. 确保文件夹名称与插件类名匹配
3. 在系统后台 → 支付设置 → 插件管理中启用插件
4. 配置插件所需的参数（APPID、密钥等）
5. 进行测试以确保支付流程正常

## 常见问题

**Q：插件不显示在后台？**
A：检查插件目录名称和文件名是否匹配，确保类名正确。

**Q：配置项不生效？**
A：检查 `$info['inputs']` 配置是否正确，确保后台保存了配置。

**Q：支付回调失败？**
A：检查服务器是否可以被支付平台访问，验证回调URL配置是否正确。

**Q：如何添加新的支付方式？**
A：在 `$info['types']` 和 `$info['transtypes']` 中添加对应标识，并实现相应的处理方法。

## 更新历史

- 2026-04-30：初始版本
