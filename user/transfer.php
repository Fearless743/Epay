<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title=__('transfer_manage');
include './head.php';
?>
<style>
.fixed-table-toolbar,.fixed-table-pagination{padding: 15px;}
.dates{max-width: 120px;}
</style>
<link href="../assets/css/datepicker.css" rel="stylesheet">
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3"><?php echo __('transfer_manage')?></h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
<?php if(!$conf['user_transfer']) showmsg(__('transfer_not_open', '未开启代付功能'));?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<?php echo __('transfer_list')?>
		</div>
		<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
			<div class="form-group">
				<select name="type" class="form-control"><option value="1"><?php echo __('system_trade_no')?></option><option value="2"><?php echo __('merchant_trade_no')?></option><option value="3"><?php echo __('api_trade_no')?></option><option value="4"><?php echo __('transfer_account')?></option><option value="5"><?php echo __('your_name')?></option><option value="6"><?php echo __('transfer_amount')?></option></select>
			</div>
			<div class="form-group">
				<input type="text" class="form-control" name="kw" placeholder="<?php echo __('search_content')?>" value="">
			</div>
			<div class="input-group input-daterange">
				<input type="text" id="starttime" name="starttime" class="form-control dates" placeholder="<?php echo __('start_date')?>" autocomplete="off">
				<span class="input-group-addon" onclick="$('#starttime').val('');$('#endtime').val('');" title="清除"><i class="fa fa-chevron-right"></i></span>
				<input type="text" id="endtime" name="endtime" class="form-control dates" placeholder="<?php echo __('end_date')?>" autocomplete="off">
			</div>
			<div class="form-group">
				<select name="paytype" class="form-control"><option value=""><?php echo __('all_status')?></option><option value="alipay"><?php echo __('settle_type_alipay')?></option><option value="wxpay"><?php echo __('settle_type_wxpay')?></option><option value="qqpay"><?php echo __('settle_type_qqpay')?></option><option value="bank"><?php echo __('settle_type_bank')?></option></select>
			</div>
			<div class="form-group">
				<select name="dstatus" class="form-control"><option value="-1"><?php echo __('all_status')?></option><option value="0"><?php echo __('settle_status_processing')?></option><option value="1"><?php echo __('status_transfer_success')?></option><option value="2"><?php echo __('status_transfer_failed')?></option><option value="3"><?php echo __('settle_status_pending')?></option></select>
			</div>
			<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> <?php echo __('search')?></button>
			<a href="javascript:searchClear()" class="btn btn-default"><i class="fa fa-refresh"></i> <?php echo __('reset')?></a>
			<div class="btn-group">
				<a href="./transfer_add.php" class="btn btn-success"><i class="fa fa-plus"></i> <?php echo __('add_transfer')?></a>
				<?php if($conf['user_transfer_red']==1){?><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a href="./transfer_red.php"><?php echo __('create_red_packet', '创建红包')?></a></li>
				</ul><?php }?>
			</div>
			<button type="button" onclick="statistics()" class="btn btn-default">&nbsp;<?php echo __('statistics')?>&nbsp;</button>
		</form>
      <table id="listTable">
	  </table>
	</div>
</div>
    </div>
  </div>

<div class="modal" id="modal-statistics" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title"><?php echo __('payment_statistics', '付款统计')?></h4>
			</div>
			<div class="modal-body">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo __('close')?></button>
			</div>
		</div>
	</div>
</div>
<template id="statistics">
<ul class="list-inline" style="margin-bottom: 0;padding-bottom: 10px;border-bottom: 1px solid #dddddd;">
    <li><?php echo __('total_transfer_amount', '总转账金额')?>：<span style="font-weight: 600;">¥ {totalMoney}</span></li>
</ul>
<ul class="list-inline" style="padding-top:10px;margin-bottom: 0;">
	<li><?php echo __('total_count')?>：<span style="font-weight: 600;">{totalCount}</span></li>
    <li><?php echo __('settle_status_processing')?>：<span style="font-weight: 600;">{status0count}</span></li>
    <li><?php echo __('status_transfer_success')?>：<span style="font-weight: 600;">{status1count}</span></li>
    <li><?php echo __('status_transfer_failed')?>：<span style="font-weight: 600;">{status2count}</span></li>
    <li><?php echo __('settle_status_pending')?>：<span style="font-weight: 600;">{status3count}</span></li>
</ul>
</template>

<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
$(document).ready(function(){
	updateToolbar();
	const defaultPageSize = 30;
	const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
	const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

	$("#listTable").bootstrapTable({
		url: 'ajax2.php?act=transferList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: 'biz_no',
				title: '<?php echo __('system_trade_no')?>/<?php echo __('channel_trade_no')?>',
				formatter: function(value, row, index) {
					return '<b>'+value+'</b><br/>'+row.pay_order_no;
				}
			},
			{
				field: 'type',
				title: '<?php echo __('pay_method')?>/<?php echo __('transaction_remark')?>',
				formatter: function(value, row, index) {
					let typename = '';
					if(value == 'alipay'){
						typename='<img src="/assets/icon/alipay.ico" width="16" onerror="this.style.display=\'none\'"><?php echo __('settle_type_alipay')?>';
					}else if(value == 'wxpay'){
						typename='<img src="/assets/icon/wxpay.ico" width="16" onerror="this.style.display=\'none\'"><?php echo __('settle_type_wxpay')?>';
					}else if(value == 'qqpay'){
						typename='<img src="/assets/icon/qqpay.ico" width="16" onerror="this.style.display=\'none\'"><?php echo __('settle_type_qqpay')?>';
					}else if(value == 'bank'){
						typename='<img src="/assets/icon/bank.ico" width="16" onerror="this.style.display=\'none\'"><?php echo __('settle_type_bank')?>';
					}
					return typename+'<br/>'+(row.desc?'<font color="#bf7fef">'+row.desc+'</font>':'')+'';
				}
			},
			{
				field: 'account',
				title: '<?php echo __('transfer_account')?>/<?php echo __('your_name')?>',
				formatter: function(value, row, index) {
					return ''+value+'<br/>'+row.username+'';
				}
			},
			{
				field: 'money',
				title: '<?php echo __('transfer_amount')?>/<?php echo __('cost_amount', '花费金额')?>',
				formatter: function(value, row, index) {
					return '¥<b>'+value+'</b><br/>¥<b>'+row.costmoney+'</b>';
				}
			},
			{
				field: 'paytime',
				title: '<?php echo __('submit_time', '提交时间')?>/<?php echo __('settle_time')?>',
				formatter: function(value, row, index) {
					return (row.addtime ? row.addtime : value)+'<br/>'+value;
				}
			},
			{
				field: 'status',
				title: '<?php echo __('status')?>',
				formatter: function(value, row, index) {
					if(value == '1'){
						return '<font color=green><?php echo __('status_transfer_success')?></font>';
					}else if(value == '2'){
						return '<a href="javascript:showResult(\''+row.biz_no+'\')" title="<?php echo __('failure_reason')?>"><font color=red><?php echo __('status_transfer_failed')?></font></a>';
					}else if(value == '3'){
						return '<font color=blue><?php echo __('settle_status_pending')?></font>';
					}else if(value == '4'){
						return '<font color=#26a7e8><?php echo __('pending_claim', '待领取')?></font><br/><a href="javascript:showQrcode(\''+row.jumpurl+'\',\''+row.type+'\')" class="btn btn-xs btn-success"><i class="fa fa-qrcode"></i> <?php echo __('red_packet_code', '红包码')?></a>';
					}else{
						return '<a href="javascript:queryStatus(\''+row.biz_no+'\')" title="<?php echo __('query_status', '点此查询转账状态')?>"><font color=orange><?php echo __('settle_status_processing')?></font></a>' + (row.jumpurl ? '<br/><a href="javascript:showQrcode(\''+row.jumpurl+'\',\''+row.type+'\')" class="btn btn-xs btn-success"><i class="fa fa-qrcode"></i> <?php echo __('confirm_receipt')?></a>' : '');
					}
				}
			},
		],
	})
})
function statistics(){
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    $.ajax({
        type : 'POST',
        url : 'ajax2.php?act=transfer_statistics',
        data: $('#searchToolbar').serializeArray(),
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                var element = $('#modal-statistics');
                var htmlContent = $("#statistics").html().replace(/\{(\w+)\}/g, function (match, key) {
                    return data.data[key] || '';
                });
                element.find('.modal-body').html(htmlContent);
                element.modal('show');
            }else{
                layer.alert(data.msg);
            }
        },
        error:function(data){
            layer.close(ii);
            layer.msg('服务器错误');
        }
    });
}
function showResult(biz_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax2.php?act=transfer_result&biz_no='+biz_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:0, title:'<?php echo __('failure_reason')?>', shadeClose:true});
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.msg('<?php echo __('server_error')?>');
			return false;
		}
	});
}
function queryStatus(biz_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax2.php?act=transfer_query&biz_no='+biz_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				searchSubmit();
				layer.alert(data.msg, {title:'<?php echo __('query_result', '查询结果')?>'});
			}else{
				layer.alert(data.msg, {icon:2, title:'<?php echo __('query_failed', '查询失败')?>'});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('<?php echo __('server_error')?>');
		}
	});
}
function getProof(biz_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax2.php?act=transfer_proof',
		data : {biz_no:biz_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				if(data.download_url){
					layer.alert('<?php echo __('get_proof_success', '获取转账凭证成功！')?><a href="'+data.download_url+'" target="_blank"><?php echo __('click_download_proof', '点击下载凭证')?></a>', {icon:1, title:'<?php echo __('get_proof', '获取凭证')?>'});
				}else{
					layer.alert(data.msg, {icon:1, title:'获取凭证'});
				}
			}else{
				layer.alert(data.msg, {icon:2, title:'<?php echo __('get_failed', '获取失败')?>'});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('<?php echo __('server_error')?>');
		}
	});
}
function showQrcode(url, type){
	var typename = type == 'alipay' ? '<?php echo __('settle_type_alipay')?>' : '<?php echo __('settle_type_wxpay')?>';
	layer.open({
		type: 1,
		title: typename + '<?php echo __('scan_qr_to_pay', '扫描以下二维码收款')?>',
		skin: 'layui-layer-demo',
		shadeClose: true,
		content: '<div id="qrcode" class="list-group-item text-center"></div>',
		btn: ['<?php echo __('copy')?>', '<?php echo __('close')?>'],
		success: function(){
			$('#qrcode').qrcode({
				text: url,
				width: 230,
				height: 230,
				foreground: "#000000",
				background: "#ffffff",
				typeNumber: -1
			});
		},
		yes: function(index, layero){
			var textarea = document.createElement("textarea");
			textarea.value = url;
			document.body.appendChild(textarea);
			textarea.select();
			document.execCommand("copy");
			document.body.removeChild(textarea);
			layer.msg('<?php echo __('copy_success')?>', {icon: 1, time: 900});
		}
	});
}
$(document).ready(function(){
	$('.input-datepicker, .input-daterange').datepicker({
        format: 'yyyy-mm-dd',
		autoclose: true,
        clearBtn: true,
        language: 'zh-CN'
    });
})
</script>