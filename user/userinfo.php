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

if(!$conf['apiurl'])$conf['apiurl'] = $siteurl;

?>
		<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php echo __('close')?></span>
						</button>
						<h4 class="modal-title"><?php echo __('merchant_private_key_window')?></h4>
					</div>
					<div class="modal-body">
						<div class="form-group"><font color="red"><i class="fa fa-info-circle"></i> <?php echo __('private_key_hint')?></font></div>
						<div class="form-group">
							<label><?php echo __('merchant_private_key')?></label>
							<textarea class="form-control" name="merchant_private_key" rows="5" readonly></textarea>
							<center><a href="javascript:;" class="btn btn-default" data-clipboard-text="" title="<?php echo __('copy')?>" id="merchant_private_key_copy"><i class="fa fa-copy"></i> <?php echo __('copy')?></a></center>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-white" onclick="if(confirm('<?php echo __('close_cannot_query')?>'))$('#myModal').modal('hide');"><?php echo __('close')?></button>
					</div>
				</div>
			</div>
		</div>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">
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
	<li style="width: 25%;" align="center" class="<?php echo $mod=='api'?'active':null?>">
		<a href="userinfo.php?mod=api"><?php echo __('api_info')?></a>
	</li>
	<li style="width: 25%;" align="center" class="<?php echo $mod=='info'?'active':null?>">
		<a href="editinfo.php"><?php echo __('edit_profile')?></a>
	</li>
	<li style="width: 25%;" align="center" class="<?php echo $mod=='account'?'active':null?>">
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
<?php if($mod=='api'){?>
			<form class="form-horizontal devform">
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('api_endpoint')?></label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" value="<?php echo $conf['apiurl']?>" readonly><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $conf['apiurl']?>" title="<?php echo __('copy')?>"><i class="fa fa-copy"></i></a></div></div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('merchant_id_label')?></label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" value="<?php echo $uid?>" readonly><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $uid?>" title="<?php echo __('copy')?>"><i class="fa fa-copy"></i></a></div></div>
					</div>
				</div>
				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4><?php echo __('v1_interface')?></h4></div></div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('merchant_md5_key')?></label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" value="<?php echo $userrow['key']?>" readonly><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $userrow['key']?>" title="<?php echo __('copy')?>"><i class="fa fa-copy"></i></a></div></div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><a href="/doc_old.html" class="btn btn-sm btn-info" target="_blank"><?php echo __('view_v1_doc')?></a>&nbsp;&nbsp;<a href="javascript:resetKey()" class="btn btn-sm btn-danger"><?php echo __('reset_md5_key')?></a>
				 </div>
				</div>
				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4><?php echo __('v2_interface')?></h4></div></div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('platform_public_key')?></label>
					<div class="col-sm-9">
						<div class="input-group"><textarea class="form-control" name="platform_public_key" rows="3" readonly><?php echo $conf['public_key']?></textarea><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $conf['public_key']?>" title="<?php echo __('copy')?>"><i class="fa fa-copy"></i></a></div></div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('merchant_public_key')?></label>
					<div class="col-sm-9">
						<div class="input-group"><textarea class="form-control" name="merchant_public_key" rows="3" readonly><?php echo $userrow['publickey']?></textarea><div class="input-group-addon"><a href="javascript:;" class="copy-btn" title="<?php echo __('copy')?>" onclick="alert('<?php echo __('js_distinguish_keys')?>')"><i class="fa fa-copy"></i></a></div></div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><a href="/doc.html" class="btn btn-sm btn-info" target="_blank"><?php echo __('view_v2_doc')?></a>&nbsp;&nbsp;<?php if($userrow['publickey']){?><a href="javascript:createRsaPair()" class="btn btn-sm btn-danger"><?php echo __('reset_rsa_pair')?></a><?php }else{?><a href="javascript:createRsaPair()" class="btn btn-sm btn-success"><?php echo __('generate_rsa_pair')?></a><?php }?>
				 </div>
				</div>
				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4><?php echo __('sign_method_setting')?></h4></div></div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('sign_method_switch')?></label>
					<div class="col-sm-9">
						<select class="form-control" name="keytype" default="<?php echo $userrow['keytype']?>"><option value="0"><?php echo __('md5_rsa_compat')?></option><option value="1"><?php echo __('rsa_only')?></option></select>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editKeyType" value="<?php echo __('btn_confirm')?>" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>
			</form>
<?php }elseif($mod=='account'){?>
			<form class="form-horizontal devform">
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4><?php echo __('change_login_password')?></h4></div></div>
				<?php if(!empty($userrow['pwd'])){?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('old_password')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="password" name="oldpwd" value="">
					</div>
				</div>
				<?php }?>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('new_password')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="password" name="newpwd" value="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label"><?php echo __('repeat_password')?></label>
					<div class="col-sm-9">
						<input class="form-control" type="password" name="newpwd2" value="">
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="changePwd" value="<?php echo __('btn_change_password')?>" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>
			</form>
<?php }?>
		</div>
	</div>
</div>
</div>
    </div>
  </div>
<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
$(document).ready(function(){
	var items = $("select[default]");
	for (i = 0; i < items.length; i++) {
		$(items[i]).val($(items[i]).attr("default")||0);
	}
	var clipboard = new Clipboard('.copy-btn');
	clipboard.on('success', function (e) {
		layer.msg('<?php echo __('copy_success')?>', {icon: 1});
	});
	clipboard.on('error', function (e) {
		layer.msg('<?php echo __('copy_failed')?>', {icon: 2});
	});
	var clipboard = new Clipboard('#merchant_private_key_copy', {
    	container: document.getElementById('myModal')
	});
	clipboard.on('success', function (e) {
		layer.msg('<?php echo __('copy_success')?>', {icon: 1});
	});
	clipboard.on('error', function (e) {
		layer.msg('<?php echo __('copy_failed')?>', {icon: 2});
	});
	$("#changePwd").click(function(){
		var oldpwd=$("input[name='oldpwd']").val();
		var newpwd=$("input[name='newpwd']").val();
		var newpwd2=$("input[name='newpwd2']").val();
		if(oldpwd==''){layer.alert('<?php echo __('js_old_pwd_empty')?>');return false;}
		if(newpwd==''||newpwd2==''){layer.alert('<?php echo __('js_new_pwd_empty')?>');return false;}
		if(newpwd!=newpwd2){layer.alert('<?php echo __('js_pwd_mismatch')?>');return false;}
		if(oldpwd==newpwd){layer.alert('<?php echo __('js_pwd_same')?>');return false;}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_pwd",
			data : {oldpwd:oldpwd,newpwd:newpwd,newpwd2:newpwd2},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert(data.msg, {icon: 1}, function(){window.location.reload()});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editKeyType").click(function(){
		var keytype=$("select[name='keytype']").val();
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_keytype",
			data : {keytype:keytype},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert('<?php echo __('js_modify_success')?>', {icon:1});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
});
function resetKey(){
	var confirmobj = layer.confirm('<?php echo __('js_reset_key_confirm')?>', {
	  btn: ['<?php echo __('confirm')?>','<?php echo __('cancel')?>']
	}, function(){
		$.ajax({
			type : 'POST',
			url : 'ajax2.php?act=resetKey',
			data : 'submit=do',
			dataType : 'json',
			success : function(data) {
				if(data.code == 0){
					layer.alert('<?php echo __('js_reset_key_success')?>', {icon:1}, function(){window.location.reload()});
				}else{
					layer.alert(data.msg, {icon:2});
				}
			},
			error:function(data){
				layer.msg('<?php echo __('server_error')?>');
				return false;
			}
		});
	}, function(){
		layer.close(confirmobj);
	});
}
function createRsaPair(){
	var confirmobj = layer.confirm('<?php echo __('js_generate_rsa_confirm')?>', {
	  btn: ['<?php echo __('confirm')?>','<?php echo __('cancel')?>']
	}, function(){
		$.ajax({
			type : 'POST',
			url : 'ajax2.php?act=createRsaPair',
			data : 'submit=do',
			dataType : 'json',
			success : function(data) {
				if(data.code == 0){
					$("textarea[name='merchant_private_key']").val(data.private_key);
					$("textarea[name='merchant_public_key']").val(data.public_key);
					$("#merchant_private_key_copy").attr('data-clipboard-text', data.private_key);
					layer.alert('<?php echo __('js_rsa_generate_success')?>', {icon:1}, function(){
						layer.closeAll();
						$('#myModal').modal('show');
					});
				}else{
					layer.alert(data.msg, {icon:2});
				}
			},
			error:function(data){
				layer.msg('<?php echo __('server_error')?>');
				return false;
			}
		});
	}, function(){
		layer.close(confirmobj);
	});
}
</script>