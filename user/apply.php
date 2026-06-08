<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title=__('withdraw');
include './head.php';
?>
<?php

function display_type($type){
	if($type==1)
		return __('settle_type_alipay');
	elseif($type==2)
		return __('settle_type_wxpay');
	elseif($type==3)
		return __('settle_type_qqpay');
	elseif($type==4)
		return __('settle_type_bank');
	elseif($type==5)
		return __('settle_type_usdt');
	else
		return 1;
}

function convert_type($type){
	if($type==1)
		return 'alipay';
	elseif($type==2)
		return 'wxpay';
	elseif($type==3)
		return 'qqpay';
	elseif($type==4)
		return 'bank';
	elseif($type==5)
		return 'usdt';
	else
		return null;
}

if($conf['settle_open']==0||$conf['settle_open']==1)exit(__('withdraw_not_open'));

if($conf['settle_type']==1){
	$today=date("Y-m-d").' 00:00:00';
	$order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$uid} and status=1 and endtime>='$today' and deleted=0");
	if(!$order_today) $order_today = 0;
	$enable_money=round($userrow['money']-$order_today,2);
	if($enable_money<0)$enable_money=0;
}else{
	$enable_money=$userrow['money'];
}
if($userrow['remain_money'] > 0 && !strpos($userrow['remain_money'], '%') && $enable_money > 0){
	$enable_money = round($enable_money - $userrow['remain_money'], 2);
	if($enable_money<0)$enable_money=0;
}

if(isset($_GET['act']) && $_GET['act']=='do'){
	if($_POST['submit']=='申请提现'){
		if(!checkRefererHost())exit();
		$money=daddslashes(strip_tags($_POST['money']));
		if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money) || $money<=0)exit("<script language='javascript'>alert('".__('js_withdraw_amount_invalid')."');history.go(-1);</script>");
		if($enable_money<$conf['settle_money']){
			exit("<script language='javascript'>alert('".sprintf(__('js_withdraw_min'), $conf['settle_money'])."');history.go(-1);</script>");
		}
		if($money>$enable_money){
			exit("<script language='javascript'>alert('".__('js_withdraw_exceed')."');history.go(-1);</script>");
		}
		if($money<$conf['settle_money']){
			exit("<script language='javascript'>alert('".sprintf(__('js_withdraw_min_amount'), $conf['settle_money'])."');history.go(-1);</script>");
		}
		if($userrow['settle']==0){
			exit("<script language='javascript'>alert('".__('js_withdraw_error')."');history.go(-1);</script>");
		}
		if($conf['settle_maxlimit']>0){
			$a_count = $DB->getColumn('SELECT count(*) FROM pre_settle WHERE uid=:uid AND addtime>=:addtime AND deleted=0', [':uid'=>$uid, ':addtime'=>date('Y-m-d').' 00:00:00']);
			if($a_count >= $conf['settle_maxlimit']){
				exit("<script language='javascript'>alert('".__('js_withdraw_limit')."');history.go(-1);</script>");
			}
		}
		if($conf['settle_rate']>0){
			$fee=round($money*$conf['settle_rate']/100,2);
			if(!empty($conf['settle_fee_min']) && $fee<$conf['settle_fee_min'])$fee=$conf['settle_fee_min'];
			if(!empty($conf['settle_fee_max']) && $fee>$conf['settle_fee_max'])$fee=$conf['settle_fee_max'];
			$realmoney=round($money-$fee, 2);
		}else{
			$realmoney=round($money, 2);
		}
		$data = ['uid'=>$uid, 'type'=>$userrow['settle_id'], 'account'=>$userrow['account'], 'username'=>$userrow['username'], 'money'=>$money, 'realmoney'=>$realmoney, 'addtime'=>'NOW()', 'status'=>0];
		if($userrow['settle_id']==5 && !empty($userrow['usdt_chain'])) $data['usdt_chain'] = $userrow['usdt_chain'];
		if($DB->insert('settle', $data)){
			$settleid=$DB->lastInsertId();
			changeUserMoney($uid, $money, false, '手动提现');
			if($conf['settle_transfer']==1 && $conf['settle_transfermax']>0 && $money>$conf['settle_transfermax']) $conf['settle_transfer']=0;
			$app = convert_type($userrow['settle_id']);
			$channelid = $conf['transfer_'.$app];
			if($conf['settle_transfer']==1 && $channelid > 0){
				$out_biz_no = date("YmdHis").rand(11111,99999);
				$channel = \lib\Channel::get($channelid);
				$result = \lib\Transfer::submit($app, $channel, $out_biz_no, $userrow['account'], $userrow['username'], $realmoney);
				if($result['code']==0){
					$update = ['status'=>1, 'endtime'=>'NOW()', 'transfer_no'=>$out_biz_no, 'transfer_channel'=>$channelid, 'transfer_status'=>1, 'transfer_result'=>$result["orderid"], 'transfer_date'=>$result["paydate"]];
					if(isset($result['wxpackage'])) $update['transfer_ext'] = $result['wxpackage'];
					$DB->update('settle', $update, ['id'=>$settleid]);
					if($result['status'] == 1){
						$msg = __('js_withdraw_success_fund');
					}elseif(isset($result['wxpackage'])){
						if(checkwechat()){
							$jumpurl = $siteurl.'paypage/wxtrans.php?id='.$out_biz_no.'&type=transfer';
							exit("<script language='javascript'>window.location.href='{$jumpurl}';</script>");
						}
						$msg = __('js_withdraw_scan_qr');
					}else{
						$msg = __('js_withdraw_later');
					}
					exit("<script language='javascript'>alert('$msg');window.location.href='./settle.php';</script>");
				}else{
					$message='转账失败 '.$result['msg'];
					$DB->update('settle', ['status'=>3, 'result'=>$result["msg"], 'transfer_status'=>2, 'transfer_result'=>$message], ['id'=>$settleid]);
					\lib\MsgNotice::send('apply', 0, ['uid'=>$uid, 'money'=>$money, 'realmoney'=>$realmoney, 'type'=>display_type($userrow['settle_id']), 'account'=>$userrow['account'], 'username'=>$userrow['username'], 'usdt_chain'=>$userrow['usdt_chain']]);
					exit("<script language='javascript'>alert('".__('js_withdraw_transfer_fail')."');window.location.href='./settle.php';</script>");
				}
			}else{
				\lib\MsgNotice::send('apply', 0, ['uid'=>$uid, 'money'=>$money, 'realmoney'=>$realmoney, 'type'=>display_type($userrow['settle_id']), 'account'=>$userrow['account'], 'username'=>$userrow['username'], 'usdt_chain'=>$userrow['usdt_chain']]);
			}
		}
		exit("<script language='javascript'>alert('".__('js_withdraw_success')."');window.location.href='./settle.php';</script>");
	}
}


?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3"><?php echo __('withdraw')?></h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<?php echo __('withdraw')?>
		</div>
		<div class="panel-body">
			<form class="form-horizontal devform" action="./apply.php?act=do" method="post">
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('withdraw_method')?></label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" value="<?php echo display_type($userrow['settle_id'])?>" disabled><a href="./editinfo.php" class="input-group-addon"><?php echo __('modify_receive_account')?></a></div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('withdraw_account')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" value="<?php echo $userrow['account']?>" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('your_name')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" value="<?php echo $userrow['username']?>" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('current_balance')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" value="<?php echo $userrow['money']?>" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('available_balance')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="tmoney" value="<?php echo $enable_money?>" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('apply_withdraw_amount')?></label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" name="money" value="" required><a href="javascript:inputMoney()" class="input-group-addon"><?php echo __('all')?></a></div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="submit" name="submit" value="<?php echo __('btn_apply_withdraw')?>" class="btn btn-primary form-control"/><br/>
				 </div>
			</form>
			<footer class="panel-footer">
				<div class="col-sm-offset-2 col-sm-6"><br/>
				<h4><span class="glyphicon glyphicon-info-sign"></span><?php echo __('withdraw_notice')?></h4>
					<?php echo __('min_withdraw')?><b><?php echo $conf['settle_money']?></b><?php echo __('yuan')?><br/>
					<?php echo __('withdraw_mode_label')?><?php echo $conf['settle_type']==1?'<b>'.__('withdraw_mode_d1').'</b>':'<b>'.__('withdraw_mode_d0').'</b>';?><br/>
					<?php echo $conf['settle_transfer']==1?__('withdraw_auto_hint'):__('withdraw_manual_hint');?>
				</div>
			</footer>
		</div>
	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>
<script>
function inputMoney(){
	$("input[name='money']").val($("input[name='tmoney']").val());
}
</script>