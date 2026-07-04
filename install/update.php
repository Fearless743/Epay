<?php
error_reporting(0);
require '../config.php';
require __DIR__ . '/db_upgrade.php';
require __DIR__ . '/../includes/lib/Cache.php';

@header('Content-Type: text/html; charset=UTF-8');

try{
	$db=new PDO("mysql:host=".$dbconfig['host'].";dbname=".$dbconfig['dbname'].";port=".$dbconfig['port'],$dbconfig['user'],$dbconfig['pwd']);
}catch(Exception $e){
	exit('链接数据库失败:'.$e->getMessage());
}
date_default_timezone_set("PRC");
$date = date("Y-m-d");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
$db->exec("set sql_mode = ''");
$db->exec("set names utf8");

$version = 0;
if($rs = $db->query("SELECT v FROM `{$dbconfig['dbqz']}_config` WHERE k='version'")){
	$version = $rs->fetchColumn();
}

$latest_version = 0;
$sql_files = getUpgradeSqlFiles(__DIR__ . '/', $version, $latest_version);

if($latest_version == 0){
	exit('未找到任何升级文件');
}

if(empty($sql_files)){
	exit('你的网站已经升级到最新版本了');
}

$success=0;$error=0;$errorMsg=null;
foreach ($sql_files as $sql_file) {
	$sqls = explode(';', file_get_contents($sql_file));
	foreach ($sqls as $value) {
		$value=trim($value);
		if(empty($value))continue;
		$value = str_replace('pre_',$dbconfig['dbqz'].'_',$value);
		if($db->exec($value)===false){
			$error++;
			$dberror=$db->errorInfo();
			$errorMsg.=$dberror[2]."<br>";
		}else{
			$success++;
		}
	}
}
$db->exec("UPDATE `{$dbconfig['dbqz']}_config` SET `v` = '$latest_version' where `k` = 'version'");

// 升级后必须清掉所有缓存层（包括进程内静态 + APCu 共享内存 + Redis + MySQL pre_cache），
// 否则 PHP-FPM worker 内的 processCache 仍会返回旧的 conf['version']，
// 触发 common.php 中 "请先完成网站升级" 的判断。
$DB = $db;
$_CACHE = [];
$cache = new \lib\Cache();
$cache->clear('config');

echo '成功执行SQL语句'.$success.'条！<br/>';
if($errorMsg){
//echo '<div class="alert alert-danger text-center" role="alert">'.$errorMsg.'</div>';
}
echo '<hr/><a href="/">点此返回首页</a>';
?>
