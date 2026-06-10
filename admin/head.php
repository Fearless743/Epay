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
 * 检查菜单项是否对当前管理员可见
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

// 菜单权限映射
$menuPermissions = [
    'index'           => true,
    'order'           => 'order.view',
    'export'          => ['order.view', 'order.export'],
    'buyerstat'       => ['order.view', 'buyerstat'],
    'blacklist'       => 'blacklist',
    'ps_receiver'     => 'profitview',
    'ps_order'        => 'profitview',
    'slist'           => 'settle',
    'settle'          => 'settle',
    'settle_batch'    => 'settle',
    'transfer'        => 'transfer.view',
    'transfer_add'    => 'transfer',
    'transfer_red'    => 'transfer',
    'transfer_batch'  => 'transfer',
    'transfer_stat'   => 'transfer.view',
    'transfer_export' => 'transfer.view',
    'ulist'           => 'user.manage',
    'glist'           => 'user.manage',
    'group'           => 'user.manage',
    'record'          => 'record',
    'ustat'           => 'user.manage',
    'domain'          => 'domain',
    'invitecode'      => 'invitecode',
    'pay_channel'     => 'channel',
    'pay_roll'        => 'channel',
    'pay_type'        => 'paytype',
    'pay_plugin'      => 'plugin',
    'pay_weixin'      => 'channel',
    'set'             => 'settings',
    'clean'           => 'clean',
    'log'             => 'log',
    'risk'            => 'risk',
    'gettoken'        => 'token',
    'update'          => 'update',
    'gonggao'         => 'anounce',
    'admin_manage'    => 'admin.manage',
    'role_manage'     => 'admin.manage',
];
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
          <li class="<?php echo checkIfActive('order,export,ps_receiver,ps_order,buyerstat,blacklist')?>">
            <?php if(menuVisible('order', $menuPermissions)): ?>
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-list"></i> 收款订单<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="./order.php">订单管理</a></li>
              <?php if(menuVisible('export', $menuPermissions)): ?><li><a href="./export.php">导出订单</a></li><?php endif; ?>
              <?php if(menuVisible('buyerstat', $menuPermissions)): ?><li><a href="./buyerstat.php">支付用户统计</a></li><?php endif; ?>
              <?php if(menuVisible('blacklist', $menuPermissions)): ?><li><a href="./blacklist.php">黑名单管理</a></li><?php endif; ?>
              <li role="separator" class="divider"></li>
              <?php if(menuVisible('ps_receiver', $menuPermissions)): ?><li><a href="./ps_receiver.php">分账规则</a></li><li><a href="./ps_order.php">分账记录</a></li><?php endif; ?>
            </ul>
            <?php else: ?>
            <a href="./order.php"><i class="fa fa-list"></i> 收款订单</a>
            <?php endif; ?>
          </li>
          <li class="<?php echo checkIfActive('settle,settle_batch,slist,transfer,transfer_add,transfer_export,transfer_red,transfer_batch,transfer_stat')?>">
            <?php if(menuVisible('slist', $menuPermissions)): ?>
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-cloud"></i> 付款管理<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="./slist.php">结算管理</a></li>
              <?php if(menuVisible('settle', $menuPermissions)): ?><li><a href="./settle.php">批量结算</a></li><?php endif; ?>
              <li role="separator" class="divider"></li>
              <?php if(menuVisible('transfer', $menuPermissions)): ?><li><a href="./transfer.php">付款记录</a></li><li><a href="./transfer_add.php">新增付款</a></li><li><a href="./transfer_red.php">创建红包</a></li><?php endif; ?>
              <?php if(menuVisible('transfer_stat', $menuPermissions)): ?><li><a href="./transfer_stat.php">付款统计</a></li><?php endif; ?>
              <?php if(menuVisible('transfer_export', $menuPermissions)): ?><li><a href="./transfer_export.php">导出付款记录</a></li><?php endif; ?>
              <?php if(class_exists('\\lib\\AlipaySATF\\AlipaySATF')){?><li><a href="./satf_transfer.php">安全发转账记录</a></li><?php }?>
            </ul>
            <?php else: ?>
            <a href="./slist.php"><i class="fa fa-cloud"></i> 付款管理</a>
            <?php endif; ?>
          </li>
		  <li class="<?php echo checkIfActive('ulist,glist,gedit,group,record,uset,domain,ustat,invitecode,uexport')?>">
            <?php if(menuVisible('ulist', $menuPermissions)): ?>
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-user"></i> 商户管理<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="./ulist.php">用户列表</a></li>
			  <li><a href="./glist.php">用户组设置</a></li>
			  <li><a href="./group.php">用户组购买</a></li>
			  <?php if(menuVisible('record', $menuPermissions)): ?><li><a href="./record.php">资金明细</a></li><?php endif; ?>
            <?php if(menuVisible('ustat', $menuPermissions)): ?><li><a href="./ustat.php">支付统计</a></li><?php endif; ?>
            <?php if(menuVisible('domain', $menuPermissions)): ?><?php if($conf['pay_domain_forbid']==1 || $conf['pay_domain_open']==1){?><li><a href="./domain.php">授权域名</a></li><?php } ?><?php endif; ?>
            <?php if(menuVisible('invitecode', $menuPermissions)): ?><?php if($conf['reg_open']==2){?><li><a href="./invitecode.php">邀请码管理</a></li><?php } ?><?php endif; ?>
            </ul>
            <?php else: ?>
            <a href="./ulist.php"><i class="fa fa-user"></i> 商户管理</a>
            <?php endif; ?>
          </li>
		  <li class="<?php echo checkIfActive('pay_channel,pay_roll,pay_type,pay_plugin,pay_weixin,applyments_channel,applyments_merchant,applyments_form')?>">
            <?php if(menuVisible('pay_channel', $menuPermissions)): ?>
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-credit-card"></i> 支付接口<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="./pay_channel.php">支付通道</a></li>
			  <li><a href="./pay_type.php">支付方式</a></li>
			  <li><a href="./pay_plugin.php">支付插件</a></li>
            <?php if(menuVisible('pay_channel', $menuPermissions)): ?><li><a href="./pay_roll.php">支付通道轮询</a></li><?php endif; ?>
            <?php if(menuVisible('pay_channel', $menuPermissions)): ?><li><a href="./pay_weixin.php">公众号小程序</a></li><?php endif; ?>
            <?php if(class_exists('\\lib\\Applyments\\CommUtil')){?><li><a href="./applyments_channel.php">进件渠道管理</a></li>
            <li><a href="./applyments_merchant.php">进件商户管理</a></li><?php }?>
            </ul>
            <?php else: ?>
            <a href="./pay_channel.php"><i class="fa fa-credit-card"></i> 支付接口</a>
            <?php endif; ?>
          </li>
		  <li class="<?php echo checkIfActive('set,gonggao,set_wxkf,update')?>">
            <?php if(menuVisible('set', $menuPermissions)): ?>
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-cog"></i> 系统设置<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="./set.php?mod=site">网站信息配置</a></li>
			  <li><a href="./set.php?mod=pay">支付相关配置</a><li>
            <li><a href="./set.php?mod=risk">风控检测配置</a><li>
            <li><a href="./set.php?mod=settle">结算规则配置</a><li>
			  <li><a href="./set.php?mod=transfer">转账付款配置</a><li>
			  <li><a href="./set.php?mod=oauth">快捷登录配置</a><li>
            <li><a href="./set.php?mod=notice">消息提醒配置</a><li>
			  <li><a href="./set.php?mod=certificate">实名认证配置</a><li>
			  <li><a href="./gonggao.php">网站公告配置</a></li>
			  <li><a href="./set.php?mod=template">首页模板配置</a><li>
			  <li><a href="./set.php?mod=mail">邮箱与短信配置</a><li>
			  <li><a href="./set.php?mod=upimg">网站Logo上传</a><li>
			  <li><a href="./set.php?mod=cron">计划任务配置</a><li>
            <li><a href="./set.php?mod=proxy">中转代理配置</a><li>
            <?php if(menuVisible('update', $menuPermissions)): ?><li><a href="./update.php">系统更新</a></li><?php endif; ?>
            <li><a href="./set_wxkf.php">H5跳转微信客服支付</a></li>
            </ul>
            <?php else: ?>
            <a href="./set.php?mod=site"><i class="fa fa-cog"></i> 系统设置</a>
            <?php endif; ?>
          </li>
          <?php if(menuVisible('admin_manage', $menuPermissions)): ?>
          <li class="<?php echo checkIfActive('admin_manage,role_manage')?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-users"></i> 管理员<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="./admin_manage.php">管理员管理</a></li>
              <li><a href="./role_manage.php">角色权限管理</a></li>
            </ul>
          </li>
          <?php endif; ?>
		  <li class="<?php echo checkIfActive('clean,log,risk,gettoken,complain,complain_info,mchrisk')?>">
            <?php if(menuVisible('log', $menuPermissions) || menuVisible('risk', $menuPermissions) || menuVisible('clean', $menuPermissions)): ?>
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-cube"></i> 其他功能<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <?php if(menuVisible('risk', $menuPermissions)): ?><li><a href="./risk.php">风控记录</a></li><?php endif; ?>
              <?php if(menuVisible('log', $menuPermissions)): ?><li><a href="./log.php">登录日志</a></li><?php endif; ?>
              <?php if(menuVisible('clean', $menuPermissions)): ?><li><a href="./clean.php">数据清理</a></li><?php endif; ?>
            <?php if(menuVisible('token', $menuPermissions)): ?><li><a href="./gettoken.php">获取用户标识</a></li><?php endif; ?>
            <?php if(class_exists('\\lib\\Complain\\CommUtil')){?><li><a href="./complain.php">支付交易投诉</a></li><?php }?>
            <?php if(class_exists('\\lib\\WxMchRisk')){?><li><a href="./mchrisk.php">渠道商户违规记录</a></li><?php }?>
            </ul>
            <?php else: ?>
            <a href="./log.php"><i class="fa fa-cube"></i> 其他功能</a>
            <?php endif; ?>
          </li>
          <li><a href="./login.php?logout" onclick="return confirm('是否确定退出登录？')"><i class="fa fa-power-off"></i> 退出登录</a></li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->
<?php }?>