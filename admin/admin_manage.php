<?php
include("../includes/common.php");
$title = '管理员管理';
include './head.php';
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
requirePermission('admin.manage');

$act = isset($_GET['act']) ? $_GET['act'] : '';

// 新增管理员
if($act == 'add' && isset($_POST['do'])){
    if(!checkRefererHost()) exit('{"code":403}');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');
    $pay_password = trim($_POST['pay_password'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 1);
    $status = intval($_POST['status'] ?? 1);

    if(!$username) showmsg('用户名不能为空', 3);
    if(!$password) showmsg('密码不能为空', 3);
    if($password !== $password2) showmsg('两次密码不一致', 3);
    if(strlen($password) < 6) showmsg('密码不能少于6位', 3);
    if(!$pay_password) showmsg('支付密码不能为空', 3);
    if(strlen($pay_password) < 6) showmsg('支付密码不能少于6位', 3);

    if(\lib\AdminAuth::createAdmin($username, $password, $pay_password, $role_id, $status)){
        showmsg('添加成功', 1);
    } else {
        showmsg('添加失败，用户名可能已存在', 4);
    }
}

// 编辑管理员
if($act == 'edit' && isset($_POST['do'])){
    if(!checkRefererHost()) exit('{"code":403}');
    $id = intval($_POST['id'] ?? 0);
    $role_id = intval($_POST['role_id'] ?? 0);
    $status = intval($_POST['status'] ?? 1);
    $password = trim($_POST['password'] ?? '');
    $pay_password = trim($_POST['pay_password'] ?? '');

    if(!$id) showmsg('ID错误', 3);

    // 超级管理员 (id==1 或唯一的超级管理员) 不可禁用
    $target = $DB->find('admin', '*', ['id' => $id]);
    if(!$target) showmsg('管理员不存在', 3);

    $data = ['role_id' => $role_id, 'status' => $status];
    if($password) {
        if(strlen($password) < 6) showmsg('密码不能少于6位', 3);
        $data['password'] = $password;
    }
    if($pay_password) {
        if(strlen($pay_password) < 6) showmsg('支付密码不能少于6位', 3);
        $data['pay_password'] = $pay_password;
    }

    if(\lib\AdminAuth::updateAdmin($id, $data)){
        showmsg('修改成功', 1);
    } else {
        showmsg('修改失败', 4);
    }
}

// 删除管理员
if($act == 'del' && isset($_POST['id'])){
    if(!checkRefererHost()) exit('{"code":403}');
    $id = intval($_POST['id']);
    if($id == $admin_id) showmsg('不能删除当前登录的管理员', 3);

    if(\lib\AdminAuth::deleteAdmin($id)){
        showmsg('删除成功', 1);
    } else {
        showmsg('删除失败，超级管理员或最后一个管理员不可删除', 4);
    }
}

// 获取所有角色
$roles = $DB->getAll("SELECT * FROM pre_admin_role ORDER BY id ASC");
$roleMap = [];
foreach($roles as $r) $roleMap[$r['id']] = $r['name'];

// 获取所有管理员
$admins = $DB->getAll("SELECT a.*, r.name as role_name FROM pre_admin a LEFT JOIN pre_admin_role r ON a.role_id=r.id ORDER BY a.id ASC");
?>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-lg-10 center-block" style="float: none;">

<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">管理员列表 <a href="javascript:void(0)" onclick="showAddForm()" class="btn btn-xs btn-success pull-right"><i class="fa fa-plus"></i> 新增管理员</a></h3>
  </div>
  <table class="table table-striped table-bordered">
    <thead>
      <tr><th>ID</th><th>用户名</th><th>角色</th><th>状态</th><th>最后登录IP</th><th>最后登录时间</th><th>登录次数</th><th>操作</th></tr>
    </thead>
    <tbody>
      <?php foreach($admins as $a): ?>
      <tr>
        <td><?= $a['id'] ?></td>
        <td><?= htmlspecialchars($a['username']) ?><?php if($a['id'] == $admin_id): ?> <span class="label label-info">当前</span><?php endif; ?></td>
        <td><?= htmlspecialchars($a['role_name'] ?? '-') ?></td>
        <td><?= $a['status'] ? '<span class="label label-success">启用</span>' : '<span class="label label-danger">禁用</span>' ?></td>
        <td><?= htmlspecialchars($a['last_login_ip'] ?? '-') ?></td>
        <td><?= $a['last_login_time'] ?? '-' ?></td>
        <td><?= intval($a['login_count']) ?></td>
        <td>
          <button class="btn btn-xs btn-primary" onclick="showEditForm(<?= $a['id'] ?>, '<?= htmlspecialchars($a['username']) ?>', <?= $a['role_id'] ?>, <?= $a['status'] ?>)"><i class="fa fa-edit"></i> 编辑</button>
          <?php if($a['id'] != $admin_id): ?>
          <button class="btn btn-xs btn-danger" onclick="delAdmin(<?= $a['id'] ?>, '<?= htmlspecialchars($a['username']) ?>')"><i class="fa fa-trash"></i> 删除</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- 新增管理员弹窗 -->
<div class="modal fade" id="modal-add" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title">新增管理员</h4>
      </div>
      <form method="post" action="?act=add">
        <input type="hidden" name="do" value="1">
        <div class="modal-body">
          <div class="form-group"><label>用户名</label><input type="text" name="username" class="form-control" required></div>
          <div class="form-group"><label>登录密码</label><input type="password" name="password" class="form-control" placeholder="至少6位" required></div>
          <div class="form-group"><label>确认密码</label><input type="password" name="password2" class="form-control" required></div>
          <div class="form-group"><label>支付密码</label><input type="password" name="pay_password" class="form-control" placeholder="至少6位" required></div>
          <div class="form-group">
            <label>角色</label>
            <select name="role_id" class="form-control">
              <?php foreach($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>状态</label>
            <select name="status" class="form-control">
              <option value="1">启用</option>
              <option value="0">禁用</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="submit" class="btn btn-primary">添加</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 编辑管理员弹窗 -->
<div class="modal fade" id="modal-edit" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title">编辑管理员: <span id="edit-username"></span></h4>
      </div>
      <form method="post" action="?act=edit">
        <input type="hidden" name="do" value="1">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-body">
          <div class="form-group">
            <label>角色</label>
            <select name="role_id" id="edit-role" class="form-control">
              <?php foreach($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>状态</label>
            <select name="status" id="edit-status" class="form-control">
              <option value="1">启用</option>
              <option value="0">禁用</option>
            </select>
          </div>
          <div class="form-group"><label>新登录密码</label><input type="password" name="password" class="form-control" placeholder="不修改请留空"></div>
          <div class="form-group"><label>新支付密码</label><input type="password" name="pay_password" class="form-control" placeholder="不修改请留空"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="submit" class="btn btn-primary">保存</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 删除表单 -->
<form id="del-form" method="post" action="?act=del">
  <input type="hidden" name="id" id="del-id">
</form>

</div>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function showAddForm() {
  $('#modal-add').modal('show');
}
function showEditForm(id, username, roleId, status) {
  $('#edit-id').val(id);
  $('#edit-username').text(username);
  $('#edit-role').val(roleId);
  $('#edit-status').val(status);
  $('#modal-edit').modal('show');
}
function delAdmin(id, username) {
  layer.confirm('确定要删除管理员【' + username + '】吗？', {icon: 3, title: '确认删除'}, function(index){
    $('#del-id').val(id);
    $('#del-form').submit();
    layer.close(index);
  });
}
</script>
</body>
</html>
