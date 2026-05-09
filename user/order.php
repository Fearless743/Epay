<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title=__('order_list');
include './head.php';
?>
<?php

$type_select = '<option value="0">'.__('pay_method').'</option>';
$rs = $DB->getAll("SELECT * FROM pre_type WHERE status=1 ORDER BY id ASC");
foreach($rs as $row){
	$type_select .= '<option value="'.$row['id'].'">'.$row['showname'].'</option>';
}
unset($rs);

?>
<style>
#orderItem .orderTitle{word-break:keep-all;}
#orderItem .orderContent{word-break:break-all;}
.dates{max-width: 120px;}
.fixed-table-toolbar,.fixed-table-pagination{padding: 15px;}
</style>
<link href="../assets/css/datepicker.css" rel="stylesheet">
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3"><?php echo __('order_list')?></h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<h3 class="panel-title"><?php echo __('order_list')?></h3>
		</div>

	    <form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
		<input type="hidden" name="channel" value="">
		<input type="hidden" name="subchannel" value="">
		<input type="hidden" name="applyid" value="">
	      <div class="form-group">
			<select class="form-control" name="type">
			  <option value="1"><?php echo __('system_trade_no')?></option>
			  <option value="2"><?php echo __('merchant_trade_no')?></option>
			  <option value="9"><?php echo __('api_trade_no')?></option>
			  <option value="10"><?php echo __('user_trade_no')?></option>
			  <option value="11"><?php echo __('channel_trade_no')?></option>
			  <option value="3"><?php echo __('product_name')?></option>
			  <option value="4"><?php echo __('product_amount')?></option>
			  <option value="5"><?php echo __('actual_pay')?></option>
			  <option value="6"><?php echo __('website_domain')?></option>
			<option value="7"><?php echo __('pay_ip')?></option>
			  <option value="8"><?php echo __('pay_account')?></option>
			</select>
		  </div>
			<div class="form-group" id="searchword">
			  <input type="text" class="form-control" name="kw" placeholder="<?php echo __('search_content')?>" style="min-width: 200px;">
			</div>
			<div class="input-group input-daterange">
				<input type="text" id="starttime" name="starttime" class="form-control dates" placeholder="<?php echo __('start_date')?>" autocomplete="off" title="留空则不限时间范围">
				<span class="input-group-addon" onclick="$('#starttime').val('');$('#endtime').val('');" title="清除"><i class="fa fa-chevron-right"></i></span>
				<input type="text" id="endtime" name="endtime" class="form-control dates" placeholder="<?php echo __('end_date')?>" autocomplete="off" title="留空则不限时间范围">
			</div>
			<div class="form-group">
			  <select name="paytype" class="form-control"><?php echo $type_select?></select>
		    </div>
			<div class="form-group">
				<select name="dstatus" class="form-control"><option value="-1"><?php echo __('all_status')?></option><option value="0"><?php echo __('status_unpaid')?></option><option value="1"><?php echo __('status_paid')?></option><option value="2"><?php echo __('status_refunded')?></option><option value="3"><?php echo __('status_frozen')?></option></select>
			</div>
			<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> <?php echo __('search')?></button>
			<a href="javascript:searchClear()" class="btn btn-default"><i class="fa fa-refresh"></i> <?php echo __('reset')?></a>
			<button type="button" onclick="statistics()" class="btn btn-default">&nbsp;<?php echo __('statistics')?>&nbsp;</button>
			<button type="button" onclick="exportOrder()" class="btn btn-default">&nbsp;<?php echo __('export')?>&nbsp;</button>
		</form>
      <table id="listTable">
	  </table>
	</div>
</div>
    </div>
  </div>
  <a style="display: none;" href="" id="vurl" rel="noreferrer" target="_blank"></a>

<div class="modal" id="modal-statistics" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title"><?php echo __('order_stats_overview')?></h4>
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
    <li><?php echo __('total_money')?>：<span style="font-weight: 600;">¥ {totalMoney}</span></li>
    <li><?php echo __('paid_money')?>：<span style="font-weight: 600;">¥ {successMoney}</span></li>
    <li><?php echo __('unpaid_money')?>：<span style="font-weight: 600;">¥ {unpaidMoney}</span></li>
    <li><?php echo __('refund_money')?>：<span style="font-weight: 600;">¥ {refundMoney}</span></li>
    <li><?php echo __('total_profit')?>: <span style="font-weight: 600;">¥ {platformProfit}</li>
</ul>
<ul class="list-inline" style="padding-top:10px;margin-bottom: 0;">
    <li><?php echo __('total_count')?>：<span style="font-weight: 600;">{totalCount}</span></li>
    <li><?php echo __('paid_count')?>：<span style="font-weight: 600;">{successCount}</span></li>
    <li><?php echo __('unpaid_count')?>：<span style="font-weight: 600;">{unpaidCount}</span></li>
    <li><?php echo __('refund_count')?>：<span style="font-weight: 600;">{refundCount}</span></li>
	<li><?php echo __('success_rate_label')?>：<span style="font-weight: 600;">{successRate}%</span></li>
</ul>
</template>

<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
var is_user_refund = '<?php echo $conf['user_refund']?>';
var is_print = '<?php echo $conf['orderprint']==1&&$userrow['print_order']>0?'1':'0';?>';
$(document).ready(function(){
	updateToolbar();
	const defaultPageSize = 20;
	const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
	const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

	$("#listTable").bootstrapTable({
		url: 'ajax2.php?act=orderList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: 'trade_no',
				title: '<?php echo __('system_trade_no')?>/<?php echo __('merchant_trade_no')?>',
				formatter: function(value, row, index) {
					return '<a href="javascript:showOrder(\''+value+'\')" title="<?php echo __('detail')?>">'+value+'</a></b><br/>'+row.out_trade_no;
				}
			},
			{
				field: 'name',
				title: '<?php echo __('product_name')?>'
			},
			{
				field: 'money',
				title: '<?php echo __('product_amount')?>',
				formatter: function(value, row, index) {
					return '¥<b>'+value+'</b>';
				}
			},
			{
				field: 'realmoney',
				title: '<?php echo __('actual_pay')?>',
				formatter: function(value, row, index) {
					return '¥<b>'+value+'</b>';
				}
			},
			{
				field: 'typename',
				title: '<?php echo __('pay_method')?>',
				formatter: function(value, row, index) {
					var html = value ? '<b><img src="/assets/icon/'+value+'.ico" width="16" onerror="this.style.display=\'none\'">'+row.typeshowname+'</b>' : '';
					if(row.subchannel > 0 && row.submchid > 0){
						html += '('+row.submchid+')';
					}
					return html;
				}
			},
			{
				field: 'addtime',
				title: '<?php echo __('create_time')?>/<?php echo __('finish_time')?>',
				formatter: function(value, row, index) {
					return value+'<br/>'+(row.endtime??'&nbsp;');
				}
			},
			{
				field: 'status',
				title: '<?php echo __('pay_status')?>',
				formatter: function(value, row, index) {
					if(value == '1'){
						text = '<font color=green><?php echo __('status_paid')?></font>';
					}else if(value == '2'){
						text = '<font color=red><?php echo __('status_refunded')?></font>';
						if(row.refundmoney > 0 && row.refundmoney < row.realmoney){
							text += '<br/><font color=red>('+row.refundmoney+')</font>';
						}
					}else if(value == '3'){
						text = '<font color=red><?php echo __('status_frozen')?></font>';
					}else if(value == '4'){
						text = '<font color=orange><?php echo __('status_preauth')?></font>';
					}else{
						text = '<font color=blue><?php echo __('status_unpaid')?></font>';
					}
					if(row.plugin=='alipayd'){
						if(row.settle == '1'){
							text += '<br/><font color=#8c8f93><?php echo __('status_pending_settle')?></font>';
						}else if(row.settle == '2'){
							text += '<br/><font color=#37db3c><?php echo __('status_settle_success')?></font>';
						}else if(row.settle == '3'){
							text += '<br/><font color=#ed6565><?php echo __('status_settle_failed')?></font>';
						}
					}else if(row.plugin=='alipayrp'){
						if(row.settle == '1'){
							text += '<br/><font color=#8c8f93><?php echo __('status_pending_transfer')?></font>';
						}else if(row.settle == '2'){
							text += '<br/><font color=#37db3c><?php echo __('status_transfer_success')?></font>';
						}else if(row.settle == '3'){
							text += '<br/><font color=#ed6565><?php echo __('status_transfer_failed')?></font>';
						}
					}
					return text;
				}
			},
			{
				field: '',
				title: '<?php echo __('operation')?>',
				formatter: function(value, row, index) {
					var html = '<a href="./record.php?type=3&kw='+row.trade_no+'" class="btn btn-info btn-xs"><?php echo __('detail')?></a>&nbsp;<a href="javascript:callnotify(\''+row.trade_no+'\')" class="btn btn-success btn-xs"><?php echo __('btn_renotify')?></a>';
					if(is_user_refund=='1' && (row.status=='1' || row.status=='3' || row.status=='2' && row.refundmoney > 0 && row.refundmoney < row.realmoney)){
						html += '&nbsp;<a href="javascript:refund(\''+row.trade_no+'\')" class="btn btn-danger btn-xs"><?php echo __('btn_refund')?></a>';
					}
					if(is_print=='1'&&row.status=='1'){
						html += '&nbsp;<a href="javascript:void(0);" onclick="printOrder(\''+row.trade_no+'\')" class="btn btn-warning btn-xs"><?php echo __('btn_print')?></a>';
					}
					return html;
				}
			},
		],
	});

	$('.input-datepicker, .input-daterange').datepicker({
        format: 'yyyy-mm-dd',
		autoclose: true,
        clearBtn: true,
        language: 'zh-CN'
    });
})

function callnotify(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax2.php?act=notify',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#vurl").attr("href",data.url);
				document.getElementById("vurl").click();
				listTable();
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('<?php echo __('server_error')?>');
		}
	});
	return false;
}
function callreturn(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax2.php?act=notify',
		data : {trade_no:trade_no,isreturn:1},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#vurl").attr("href",data.url);
				document.getElementById("vurl").click();
				listTable();
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('<?php echo __('server_error')?>');
		}
	});
	return false;
}
function refund(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax2.php?act=refund_query',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.open({
					area: ['360px'],
					title: '<?php echo __('confirm_refund')?>',
					content: '<p><?php echo __('refund_hint')?></p><div class="form-group"><div class="input-group"><div class="input-group-addon"><?php echo __('refund_amount')?></div><input type="text" class="form-control" name="refund2" value="'+data.money+'" placeholder="<?php echo __('enter_refund_amount')?>" autocomplete="off"/></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon"><?php echo __('login_pwd')?></div><input type="text" class="form-control" name="paypwd" value="" placeholder="<?php echo __('enter_login_pwd')?>" autocomplete="off"/></div></div>',
					yes: function(){
						var money = $("input[name='refund2']").val();
						var paypwd = $("input[name='paypwd']").val();
						if(money == '' || paypwd == ''){
							layer.alert('<?php echo __('js_amount_pwd_empty')?>');return;
						}
						var ii = layer.load(2, {shade:[0.1,'#fff']});
						$.ajax({
							type : 'POST',
							url : 'ajax2.php?act=refund_submit',
							data : {trade_no:trade_no, money:money, pwd:paypwd},
							dataType : 'json',
							success : function(data) {
								layer.close(ii);
								if(data.code == 0){
									layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
								}else{
									layer.alert(data.msg, {icon:7});
								}
							},
							error:function(data){
								layer.close(ii);
								layer.msg('<?php echo __('server_error')?>');
							}
						});
					}
				});
			}else{
				layer.alert(data.msg, {icon:7});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('<?php echo __('server_error')?>');
		}
	});
}
function showOrder(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	var status = ['<span class="label label-primary"><?php echo __('status_unpaid')?></span>','<span class="label label-success"><?php echo __('status_paid')?></span>','<span class="label label-danger"><?php echo __('status_refunded')?></span>','<span class="label label-info"><?php echo __('status_frozen')?></span>','<span class="label label-warning"><?php echo __('status_preauth')?></span>'];
	$.ajax({
		type : 'GET',
		url : 'ajax2.php?act=order&trade_no='+trade_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				var data = data.data;
				var item = '<table class="table table-condensed table-hover" id="orderItem">';
				item += '<tr><td colspan="6" style="text-align:center" class="orderTitle"><b>订单信息</b></td></tr>';
				item += '<tr class="orderTitle"><td class="info" class="orderTitle"><?php echo __('system_trade_no')?></td><td colspan="5" class="orderContent">'+data.trade_no+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('merchant_trade_no')?></td><td colspan="5" class="orderContent">'+data.out_trade_no+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('api_trade_no')?></td><td colspan="5" class="orderContent">'+data.api_trade_no+'</td></tr>';
				if(data.bill_mch_trade_no){
					item += '<tr><td class="info" class="orderTitle"><?php echo __('channel_trade_no')?></td><td colspan="5" class="orderContent">'+data.bill_mch_trade_no+'</td></tr>';
				}
				if(data.bill_trade_no){
					item += '<tr><td class="info" class="orderTitle"><?php echo __('user_trade_no')?></td><td colspan="5" class="orderContent">'+data.bill_trade_no+'</td></tr>';
				}
				item += '<tr><td class="info" class="orderTitle"><?php echo __('payment_method')?></td><td colspan="5" class="orderContent">'+data.typename+'</td></tr>';
				if(data.subchannel > 0){
					item += '<tr><td class="info" class="orderTitle"><?php echo __('sub_channel')?></td><td colspan="5" class="orderContent">'+data.subchannelname+'</td></tr>';
				}
				item += '<tr><td class="info" class="orderTitle"><?php echo __('product_name')?></td><td colspan="5" class="orderContent">'+data.name+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('order_amount')?></td><td colspan="5" class="orderContent">'+data.money+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('actual_pay')?></td><td colspan="5" class="orderContent">'+data.realmoney+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('merchant_share')?></td><td colspan="5" class="orderContent">'+data.getmoney+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('create_time')?></td><td colspan="5" class="orderContent">'+data.addtime+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('finish_time')?></td><td colspan="5" class="orderContent">'+data.endtime+'</td></tr>';
				if(data.status==2){
					item += '<tr><td class="info" class="orderTitle"><?php echo __('refund_time')?></td><td colspan="5" class="orderContent">'+data.refundtime+'</td></tr>';
				}
				item += '<tr><td class="info" class="orderTitle"><?php echo __('pay_account')?></td><td colspan="5" class="orderContent">'+data.buyer+'</td></tr>';
				if(data.mobile){
					item += '<tr><td class="info" class="orderTitle"><?php echo __('phone_number_label')?></td><td colspan="5" class="orderContent">'+data.mobile+'</td></tr>';
				}
				item += '<tr><td class="info" class="orderTitle"><?php echo __('website_domain')?></td><td colspan="5" class="orderContent"><a href="http://'+data.domain+'" target="_blank" rel="noreferrer">'+data.domain+'</a></td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('pay_ip')?></td><td colspan="5" class="orderContent"><a href="https://m.ip138.com/iplookup.asp?ip='+data.ip+'" target="_blank" rel="noreferrer">'+data.ip+'</a></td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('ext_params')?></td><td colspan="5" class="orderContent">'+data.param+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle"><?php echo __('order_status')?></td><td colspan="5" class="orderContent">'+status[data.status]+'</td></tr>';
				if(data.status>0){
					item += '<tr><td class="info" class="orderTitle"><?php echo __('notify_status')?></td><td colspan="5" class="orderContent">'+(data.notify==0?'<span class="label label-success"><?php echo __('notify_success')?></span>':'<span class="label label-danger"><?php echo __('notify_failed')?></span>（<?php echo __('notified_times')?>'+data.notify+'<?php echo __('times')?>）')+'</td></tr>';
				}
				item += '<tr><td colspan="6" style="text-align:center" class="orderTitle"><b><?php echo __('order_operations')?></b></td></tr>';
				item += '<tr><td colspan="6"><a href="javascript:callnotify(\''+data.trade_no+'\')" class="btn btn-xs btn-default"><?php echo __('renotify_async')?></a>&nbsp;<a href="javascript:callreturn(\''+data.trade_no+'\')" class="btn btn-xs btn-default"><?php echo __('renotify_sync')?></a>'+(data.combine==1?'&nbsp;<a href="javascript:showSubOrders(\''+data.trade_no+'\')" class="btn btn-xs btn-default"><?php echo __('view_sub_orders')?></a>':'')+'</td></tr>';
				item += '</table>';
				var area = [$(window).width() > 480 ? '480px' : '100%', ';max-height:100%'];
				layer.open({
				  type: 1,
				  area: area,
				  title: '<?php echo __('order_detail')?>',
				  skin: 'layui-layer-rim',
				  content: item
				});
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('<?php echo __('server_error')?>');
			return false;
		}
	});
}
function statistics(){
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    $.ajax({
        type : 'POST',
        url : 'ajax2.php?act=statistics',
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
function showSubOrders(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax2.php?act=subOrders&trade_no='+trade_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				var list = data.data;
				var status = ['<span class="label label-primary"><?php echo __('status_unpaid')?></span>','<span class="label label-success"><?php echo __('status_paid')?></span>','<span class="label label-danger"><?php echo __('status_refunded')?></span>'];
				var settle = ['<span class="label label-info"><?php echo __('status_pending_settle')?></span>','<span class="label label-success"><?php echo __('status_settle_success')?></span>'];
				var item = '<table class="table table-condensed table-hover" id="orderItem">';
				item += '<thead><th class="orderTitle"><?php echo __('system_trade_no')?></th><th class="orderTitle"><?php echo __('api_trade_no')?></th><th class="orderTitle"><?php echo __('order_amount')?></th class="orderTitle"><th class="orderTitle"><?php echo __('order_status')?></th><th class="orderTitle"><?php echo __('settle_status')?></th></thead><tbody>';
				for(var i=0; i<list.length; i++){
					var statustext = status[list[i].status];
					if(list[i].status == 2 && list[i].refundmoney > 0 && list[i].refundmoney < list[i].money){
						statustext += '<font color=red>('+list[i].refundmoney+')</font>';
					}
					item += '<tr><td>'+list[i].sub_trade_no+'</td><td>'+list[i].api_trade_no+'</td><td>¥<b>'+list[i].money+'</b></td><td>'+statustext+'</td><td>'+(data.settle>0?settle[list[i].settle]:'')+'</td></tr>';
				}
				item += '</tbody></table>';
				var area = [$(window).width() > 680 ? '680px' : '100%'];
				layer.open({
				  type: 1,
				  area: area,
				  title: '<?php echo __('combine_pay_sub_orders')?>',
				  skin: 'layui-layer-rim',
				  shadeClose: true,
				  content: item
				});
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('<?php echo __('server_error')?>');
			return false;
		}
	});
}
function exportOrder(){
	var params = {};
	$('#searchToolbar').find(':input[name]').each(function() {
		params[$(this).attr('name')] = $(this).val()
	})
	if(params['starttime'] == '' && params['endtime'] == ''){
		layer.alert('<?php echo __('js_select_export_range')?>');
		return false;
	}
	window.location.href='./download.php?act=order&'+$.param(params);
}
function printOrder(trade_no){
	layer.confirm('<?php echo __('confirm_reprint')?>',{btn:['<?php echo __('confirm')?>','<?php echo __('cancel')?>'],title:'<?php echo __('print_title')?>'}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax2.php?act=printOrder',
			data : {trade_no:trade_no},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.alert('<?php echo __('print_sent')?>', {icon:1}, function(){ layer.closeAll(); });
				}else{
					layer.alert(data.msg, {icon:7});
				}
			},
			error:function(data){
				layer.close(ii);
				layer.msg('<?php echo __('server_error')?>');
			}
		});
	});
}
</script>