<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title=__('personal_info');
include './head.php';
?>
<?php
$mod=isset($_GET['mod'])?$_GET['mod']:'api';

if(strlen($userrow['phone'])==11){
	$userrow['phone']=substr($userrow['phone'],0,3).'****'.substr($userrow['phone'],7,10);
}

?>
<input type="hidden" id="situation" value="">
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">
		<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">关闭</span>
						</button>
						<h4 class="modal-title"><?php echo __('verify_security')?></h4>
					</div>
					<div class="modal-body">
<?php if($conf['verifytype']==1){?>
<div class="list-group-item"><?php echo __('security_phone')?>：<?php echo $userrow['phone']?></div>
<div class="list-group-item">
<div class="input-group">
<input type="text" name="code" placeholder="<?php echo __('enter_sms_code')?>" class="form-control" required>
<a class="input-group-addon" id="sendcode"><?php echo __('get_code')?></a>
</div>
</div>
<?php }else{?>
<div class="list-group-item"><?php echo __('security_email')?>：<?php echo $userrow['email']?></div>
<div class="list-group-item">
<div class="input-group">
<input type="text" name="code" placeholder="<?php echo __('enter_code')?>" class="form-control" required>
<a class="input-group-addon" id="sendcode"><?php echo __('get_code')?></a>
</div>
</div>
<?php }?>
<button type="button" id="verifycode" class="btn btn-primary btn-block"><?php echo __('btn_confirm_code')?></button>
<div id="embed-captcha"></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-white" data-dismiss="modal"><?php echo __('close')?></button>
					</div>
				</div>
			</div>
		</div>
		<div class="modal inmodal fade" id="myModal2" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php echo __('close')?></span>
						</button>
						<h4 class="modal-title"><?php echo __('modify_security')?></h4>
					</div>
					<div class="modal-body">
<?php if($conf['verifytype']==1){?>
<div class="list-group-item">
<input type="text" name="phone_n" placeholder="<?php echo __('new_phone')?>" class="form-control" required>
</div>
<div class="list-group-item">
<div class="input-group">
<input type="text" name="code_n" placeholder="<?php echo __('enter_sms_code')?>" class="form-control" required>
<a class="input-group-addon" id="sendcode2"><?php echo __('get_code')?></a>
</div>
</div>
<?php }else{?>
<div class="list-group-item">
<input type="email" name="email_n" placeholder="<?php echo __('new_email')?>" class="form-control" required>
</div>
<div class="list-group-item">
<div class="input-group">
<input type="text" name="code_n" placeholder="<?php echo __('enter_code')?>" class="form-control" required>
<a class="input-group-addon" id="sendcode2"><?php echo __('get_code')?></a>
</div>
</div>
<?php }?>
<button type="button" id="editBind" class="btn btn-primary btn-block"><?php echo __('btn_confirm_code')?></button>
<div id="embed-captcha"></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
					</div>
				</div>
			</div>
		</div>
		<div class="modal inmodal fade" id="myModal3" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">关闭</span>
						</button>
						<h4 class="modal-title">修改手机号码</h4>
					</div>
					<div class="modal-body">
<div class="list-group-item">
<input type="text" name="phone_s" placeholder="输入新的手机号码" class="form-control" required>
</div>
<div class="list-group-item">
<div class="input-group">
<input type="text" name="code_s" placeholder="输入短信验证码" class="form-control" required>
<a class="input-group-addon" id="sendcode3">获取验证码</a>
</div>
</div>
<button type="button" id="editBindPhone" class="btn btn-primary btn-block">确定</button>
<div id="embed-captcha"></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
					</div>
				</div>
			</div>
		</div>
<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3"><?php echo __('personal_info')?></h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
<div class="tab-container ng-isolate-scope">
<ul class="nav nav-tabs">
	<li style="width: 25%;" align="center">
		<a href="userinfo.php?mod=api"><?php echo __('api_info')?></a>
	</li>
	<li style="width: 25%;" align="center" class="active">
		<a href="editinfo.php"><?php echo __('edit_profile')?></a>
	</li>
	<li style="width: 25%;" align="center">
		<a href="userinfo.php?mod=account"><?php echo __('change_password')?></a>
	</li>
	<?php if($conf['cert_open']>0){?>
	<li style="width: 25%;" align="center">
		<a href="certificate.php"><?php echo __('real_name')?></a>
	</li>
	<?php }?>
</ul>
	<div class="tab-content">
		<div class="tab-pane ng-scope active">
			<form class="form-horizontal devform">
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4><?php echo __('settle_account_setting')?></h4></div></div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('settle_type')?></label>
					<div class="col-sm-9">
						<select class="form-control" name="stype" default="<?php echo $userrow['settle_id']?>">
						<?php if($conf['settle_alipay']){?><option value="1" input="<?php echo __('settle_type_alipay')?>"><?php echo __('alipay_settle')?></option>
						<?php }if($conf['settle_wxpay']){?><option value="2" input="<?php echo $conf['transfer_wxpay']?'微信OpenId':'微信号';?>"><?php echo __('wxpay_settle')?></option>
						<?php }if($conf['settle_qqpay']){?><option value="3" input="ＱＱ号码"><?php echo __('qqpay_settle')?></option>
						<?php }if($conf['settle_bank']){?><option value="4" input="<?php echo __('settle_type_bank')?>"><?php echo __('bank_settle')?></option>
						<?php }?></select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label" id="typename"><?php echo __('receive_account')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="account" value="<?php echo $userrow['account']?>">
					</div>
				</div>
				<?php if($conf['transfer_wxpay']){?>
				<div class="form-group" style="display:none;" id="getopenid_form">
					<div class="col-sm-offset-2 col-sm-4">
						<a class="btn btn-sm btn-default" id="getopenid"><?php echo __('click_get_wechat_openid')?></a>
					</div>
				</div>
				<?php }?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('real_name_label')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="username" value="<?php echo $userrow['username']?>">
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editSettle" value="<?php echo __('btn_confirm_edit')?>" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>

								
				<?php if($conf['voicenotice'] == 1){?><div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4><?php echo __('voice_speaker_setting')?></h4></div></div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('device_report_api')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="voice_gateway" value="<?php echo $siteurl?>gateway.php" readonly><font color="green">在ini写码配置文件中添加，然后通过写码工具配置到设备中</font>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('device_sn')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="voice_devid" value="<?php echo $userrow['voice_devid']?>">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('order_voice_broadcast')?></label>
					<div class="col-sm-9">
						<select class="form-control" name="voice_order" default="<?php echo $userrow['voice_order']?>"><option value="0"><?php echo __('off')?></option><option value="1"><?php echo __('on')?></option></select><font color="green"><?php echo __('voice_broadcast_hint')?></font>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editVoice" value="<?php echo __('btn_confirm_edit')?>" class="btn btn-primary form-control"/><br/>
				 </div>
				</div><?php }?>

				<?php if($conf['orderprint'] == 1){
					$print_config = unserialize($userrow['print_config']);
				?><div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4><?php echo __('print_setting')?></h4></div></div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('printer_sn')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="print_devid" value="<?php echo $print_config['devid']?>">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('auto_print_switch')?></label>
					<div class="col-sm-9">
						<select class="form-control" name="print_order" default="<?php echo $userrow['print_order']?>"><option value="0"><?php echo __('off')?></option><option value="1"><?php echo __('print_only_qr')?></option><option value="2"><?php echo __('print_all_orders')?></option></select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('print_count')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="number" name="print_count" value="<?php echo $print_config['count']?>" min="1" max="5" placeholder="<?php echo __('print_count_hint')?>">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('auto_voice_on_print')?></label>
					<div class="col-sm-9">
						<select class="form-control" name="print_voice" default="<?php echo $print_config['voice']?>"><option value="0"><?php echo __('no')?></option><option value="1"><?php echo __('yes')?></option></select>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editPrint" value="<?php echo __('btn_confirm_edit')?>" class="btn btn-primary form-control"/><br/>
				 </div>
				</div><?php }?>

				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4><?php echo __('contact_function_setting')?></h4></div></div>
				<?php if($conf['verifytype']==1){?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('phone_label')?></label>
					<div class="col-sm-9">
						<div class="input-group">
						<input class="form-control" type="text" name="phone" value="<?php echo $userrow['phone']?>" disabled>
						<a class="input-group-addon" id="checkbind"><?php echo __('modify_binding')?></a>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('email_label')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="email" value="<?php echo $userrow['email']?>">
					</div>
				</div>
				<?php }else{?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('email_label')?></label>
					<div class="col-sm-9">
						<div class="input-group">
						<input class="form-control" type="text" name="email" value="<?php echo $userrow['email']?>" disabled>
						<a class="input-group-addon" id="checkbind"><?php echo __('modify_binding')?></a>
						</div>
					</div>
				</div>
				<?php if(!empty($conf['sms_appkey'])){?><div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('phone_label')?></label>
					<div class="col-sm-9">
						<div class="input-group">
						<input class="form-control" type="text" name="phone" value="<?php echo $userrow['phone']?>" disabled>
						<a class="input-group-addon" id="bindphone"><?php echo __('modify_binding')?></a>
						</div>
					</div>
				</div>
				<?php }}?>
					<label class="col-sm-2 control-label"><?php echo __('enable_key_login')?></label>
					<div class="col-sm-9">
						<select class="form-control" name="keylogin" default="<?php echo $userrow['keylogin']?>"><option value="0"><?php echo __('off')?></option><option value="1"><?php echo __('on')?></option></select>
					</div>
				</div>
				<?php if($conf['user_refund']==1){?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('order_refund_api')?></label>
					<div class="col-sm-9">
						<select class="form-control" name="refund" default="<?php echo $userrow['refund']?>"><option value="0"><?php echo __('off')?></option><option value="1"><?php echo __('on')?></option></select>
					</div>
				</div><?php }?>
				<?php if($conf['user_transfer']==1){?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('transfer_api')?></label>
					<div class="col-sm-9">
						<select class="form-control" name="transfer" default="<?php echo $userrow['transfer']?>"><option value="0"><?php echo __('off')?></option><option value="1"><?php echo __('on')?></option></select>
					</div>
				</div><?php }?>
				<?php if($conf['settle_open']==1 || $conf['settle_open']==3){?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('reserve_balance')?></label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" name="remain_money" value="<?php echo $userrow['remain_money']?>" placeholder="<?php echo __('reserve_balance_hint')?>"><span class="input-group-addon"><?php echo __('yuan')?></span></div>
					</div>
				</div><?php }?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('max_pay_amount')?></label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" name="pay_maxmoney" value="<?php echo $userrow['pay_maxmoney']?>" placeholder="<?php echo __('max_pay_hint')?>"><span class="input-group-addon"><?php echo __('yuan')?></span></div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('min_pay_amount')?></label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" name="pay_minmoney" value="<?php echo $userrow['pay_minmoney']?>" placeholder="<?php echo __('min_pay_hint')?>"><span class="input-group-addon"><?php echo __('yuan')?></span></div>
					</div>
				</div>
				
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editInfo" value="<?php echo __('btn_confirm_edit')?>" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>

<?php if($conf['wxnotice']==1 || $conf['mailnotice']==1 || $conf['robotnotice']==1){
	$userrow['msgconfig'] = unserialize($userrow['msgconfig']);
?>
				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>消息提醒接收设置：</h4><?php if(!$userrow['wx_uid']&&$conf['wxnotice']==1){?><font color="#ff7373">微信公众号消息需要先绑定微信才可以收到消息提醒</font><?php }?></div></div>
				<?php if($conf['wxnotice_tpl_order'] || $conf['msgconfig_order']){?><div class="form-group">
					<label class="col-sm-2 control-label">新订单通知</label>
					<div class="col-sm-9">
						<select class="form-control" name="notice_order" default="<?php echo $userrow['msgconfig']['order']?>"><option value="0">关闭</option><?php if($conf['wxnotice_tpl_order']){?><option value="1">开启 - 微信公众号</option><?php } if($conf['mailnotice']){?><option value="2">开启 - 邮件</option><?php } if($conf['robotnotice']){?><option value="4">开启 - 群机器人</option><?php }?></select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">通知订单金额大于</label>
					<div class="col-sm-9">
					<div class="input-group"><input class="form-control" type="text" name="notice_order_money" value="<?php echo $userrow['msgconfig']['order_money']?>"><span class="input-group-addon">元</span></div>
					</div>
				</div><?php }?>
				<?php if($conf['wxnotice_tpl_settle'] || $conf['msgconfig_settle']){?><div class="form-group">
					<label class="col-sm-2 control-label">结算通知</label>
					<div class="col-sm-9">
						<select class="form-control" name="notice_settle" default="<?php echo $userrow['msgconfig']['settle']?>"><option value="0">关闭</option><?php if($conf['wxnotice_tpl_settle']){?><option value="1">开启 - 微信公众号</option><?php } if($conf['mailnotice']){?><option value="2">开启 - 邮件</option><?php } if($conf['robotnotice']){?><option value="4">开启 - 群机器人</option><?php }?></select>
					</div>
				</div><?php }?>
				<?php if($conf['wxnotice_tpl_login']){?><div class="form-group">
					<label class="col-sm-2 control-label">登录通知</label>
					<div class="col-sm-9">
						<select class="form-control" name="notice_login" default="<?php echo $userrow['msgconfig']['login']?>"><option value="0">关闭</option><?php if($conf['wxnotice_tpl_login']){?><option value="1">开启 - 微信公众号</option><?php } if($conf['mailnotice']){?><option value="2">开启 - 邮件</option><?php } if($conf['robotnotice']){?><option value="4">开启 - 群机器人</option><?php }?></select>
					</div>
				</div><?php }?>
				<?php if($conf['wxnotice_tpl_complain'] || $conf['msgconfig_complain'] || $conf['sms_tpl_complain']){?><div class="form-group">
					<label class="col-sm-2 control-label">交易投诉通知</label>
					<div class="col-sm-9">
						<select class="form-control" name="notice_complain" default="<?php echo $userrow['msgconfig']['complain']?>"><option value="0">关闭</option><?php if($conf['wxnotice_tpl_complain']){?><option value="1">开启 - 微信公众号</option><?php } if($conf['mailnotice']){?><option value="2">开启 - 邮件</option><?php } if($conf['sms_tpl_complain']){?><option value="3">开启 - 短信</option><?php } if($conf['robotnotice']){?><option value="4">开启 - 群机器人</option><?php }?></select>
					</div>
				</div><?php }?>
				<?php if($conf['msgconfig_mchrisk']){?><div class="form-group">
					<label class="col-sm-2 control-label">渠道商户违规通知</label>
					<div class="col-sm-9">
						<select class="form-control" name="notice_mchrisk" default="<?php echo $userrow['msgconfig']['mchrisk']?>"><option value="0">关闭</option><?php if($conf['mailnotice']){?><option value="2">开启 - 邮件</option><?php }  if($conf['robotnotice']){?><option value="4">开启 - 群机器人</option><?php }?></select>
					</div>
				</div><?php }?>
				<?php if($conf['wxnotice_tpl_balance'] || $conf['msgconfig_balance'] || $conf['sms_tpl_balance']){?><div class="form-group">
					<label class="col-sm-2 control-label">余额不足提醒</label>
					<div class="col-sm-9">
						<select class="form-control" name="notice_balance" default="<?php echo $userrow['msgconfig']['balance']?>"><option value="0">关闭</option><?php if($conf['wxnotice_tpl_balance']){?><option value="1">开启 - 微信公众号</option><?php } if($conf['mailnotice']){?><option value="2">开启 - 邮件</option><?php } if($conf['sms_tpl_balance']){?><option value="3">开启 - 短信</option><?php } if($conf['robotnotice']){?><option value="4">开启 - 群机器人</option><?php }?></select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">余额小于多少时</label>
					<div class="col-sm-9">
					<div class="input-group"><input class="form-control" type="text" name="notice_balance_money" value="<?php echo $userrow['msgconfig']['balance_money']?>" placeholder="如开启余额提醒则必填"><span class="input-group-addon">元</span></div>
					</div>
				</div><?php }?>
				<?php if($conf['robotnotice']){?><div class="form-group">
					<label class="col-sm-2 control-label">群机器人Webhook地址</label>
					<div class="col-sm-9">
					<input class="form-control" type="text" name="notice_msgrobot_url" value="<?php echo $userrow['msgconfig']['msgrobot_url']?>" placeholder="仅支持填写企业微信、钉钉、飞书、Telegram群机器人的Webhook地址">
					</div>
				</div><?php }?>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editMsgConfig" value="确定修改" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>
<?php }?>

<?php
if($conf['user_settings_edit']){
$group_settings=$DB->getColumn("SELECT settings FROM pre_group WHERE gid='{$userrow['gid']}' LIMIT 1");
if(!$group_settings)$group_settings=$DB->getColumn("SELECT settings FROM pre_group WHERE gid=0 LIMIT 1");
$channelinfo = json_decode($userrow['channelinfo'], true);
if($group_settings){
?>
				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>自定义接口信息设置：</h4></div></div>

<?php foreach(explode(',',$group_settings) as $row){
	$arr = explode(':', $row);
	echo '<div class="form-group">
<label class="col-sm-2 control-label">'.$arr[1].'</label>
<div class="col-sm-9"><input type="text" class="form-control" name="setting['.$arr[0].']" value="'.$channelinfo[$arr[0]].'" required></div>
</div>';
}?>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editChannelInfo" value="确定修改" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>
<?php }}?>
				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>支付手续费扣除模式选择：</h4></div></div>
				<div class="form-group has-success">
					<div class="col-sm-offset-2 col-sm-9">
					<div class="alert alert-success">
					1、余额扣费 (经典模式，默认)：例如费率1%，客户购买100元商品，客户需支付100元，卖家到账99元，手续费1元由卖家支付<br>            2、订单加费 (奸商模式)：例如费率1%，客户购买100元商品，客户需支付101元，卖家到账100元，手续费1元由买家支付
					  </div>
						<select class="form-control" name="mode" default="<?php echo $userrow['mode']?>">
								<option value="0">余额扣费</option>
								<option value="1">订单加费</option>
							  </select>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editMode" value="确定修改" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>

				 <div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>第三方账号绑定：</h4></div></div>
				<?php if($conf['login_qq']>0){?>
				<div class="form-group">
					<div class="col-xs-6"><span class="pull-right" style="margin-top:5px"><img src="assets/img/qqpay.png" style="margin-top: -6px;margin-bottom: -2px; height:30px">&nbsp;&nbsp;&nbsp;ＱＱ快捷登录&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
					<div class="col-xs-6">
					<?php if($userrow['qq_uid']){?>
						<a class="btn btn-sm btn-success" disabled title="<?php echo $userrow['qq_uid']?>">已绑定</a>&nbsp;&nbsp;&nbsp;<a class="btn btn-sm btn-danger" href="./connect.php?unbind=1" onclick="return confirm('解绑后将无法通过QQ一键登录，是否确定解绑？');">解绑</a>
					<?php }else{?>
						<a class="btn btn-sm btn-success" href="javascript:connect('qq')">立即绑定</a>
					<?php }?>
					</div>
				</div>
				<?php }?>
				<?php if($conf['login_wx']!=0){?>
				<div class="form-group">
					<div class="col-xs-6"><span class="pull-right" style="margin-top:5px"><img src="assets/img/wxpay.png" style="margin-top: -6px;margin-bottom: -2px; height:30px">&nbsp;&nbsp;&nbsp;&nbsp;微信快捷登录&nbsp;&nbsp;&nbsp;</span></div>
					<div class="col-xs-6">
					<?php if($userrow['wx_uid']){?>
						<a class="btn btn-sm btn-success" disabled title="<?php echo $userrow['wx_uid']?>">已绑定</a>&nbsp;&nbsp;&nbsp;<a class="btn btn-sm btn-danger" href="./wxlogin.php?unbind=1" onclick="return confirm('解绑后将无法通过微信一键登录，是否确定解绑？');">解绑</a>
					<?php }else{?>
						<a class="btn btn-sm btn-success" href="javascript:connect('wx')">立即绑定</a>
					<?php }?>
					</div>
				</div>
				<?php }?>
				<?php if($conf['login_alipay']!=0){?>
				<div class="form-group">
					<div class="col-xs-6"><span class="pull-right" style="margin-top:5px"><img src="assets/img/alipay.png" style="margin-top: -6px;margin-bottom: -2px; height:30px">&nbsp;&nbsp;支付宝快捷登录</span></div>
					<div class="col-xs-6">
					<?php if($userrow['alipay_uid']){?>
						<a class="btn btn-sm btn-success" disabled title="<?php echo $userrow['alipay_uid']?>">已绑定</a>&nbsp;&nbsp;&nbsp;<a class="btn btn-sm btn-danger" href="./oauth.php?unbind=1" onclick="return confirm('解绑后将无法通过支付宝一键登录，是否确定解绑？');">解绑</a>
					<?php }else{?>
						<a class="btn btn-sm btn-success" href="javascript:connect('alipay')">立即绑定</a>
					<?php }?>
					</div>
				</div>
				<?php }?>
			</form>
		</div>
	</div>
</div>
</div>
    </div>
  </div>
<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="//static.geetest.com/static/tools/gt.js"></script>
<script>
window.appendChildOrg = Element.prototype.appendChild;
Element.prototype.appendChild = function() {
    if(arguments[0].tagName == 'SCRIPT'){
        arguments[0].setAttribute('referrerpolicy', 'no-referrer');
    }
    return window.appendChildOrg.apply(this, arguments);
};
</script>
<script src="//static.geetest.com/v4/gt4.js"></script>
<script>
function invokeSettime(obj){
    var countdown=60;
    settime(obj);
    function settime(obj) {
        if (countdown == 0) {
            $(obj).attr("data-lock", "false");
			$(obj).attr("disabled",false);
            $(obj).text("获取验证码");
            countdown = 60;
            return;
        } else {
			$(obj).attr("data-lock", "true");
            $(obj).attr("disabled",true);
            $(obj).text("(" + countdown + ") s 重新发送");
            countdown--;
        }
        setTimeout(function() {
                    settime(obj) }
                ,1000)
    }
}
var handlerEmbed = function (captchaObj) {
	var target;
	captchaObj.onReady(function () {
		$("#wait").hide();
	}).onSuccess(function () {
		var result = captchaObj.getValidate();
		if (!result) {
			return alert('请完成验证');
		}
		var situation=$("#situation").val();
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=sendcode",
			data : {situation:situation,target:target, ...result},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					new invokeSettime("#sendcode");
					new invokeSettime("#sendcode2");
					new invokeSettime("#sendcode3");
					layer.msg('发送成功，请注意查收！');
				}else{
					layer.alert(data.msg);
					captchaObj.reset();
				}
			} 
		});
	}).onError(function(){
		layer.msg('验证码加载失败，请刷新页面重试', {icon: 5});
	});
	$('#sendcode').click(function () {
		if ($(this).attr("data-lock") === "true") return;
		if(typeof captchaObj.showCaptcha === 'function'){
			captchaObj.showCaptcha();
		}else{
			captchaObj.verify();
		}
	});
	$('#sendcode2').click(function () {
		if ($(this).attr("data-lock") === "true") return;
		if($("input[name='phone_n']").length>0){
			target=$("input[name='phone_n']").val();
			if(target==''){layer.alert('手机号码不能为空！');return false;}
			if(target.length!=11){layer.alert('手机号码不正确！');return false;}
		}else{
			target=$("input[name='email_n']").val();
			if(target==''){layer.alert('邮箱不能为空！');return false;}
			var reg = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/;
			if(!reg.test(target)){layer.alert('邮箱格式不正确！');return false;}
		}
		if(typeof captchaObj.showCaptcha === 'function'){
			captchaObj.showCaptcha();
		}else{
			captchaObj.verify();
		}
	});
	$('#sendcode3').click(function () {
		if ($(this).attr("data-lock") === "true") return;
		target=$("input[name='phone_s']").val();
		if(target==''){layer.alert('手机号码不能为空！');return false;}
		if(target.length!=11){layer.alert('手机号码不正确！');return false;}
		if(typeof captchaObj.showCaptcha === 'function'){
			captchaObj.showCaptcha();
		}else{
			captchaObj.verify();
		}
	})
};
$(document).ready(function(){
	var items = $("select[default]");
	for (i = 0; i < items.length; i++) {
		$(items[i]).val($(items[i]).attr("default")||0);
	}
	$("select[name='stype']").change(function(){
		var input = $("select[name='stype'] option:selected").attr("input");
		$("#typename").html(input);
		if($(this).val() == 2){
			$("#getopenid_form").show();
		}else{
			$("#getopenid_form").hide();
		}
	});
	$("select[name='stype']").change();
	$("#editSettle").click(function(){
		var stype=$("select[name='stype']").val();
		var account=$("input[name='account']").val();
		var username=$("input[name='username']").val();
		if(account=='' || username==''){layer.alert('请确保各项不能为空！');return false;}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_settle",
			data : {stype:stype,account:account,username:username},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改成功！', {icon:1});
				}else if(data.code == 2){
					$("#situation").val("settle");
					$('#myModal').modal('show');
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editInfo").click(function(){
		var email=$("input[name='email']").val();
		var keylogin=$("select[name='keylogin']").val();
		var refund=$("select[name='refund']").val();
		var transfer=$("select[name='transfer']").val();
		var remain_money=$("input[name='remain_money']").val();
		var pay_maxmoney=$("input[name='pay_maxmoney']").val();
		var pay_minmoney=$("input[name='pay_minmoney']").val();
		if(pay_maxmoney!='' && isNaN(pay_maxmoney)){layer.alert('最大支付金额必须为数字！');return false;}
		if(pay_minmoney!='' && isNaN(pay_minmoney)){layer.alert('最小支付金额必须为数字！');return false;}
		if(email.length>0){
			var reg = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/;
			if(!reg.test(email)){layer.alert('邮箱格式不正确！');return false;}
		}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_info",
			data : {email:email,keylogin:keylogin,refund:refund,transfer:transfer,remain_money:remain_money, pay_maxmoney:pay_maxmoney,pay_minmoney:pay_minmoney},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改成功！', {icon:1});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editChannelInfo").click(function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		var setting = {};
		$("input[name^='setting']").each(function(i, el) {
			setting[el.name] =$(this).val();
		});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_channel_info",
			data : setting,
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改成功！', {icon:1});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editMode").click(function(){
		var mode=$("select[name='mode']").val();
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_mode",
			data : {mode:mode},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改成功！', {icon:1});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editVoice").click(function(){
		var voice_devid=$("input[name='voice_devid']").val();
		var voice_order=$("select[name='voice_order']").val();
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_voice",
			data : {voice_devid:voice_devid, voice_order:voice_order},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改成功！', {icon:1});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editPrint").click(function(){
		var print_order=$("select[name='print_order']").val();
		var print_devid=$("input[name='print_devid']").val();
		var print_count=$("input[name='print_count']").val();
		var print_voice=$("select[name='print_voice']").val();
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_print",
			data : {print_order:print_order, print_devid:print_devid, print_count:print_count, print_voice:print_voice},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改成功！', {icon:1});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editMsgConfig").click(function(){
		var notice_order=$("select[name='notice_order']").val();
		var notice_settle=$("select[name='notice_settle']").val();
		var notice_login=$("select[name='notice_login']").val();
		var notice_complain=$("select[name='notice_complain']").val();
		var notice_mchrisk=$("select[name='notice_mchrisk']").val();
		var notice_order_money=$("input[name='notice_order_money']").val();
		var notice_balance=$("select[name='notice_balance']").val();
		var notice_balance_money=$("input[name='notice_balance_money']").val();
		var notice_msgrobot_url=$("input[name='notice_msgrobot_url']").val();
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_msgconfig",
			data : {notice_order:notice_order, notice_settle:notice_settle, notice_login:notice_login, notice_complain:notice_complain, notice_mchrisk:notice_mchrisk, notice_order_money:notice_order_money, notice_balance:notice_balance, notice_balance_money:notice_balance_money, notice_msgrobot_url:notice_msgrobot_url},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改成功！', {icon:1});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#checkbind").click(function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "GET",
			url : "ajax2.php?act=checkbind",
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					$("#situation").val("bind");
					$('#myModal2').modal('show');
				}else if(data.code == 2){
					$("#situation").val("mibao");
					$('#myModal').modal('show');
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editBind").click(function(){
		var phone=$("input[name='phone_n']").val();
		var email=$("input[name='email_n']").val();
		var code=$("input[name='code_n']").val();
		if(code==''){layer.alert('请输入验证码！');return false;}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_bind",
			data : {phone:phone,email:email,code:code},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改绑定成功！', {icon:1}, function(){window.location.reload()});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editBindPhone").click(function(){
		var phone=$("input[name='phone_s']").val();
		var code=$("input[name='code_s']").val();
		if(code==''){layer.alert('请输入验证码！');return false;}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_bind",
			data : {phone:phone,code:code},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('修改绑定成功！', {icon:1}, function(){window.location.reload()});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#verifycode").click(function(){
		var code=$("input[name='code']").val();
		var situation=$("#situation").val();
		if(code==''){layer.alert('请输入验证码！');return false;}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=verifycode",
			data : {code:code},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.msg('验证成功！', {icon:1});
					$('#myModal').modal('hide');
					if(situation=='settle'){
						$("#editSettle").click();
					}else if(situation=='mibao'){
						$("#situation").val("bind");
						$('#myModal2').modal('show');
					}else if(situation=='bind'){
						$('#myModal2').modal('hide');
						window.location.reload();
					}
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#bindphone").click(function(){
		$("#situation").val("bindphone");
		$('#myModal3').modal('show');
	});
	$('#getopenid').click(function () {
		if ($(this).attr("data-lock") === "true") return;
		$(this).attr("data-lock", "true");
		$.ajax({
			type : "GET",
			url : "ajax.php?act=qrcode",
			dataType : 'json',
			success : function(data) {
				$('#getopenid').attr("data-lock", "false");
				if(data.code == 0){
					$.openidform = layer.open({
					  type: 1,
					  title: '请使用微信扫描以下二维码',
					  skin: 'layui-layer-demo',
					  anim: 2,
					  shadeClose: true,
					  content: '<div id="qrcode" class="list-group-item text-center"></div>',
					  success: function(){
						$('#qrcode').qrcode({
							text: data.url,
							width: 230,
							height: 230,
							foreground: "#000000",
							background: "#ffffff",
							typeNumber: -1
						});
						$.ostart = true;
						setTimeout('checkopenid()', 2000);
					  },
					  end: function(){
						$.ostart = false;
					  }
					});
				}else{
					layer.alert(data.msg, {icon: 0});
				}
			},
			error:function(data){
				layer.msg('服务器错误', {icon: 2});
				return false;
			}
		});
	});
	$.ajax({
		url: "ajax.php?act=captcha",
		type: "get",
		cache: false,
		dataType: "json",
		success: function (data) {
			if(data.version == 1){
				initGeetest4({
					captchaId: data.gt,
					product: 'bind',
					protocol: 'https://',
					riskType: 'slide',
					hideSuccess: true,
				}, handlerEmbed);
			}else{
				initGeetest({
					width: '100%',
					gt: data.gt,
					challenge: data.challenge,
					new_captcha: data.new_captcha,
					product: "bind",
					offline: !data.success
				}, handlerEmbed);
			}
		}
	});
});
function checkopenid(){
	$.ajax({
		type: "GET",
		dataType: "json",
		url: "ajax.php?act=getopenid",
		success: function (data, textStatus) {
			if (data.code == 0) {
				layer.msg('Openid获取成功');
				layer.close($.openidform);
				$("input[name='account']").val(data.openid);
			}else if($.ostart==true){
				setTimeout('checkopenid()', 2000);
			}else{
				return false;
			}
		},
		error: function (data) {
			layer.msg('服务器错误', {icon: 2});
			return false;
		}
	});
}
function connect(type){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : "POST",
		url : "ajax.php?act=connect",
		data : {type:type, bind:'1'},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				window.location.href = data.url;
			}else{
				layer.alert(data.msg, {icon: 7});
			}
		} 
	});
}
</script>