<?php
include("../includes/common.php");
$title='系统更新';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-lg-9 center-block" style="float: none;">
  <div class="panel panel-primary">
    <div class="panel-heading">
      <h3 class="panel-title">
        <i class="fa fa-cloud-download"></i> 系统更新
        <span class="pull-right" id="version_info"></span>
      </h3>
    </div>
    <div class="panel-body">
      <div id="alert_box"></div>

      <!-- 状态卡片 -->
      <div class="row" style="margin-bottom:15px;">
        <div class="col-xs-4">
          <div class="panel panel-info">
            <div class="panel-body text-center">
              <div style="font-size:12px;color:#999;">当前版本</div>
              <div style="font-size:24px;font-weight:bold;" id="local_ver">-</div>
            </div>
          </div>
        </div>
        <div class="col-xs-4">
          <div class="panel panel-warning">
            <div class="panel-body text-center">
              <div style="font-size:12px;color:#999;">落后版本数</div>
              <div style="font-size:24px;font-weight:bold;" id="behind_count">-</div>
            </div>
          </div>
        </div>
        <div class="col-xs-4">
          <div class="panel panel-success">
            <div class="panel-body text-center">
              <div style="font-size:12px;color:#999;">最新版本</div>
              <div style="font-size:24px;font-weight:bold;" id="latest_ver">-</div>
            </div>
          </div>
        </div>
      </div>

      <!-- 操作按钮 -->
      <div style="margin-bottom:15px;">
        <button class="btn btn-info" id="btn_check" onclick="checkUpdate()">
          <i class="fa fa-refresh"></i> 检查更新
        </button>
        <button class="btn btn-success" id="btn_upgrade" onclick="doUpgrade()" style="display:none;">
          <i class="fa fa-rocket"></i> 一键更新到最新版
        </button>
      </div>

      <!-- 进度条 -->
      <div id="progress_box" style="display:none;margin-bottom:15px;">
        <div class="progress">
          <div class="progress-bar progress-bar-striped active" id="progress_bar" role="progressbar" style="width:0%">
            0%
          </div>
        </div>
        <div id="progress_text" style="font-size:13px;color:#666;"></div>
      </div>

      <!-- 版本列表 -->
      <table class="table table-bordered table-hover" id="release_table" style="display:none;">
        <thead>
          <tr>
            <th>版本</th>
            <th>更新日志</th>
            <th>发布时间</th>
            <th>状态</th>
          </tr>
        </thead>
        <tbody id="release_list"></tbody>
      </table>
    </div>
  </div>
</div>
</div>
<script>
var releasesData = [];
var behindCount = 0;

function showAlert(type, msg) {
    $('#alert_box').html('<div class="alert alert-' + type + ' alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' + msg + '</div>');
}

function checkUpdate() {
    $('#btn_check').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> 检查中...');
    $.ajax({
        type: 'GET',
        url: 'ajax_update.php?act=check',
        dataType: 'json',
        timeout: 30000,
        success: function(data) {
            $('#btn_check').prop('disabled', false).html('<i class="fa fa-refresh"></i> 检查更新');
            if (data.code !== 0) {
                showAlert('danger', data.msg);
                return;
            }
            releasesData = data.releases;
            behindCount = data.behind_count;
            $('#local_ver').text('v' + data.local_version);
            $('#behind_count').text(behindCount);
            var latest = data.releases.length > 0 ? data.releases[data.releases.length - 1].tag : '-';
            $('#latest_ver').text(latest);

            // 渲染版本列表
            var html = '';
            for (var i = data.releases.length - 1; i >= 0; i--) {
                var r = data.releases[i];
                var badge = r.is_behind
                    ? '<span class="label label-warning">待更新</span>'
                    : '<span class="label label-success">已安装</span>';
                var body = r.body ? renderMarkdown(r.body) : '-';
                var pubDate = r.published_at ? r.published_at.replace('T', ' ').replace('Z', '') : '-';
                html += '<tr class="' + (r.is_behind ? 'warning' : '') + '">';
                html += '<td><b>' + r.tag + '</b></td>';
                html += '<td style="max-width:400px;">' + body + '</td>';
                html += '<td>' + pubDate + '</td>';
                html += '<td>' + badge + '</td>';
                html += '</tr>';
            }
            $('#release_list').html(html);
            $('#release_table').show();

            if (behindCount > 0) {
                $('#btn_upgrade').show();
                showAlert('warning', '发现 <b>' + behindCount + '</b> 个新版本，点击「一键更新」可自动逐版本升级。');
            } else {
                $('#btn_upgrade').hide();
                showAlert('success', '当前已是最新版本！');
            }
        },
        error: function() {
            $('#btn_check').prop('disabled', false).html('<i class="fa fa-refresh"></i> 检查更新');
            showAlert('danger', '请求失败，请检查网络或 GitHub API 访问。');
        }
    });
}

function doUpgrade() {
    if (releasesData.length === 0) return;
    var latest = releasesData[releasesData.length - 1];
    if (!confirm('即将从 v' + $('#local_ver').text().replace('v','') + ' 逐步更新到 ' + latest.tag + '，共 ' + behindCount + ' 个版本。\n\n更新前会自动备份，确认继续？')) return;

    $('#btn_upgrade').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> 更新中...');
    $('#btn_check').prop('disabled', true);
    $('#progress_box').show();
    setProgress(10, '正在准备更新...');

    $.ajax({
        type: 'POST',
        url: 'ajax_update.php?act=upgrade',
        data: { target_version: latest.version },
        dataType: 'json',
        timeout: 600000,
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            return xhr;
        },
        success: function(data) {
            $('#btn_upgrade').prop('disabled', false).html('<i class="fa fa-rocket"></i> 一键更新到最新版');
            $('#btn_check').prop('disabled', false);
            if (data.code === 0) {
                setProgress(100, '更新完成！');
                var html = '更新成功！版本已从 <b>v' + data.old_version + '</b> 升级到 <b>v' + data.new_version + '</b>';
                if (data.backup) html += '<br>备份文件：<code>data/backup/' + data.backup + '</code>';
                html += '<br><br><b>更新详情：</b><ul>';
                for (var i = 0; i < data.details.length; i++) {
                    var d = data.details[i];
                    html += '<li>' + d.tag + '：' + (d.status === 'ok' ? '<span style="color:green">✓ ' + d.msg + '</span>' : '<span style="color:red">✗ ' + d.msg + '</span>') + '</li>';
                }
                html += '</ul>';
                showAlert('success', html);
                setTimeout(function(){ checkUpdate(); }, 2000);
            } else {
                setProgress(100, '更新失败');
                showAlert('danger', '更新失败：' + data.msg);
            }
        },
        error: function(xhr, status) {
            $('#btn_upgrade').prop('disabled', false).html('<i class="fa fa-rocket"></i> 一键更新到最新版');
            $('#btn_check').prop('disabled', false);
            setProgress(100, '请求失败');
            showAlert('danger', '更新请求失败（' + status + '），请检查网络或 GitHub API 访问。');
        }
    });
}

function setProgress(pct, text) {
    $('#progress_bar').css('width', pct + '%').text(pct + '%');
    if (text) $('#progress_text').text(text);
}

function renderMarkdown(text) {
    // Escape HTML first
    text = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    var lines = text.split('\n');
    var html = '';
    var inList = false;
    for (var i = 0; i < lines.length; i++) {
        var line = lines[i];
        // Headings
        if (/^### (.+)/.test(line)) {
            if (inList) { html += '</ul>'; inList = false; }
            html += '<b>' + line.replace(/^### /, '') + '</b><br>';
            continue;
        }
        if (/^## (.+)/.test(line)) {
            if (inList) { html += '</ul>'; inList = false; }
            html += '<b>' + line.replace(/^## /, '') + '</b><br>';
            continue;
        }
        if (/^# (.+)/.test(line)) {
            if (inList) { html += '</ul>'; inList = false; }
            html += '<b>' + line.replace(/^# /, '') + '</b><br>';
            continue;
        }
        // List items
        if (/^[-*] (.+)/.test(line)) {
            if (!inList) { html += '<ul style="margin:4px 0 4px 18px;padding:0;">'; inList = true; }
            html += '<li>' + inlineMarkdown(line.replace(/^[-*] /, '')) + '</li>';
            continue;
        }
        // End list on blank or non-list line
        if (inList) { html += '</ul>'; inList = false; }
        if (line.trim() === '') {
            html += '<br>';
        } else {
            html += inlineMarkdown(line) + '<br>';
        }
    }
    if (inList) html += '</ul>';
    return html;
}
function inlineMarkdown(text) {
    // Bold
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/__(.+?)__/g, '<strong>$1</strong>');
    // Italic
    text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
    text = text.replace(/_(.+?)_/g, '<em>$1</em>');
    // Inline code
    text = text.replace(/`(.+?)`/g, '<code>$1</code>');
    // Links
    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
    return text;
}

// 页面加载后自动检查
$(document).ready(function(){
    checkUpdate();
});
</script>
