<?php

class usdtpay_plugin
{
	static public $info = [
		'name'        => 'usdtpay',
		'showname'    => 'USDT代付插件',
		'author'      => 'Epay',
		'link'        => '',
		'types'       => ['usdt'],
		'transtypes'  => ['usdt'],
		'inputs' => [
			'appurl' => [
				'name' => '接口地址',
				'type' => 'input',
				'note' => 'USDT代付接口地址，必须以http://或https://开头',
			],
			'appid' => [
				'name' => '商户ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'textarea',
				'note' => '用于签名',
			],
			'appsecret' => [
				'name' => '通知密钥',
				'type' => 'input',
				'note' => '用于验证回调',
			],
			'network' => [
				'name' => '默认网络',
				'type' => 'select',
				'options' => [
					'trc20' => 'TRC20',
					'erc20' => 'ERC20',
					'bep20' => 'BEP20',
					'solana' => 'Solana',
					'polygon' => 'Polygon',
					'arbitrum' => 'Arbitrum',
				],
				'note' => '默认使用的USDT网络',
			],
		],
		'select' => null,
		'note' => '通用的USDT代付（转账）插件，需配合支持USDT代付的接口使用。',
	];

	// 代付（转账）
	static public function transfer($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		$params = [
			'merchant_id' => $channel['appid'],
			'out_biz_no' => $bizParam['out_biz_no'],
			'address' => $bizParam['payee_account'],
			'name' => $bizParam['payee_real_name'],
			'amount' => $bizParam['money'],
			'network' => $channel['network'] ?? 'trc20',
			'currency' => 'USDT',
			'remark' => $bizParam['transfer_desc'],
			'notify_url' => self::_getNotifyUrl($channel['id']),
		];
		$params['sign'] = self::_sign($params, $channel['appkey']);

		$url = rtrim($channel['appurl'], '/') . '/api/transfer/submit';
		$data = self::_post($url, $params);

		if(!$data){
			return ['code'=>-1, 'msg'=>'请求代付接口失败'];
		}

		if($data['code'] == 0){
			return ['code'=>0, 'status'=>$data['status']??0, 'orderid'=>$data['orderid']??$bizParam['out_biz_no'], 'paydate'=>$data['paydate']??null];
		}else{
			return ['code'=>-1, 'errcode'=>$data['errcode']??null, 'msg'=>$data['msg']??'代付请求失败'];
		}
	}

	// 转账查询
	static public function transfer_query($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		$params = [
			'merchant_id' => $channel['appid'],
			'out_biz_no' => $bizParam['out_biz_no'],
		];
		$params['sign'] = self::_sign($params, $channel['appkey']);

		$url = rtrim($channel['appurl'], '/') . '/api/transfer/query';
		$data = self::_post($url, $params);

		if(!$data){
			return ['code'=>-1, 'msg'=>'查询代付接口失败'];
		}

		if($data['code'] == 0){
			return ['code'=>0, 'status'=>$data['status'], 'amount'=>$data['amount']??null, 'paydate'=>$data['paydate']??null, 'errmsg'=>$data['errmsg']??null];
		}else{
			return ['code'=>-1, 'msg'=>$data['msg']??'查询失败'];
		}
	}

	// 余额查询
	static public function balance_query($channel, $bizParam){
		if(empty($channel))exit();

		$params = [
			'merchant_id' => $channel['appid'],
		];
		$params['sign'] = self::_sign($params, $channel['appkey']);

		$url = rtrim($channel['appurl'], '/') . '/api/balance/query';
		$data = self::_post($url, $params);

		if(!$data){
			return ['code'=>-1, 'msg'=>'查询余额失败'];
		}

		if($data['code'] == 0){
			return ['code'=>0, 'balance'=>$data['balance']??0];
		}else{
			return ['code'=>-1, 'msg'=>$data['msg']??'查询失败'];
		}
	}

	// 转账回调处理
	static public function transfernotify(){
		global $channel;

		$data = $_POST;
		if(empty($data)) exit('fail');

		$sign = $data['sign'] ?? '';
		unset($data['sign']);
		$localSign = self::_sign($data, $channel['appsecret']);

		if($sign !== $localSign){
			exit('sign error');
		}

		$biz_no = $data['out_biz_no'] ?? '';
		$status = intval($data['status'] ?? 0);

		if($biz_no && $status > 0){
			\lib\Transfer::processNotify($biz_no, $status == 1 ? 1 : 2, $data['errmsg'] ?? null);
			exit('success');
		}

		exit('fail');
	}

	private static function _sign($params, $key){
		ksort($params);
		$signStr = '';
		foreach($params as $k => $v){
			if($k == 'sign' || $v === '' || $v === null) continue;
			$signStr .= $k . '=' . $v . '&';
		}
		$signStr = rtrim($signStr, '&');
		return md5($signStr . $key);
	}

	private static function _post($url, $data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
		$resp = curl_exec($ch);
		curl_close($ch);
		return json_decode($resp, true);
	}

	private static function _getNotifyUrl($channelId){
		global $conf;
		return $conf['localurl'] . 'pay/transfernotify/' . $channelId . '/';
	}
}
