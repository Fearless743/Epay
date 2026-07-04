<?php
$clientip=real_ip($conf['ip_type']?$conf['ip_type']:0);

$adminInfo = \lib\AdminAuth::check();
if ($adminInfo) {
    $islogin = 1;
}
if(isset($_COOKIE["user_token"]))
{
	$token=authcode(daddslashes($_COOKIE['user_token']), 'DECODE', SYS_KEY);
	if($token){
		list($uid, $sid, $expiretime) = explode("\t", $token);
		$uid = intval($uid);
		$expiretime = intval($expiretime);
		// 先快速判断 cookie 中的 expiretime，避免每次请求都查 pre_user
		if($uid > 0 && $expiretime > time()){
			$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid limit 1", [':uid'=>$uid]);
			$session=md5($userrow['uid'].$userrow['key'].$password_hash);
			if($userrow && $session==$sid) {
				$islogin2=1;
			}
		}
	}
}
?>