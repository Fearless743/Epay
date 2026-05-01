<?php

class chuangyupay_plugin
{
    const API_BASE = 'https://api.chuangyugou.com';

    public static $info = [
        'name'     => 'chuangyupay',
        'showname' => '创鱼支付',
        'author'   => '',
        'link'     => '',
        'types'    => ['alipay', 'wxpay'],
        'inputs'   => [
            'user_key' => [
                'name' => '用户密钥',
                'type' => 'input',
                'note' => '在创鱼平台用户设置中申请的密钥',
            ],
            'product_id' => [
                'name' => '商品ID',
                'type' => 'input',
                'note' => '创鱼平台的商品ID',
            ],
            'specification_id' => [
                'name' => '规格ID',
                'type' => 'input',
                'note' => '创鱼平台的规格ID',
            ],
        ],
        'select' => null,
        'note'   => '创鱼平台不会主动通知支付结果，需配合定时任务轮询查询订单状态。定时任务地址：<a href="cron.php?do=plugin&channel={channel_id}&key={cronkey}" target="_blank">cron.php?do=plugin&channel={channel_id}&key={cronkey}</a>',
    ];

    public static function submit(): array
    {
        global $order;
        return self::_getOrCreateResult($order['typename']);
    }

    public static function mapi()
    {
        global $order;
        return self::_getOrCreateResult($order['typename']);
    }

    private static function _getOrCreateResult(string $typename): array
    {
        global $order;

        // 已创建过API订单，直接复用
        if (!empty($order['api_trade_no']) && !empty($order['payurl'])) {
            return self::_showResult($typename, $order['payurl']);
        }

        try {
            $result = self::_createOrder($typename);
        } catch (\Exception $e) {
            return ['type' => 'error', 'msg' => $e->getMessage()];
        }

        \lib\Payment::updateOrder(TRADE_NO, $result['order_no']);
        if (!empty($result['payment_url'])) {
            \lib\Payment::updateOrderPayUrl(TRADE_NO, $result['payment_url']);
        }

        return self::_showResult($typename, $result['payment_url']);
    }

    private static function _createOrder(string $typename): array
    {
        global $channel, $order;

        $payment_method = self::_getPaymentMethod($typename);

        $parameter = [
            'product_id'       => trim($channel['product_id']),
            'specification_id' => trim($channel['specification_id']),
            'user_key'         => trim($channel['user_key']),
            'quantity'         => floatval($order['realmoney']),
            'payment_method'   => $payment_method,
            'order_title'      => mb_substr($order['name'], 0, 128),
        ];

        $url  = self::API_BASE . '/api/pay/create_order';
        $data = self::_post($url, $parameter);

        if (!is_array($data)) {
            throw new \Exception('请求失败，请检查接口地址是否正确');
        }

        if ($data['code'] != 200) {
            throw new \Exception('订单创建失败：' . ($data['msg'] ?? '未知错误'));
        }

        return $data['data'];
    }

    private static function _showResult(string $typename, string $payurl): array
    {
        if ($typename == 'alipay') {
            if (checkalipay()) {
                return ['type' => 'jump', 'url' => $payurl];
            }
            return ['type' => 'qrcode', 'page' => 'alipay_qrcode', 'url' => $payurl];
        } else {
            if (checkwechat()) {
                return ['type' => 'jump', 'url' => $payurl];
            } elseif (checkmobile()) {
                return ['type' => 'qrcode', 'page' => 'wxpay_wap', 'url' => $payurl];
            } else {
                return ['type' => 'qrcode', 'page' => 'wxpay_qrcode', 'url' => $payurl];
            }
        }
    }

    public static function notify()
    {
        global $channel, $order;

        // 创鱼平台不主动发送通知
        // 保留此方法以供未来扩展，或处理可能的服务端回调
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['order_no']) && !empty($input['status'])) {
            if ($input['order_no'] == $order['api_trade_no'] && $input['status'] == 'paid') {
                processNotify($order, $input['order_no']);
                exit('ok');
            }
        }
        exit('ok');
    }

    public static function return(): array
    {
        return ['type' => 'page', 'page' => 'return'];
    }

    // 定时任务轮询查询订单状态
    // 通过 cron.php?do=plugin&channel={id}&key={key} 调用
    public static function _cron($channel)
    {
        global $DB;

        $limit = 20;

        $orders = $DB->getAll(
            "SELECT * FROM pre_order WHERE channel=:channel AND status=0 AND addtime>DATE_SUB(NOW(), INTERVAL 45 MINUTE) ORDER BY addtime ASC LIMIT {$limit}",
            [':channel' => $channel['id']]
        );

        if (empty($orders)) {
            echo '创鱼支付[' . $channel['name'] . ']：没有待查询的订单<br/>';
            return;
        }

        $user_key = trim($channel['user_key']);
        $processed = 0;

        foreach ($orders as $order) {
            // 跳过没有平台订单号的
            if (empty($order['api_trade_no'])) {
                continue;
            }

            // 检查查询频次限制：同一订单5分钟内最多3次
            $ext = !empty($order['ext']) ? unserialize($order['ext']) : [];
            $queryCount    = intval($ext['chuangyu_query_count'] ?? 0);
            $lastQueryTime = $ext['chuangyu_last_query_time'] ?? '';

            if ($queryCount >= 3 && !empty($lastQueryTime) && strtotime($lastQueryTime) > time() - 300) {
                continue;
            }

            // 调用查询接口
            $parameter = [
                'order_no' => $order['api_trade_no'],
                'user_key' => $user_key,
            ];

            $url  = self::API_BASE . '/api/pay/query_order';
            $data = self::_post($url, $parameter);

            if (!is_array($data) || $data['code'] != 200) {
                echo '订单查询失败：' . $order['trade_no'] . ' - ' . ($data['msg'] ?? '无响应') . '<br/>';
                continue;
            }

            $processed++;

            // 更新查询计数
            $ext['chuangyu_query_count']     = $queryCount + 1;
            $ext['chuangyu_last_query_time'] = date('Y-m-d H:i:s');
            \lib\Payment::updateOrderExt($order['trade_no'], $ext);

            $status = $data['data']['status'];
            echo '订单 ' . $order['trade_no'] . '（平台订单号：' . $order['api_trade_no'] . '）状态：' . $status;

            if ($status == 'paid') {
                processNotify($order, $data['data']['order_no']);
                echo ' - 已处理支付成功<br/>';
            } elseif ($status == 'expired' || $status == 'failed') {
                echo ' - 已过期/失败，不再轮询<br/>';
            } else {
                echo '<br/>';
            }
        }

        echo '创鱼支付[' . $channel['name'] . ']：轮询完成，共处理 ' . $processed . ' 个订单<br/>';
    }

    private static function _getPaymentMethod(string $typename): string
    {
        $map = [
            'alipay' => 'alipay',
            'wxpay'  => 'wechat',
        ];
        return $map[$typename] ?? 'alipay';
    }

    private static function _post(string $url, array $json)
    {
        $headers = [
            'Accept: */*',
            'Accept-Language: zh-CN,zh;q=0.8',
            'Connection: close',
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $resp = curl_exec($ch);
        curl_close($ch);

        return json_decode($resp, true);
    }
}
