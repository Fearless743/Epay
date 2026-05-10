<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title=__('recharge');
include './head.php';
?>
<?php
$urow = $DB->getRow("SELECT uid,gid FROM pre_user WHERE uid='{$conf['reg_pay_uid']}' limit 1");
if(!$urow)exit(__('recharge_merchant_not_exist', '充值收款商户不存在'));
$paytype = \lib\Channel::getTypes($urow['uid'], $urow['gid']);
$csrf_token = md5(mt_rand(0,999).time());
$_SESSION['csrf_token'] = $csrf_token;
?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3"><?php echo __('recharge')?></h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="row">
	<div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
	<?php if(isset($_GET['ok']) && $_GET['ok']==1){
	$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no AND deleted=0 limit 1", [':trade_no'=>$_GET['trade_no']]);
	?>
	<div class="alert alert-success alert-dismissible" role="alert">
	  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	  <?php echo sprintf(__('recharge_success_msg', '恭喜你成功充值 %s 元余额！'), '<strong>'.$order['money'].'</strong>')?>
	</div>
	<?php }?>
	<div class="alert alert-info text-md">
		<p><?php echo __('recharge_warning')?></p>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="fa fa-cny"></i>&nbsp;<?php echo __('recharge')?>
		</div>
		<div class="panel-body">
			<form class="form-horizontal devform">
			<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo __('current_balance')?></label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="rmoney" value="<?php echo $userrow['money']?> <?php echo __('yuan')?>" readonly="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo __('recharge_amount')?></label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="money" value="" autocomplete="off">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo __('recharge_method')?></label>
					<div class="col-sm-8">
						<div class="radio">
						<?php foreach($paytype as $row){?>
						  <label class="i-checks"><input type="radio" name="type" value="<?php echo $row['id']?>" rate="<?php echo $row['rate']?>"><i></i><?php echo $row['showname']?>
						  </label>&nbsp;
						<?php }?>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo __('total_pay')?></label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="need" value="" readonly="">
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-3 col-sm-8"><input type="button" id="submit" value="<?php echo __('btn_recharge')?>" class="btn btn-success form-control"/><br/>
				 </div>
				</div>
			</form>
		</div>
	</div>
	</div>
	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function showneed(){
	var money = parseFloat($("input[name='money']").val());
	var rate = parseFloat($("input[name=type]:checked").attr('rate'));
	if(isNaN(money) || isNaN(rate))return;
	var need = (money + money * (1-rate/100)).toFixed(2);
	$("input[name='need']").val(need)
}
$(document).ready(function(){
	$("input[name=type]:first").attr("checked",true);
	$("input[name='money']").blur(function(){
		showneed()
	});
	$("input[name='type']").click(function(){
		showneed()
	});
	$("#submit").click(function(){
		var csrf_token=$("input[name='csrf_token']").val();
		var money=$("input[name='money']").val();
		var typeid=$("input[name=type]:checked").val();
		if(money==''){
			layer.alert("<?php echo __('js_amount_empty')?>");
			return false;
		}
		var ii = layer.load();
		$.ajax({
			type: "POST",
			dataType: "json",
			data: {money:money, typeid:typeid, csrf_token:csrf_token},
			url: "ajax2.php?act=recharge",
			success: function (data, textStatus) {
				layer.close(ii);
				if (data.code == 0) {
					window.location.href=data.url;
				}else{
					layer.alert(data.msg, {icon: 2});
				}
			},
			error: function (data) {
				layer.msg('<?php echo __('server_error')?>', {icon: 2});
			}
		});
		return false;
	})
});
</script>
