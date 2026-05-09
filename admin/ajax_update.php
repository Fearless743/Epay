<?php
include("../includes/common.php");
if($islogin==1){}else exit(json_encode(['code'=>-1,'msg'=>'未登录']));
if(!checkRefererHost())exit(json_encode(['code'=>403,'msg'=>'非法请求']));

@header('Content-Type: application/json; charset=UTF-8');

$act = isset($_GET['act']) ? daddslashes($_GET['act']) : null;
$github_repo = 'Fearless743/Epay';
$github_api = "https://api.github.com/repos/{$github_repo}/releases";

switch($act) {
    /**
     * 检查更新 - 获取 GitHub 所有 Release，计算与本地版本的差异
     */
    case 'check':
        $local_version = defined('VERSION') ? intval(VERSION) : 0;
        $remote_releases = getGitHubReleases($github_api);
        if($remote_releases === false) {
            exit(json_encode(['code'=>-1,'msg'=>'获取远程版本信息失败，请检查网络连接或配置代理']));
        }

        $releases = [];
        $behind_count = 0;
        foreach($remote_releases as $release) {
            $tag = $release['tag_name']; // e.g. v3097
            $ver_num = intval(preg_replace('/[^0-9]/', '', $tag));
            $asset_url = null;
            $asset_name = null;
            $asset_size = 0;
            foreach($release['assets'] as $asset) {
                if(strpos($asset['name'], '.zip') !== false) {
                    $asset_url = $asset['browser_download_url'];
                    $asset_name = $asset['name'];
                    $asset_size = $asset['size'];
                    break;
                }
            }
            $is_behind = $ver_num > $local_version;
            if($is_behind) $behind_count++;

            $releases[] = [
                'tag' => $tag,
                'version' => $ver_num,
                'name' => $release['name'],
                'body' => $release['body'] ?? '',
                'published_at' => $release['published_at'],
                'asset_url' => $asset_url,
                'asset_name' => $asset_name,
                'asset_size' => $asset_size,
                'is_behind' => $is_behind
            ];
        }

        // 按版本号排序（升序）
        usort($releases, function($a, $b) {
            return $a['version'] - $b['version'];
        });

        exit(json_encode([
            'code' => 0,
            'local_version' => $local_version,
            'behind_count' => $behind_count,
            'releases' => $releases
        ]));
        break;

    /**
     * 执行更新 - 按版本顺序下载并应用所有差异更新包
     */
    case 'upgrade':
        $local_version = defined('VERSION') ? intval(VERSION) : 0;
        $target_version = isset($_POST['target_version']) ? intval($_POST['target_version']) : 0;
        if($target_version <= 0) {
            exit(json_encode(['code'=>-1,'msg'=>'目标版本无效']));
        }

        $remote_releases = getGitHubReleases($github_api);
        if($remote_releases === false) {
            exit(json_encode(['code'=>-1,'msg'=>'获取远程版本信息失败']));
        }

        // 筛选出需要更新的版本（大于本地版本且小于等于目标版本）
        $pending = [];
        foreach($remote_releases as $release) {
            $ver_num = intval(preg_replace('/[^0-9]/', '', $release['tag_name']));
            if($ver_num > $local_version && $ver_num <= $target_version) {
                $asset_url = null;
                foreach($release['assets'] as $asset) {
                    if(strpos($asset['name'], '.zip') !== false) {
                        $asset_url = $asset['browser_download_url'];
                        break;
                    }
                }
                if($asset_url) {
                    $pending[] = [
                        'tag' => $release['tag_name'],
                        'version' => $ver_num,
                        'asset_url' => $asset_url
                    ];
                }
            }
        }

        if(empty($pending)) {
            exit(json_encode(['code'=>-1,'msg'=>'没有可用的更新包']));
        }

        // 按版本号升序排序
        usort($pending, function($a, $b) {
            return $a['version'] - $b['version'];
        });

        // 备份当前版本
        $backup_dir = ROOT.'data/backup/';
        if(!is_dir($backup_dir)) @mkdir($backup_dir, 0755, true);
        $backup_file = $backup_dir.'backup_v'.$local_version.'_'.date('YmdHis').'.zip';
        if(class_exists('ZipArchive')) {
            createBackupZip(ROOT, $backup_file, $backup_dir);
        }

        $update_dir = ROOT.'data/update/';
        if(!is_dir($update_dir)) @mkdir($update_dir, 0755, true);

        $results = [];
        $current_ver = $local_version;

        foreach($pending as $pkg) {
            $zip_file = $update_dir.$pkg['tag'].'.zip';

            // 下载更新包
            $downloaded = downloadFile($pkg['asset_url'], $zip_file);
            if(!$downloaded) {
                $results[] = ['tag'=>$pkg['tag'],'status'=>'error','msg'=>'下载失败'];
                break;
            }

            // 解压并应用
            $applied = applyUpdate($zip_file, ROOT);
            if(!$applied) {
                $results[] = ['tag'=>$pkg['tag'],'status'=>'error','msg'=>'解压或应用更新失败'];
                break;
            }

            $current_ver = $pkg['version'];
            $results[] = ['tag'=>$pkg['tag'],'status'=>'ok','msg'=>'更新成功'];

            // 清理临时文件
            @unlink($zip_file);
        }

        // 更新本地 VERSION 常量
        if($current_ver > $local_version) {
            updateVersionConstant($current_ver);
        }

        // 尝试执行数据库更新
        if(file_exists(ROOT.'install/update.php') && $current_ver > $local_version) {
            @include ROOT.'install/update.php';
        }

        // 清理缓存
        if(isset($CACHE)) $CACHE->clear();

        exit(json_encode([
            'code' => 0,
            'msg' => '更新完成',
            'old_version' => $local_version,
            'new_version' => $current_ver,
            'backup' => basename($backup_file),
            'details' => $results
        ]));
        break;

    default:
        exit(json_encode(['code'=>-4,'msg'=>'未知操作']));
}

/**
 * 获取 GitHub Release 列表（支持分页）
 */
function getGitHubReleases($api_url) {
    $releases = [];
    $page = 1;
    while(true) {
        $url = $api_url.'?per_page=100&page='.$page;
        $response = get_curl($url, 0, 0, 0, 0, 'Mozilla/5.0 Epay-Updater', 0, ['Accept: application/vnd.github.v3+json'], 1);
        if(!$response) return false;
        $data = json_decode($response, true);
        if(!is_array($data) || empty($data)) break;
        $releases = array_merge($releases, $data);
        if(count($data) < 100) break;
        $page++;
    }
    return $releases;
}

/**
 * 下载文件
 */
function downloadFile($url, $dest) {
    // GitHub asset downloads require following redirects (302)
    $content = get_curl($url, 0, 0, 0, 0, 'Mozilla/5.0 Epay-Updater', 0, 0, 1);
    if(!$content || strlen($content) < 100) return false;
    return file_put_contents($dest, $content) !== false;
}

/**
 * 应用更新 - 解压 zip 并覆盖文件（保留 config.php）
 */
function applyUpdate($zip_file, $target_dir) {
    if(!file_exists($zip_file)) return false;
    $zip = new ZipArchive();
    if($zip->open($zip_file) !== true) return false;

    $temp_dir = $target_dir.'data/update/temp_'.time().'/';
    if(!is_dir($temp_dir)) @mkdir($temp_dir, 0755, true);

    $zip->extractTo($temp_dir);
    $zip->close();

    // 查找解压后的根目录（可能有一层子目录）
    $items = scandir($temp_dir);
    $source_dir = $temp_dir;
    foreach($items as $item) {
        if($item === '.' || $item === '..') continue;
        if(is_dir($temp_dir.$item) && count(scandir($temp_dir.$item)) > 2) {
            $source_dir = $temp_dir.$item.'/';
            break;
        }
    }

    // 复制文件（排除 config.php 和 install.lock）
    $excludes = ['config.php', 'install/install.lock', '.git', '.github', 'data/'];
    copyDirectory($source_dir, $target_dir, $excludes);

    // 清理临时目录
    deleteDirectory($temp_dir);
    return true;
}

/**
 * 递归复制目录
 */
function copyDirectory($source, $dest, $excludes = []) {
    if(!is_dir($source)) return;
    if(!is_dir($dest)) @mkdir($dest, 0755, true);

    $items = scandir($source);
    foreach($items as $item) {
        if($item === '.' || $item === '..') continue;

        $source_path = $source.$item;
        $dest_path = $dest.$item;

        // 检查是否在排除列表
        $relative = $item;
        $skip = false;
        foreach($excludes as $ex) {
            if(strpos($relative, $ex) === 0 || $item === $ex) {
                $skip = true;
                break;
            }
        }
        if($skip) continue;

        if(is_dir($source_path)) {
            copyDirectory($source_path.'/', $dest_path.'/', $excludes);
        } else {
            @copy($source_path, $dest_path);
        }
    }
}

/**
 * 更新 common.php 中的 VERSION 常量
 */
function updateVersionConstant($new_version) {
    $file = ROOT.'includes/common.php';
    $content = file_get_contents($file);
    $content = preg_replace(
        "/define\('VERSION',\s*'[0-9]+'\)/",
        "define('VERSION', '{$new_version}')",
        $content
    );
    file_put_contents($file, $content);
}

/**
 * 创建备份 zip
 */
function createBackupZip($source_dir, $dest_file, $backup_dir) {
    $zip = new ZipArchive();
    if($zip->open($dest_file, ZipArchive::CREATE) !== true) return false;
    $exclude = ['.git', '.github', 'data', '.agents', '.codex'];
    addDirToZip($zip, $source_dir, '', $exclude);
    $zip->close();
}

function addDirToZip($zip, $source, $prefix, $exclude) {
    $items = scandir($source);
    foreach($items as $item) {
        if($item === '.' || $item === '..') continue;
        if(in_array($item, $exclude)) continue;
        $full = $source.$item;
        $zip_path = $prefix ? $prefix.'/'.$item : $item;
        if(is_dir($full)) {
            $zip->addEmptyDir($zip_path);
            addDirToZip($zip, $full.'/', $zip_path, $exclude);
        } else {
            $zip->addFile($full, $zip_path);
        }
    }
}

/**
 * 递归删除目录
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . $item;
        if (is_dir($path)) {
            deleteDirectory($path . '/');
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
