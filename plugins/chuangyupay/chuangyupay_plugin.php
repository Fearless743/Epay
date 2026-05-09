<?php

class chuangyupay_plugin
{
    const API_BASE = "https://api.chuangyugou.com";

    public static $info = [
        "name" => "chuangyupay",
        "showname" => "创鱼支付",
        "author" => "",
        "link" => "",
        "types" => ["alipay", "wxpay"],
        "inputs" => [
            "user_key" => [
                "name" => "用户密钥",
                "type" => "input",
                "note" => "在创鱼平台用户设置中申请的密钥",
            ],
            "product_id" => [
                "name" => "商品ID",
                "type" => "input",
                "note" => "创鱼平台的商品ID",
            ],
            "specification_id" => [
                "name" => "规格ID",
                "type" => "input",
                "note" => "创鱼平台的规格ID",
            ],
            "username" => [
                "name" => "用户名",
                "type" => "input",
                "note" => "在创鱼平台用户设置中申请的用户名",
            ],
            "password" => [
                "name" => "密码",
                "type" => "input",
                "note" => "在创鱼平台用户设置中申请的密码",
            ],
        ],
        "select" => null,
        "note" =>
            '创鱼平台不会主动通知支付结果，需配合定时任务轮询查询订单状态。定时任务地址：<a href="cron.php?do=plugin&channel={channel_id}&key={cronkey}" target="_blank">cron.php?do=plugin&channel={channel_id}&key={cronkey}</a>',
    ];

    public static function submit(): array
    {
        global $order;
        return self::_getOrCreateResult($order["typename"]);
    }

    public static function mapi()
    {
        global $order;
        return self::_getOrCreateResult($order["typename"]);
    }

    private static function _getOrCreateResult(string $typename): array
    {
        global $order;

        // 已创建过API订单，直接复用
        if (!empty($order["api_trade_no"]) && !empty($order["payurl"])) {
            return self::_showResult($typename, $order["payurl"]);
        }

        try {
            $result = self::_createOrder($typename);
        } catch (\Exception $e) {
            return ["type" => "error", "msg" => $e->getMessage()];
        }

        \lib\Payment::updateOrder(TRADE_NO, $result["order_no"]);
        if (!empty($result["payment_url"])) {
            \lib\Payment::updateOrderPayUrl(TRADE_NO, $result["payment_url"]);
        }

        return self::_showResult($typename, $result["payment_url"]);
    }

    private static function _createOrder(string $typename): array
    {
        global $channel, $order;

        $payment_method = self::_getPaymentMethod($typename);

        $parameter = [
            "product_id" => trim($channel["product_id"]),
            "specification_id" => trim($channel["specification_id"]),
            "user_key" => trim($channel["user_key"]),
            "quantity" => floatval($order["realmoney"]),
            "payment_method" => $payment_method,
            "order_title" => mb_substr($order["name"], 0, 128),
        ];

        $url = self::API_BASE . "/api/pay/create_order";
        $data = self::_post($url, $parameter);

        if (!is_array($data)) {
            throw new \Exception("请求失败，请检查接口地址是否正确");
        }

        if ($data["code"] != 200) {
            throw new \Exception(
                "订单创建失败：" . ($data["msg"] ?? "未知错误"),
            );
        }

        return $data["data"];
    }

    private static function _showResult(string $typename, string $payurl): array
    {
        if ($typename == "alipay") {
            if (checkalipay()) {
                return ["type" => "jump", "url" => $payurl];
            }
            return [
                "type" => "qrcode",
                "page" => "alipay_qrcode",
                "url" => $payurl,
            ];
        } else {
            if (checkwechat()) {
                return ["type" => "jump", "url" => $payurl];
            } elseif (checkmobile()) {
                return [
                    "type" => "qrcode",
                    "page" => "wxpay_wap",
                    "url" => $payurl,
                ];
            } else {
                return [
                    "type" => "qrcode",
                    "page" => "wxpay_qrcode",
                    "url" => $payurl,
                ];
            }
        }
    }

    public static function notify()
    {
        global $channel, $order;

        // 创鱼平台不主动发送通知
        // 保留此方法以供未来扩展，或处理可能的服务端回调
        $input = json_decode(file_get_contents("php://input"), true);
        if (!empty($input["order_no"]) && !empty($input["status"])) {
            if (
                $input["order_no"] == $order["api_trade_no"] &&
                $input["status"] == "paid"
            ) {
                processNotify($order, $input["order_no"]);
                exit("ok");
            }
        }
        exit("ok");
    }

    public static function return(): array
    {
        return ["type" => "page", "page" => "return"];
    }

    // 定时任务轮询查询订单状态
    // 通过 cron.php?do=plugin&channel={id}&key={key} 调用
    public static function _cron($channel)
    {
        global $DB;

        $limit = 20;

        $orders = $DB->getAll(
            "SELECT * FROM pre_order WHERE channel=:channel AND status=0 AND addtime>DATE_SUB(NOW(), INTERVAL 45 MINUTE) ORDER BY addtime ASC LIMIT {$limit}",
            [":channel" => $channel["id"]],
        );

        if (empty($orders)) {
            echo "创鱼支付[" . $channel["name"] . "]：没有待查询的订单<br/>";
            return;
        }

        $user_key = trim($channel["user_key"]);
        $processed = 0;

        foreach ($orders as $order) {
            // 跳过没有平台订单号的
            if (empty($order["api_trade_no"])) {
                continue;
            }

            // 检查查询频次限制：同一订单5分钟内最多3次
            $ext = !empty($order["ext"]) ? unserialize($order["ext"]) : [];
            $queryCount = intval($ext["chuangyu_query_count"] ?? 0);
            $lastQueryTime = $ext["chuangyu_last_query_time"] ?? "";

            if (
                $queryCount >= 3 &&
                !empty($lastQueryTime) &&
                strtotime($lastQueryTime) > time() - 300
            ) {
                continue;
            }

            // 调用查询接口
            $parameter = [
                "order_no" => $order["api_trade_no"],
                "user_key" => $user_key,
            ];

            $url = self::API_BASE . "/api/pay/query_order";
            $data = self::_post($url, $parameter);

            if (!is_array($data) || $data["code"] != 200) {
                echo "订单查询失败：" .
                    $order["trade_no"] .
                    " - " .
                    ($data["msg"] ?? "无响应") .
                    "<br/>";
                continue;
            }

            $processed++;

            // 更新查询计数
            $ext["chuangyu_query_count"] = $queryCount + 1;
            $ext["chuangyu_last_query_time"] = date("Y-m-d H:i:s");
            \lib\Payment::updateOrderExt($order["trade_no"], $ext);

            $status = $data["data"]["status"];
            echo "订单 " .
                $order["trade_no"] .
                "（平台订单号：" .
                $order["api_trade_no"] .
                "）状态：" .
                $status;

            if ($status == "paid") {
                processNotify($order, $data["data"]["order_no"]);
                echo " - 已处理支付成功<br/>";
            } elseif ($status == "expired" || $status == "failed") {
                echo " - 已过期/失败，不再轮询<br/>";
            } else {
                echo "<br/>";
            }
        }

        echo "创鱼支付[" .
            $channel["name"] .
            "]：轮询完成，共处理 " .
            $processed .
            " 个订单<br/>";

        // 自动收货
        if (!empty($channel["username"]) && !empty($channel["password"])) {
            echo "<br/>";
            self::_autoReceive($channel);
        }
    }

    private static function _getPaymentMethod(string $typename): string
    {
        $map = [
            "alipay" => "alipay",
            "wxpay" => "wechat",
        ];
        return $map[$typename] ?? "alipay";
    }

    /**
     * 获取创鱼平台待收货的订单列表
     * 返回所有 delivery_status=2 且未确认收货的订单
     */
    private static function _getPendingReceiveOrders($channel): array
    {
        $token = self::_login($channel);

        $page = 1;
        $allOrders = [];

        do {
            $parameter = [
                "order_type" => 1,
                "order_sn" => "",
                "order_status" => 2,
                "delivery_status" => 2,
                "evaluate_status" => "",
                "refund_status" => "",
                "page" => $page,
                "limit" => 10,
            ];

            $url = self::API_BASE . "/api/orders/lists";
            $data = self::_post($url, $parameter, $token);

            if (!is_array($data) || $data["code"] != 200) {
                throw new \Exception("获取订单列表失败：" . ($data["msg"] ?? "无响应"));
            }

            $orders = $data["data"]["data"] ?? [];
            $lastPage = $data["data"]["last_page"] ?? 1;

            foreach ($orders as $order) {
                // take_time 为 null 表示未确认收货
                if (empty($order["take_time"])) {
                    $allOrders[] = $order;
                }
            }

            $page++;
        } while ($page <= $lastPage);

        return $allOrders;
    }

    /**
     * 确认收货（调用创鱼 API）
     * 成功返回 true，失败抛出异常
     */
    private static function _confirmTake($channel, int $orderId): bool
    {
        $token = self::_login($channel);

        $url = self::API_BASE . "/api/orders/confirmTake";
        $data = self::_post($url, ["order_id" => $orderId], $token);

        if (!is_array($data) || $data["code"] != 200) {
            throw new \Exception("确认收货失败：" . ($data["msg"] ?? "无响应"));
        }

        return true;
    }

    /**
     * 自动收货
     * 由 _cron() 在轮询支付状态后自动调用（需配置 username 和 password）
     */
    public static function _autoReceive($channel)
    {
        try {
            $orders = self::_getPendingReceiveOrders($channel);

            if (empty($orders)) {
                echo "创鱼支付[" . $channel["name"] . "]：没有待收货的订单<br/>";
                return;
            }

            echo "创鱼支付[" . $channel["name"] . "]：找到 " . count($orders) . " 笔待收货订单<br/>";

            $success = 0;
            $fail = 0;

            foreach ($orders as $order) {
                $orderSn = $order["order_sn"];
                $orderId = intval($order["id"]);

                try {
                    self::_confirmTake($channel, $orderId);
                    echo "订单 " . $orderSn . "（ID:" . $orderId . "）：确认收货成功<br/>";
                    $success++;
                } catch (\Exception $e) {
                    echo "订单 " . $orderSn . "（ID:" . $orderId . "）：" . $e->getMessage() . "<br/>";
                    $fail++;
                }
            }

            echo "创鱼支付[" . $channel["name"] . "]：自动收货完成，成功 " . $success . " 笔，失败 " . $fail . " 笔<br/>";
        } catch (\Exception $e) {
            echo "创鱼自动收货异常：" . $e->getMessage() . "<br/>";
        }
    }

    private static function _login($channel): string
    {
        global $CACHE;

        $username = trim($channel["username"]);
        $password = trim($channel["password"]);

        if (empty($username) || empty($password)) {
            throw new \Exception("未配置创鱼登录用户名或密码");
        }

        // 检查缓存的 token（存到 pre_cache 表）
        $cacheKey = "chuangyupay_token_" . md5($username);
        $cached = $CACHE->read($cacheKey);
        if (!empty($cached)) {
            $cached = @unserialize($cached);
            if (!empty($cached["token"]) && !empty($cached["expires_at"]) && $cached["expires_at"] > time() + 60) {
                return $cached["token"];
            }
        }

        // 登录获取新 token
        $parameter = [
            "username" => $username,
            "password" => $password,
            "code" => "",
        ];

        $url = self::API_BASE . "/api/login/index";
        $data = self::_post($url, $parameter);

        if (!is_array($data) || $data["code"] != 200) {
            throw new \Exception("创鱼登录失败：" . ($data["msg"] ?? "无响应"));
        }

        $token = $data["data"];

        // 解析 JWT 获取过期时间
        $parts = explode(".", $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            $expiresAt = $payload["exp"] ?? (time() + 82800);
        } else {
            $expiresAt = time() + 82800;
        }

        // 缓存 token（有效期与 JWT 一致）
        $ttl = $expiresAt - time();
        $CACHE->save($cacheKey, ["token" => $token, "expires_at" => $expiresAt], $ttl);

        return $token;
    }

    private static function _post(string $url, array $json, string $token = null)
    {
        $headers = [
            "Accept: */*",
            "Accept-Language: zh-CN,zh;q=0.8",
            "Connection: close",
            "Content-Type: application/json",
        ];

        if (!empty($token)) {
            $headers[] = "Authorization: " . $token;
        }

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
