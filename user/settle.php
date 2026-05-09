<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title=__('settle_list');
include './head.php';
?>
<style>
.fixed-table-toolbar,.fixed-table-pagination{padding: 15px;}
</style>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3"><?php echo __('settle_list')?></h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<?php echo __('settle_list')?>
		</div>
		<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
			<div class="form-group">
				<select name="dstatus" class="form-control"><option value="-1"><?php echo __('all_status')?></option><option value="0"><?php echo __('settle_status_pending')?></option><option value="1"><?php echo __('settle_status_done')?></option><option value="2"><?php echo __('settle_status_processing')?></option><option value="3"><?php echo __('settle_status_failed')?></option></select>
			</div>
			<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> <?php echo __('search')?></button>
			<a href="javascript:searchClear()" class="btn btn-default"><i class="fa fa-refresh"></i> <?php echo __('reset')?></a>
		</form>
      <table id="listTable">
	  </table>
	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
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
		url: 'ajax2.php?act=settleList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover',
		columns: [
			{
				field: 'type',
				title: '<?php echo __('settle_method')?>',
				formatter: function(value, row, index) {
					let typename = '';
					if(value == '1'){
						typename='<?php echo __('settle_type_alipay')?>';
					}else if(value == '2'){
						typename='<?php echo __('settle_type_wxpay')?>';
					}else if(value == '3'){
						typename='<?php echo __('settle_type_qqpay')?>';
					}else if(value == '4'){
						typename='<?php echo __('settle_type_bank')?>';
					}else if(value == '5'){
						typename='<?php echo __('settle_type_org')?>';
					}
					if(row.auto!=1) typename+='<small>[<?php echo __('manual')?>]</small>'
					return typename;
				}
			},
			{
				field: 'account',
				title: '<?php echo __('settle_account')?>'
			},
			{
				field: 'money',
				title: '<?php echo __('settle_amount_label')?>',
				formatter: function(value, row, index) {
					return '¥<b>'+value+'</b>';
				}
			},
			{
				field: 'realmoney',
				title: '<?php echo __('actual_received')?>',
				formatter: function(value, row, index) {
					return '¥<b>'+value+'</b>';
				}
			},
			{
				field: 'addtime',
				title: '<?php echo __('settle_time')?>'
			},
			{
				field: 'status',
				title: '<?php echo __('status')?>',
				formatter: function(value, row, index) {
					if(value == '1'){
						return '<font color=green><?php echo __('settle_status_done')?></font>' + (row.jumpurl ? '<br/><a href="javascript:showQrcode(\''+row.jumpurl+'\')" class="btn btn-xs btn-success"><i class="fa fa-qrcode"></i> <?php echo __('confirm_receipt')?></a>' : '');
					}else if(value == '2'){
						return '<font color=orange><?php echo __('settle_status_processing')?></font>';
					}else if(value == '3'){
						return '<a href="javascript:showResult('+row.id+')" title="<?php echo __('failure_reason')?>"><font color=red><?php echo __('settle_status_failed')?></font></a>';
					}else{
						return '<font color=blue><?php echo __('settle_status_pending')?></font>';
					}
				}
			},
		],
	})
})
function showResult(id) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax2.php?act=settle_result&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:0, title:'<?php echo __('failure_reason')?>'});
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
function showQrcode(url){
	layer.open({
		type: 1,
		title: '<?php echo __('scan_qrcode_confirm')?>',
		skin: 'layui-layer-demo',
		shadeClose: true,
		content: '<div id="qrcode" class="list-group-item text-center"></div>',
		success: function(){
			$('#qrcode').qrcode({
				text: url,
				width: 230,
				height: 230,
				foreground: "#000000",
				background: "#ffffff",
				typeNumber: -1
			});
		}
	});
}
</script>