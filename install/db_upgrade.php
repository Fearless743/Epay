<?php
/**
 * 数据库自动升级
 * 扫描 install/ 目录下所有 update_*.sql 文件（如 update_2055.sql），
 * 根据当前数据库版本号自动执行需要的升级 SQL。
 *
 * SQL 文件命名格式: update_{目标版本号}.sql
 */

/**
 * 获取需要执行的升级 SQL 文件列表
 * @param string $install_dir install/ 目录的绝对路径
 * @param int $current_version 当前数据库版本号
 * @param int $latest_version [out] 返回最新的目标版本号
 * @return array 需要执行的 SQL 文件路径列表（按版本号升序）
 */
function getUpgradeSqlFiles($install_dir, $current_version, &$latest_version = 0) {
    $files = glob($install_dir . 'update_*.sql');
    $upgrades = [];
    foreach ($files as $file) {
        $basename = basename($file);
        if (preg_match('/^update_(\d+)\.sql$/', $basename, $m)) {
            $ver = intval($m[1]);
            $upgrades[$ver] = $file;
        }
    }
    ksort($upgrades);

    $result = [];
    $latest_version = 0;
    foreach ($upgrades as $ver => $file) {
        if ($ver > $latest_version) $latest_version = $ver;
        if ($ver > $current_version) {
            $result[] = $file;
        }
    }
    return $result;
}
