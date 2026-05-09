<?php

include("../includes/common.php");

//if($conf['reg_open']==0)sysmsg('未开放商户申请');

$csrf_token = md5(mt_rand(0,999).time());
$_SESSION['csrf_token'] = $csrf_token;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<title><?php echo __('find_password')?> | <?php echo $conf['sitename']?></title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>animate.css/3.7.2/animate.min.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css" type="text/css" />
<link rel="stylesheet" href="./assets/css/font.css" type="text/css" />
<link rel="stylesheet" href="./assets/css/app.css" type="text/css" />
<style>input:-webkit-autofill{-webkit-box-shadow:0 0 0px 1000px white inset;-webkit-text-fill-color:#333;}img.logo{width:14px;height:14px;margin:0 5px 0 3px;}</style>
</head>
<body>
<div class="app app-header-fixed  ">
<div class="container w-xxl w-auto-xs" ng-controller="SigninFormController" ng-init="app.settings.container = false;">
<span class="navbar-brand block m-t" id="sitename"><?php echo $conf['sitename']?></span>
<div class="m-b-lg">
<div class="wrapper text-center">
<strong><?php echo __('find_password')?></strong>
</div>
<form name="form" class="form-validation">
<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
<div class="text-danger wrapper text-center" ng-show="authError">
</div>
<div class="list-group list-group-sm swaplogin">
<div class="list-group-item">
<select class="form-control" name="type">
<option value="email"><?php echo __('use_email')?></option><option value="phone"><?php echo __('use_phone')?></option></select>
</div>
<div class="list-group-item">
<input type="text" name="account" placeholder="<?php echo __('email_phone')?>" class="form-control no-border" required>
</div>
<div class="list-group-item">
<div class="input-group">
<input type="text" name="code" placeholder="<?php echo __('enter_code')?>" class="form-control no-border" required>
<a class="input-group-addon" id="sendcode"><?php echo __('get_code')?></a>
</div>
</div>
<div class="list-group-item">
<input type="password" name="pwd" placeholder="<?php echo __('enter_new_password')?>" class="form-control no-border" required>
</div>
<div class="list-group-item">
<input type="password" name="pwd2" placeholder="<?php echo __('re_enter_password')?>" class="form-control no-border" required>
</div>
</div>
<button type="button" id="submit" class="btn btn-lg btn-primary btn-block" ng-click="login()" ng-disabled='form.$invalid'><?php echo __('btn_confirm_submit')?></button>
<a href="login.php" ui-sref="access.signup" class="btn btn-lg btn-default btn-block"><?php echo __('back_to_login')?></a>
</form>
</div>
<div class="text-center">
<p>
<small class="text-muted"><a href="/"><?php echo $conf['sitename']?></a><br>&copy; 2016~<?php echo date("Y")?></small>
</p>
</div>
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/3.4.1/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="<?php echo $cdnpublic?>jsencrypt/3.5.4/jsencrypt.min.js"></script>
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
const PUBLIC_KEY_PEM = `<?php echo base64ToPem($conf['public_key'], 'PUBLIC KEY')?>`;
function invokeSettime(obj){
    var countdown=60;
    settime(obj);
    function settime(obj) {
        if (countdown == 0) {
            $(obj).attr("data-lock", "false");
            $(obj).text("<?php echo __('get_code')?>");
            countdown = 60;
            return;
        } else {
			$(obj).attr("data-lock", "true");
            $(obj).attr("disabled",true);
            $(obj).text("(" + countdown + ") <?php echo __('reg_resend')?>");
            countdown--;
        }
        setTimeout(function() {
                    settime(obj) }
                ,1000)
    }
}
var handlerEmbed = function (captchaObj) {
	var sendto,type;
	captchaObj.onReady(function () {
		$("#wait").hide();
	}).onSuccess(function () {
		var result = captchaObj.getValidate();
		if (!result) {
			return alert('<?php echo __('js_complete_captcha')?>');
		}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax.php?act=sendcode2",
			data : {type:type,sendto:sendto,...result},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					new invokeSettime("#sendcode");
					layer.msg('<?php echo __('js_send_success')?>');
				}else{
					layer.alert(data.msg);
					captchaObj.reset();
				}
			} 
		});
	}).onError(function(){
		layer.msg('<?php echo __('js_captcha_load_failed')?>', {icon: 5});
	});
	$('#sendcode').click(function () {
		if ($(this).attr("data-lock") === "true") return;
		type = $("select[name='type']").val();
		sendto=$("input[name='account']").val();
		if(type=='phone'){
			if(sendto==''){layer.alert('<?php echo __('js_phone_empty')?>');return false;}
			if(sendto.length!=11){layer.alert('<?php echo __('js_phone_invalid')?>');return false;}
		}else{
			if(sendto==''){layer.alert('<?php echo __('js_email_empty')?>');return false;}
			var reg = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/;
			if(!reg.test(sendto)){layer.alert('<?php echo __('js_email_invalid')?>');return false;}
		}
		if(typeof captchaObj.showCaptcha === 'function'){
			captchaObj.showCaptcha();
		}else{
			captchaObj.verify();
		}
	});
};
$(document).ready(function(){
	$("select[name='type']").change(function(){
		if($(this).val() == 'email'){
			$("input[name='account']").attr('placeholder','<?php echo __('email_label')?>');
		}else{
			$("input[name='account']").attr('placeholder','<?php echo __('phone_label')?>');
		}
	});
	$("select[name='type']").change();
	$("#submit").click(function(){
		if ($(this).attr("data-lock") === "true") return;
		var type=$("select[name='type']").val();
		var account=$("input[name='account']").val();
		var code=$("input[name='code']").val();
		var pwd=$("input[name='pwd']").val();
		var pwd2=$("input[name='pwd2']").val();
		if(account=='' || code=='' || pwd=='' || pwd2==''){layer.alert('<?php echo __('js_fields_empty')?>');return false;}
		if(pwd!=pwd2){layer.alert('<?php echo __('js_password_mismatch')?>');return false;}
		if(type=='phone'){
			if(account.length!=11){layer.alert('<?php echo __('js_phone_invalid')?>');return false;}
		}else{
			var reg = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/;
			if(!reg.test(account)){layer.alert('<?php echo __('js_email_invalid')?>');return false;}
		}
		var enc_type = '0';
		if(PUBLIC_KEY_PEM != ''){
			const enc = new JSEncrypt();
			enc.setPublicKey(PUBLIC_KEY_PEM);
			pwd = enc.encrypt(pwd);
			if(pwd) enc_type = '1';
		}
		var csrf_token=$("input[name='csrf_token']").val();
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$(this).attr("data-lock", "true");
		$.ajax({
			type : "POST",
			url : "ajax.php?act=findpwd",
			data : {type:type,account:account,code:code,pwd:pwd,enc:enc_type,csrf_token:csrf_token},
			dataType : 'json',
			success : function(data) {
				$("#submit").attr("data-lock", "false");
				layer.close(ii);
				if(data.code == 1){
					layer.alert(data.msg, {icon: 1}, function(){window.location.href="login.php"});
				}else{
					layer.alert(data.msg);
				}
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
</script>
</body>
</html>