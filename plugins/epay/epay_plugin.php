<?php

class epay_plugin
{
	static public $info = [
		'name'        => 'epay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '彩虹易支付', //支付插件显示名称
		'author'      => '彩虹', //支付插件作者
		'link'        => '', //支付插件作者链接
		'types'       => ['alipay','qqpay','wxpay','bank','jdpay','douyinpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appurl' => [
				'name' => '接口地址',
				'type' => 'input',
				'note' => '必须以http://或https://开头，以/结尾',
			],
			'appid' => [
				'name' => '商户ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '是否使用mapi接口',
				'type' => 'select',
				'options' => [0=>'否',1=>'是'],
			],
		],
		'select' => null,
		'note' => '如上游平台异步通知不稳定，可配置定时任务轮询订单状态兜底。定时任务地址：<a href="cron.php?do=plugin&channel={channel_id}&key={cronkey}" target="_blank">cron.php?do=plugin&channel={channel_id}&key={cronkey}</a>', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		if($channel['appswitch']==1){
			return ['type'=>'jump','url'=>'/pay/'.$order['typename'].'/'.TRADE_NO.'/'];
		}else{

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");
		$parameter = array(
			"pid" => trim($epay_config['pid']),
			"type" => $order['typename'],
			"notify_url"	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			"return_url"	=> $siteurl.'pay/return/'.TRADE_NO.'/',
			"out_trade_no"	=> TRADE_NO,
			"name"	=> $order['name'],
			"money"	=> $order['realmoney']
		);
		//建立请求
		$epay = new EpayCore($epay_config);
		if(is_https() && substr($epay_config['apiurl'],0,7)=='http://'){
			$jump_url = $epay->getPayLink($parameter);
			return ['type'=>'jump','url'=>$jump_url];
		}else{
			$html_text = $epay->pagePay($parameter, '正在跳转');
			return ['type'=>'html','data'=>$html_text];
		}

		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;
		
		if($channel['appswitch']==1){
			$typename = $order['typename'];
			return self::$typename();
		}else{
			return ['type'=>'jump','url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
        }
	}

	static private function getDevice(){
		global $device, $mdevice;
		if (checkwechat() || $mdevice=='wechat') {
			$device = 'wechat';
		}elseif (checkmobbileqq() || $mdevice=='qq') {
			$device = 'qq';
		}elseif (checkalipay() || $mdevice=='alipay') {
			$device = 'alipay';
		}elseif (checkdouyin() || $mdevice=='douyin') {
			$device = 'douyin';
		}elseif (checkmobile() || $device=='mobile') {
			$device = 'mobile';
		}else{
			$device = 'pc';
		}
		return $device;
	}

	//mapi接口下单
	static private function pay_mapi($type){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");
		$parameter = array(
			"pid" => trim($epay_config['pid']),
			"type" => $type,
			"device" => self::getDevice(),
			"clientip" => $clientip,
			"notify_url"	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			"return_url"	=> $siteurl.'pay/return/'.TRADE_NO.'/',
			"out_trade_no"	=> TRADE_NO,
			"name"	=> $order['name'],
			"money"	=> $order['realmoney']
		);
		//建立请求
		$epay = new EpayCore($epay_config);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($epay, $parameter) {
			$result = $epay->apiPay($parameter);

			if(isset($result['code']) && $result['code']==1){
				if($result['payurl']){
					$method = 'jump';
					$url = $result['payurl'];
				}elseif($result['qrcode']){
					$method = 'qrcode';
					$url = $result['qrcode'];
				}elseif($result['urlscheme']){
					$method = 'scheme';
					$url = $result['urlscheme'];
				}else{
					throw new Exception('未返回支付链接');
				}
			}elseif(isset($result['msg'])){
				throw new Exception($result['msg']);
			}else{
				throw new Exception('获取支付接口数据失败');
			}
			return [$method, $url];
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			list($method, $url) = self::pay_mapi('alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$url];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			list($method, $url) = self::pay_mapi('wxpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}elseif($method == 'scheme'){
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$url];
		}else{
			if(checkwechat()){
				return ['type'=>'jump','url'=>$url];
			} elseif (checkmobile()) {
				return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$url];
			} else {
				return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$url];
			}
		}
	}

	//QQ扫码支付
	static public function qqpay(){
		try{
			list($method, $url) = self::pay_mapi('qqpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			if(checkmobbileqq()){
				return ['type'=>'jump','url'=>$url];
			} elseif(checkmobile() && !isset($_GET['qrcode'])){
				return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$url];
			} else {
				return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$url];
			}
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			list($method, $url) = self::pay_mapi('bank');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$url];
		}
	}

	//京东支付
	static public function jdpay(){
		try{
			list($method, $url) = self::pay_mapi('jdpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}
		
		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'jdpay_qrcode','url'=>$url];
		}
	}

	//抖音支付
	static public function douyinpay(){
		try{
			list($method, $url) = self::pay_mapi('douyinpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			if (checkmobile()) {
				return ['type'=>'qrcode','page'=>'douyinpay_wap','url'=>$url];
			} else {
				return ['type'=>'qrcode','page'=>'douyinpay_qrcode','url'=>$url];
			}
		}
	}

	//异步回调
	//分层校验模式：验签 → 强制成功状态 → 金额校验 → 幂等入账
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");

		$param = !empty($_POST) ? $_POST : $_GET;

		//① 验签（时间常数比较，防时序攻击）
		$epayNotify = new EpayCore($epay_config);
		if(!$epayNotify->verifyNotify()){
			return ['type'=>'html','data'=>'fail'];
		}

		//② 强制交易状态为成功，避免未支付/处理中状态被误入账
		if(!isset($param['trade_status']) || $param['trade_status'] !== 'TRADE_SUCCESS'){
			return ['type'=>'html','data'=>'fail'];
		}

		//③ 商户订单号校验
		if(!isset($param['out_trade_no']) || $param['out_trade_no'] != TRADE_NO){
			return ['type'=>'html','data'=>'fail'];
		}

		//④ 金额校验：必须可解析为金额，且拒绝少付（按分为单位比较）
		if(!isset($param['money']) || !is_numeric($param['money'])){
			return ['type'=>'html','data'=>'fail'];
		}
		$paid = (int) round(((float)$param['money']) * 100);
		$expected = (int) round(((float)$order['realmoney']) * 100);
		if($paid < $expected){
			return ['type'=>'html','data'=>'fail'];
		}

		//⑤ 幂等入账：processOrder 内部对 status IN (0,4) 才真正入账，重复通知安全
		$trade_no = isset($param['trade_no']) ? $param['trade_no'] : null;
		processNotify($order, $trade_no);

		return ['type'=>'html','data'=>'success'];
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");

		//计算得出通知验证结果
		$epayNotify = new EpayCore($epay_config);
		$verify_result = $epayNotify->verifyReturn();
		if($verify_result) {
			//商户订单号
			$out_trade_no = $_GET['out_trade_no'];

			//易支付交易号
			$trade_no = $_GET['trade_no'];

			//交易金额
			$money = $_GET['money'];

			if($_GET['trade_status'] == 'TRADE_SUCCESS') {
				if ($out_trade_no == TRADE_NO && round($money, 2)==round($order['realmoney'], 2)) {
					processReturn($order, $trade_no);
				}else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
			}else{
				return ['type'=>'error','msg'=>'trade_status='.$_GET['trade_status']];
			}
		}
		else {
			//验证失败
			return ['type'=>'error','msg'=>'验证失败！'];
		}
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");

		$epay = new EpayCore($epay_config);
		$result = $epay->refund($order['refund_no'], $order['api_trade_no'], $order['refundmoney']);

		if($result['code'] == 0){
			return ['code'=>0];
		}else{
			return ['code'=>-1, 'msg'=>$result['msg']?$result['msg']:'返回数据解密失败'];
		}
	}

	//定时任务轮询：通过上游 [API]查询单个订单(act=order) 反查待支付订单，金额校验通过后入账
	//通过 cron.php?do=plugin&channel={id}&key={key} 或 cron.php?do=pluginall&plugin=epay&key={key} 调用
	static public function _cron($channel){
		global $DB;

		//查询最近24小时内未支付的订单
		$orders = $DB->getAll("SELECT * FROM pre_order WHERE channel=:channel AND deleted=0 AND status=0 AND addtime>DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY addtime ASC", [':channel'=>$channel['id']]);
		if(empty($orders)){
			echo '彩虹易支付['.$channel['name'].']：没有待查询的订单<br/>';
			return;
		}

		require(PAY_ROOT."inc/epay.config.php");
		require_once(PAY_ROOT."inc/EpayCore.class.php");
		$epay = new EpayCore($epay_config);

		$matched = 0;
		$failed = 0;
		$tStart = microtime(true);

		// 并发反查：分批并行查询所有待支付订单，避免串行逐单 HTTP 在订单量大时拖慢整个 cron
		// 上游为普通 PHP-FPM 服务，并发过大易触发 500/连接失败，取 5 一批
		$mh = null;
		$batch = [];
		$ordersCount = count($orders);
		$orderIndex = 0;
		$batchSize = 5;

		$flushBatch = function() use (&$mh, &$batch, &$matched, &$failed, $epay){
			if(empty($batch)) return;
			if($mh === null){
				$mh = curl_multi_init();
				foreach($batch as $b){
					curl_multi_add_handle($mh, $b['ch']);
				}
			}
			// 执行多句柄（非阻塞推进，直到全部完成）
			do{
				$status = curl_multi_exec($mh, $running);
				if($running){
					curl_multi_select($mh, 0.2);
				}
			}while($running && $status === CURLM_OK);

			// 逐条取回结果并入账
			foreach($batch as $b){
				$httpcode = curl_getinfo($b['ch'], CURLINFO_HTTP_CODE);
				$response = curl_multi_getcontent($b['ch']);
				$result = $response ? json_decode($response, true) : null;
				$order = $b['order'];
				if(!is_array($result) || $result['code']!=1 || empty($result['trade_no'])){
					$failed++;
					echo '订单 '.$order['trade_no'].'：查询失败（'.(is_array($result)&&!empty($result['msg'])?$result['msg']:($httpcode!==200?'HTTP '.$httpcode:'无响应')).'）<br/>';
					continue;
				}
				self::_handleQueryResult($order, $result, $matched, $failed);
			}

			// 清理本批句柄
			foreach($batch as $b){
				curl_multi_remove_handle($mh, $b['ch']);
				curl_close($b['ch']);
			}
			$batch = [];
		};

		while($orderIndex < $ordersCount){
			$order = $orders[$orderIndex++];
			$ch = curl_init($epay->queryOrderUrl($order['trade_no']));
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: */*", "Accept-Language: zh-CN,zh;q=0.8", "Connection: close"]);
			$batch[] = ['ch'=>$ch, 'order'=>$order];
			if(count($batch) >= $batchSize){
				$flushBatch();
			}
			// 批间小停顿，缓解上游短时连接峰值
			usleep(50000);
		}
		// 收尾剩余批次
		if(!empty($batch)){
			$flushBatch();
		}
		if($mh !== null){
			curl_multi_close($mh);
		}

		$tTotal = microtime(true) - $tStart;
		echo '彩虹易支付['.$channel['name'].']：轮询完成，匹配 '.$matched.' 个订单，失败 '.$failed.' 个<br/>';
		echo '<b>耗时统计</b>：总计 '.number_format($tTotal*1000, 1).'ms<br/>';
	}

	//单个订单查询结果处理：状态校验→金额校验→幂等入账
	private static function _handleQueryResult($order, $result, &$matched, &$failed){
		//① 强制支付状态为成功(1=待支付,2=支付成功,3=退款成功,4=异常)
		if(intval($result['status']) != 2){
			return;
		}

		//② 金额校验：按分比较，拒绝少付
		$paid = (int) round((float)$result['money'] * 100);
		$need = (int) round((float)$order['realmoney'] * 100);
		if($paid < $need){
			echo '订单 '.$order['trade_no'].'：金额不符，跳过入账（上游实付 '.$result['money'].'，应收 '.$order['realmoney'].'）<br/>';
			$failed++;
			return;
		}

		//③ 幂等入账：processOrder 内部对 status IN (0,4) 才真正入账，重复轮询安全
		$buyer = !empty($result['buyer']) ? $result['buyer'] : null;
		processNotify($order, $result['trade_no'], $buyer);
		$matched++;
		echo '订单 '.$order['trade_no'].'（'.$result['trade_no'].'）：已支付，处理成功<br/>';
	}

}