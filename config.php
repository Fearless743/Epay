<?php
/*数据库配置*/
$dbconfig=array(
	'host' => 'localhost', //数据库服务器
	'port' => 3306, //数据库端口
	'user' => '', //数据库用户名
	'pwd' => '', //数据库密码
	'dbname' => '', //数据库名
	'dbqz' => 'pay' //数据表前缀
);
$redisconfig=array(
	'host' => '', //Redis服务器，留空则不使用Redis
	'port' => 6379, //Redis端口
	'auth' => '', //Redis密码，留空则无密码
	'database' => 0, //Redis数据库索引
);
