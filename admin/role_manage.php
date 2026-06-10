<?php
include("../includes/common.php");
$title = '角色管理';
include './head.php';
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
requirePermission('admin.manage');

$act = isset($_GET['act']) ? $_GET['act'] : '';

// 所有权限项（分组）
$allPerms = [
    '订单管理' => [
        'order.view'   => '查看订单',
        'order.export' => '导出订单',
        'buyerstat'    => '支付用户统计',
        'blacklist'    => '黑名单管理',
        'profitview'   => '分账查看',
    ],
    '结算管理' => [
        'settle' => '结算操作（查看+批量结算）',
    ],
    '转账付款' => [
        'transfer.view'  => '查看付款记录',
        'transfer'       => '付款操作（新增/红包/批量）',
        'transfer_batch' => '批量转账',
    ],
    '商户管理' => [
        'user.manage' => '商户管理（列表/组/统计）',
        'record'      => '资金明细',
        'domain'      => '授权域名',
        'invitecode'  => '邀请码管理',
    ],
    '支付接口' => [
        'channel' => '支付通道/轮询/公众号',
        'paytype' => '支付方式',
        'plugin'  => '支付插件',
    ],
    '系统设置' => [
        'settings' => '系统设置',
        'anounce'  => '公告配置',
        'update'   => '系统更新',
    ],
    '日志与安全' => [
        'log'      => '登录日志',
        'risk'     => '风控记录',
        'clean'    => '数据清理',
        'token'    => '获取用户标识',
    ],
    '管理员系统' => [
        'admin.manage' => '管理员与角色管理',
    ],
];

// 新增角色
if($act == 'add' && isset($_POST['do'])){
    if(!checkRefererHost()) exit('{"code":403}');
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $perms = isset($_POST['permissions']) ? (array)$_POST['permissions'] : [];

    if(!$name) showmsg('角色名称不能为空', 3);
    if(!$code) showmsg('角色代码不能为空', 3);
    if(!preg_match('/^[a-zA-Z0-9_\.]+$/', $code)) showmsg('角色代码只能包含字母、数字、下划线和点', 3);

    $exists = $DB->getColumn("SELECT COUNT(*) FROM pre_admin_role WHERE code=?", [$code]);
    if($exists) showmsg('角色代码已存在', 3);

    $DB->exec("INSERT INTO pre_admin_role (name, code, permissions, description, is_system) VALUES (?, ?, ?, ?, 0)",
        [$name, $code, json_encode($perms, JSON_UNESCAPED_UNICODE), $desc]);
    showmsg('添加成功', 1);
}

// 编辑角色
if($act == 'edit' && isset($_POST['do'])){
    if(!checkRefererHost()) exit('{"code":403}');
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $perms = isset($_POST['permissions']) ? (array)$_POST['permissions'] : [];

    if(!$id) showmsg('ID错误', 3);
    $role = $DB->find('admin_role', '*', ['id' => $id]);
    if(!$role) showmsg('角色不存在', 3);
    if($role['is_system'] && !in_array('all', $perms)) showmsg('系统角色权限已锁定', 3);

    $DB->exec("UPDATE pre_admin_role SET name=?, permissions=?, description=? WHERE id=?",
        [$name, json_encode($perms, JSON_UNESCAPED_UNICODE), $desc, $id]);
    showmsg('保存成功', 1);
}

// 删除角色
if($act == 'del' && isset($_POST['id'])){
    if(!checkRefererHost()) exit('{"code":403}');
    $id = intval($_POST['id']);
    $role = $DB->find('admin_role', '*', ['id' => $id]);
    if(!$role) showmsg('角色不存在', 3);
    if($role['is_system']) showmsg('系统内置角色不可删除', 3);

    $cnt = $DB->getColumn("SELECT COUNT(*) FROM pre_admin WHERE role_id=?", [$id]);
    if($cnt > 0) showmsg('该角色还有管理员关联，请先修改或删除相关管理员', 3);

    $DB->exec("DELETE FROM pre_admin_role WHERE id=? AND is_system=0", [$id]);
    showmsg('删除成功', 1);
}

$roles = $DB->getAll("SELECT r.*, (SELECT COUNT(*) FROM pre_admin a WHERE a.role_id=r.id) as admin_count FROM pre_admin_role r ORDER BY r.id ASC");
$editRole = null;
if(isset($_GET['editid'])){
    $editRole = $DB->find('admin_role', '*', ['id' => intval($_GET['editid'])]);
    if($editRole) $editRole['perm_arr'] = json_decode($editRole['permissions'] ?? '[]', true) ?: [];
}
?>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-lg-10 center-block" style="float: none;">

<?php if($editRole): ?>
<!-- 编辑角色权限 -->
<div class="panel panel-primary">
  <div class="panel-heading"><h3 class="panel-title">编辑角色：<?= htmlspecialchars($editRole['name']) ?></h3></div>
  <form method="post" action="?act=edit">
    <input type="hidden" name="do" value="1">
    <input type="hidden" name="id" value="<?= $editRole['id'] ?>">
    <div class="panel-body">
      <div class="form-group"><label>角色名称</label><input type="text" name="name" value="<?= htmlspecialchars($editRole['name']) ?>" class="form-control" required></div>
      <div class="form-group"><label>描述</label><input type="text" name="description" value="<?= htmlspecialchars($editRole['description'] ?? '') ?>" class="form-control"></div>
      <?php if(in_array('all', $editRole['perm_arr'])): ?>
      <div class="alert alert-warning"><i class="fa fa-star"></i> 超级管理员拥有全部权限</div>
      <input type="hidden" name="permissions[]" value="all">
      <?php else: ?>
      <div class="form-group">
        <label>权限配置</label>
        <?php foreach($allPerms as $groupName => $items): ?>
        <div class="panel panel-default" style="margin-top:10px;">
          <div class="panel-heading" style="padding:5px 10px;">
            <b><?= $groupName ?></b>
            <label class="pull-right" style="font-weight:normal;margin:0;cursor:pointer;">
              <input type="checkbox" class="group-all" data-group="<?= md5($groupName) ?>"> 全选
            </label>
          </div>
          <div class="panel-body" style="padding:10px;">
            <?php foreach($items as $perm => $label): ?>
            <label class="checkbox-inline" style="margin-right:20px;">
              <input type="checkbox" name="permissions[]" value="<?= $perm ?>" data-group="<?= md5($groupName) ?>"
                <?= in_array($perm, $editRole['perm_arr']) ? 'checked' : '' ?>>
              <?= htmlspecialchars($label) ?> <code><?= $perm ?></code>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="panel-footer">
      <button type="submit" class="btn btn-primary">保存</button>
      <a href="./role_manage.php" class="btn btn-default">返回列表</a>
    </div>
  </form>
</div>

<?php else: ?>
<!-- 角色列表 -->
<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">角色列表 <a href="javascript:void(0)" onclick="showAddForm()" class="btn btn-xs btn-success pull-right"><i class="fa fa-plus"></i> 新增角色</a></h3>
  </div>
  <table class="table table-striped table-bordered">
    <thead>
      <tr><th>ID</th><th>角色名称</th><th>代码</th><th>描述</th><th>关联管理员数</th><th>类型</th><th>操作</th></tr>
    </thead>
    <tbody>
      <?php foreach($roles as $r): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><code><?= htmlspecialchars($r['code']) ?></code></td>
        <td><?= htmlspecialchars($r['description'] ?? '') ?></td>
        <td><?= $r['admin_count'] ?></td>
        <td><?= $r['is_system'] ? '<span class="label label-warning">内置</span>' : '<span class="label label-default">自定义</span>' ?></td>
        <td>
          <a href="?editid=<?= $r['id'] ?>" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> 编辑权限</a>
          <?php if(!$r['is_system']): ?>
          <button class="btn btn-xs btn-danger" onclick="delRole(<?= $r['id'] ?>, '<?= htmlspecialchars($r['name']) ?>')"><i class="fa fa-trash"></i> 删除</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- 新增角色弹窗 -->
<div class="modal fade" id="modal-add" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title">新增角色</h4>
      </div>
      <form method="post" action="?act=add">
        <input type="hidden" name="do" value="1">
        <div class="modal-body">
          <div class="form-group"><label>角色名称</label><input type="text" name="name" class="form-control" required></div>
          <div class="form-group"><label>角色代码</label><input type="text" name="code" class="form-control" placeholder="如：viewer，仅字母/数字/下划线/点" required></div>
          <div class="form-group"><label>描述</label><input type="text" name="description" class="form-control" placeholder="可选"></div>
          <p class="text-muted">创建后在角色列表点击「编辑权限」配置具体权限</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="submit" class="btn btn-primary">创建</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 删除表单 -->
<form id="del-form" method="post" action="?act=del">
  <input type="hidden" name="id" id="del-id">
</form>
<?php endif; ?>

</div>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function showAddForm() {
  $('#modal-add').modal('show');
}
function delRole(id, name) {
  layer.confirm('确定要删除角色【' + name + '】吗？有管理员关联的角色不可删除。', {icon: 3, title: '确认删除'}, function(index){
    $('#del-id').val(id);
    $('#del-form').submit();
    layer.close(index);
  });
}
// 全选/取消全选
$(document).on('change', '.group-all', function(){
  var group = $(this).data('group');
  var checked = $(this).is(':checked');
  $('[data-group="' + group + '"][name="permissions[]"]').prop('checked', checked);
});
</script>
</body>
</html>
