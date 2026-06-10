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
                "name" => "买家用户名",
                "type" => "input",
                "note" => "创鱼平台买家账号的用户名",
            ],
            "password" => [
                "name" => "买家密码",
                "type" => "input",
                "note" => "创鱼平台买家账号的密码",
            ],
            "seller_username" => [
                "name" => "卖家用户名",
                "type" => "input",
                "note" => "创鱼平台卖家账号的用户名（退款时需要）",
            ],
            "seller_password" => [
                "name" => "卖家密码",
                "type" => "input",
                "note" => "创鱼平台卖家账号的密码（退款时需要）",
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
            "quantity" => floatval($order["realmoney"]) * 100,
            "payment_method" => $payment_method,
            "order_title" => $order["trade_no"],
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

    /**
     * 通过订单编号（order_sn）查找创鱼平台内部 INT id
     *  workaround：创鱼退款 API 的 order_id 参数实际是 INT 主键，但 find() 有 bug 会把字符串当主键查返回 null
     *  所以必须先通过列表接口找到 order_sn 对应的 INT id，再用该 id 调用退款
     */
    private static function _findOrderIdBySn(string $orderSn, string $token): ?int
    {
        $url = self::API_BASE . "/api/orders/lists";
        $page = 1;

        do {
            $data = self::_post($url, [
                "order_type" => 1,
                "order_sn" => "",
                "order_status" => "",
                "delivery_status" => "",
                "evaluate_status" => "",
                "refund_status" => "",
                "page" => $page,
                "limit" => 50,
            ], $token);

            if (!is_array($data) || $data["code"] != 200) {
                return null;
            }

            $orderList = $data["data"]["data"] ?? [];
            $lastPage = $data["data"]["last_page"] ?? 1;

            foreach ($orderList as $cyOrder) {
                if (($cyOrder["order_sn"] ?? "") === $orderSn) {
                    return intval($cyOrder["id"]);
                }
            }

            $page++;
        } while ($page <= $lastPage);

        return null;
    }

    /**
     * 退款处理
     * 流程：买家发起退款 → 卖家同意退款
     */
    public static function refund($order): array
    {
        global $channel;

        $orderSn = trim($order["api_trade_no"]);
        if ($orderSn === '') {
            return ["code" => -1, "msg" => "订单未关联创鱼平台订单号"];
        }

        $refundFee = floatval($order["refundmoney"]);
        if ($refundFee <= 0) {
            return ["code" => -1, "msg" => "退款金额必须大于0"];
        }

        try {
            // 0. 通过订单编号查找创鱼平台内部 INT id（workaround：创鱼 API 的 order_id 参数实际是 INT 主键）
            $buyerToken = self::_login($channel, "buyer");
            $intId = self::_findOrderIdBySn($orderSn, $buyerToken);

            if ($intId === 0 || $intId === null) {
                return ["code" => -1, "msg" => "未在创鱼平台找到订单（order_sn={$orderSn}），请确认订单已创建成功"];
            }

            // 1. 买家发起退款
            $refundUrl = self::API_BASE . "/api/orders/refundOrder";
            $refundData = self::_post($refundUrl, [
                "order_id" => (string) $intId,
                "refund_case" => "个人原因（不喜欢/不想要）",
            ], $buyerToken);

            if (!is_array($refundData) || $refundData["code"] != 200) {
                return ["code" => -1, "msg" => "发起退款失败：原始响应：" . json_encode($refundData, JSON_UNESCAPED_UNICODE)];
            }

            // 2. 卖家同意退款
            $sellerToken = self::_login($channel, "seller");

            $checkUrl = self::API_BASE . "/api/orders/sellerCheckRefund";
            $checkData = self::_post($checkUrl, [
                "order_id" => (string) $intId,
                "refund_status" => 2,
                "refund_fail_case" => "",
                "price" => number_format($refundFee, 2, ".", ""),
            ], $sellerToken);

            if (!is_array($checkData) || $checkData["code"] != 200) {
                return ["code" => -1, "msg" => "卖家同意退款失败：原始响应：" . json_encode($checkData, JSON_UNESCAPED_UNICODE)];
            }

            return [
                "code" => 0,
                "trade_no" => $order["trade_no"],
                "refund_fee" => $refundFee,
            ];
        } catch (\Exception $e) {
            return ["code" => -1, "msg" => $e->getMessage()];
        }
    }

    // 定时任务轮询：查询平台已支付未收货订单，匹配系统订单号后收货+标记已支付
    // 通过 cron.php?do=plugin&channel={id}&key={key} 调用
    public static function _cron($channel)
    {
        global $DB;

        $cronStart = microtime(true);

        // 查询最近24小时内未支付且已有平台订单号的订单
        $t0 = microtime(true);
        $orders = $DB->getAll(
            "SELECT * FROM pre_order WHERE channel=:channel AND deleted=0 AND status=0 AND api_trade_no IS NOT NULL AND api_trade_no != '' AND addtime>DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY addtime ASC",
            [":channel" => $channel["id"]],
        );
        $tDB = microtime(true) - $t0;

        if (empty($orders)) {
            echo "创鱼支付[" . $channel["name"] . "]：没有待查询的订单<br/>";
            echo "耗时统计：数据库查询 " . number_format($tDB * 1000, 1) . "ms<br/>";
            return;
        }

        // 构建系统订单号 → 订单 的映射
        $remarkMap = [];
        foreach ($orders as $order) {
            $remarkMap[$order["trade_no"]] = $order;
        }

        // 登录
        $t0 = microtime(true);
        try {
            $token = self::_login($channel);
        } catch (\Exception $e) {
            $tLogin = microtime(true) - $t0;
            echo "创鱼轮询登录失败：" . $e->getMessage() . "<br/>";
            echo "耗时统计：数据库查询 " . number_format($tDB * 1000, 1) . "ms，登录 " . number_format($tLogin * 1000, 1) . "ms<br/>";
            return;
        }
        $tLogin = microtime(true) - $t0;

        // 查询平台已支付+已发货未收货的订单（数量少，翻页快）
        $matchedCount = 0;
        $receivedCount = 0;
        $apiErrors = 0;
        $page = 1;
        $tApiTotal = 0;
        $tMatchTotal = 0;
        $pageCount = 0;
        $url = self::API_BASE . "/api/orders/lists";
        $hasCredentials = !empty($channel["username"]) && !empty($channel["password"]);

        do {
            $parameter = [
                "order_type" => 1,
                "order_sn" => "",
                "order_status" => 2,
                "delivery_status" => 2,
                "evaluate_status" => "",
                "refund_status" => "",
                "page" => $page,
                "limit" => 50,
            ];

            $t0 = microtime(true);
            $data = self::_post($url, $parameter, $token);
            $tPage = microtime(true) - $t0;
            $tApiTotal += $tPage;
            $pageCount++;

            if (!is_array($data) || $data["code"] != 200) {
                $apiErrors++;
                echo "第 {$page} 页订单列表查询失败（耗时 " . number_format($tPage * 1000, 1) . "ms）：" . ($data["msg"] ?? "无响应") . "<br/>";
                break;
            }

            $orderList = $data["data"]["data"] ?? [];
            $lastPage = $data["data"]["last_page"] ?? 1;

            $t0 = microtime(true);
            foreach ($orderList as $cyOrder) {
                // take_time 不为空说明已收货，跳过
                if (!empty($cyOrder["take_time"])) {
                    continue;
                }

                $remark = $cyOrder["gather_info"] ?? $cyOrder["remark"] ?? "";
                $title = $cyOrder["order_title"] ?? "";
                $searchStr = $remark . " " . $title;

                foreach ($remarkMap as $tradeNo => $localOrder) {
                    if (stripos($searchStr, $tradeNo) !== false) {
                        $matchedCount++;
                        $orderSn = $cyOrder["order_sn"];
                        $orderId = intval($cyOrder["id"]);

                        // 确认收货
                        if ($hasCredentials) {
                            try {
                                self::_confirmTake($orderId, $token);
                                $receivedCount++;
                                echo "订单 {$tradeNo}（{$orderSn}）：确认收货成功<br/>";
                            } catch (\Exception $e) {
                                echo "订单 {$tradeNo}（{$orderSn}）：收货失败 - " . $e->getMessage() . "<br/>";
                            }
                        }

                        // 标记本地订单为已支付
                        if ($localOrder["status"] == 0) {
                            processNotify($localOrder, $orderSn);
                            echo "订单 {$tradeNo}（{$orderSn}）：已支付，处理成功<br/>";
                        } else {
                            echo "订单 {$tradeNo}（{$orderSn}）：已由其他方式处理<br/>";
                        }

                        break;
                    }
                }
            }
            $tMatchPage = microtime(true) - $t0;
            $tMatchTotal += $tMatchPage;

            echo "第 {$page} 页：API 耗时 " . number_format($tPage * 1000, 1) . "ms，匹配耗时 " . number_format($tMatchPage * 1000, 1) . "ms，返回 " . count($orderList) . " 条<br/>";

            $page++;
        } while ($page <= $lastPage);

        if ($matchedCount == 0 && $apiErrors == 0) {
            echo "创鱼支付[" . $channel["name"] . "]：未匹配到任何订单（共查询 " . count($remarkMap) . " 个待支付订单）<br/>";
        }

        echo "创鱼支付[" .
            $channel["name"] .
            "]：轮询完成，匹配 " .
            $matchedCount .
            " 个订单，收货 " .
            $receivedCount .
            " 个<br/>";

        $tTotal = microtime(true) - $cronStart;
        echo "<br/><b>耗时统计</b>：数据库查询 " . number_format($tDB * 1000, 1) . "ms"
            . "，登录 " . number_format($tLogin * 1000, 1) . "ms"
            . "，API请求({$pageCount}页) " . number_format($tApiTotal * 1000, 1) . "ms"
            . "，订单匹配 " . number_format($tMatchTotal * 1000, 1) . "ms"
            . "，<b>总计 " . number_format($tTotal * 1000, 1) . "ms</b><br/>";
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
     * 确认收货（调用创鱼 API）
     * 成功返回 true，失败抛出异常
     */
    private static function _confirmTake(int $orderId, string $token): bool
    {
        $url = self::API_BASE . "/api/orders/confirmTake";
        $data = self::_post($url, ["order_id" => $orderId], $token);

        if (!is_array($data) || $data["code"] != 200) {
            throw new \Exception("确认收货失败：" . ($data["msg"] ?? "无响应"));
        }

        return true;
    }

    private static function _login($channel, string $role = "buyer"): string
    {
        global $CACHE;

        if ($role === "seller") {
            $username = trim($channel["seller_username"]);
            $password = trim($channel["seller_password"]);
            if (empty($username) || empty($password)) {
                throw new \Exception("未配置创鱼卖家用户名或密码");
            }
        } else {
            $username = trim($channel["username"]);
            $password = trim($channel["password"]);
            if (empty($username) || empty($password)) {
                throw new \Exception("未配置创鱼买家用户名或密码");
            }
        }

        // 检查缓存的 token（存到 cache 表，k 列长度有限，key 截短避免截断）
        $cacheKey = "chuangyupay_" . $role . "_" . substr(md5($username), 0, 16);
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

        $t0 = microtime(true);
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
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        $elapsed = (microtime(true) - $t0) * 1000;

        if ($httpCode < 200 || $httpCode >= 300) {
            return ["_http_code" => $httpCode, "_raw" => $resp, "_curl_error" => $curlErr];
        }

        $result = json_decode($resp, true);
        if ($result === null && !empty($resp)) {
            return ["_http_code" => $httpCode, "_raw" => $resp, "_decode_error" => json_last_error_msg()];
        }

        return $result;
    }
}
