<?php
@header('Content-Type: text/html; charset=UTF-8');

$admin_cdnpublic = 0;
if($admin_cdnpublic==1){
	$cdnpublic = '//lib.baomitu.com/';
}elseif($admin_cdnpublic==2){
	$cdnpublic = 'https://s4.zstatic.net/ajax/libs/';
}elseif($admin_cdnpublic==4){
	$cdnpublic = 'https://cdnjs.snrat.com/ajax/libs/';
}else{
	$cdnpublic = '/assets/vendor/';
}

// 管理员认证（$adminInfo 已由 member.php 设置）
$admin_id = 0;
$admin_username = '';
$admin_role_name = '';
if ($adminInfo) {
    $islogin = 1;
    $admin_id = $adminInfo['id'];
    $admin_username = $adminInfo['username'];
    if (!empty($adminInfo['role_id'])) {
        $role = $DB->find('admin_role', '*', ['id' => $adminInfo['role_id']]);
        $admin_role_name = $role ? $role['name'] : '未知角色';
    }
}

/**
 * 检查单个菜单项是否对当前管理员可见
 */
function menuVisible($page, $menuPermissions) {
    if (!isset($menuPermissions[$page])) return true;
    $required = $menuPermissions[$page];
    if ($required === true) return true;
    if (!is_array($required)) $required = [$required];
    foreach ($required as $perm) {
        if (adminHasPermission($perm)) return true;
    }
    return false;
}

/**
 * 检查一组菜单 key，任意一个可见则返回 true（用于分组外层判断）
 */
function menuGroup(array $pages, $menuPermissions): bool {
    foreach ($pages as $p) {
        if (menuVisible($p, $menuPermissions)) return true;
    }
    return false;
}

// ── 菜单权限映射 ──────────────────────────────────────────────────────────────
// 值为数组时：第一个元素是分组总开关，后续是该页独立权限，末尾为旧版兼容权限
$menuPermissions = [
    'index'           => true,

    // 收款订单（总开关: order）
    'order'           => ['order', 'order.view'],
    'export'          => ['order', 'order.export'],
    'buyerstat'       => ['order', 'order.buyerstat',   'buyerstat'],
    'blacklist'       => ['order', 'order.blacklist',   'blacklist'],
    'ps_receiver'     => ['order', 'order.profitshare', 'profitview'],
    'ps_order'        => ['order', 'order.profitshare', 'profitview'],

    // 付款管理（总开关: payment）
    'slist'           => ['payment', 'settle.list',    'settle'],
    'settle'          => ['payment', 'settle'],
    'settle_batch'    => ['payment', 'settle'],
    'transfer'        => ['payment', 'transfer.view'],
    'transfer_add'    => ['payment', 'transfer.add',    'transfer'],
    'transfer_red'    => ['payment', 'transfer.red',    'transfer'],
    'transfer_batch'  => ['payment', 'transfer_batch'],
    'transfer_stat'   => ['payment', 'transfer.stat',   'transfer.view'],
    'transfer_export' => ['payment', 'transfer.export', 'transfer.view'],

    // 商户管理（总开关: merchant）
    'ulist'           => ['merchant', 'user.list',   'user.manage'],
    'glist'           => ['merchant', 'user.groups', 'user.manage'],
    'group'           => ['merchant', 'user.groups', 'user.manage'],
    'ustat'           => ['merchant', 'ustat',       'user.manage'],
    'record'          => ['merchant', 'record'],
    'domain'          => ['merchant', 'domain'],
    'invitecode'      => ['merchant', 'invitecode'],

    // 支付接口（总开关: payinterface）
    'pay_channel'     => ['payinterface', 'channel'],
    'pay_roll'        => ['payinterface', 'pay_roll',   'channel'],
    'pay_type'        => ['payinterface', 'paytype'],
    'pay_plugin'      => ['payinterface', 'plugin'],
    'pay_weixin'      => ['payinterface', 'pay_weixin', 'channel'],

    // 系统设置（总开关: settings）
    // set.php 页面级：有任意子权限即可访问
    'set'          => ['settings', 'settings.site', 'settings.pay', 'settings.risk',
                       'settings.settle', 'settings.transfer', 'settings.oauth',
                       'settings.notice', 'settings.cert', 'settings.announce',
                       'settings.template', 'settings.mail', 'settings.logo',
                       'settings.cron', 'settings.proxy', 'settings.wxkf'],
    // 各子项（仅控制导航可见性，不做页面级拦截）
    'set_site'     => ['settings', 'settings.site'],
    'set_pay'      => ['settings', 'settings.pay'],
    'set_risk'     => ['settings', 'settings.risk'],
    'set_settle'   => ['settings', 'settings.settle'],
    'set_transfer' => ['settings', 'settings.transfer'],
    'set_oauth'    => ['settings', 'settings.oauth'],
    'set_notice'   => ['settings', 'settings.notice'],
    'set_cert'     => ['settings', 'settings.cert'],
    'set_template' => ['settings', 'settings.template'],
    'set_mail'     => ['settings', 'settings.mail'],
    'set_logo'     => ['settings', 'settings.logo'],
    'set_cron'     => ['settings', 'settings.cron'],
    'set_proxy'    => ['settings', 'settings.proxy'],
    'set_wxkf'     => ['settings', 'settings.wxkf'],
    'update'       => ['settings', 'update'],
    'gonggao'      => ['settings', 'settings.announce', 'anounce'],

    // 其他功能（总开关: misc）
    'clean'        => ['misc', 'clean'],
    'log'          => ['misc', 'log'],
    'risk'         => ['misc', 'risk'],
    'gettoken'     => ['misc', 'token'],

    // 管理员（总开关: admin.manage）
    'admin_manage' => ['admin.manage', 'admin.list'],
    'role_manage'  => ['admin.manage', 'role.list'],
];

// 自动根据当前页面名检查权限，已登录但无权限时拦截
if ($adminInfo) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    if (isset($menuPermissions[$currentPage])) {
        $required = $menuPermissions[$currentPage];
        if ($required !== true) {
            if (!is_array($required)) $required = [$required];
            $hasAccess = false;
            foreach ($required as $perm) {
                if (adminHasPermission($perm)) { $hasAccess = true; break; }
            }
            if (!$hasAccess) {
                header('Location: ./');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
  <meta charset="utf-8"/>
  <meta name="renderer" content="webkit">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?php echo $title ?></title>
  <link href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="../assets/css/bootstrap-table.css?v=1" rel="stylesheet"/>
  <link href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
  <script src="<?php echo $cdnpublic?>modernizr/2.8.3/modernizr.min.js"></script>
  <script src="<?php echo $cdnpublic?>jquery/3.4.1/jquery.min.js"></script>
  <script src="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <!--[if lt IE 9]>
    <script src="<?php echo $cdnpublic?>html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="<?php echo $cdnpublic?>respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body>
<?php if($islogin==1){?>
  <nav class="navbar navbar-fixed-top navbar-default">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">导航按钮</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="./">支付管理中心</a>
      </div><!-- /.navbar-header -->
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav navbar-right">
          <li class="<?php echo checkIfActive('index,')?>">
            <a href="./"><i class="fa fa-home"></i> 平台首页</a>
          </li>
          <?php if(menuGroup(['order','export','buyerstat','blacklist','ps_receiver'], $menuPermissions)): ?>
          <li class="<?php echo checkIfActive('order,export,ps_receiver,ps_order,buyerstat,blacklist')?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-list"></i> 收款订单<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <?php if(menuVisible('order', $menuPermissions)): ?><li><a href="./order.php">订单管理</a></li><?php endif; ?>
              <?php if(menuVisible('export', $menuPermissions)): ?><li><a href="./export.php">导出订单</a></li><?php endif; ?>
              <?php if(menuVisible('buyerstat', $menuPermissions)): ?><li><a href="./buyerstat.php">支付用户统计</a></li><?php endif; ?>
              <?php if(menuVisible('blacklist', $menuPermissions)): ?><li><a href="./blacklist.php">黑名单管理</a></li><?php endif; ?>
              <?php if(menuVisible('ps_receiver', $menuPermissions)): ?><li role="separator" class="divider"></li><li><a href="./ps_receiver.php">分账规则</a></li><li><a href="./ps_order.php">分账记录</a></li><?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>
          <?php if(menuGroup(['slist','settle','transfer','transfer_add','transfer_red','transfer_batch','transfer_stat','transfer_export'], $menuPermissions)): ?>
          <li class="<?php echo checkIfActive('settle,settle_batch,slist,transfer,transfer_add,transfer_export,transfer_red,transfer_batch,transfer_stat')?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-cloud"></i> 付款管理<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <?php if(menuVisible('slist', $menuPermissions)): ?><li><a href="./slist.php">结算管理</a></li><?php endif; ?>
              <?php if(menuVisible('settle', $menuPermissions)): ?><li><a href="./settle.php">批量结算</a></li><?php endif; ?>
              <?php if(menuGroup(['slist','settle'], $menuPermissions) && menuGroup(['transfer','transfer_add','transfer_red','transfer_batch','transfer_stat','transfer_export'], $menuPermissions)): ?><li role="separator" class="divider"></li><?php endif; ?>
              <?php if(menuVisible('transfer', $menuPermissions)): ?><li><a href="./transfer.php">付款记录</a></li><?php endif; ?>
              <?php if(menuVisible('transfer_add', $menuPermissions)): ?><li><a href="./transfer_add.php">新增付款</a></li><?php endif; ?>
              <?php if(menuVisible('transfer_red', $menuPermissions)): ?><li><a href="./transfer_red.php">创建红包</a></li><?php endif; ?>
              <?php if(menuVisible('transfer_batch', $menuPermissions)): ?><li><a href="./transfer_batch.php">批量转账</a></li><?php endif; ?>
              <?php if(menuVisible('transfer_stat', $menuPermissions)): ?><li><a href="./transfer_stat.php">付款统计</a></li><?php endif; ?>
              <?php if(menuVisible('transfer_export', $menuPermissions)): ?><li><a href="./transfer_export.php">导出付款记录</a></li><?php endif; ?>
              <?php if(class_exists('\\lib\\AlipaySATF\\AlipaySATF')){?><li><a href="./satf_transfer.php">安全发转账记录</a></li><?php }?>
            </ul>
          </li>
          <?php endif; ?>
          <?php if(menuGroup(['ulist','glist','record','ustat','domain','invitecode'], $menuPermissions)): ?>
          <li class="<?php echo checkIfActive('ulist,glist,gedit,group,record,uset,domain,ustat,invitecode,uexport')?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-user"></i> 商户管理<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <?php if(menuVisible('ulist', $menuPermissions)): ?><li><a href="./ulist.php">用户列表</a></li><?php endif; ?>
              <?php if(menuVisible('glist', $menuPermissions)): ?><li><a href="./glist.php">用户组设置</a></li><?php endif; ?>
              <?php if(menuVisible('group', $menuPermissions)): ?><li><a href="./group.php">用户组购买</a></li><?php endif; ?>
              <?php if(menuVisible('record', $menuPermissions)): ?><li><a href="./record.php">资金明细</a></li><?php endif; ?>
              <?php if(menuVisible('ustat', $menuPermissions)): ?><li><a href="./ustat.php">支付统计</a></li><?php endif; ?>
              <?php if(menuVisible('domain', $menuPermissions)): ?><?php if($conf['pay_domain_forbid']==1 || $conf['pay_domain_open']==1){?><li><a href="./domain.php">授权域名</a></li><?php } ?><?php endif; ?>
              <?php if(menuVisible('invitecode', $menuPermissions)): ?><?php if($conf['reg_open']==2){?><li><a href="./invitecode.php">邀请码管理</a></li><?php } ?><?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>
          <?php if(menuGroup(['pay_channel','pay_roll','pay_weixin','pay_type','pay_plugin'], $menuPermissions)): ?>
          <li class="<?php echo checkIfActive('pay_channel,pay_roll,pay_type,pay_plugin,pay_weixin,applyments_channel,applyments_merchant,applyments_form')?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-credit-card"></i> 支付接口<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <?php if(menuVisible('pay_channel', $menuPermissions)): ?><li><a href="./pay_channel.php">支付通道</a></li><?php endif; ?>
              <?php if(menuVisible('pay_roll', $menuPermissions)): ?><li><a href="./pay_roll.php">支付通道轮询</a></li><?php endif; ?>
              <?php if(menuVisible('pay_weixin', $menuPermissions)): ?><li><a href="./pay_weixin.php">公众号小程序</a></li><?php endif; ?>
              <?php if(menuVisible('pay_type', $menuPermissions)): ?><li><a href="./pay_type.php">支付方式</a></li><?php endif; ?>
              <?php if(menuVisible('pay_plugin', $menuPermissions)): ?><li><a href="./pay_plugin.php">支付插件</a></li><?php endif; ?>
              <?php if(class_exists('\\lib\\Applyments\\CommUtil')){?><li><a href="./applyments_channel.php">进件渠道管理</a></li><li><a href="./applyments_merchant.php">进件商户管理</a></li><?php }?>
            </ul>
          </li>
          <?php endif; ?>
          <?php if(menuVisible('set', $menuPermissions)): ?>
          <li class="<?php echo checkIfActive('set,gonggao,set_wxkf,update')?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-cog"></i> 系统设置<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <?php if(menuVisible('set_site', $menuPermissions)): ?><li><a href="./set.php?mod=site">网站信息配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_pay', $menuPermissions)): ?><li><a href="./set.php?mod=pay">支付相关配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_risk', $menuPermissions)): ?><li><a href="./set.php?mod=risk">风控检测配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_settle', $menuPermissions)): ?><li><a href="./set.php?mod=settle">结算规则配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_transfer', $menuPermissions)): ?><li><a href="./set.php?mod=transfer">转账付款配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_oauth', $menuPermissions)): ?><li><a href="./set.php?mod=oauth">快捷登录配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_notice', $menuPermissions)): ?><li><a href="./set.php?mod=notice">消息提醒配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_cert', $menuPermissions)): ?><li><a href="./set.php?mod=certificate">实名认证配置</a></li><?php endif; ?>
              <?php if(menuVisible('gonggao', $menuPermissions)): ?><li><a href="./gonggao.php">网站公告配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_template', $menuPermissions)): ?><li><a href="./set.php?mod=template">首页模板配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_mail', $menuPermissions)): ?><li><a href="./set.php?mod=mail">邮箱与短信配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_logo', $menuPermissions)): ?><li><a href="./set.php?mod=upimg">网站Logo上传</a></li><?php endif; ?>
              <?php if(menuVisible('set_cron', $menuPermissions)): ?><li><a href="./set.php?mod=cron">计划任务配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_proxy', $menuPermissions)): ?><li><a href="./set.php?mod=proxy">中转代理配置</a></li><?php endif; ?>
              <?php if(menuVisible('set_wxkf', $menuPermissions)): ?><li><a href="./set_wxkf.php">H5跳转微信客服支付</a></li><?php endif; ?>
              <?php if(menuVisible('update', $menuPermissions)): ?><li><a href="./update.php">系统更新</a></li><?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>
          <?php if(menuVisible('admin_manage', $menuPermissions)): ?>
          <li class="<?php echo checkIfActive('admin_manage,role_manage')?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-users"></i> 管理员<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="./admin_manage.php">管理员管理</a></li>
              <li><a href="./role_manage.php">角色权限管理</a></li>
            </ul>
          </li>
          <?php endif; ?>
          <?php if(menuGroup(['log','risk','clean','gettoken'], $menuPermissions)): ?>
          <li class="<?php echo checkIfActive('clean,log,risk,gettoken,complain,complain_info,mchrisk')?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-cube"></i> 其他功能<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <?php if(menuVisible('risk', $menuPermissions)): ?><li><a href="./risk.php">风控记录</a></li><?php endif; ?>
              <?php if(menuVisible('log', $menuPermissions)): ?><li><a href="./log.php">登录日志</a></li><?php endif; ?>
              <?php if(menuVisible('clean', $menuPermissions)): ?><li><a href="./clean.php">数据清理</a></li><?php endif; ?>
              <?php if(menuVisible('gettoken', $menuPermissions)): ?><li><a href="./gettoken.php">获取用户标识</a></li><?php endif; ?>
              <?php if(class_exists('\\lib\\Complain\\CommUtil')){?><li><a href="./complain.php">支付交易投诉</a></li><?php }?>
              <?php if(class_exists('\\lib\\WxMchRisk')){?><li><a href="./mchrisk.php">渠道商户违规记录</a></li><?php }?>
            </ul>
          </li>
          <?php endif; ?>
          <li><a href="./login.php?logout" onclick="return confirm('是否确定退出登录？')"><i class="fa fa-power-off"></i> 退出登录</a></li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->
<?php }?>