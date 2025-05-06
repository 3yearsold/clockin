<?php
if (!function_exists('res')) {
    /**
     * 返回数据
     */
    function res($data = [], $code = 0, $msg = 'success', $type = 'json')
    {
        return [
            'code' => $code,
            'msg'  => $msg,
            'time' => time(),
            'data' => $data,
        ];


    }
}


if (!function_exists('sendPostRequest')) {
    /**
     * 发送 POST 请求
     *
     * @param string $url 请求的 URL
     * @param array $data 发送的数据
     * @return array 响应结果
     */
    function sendPostRequest($url, $data)
    {
        // 初始化 cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

        // 执行请求
        $response = curl_exec($ch);
        curl_close($ch);

        // 返回响应结果
        return json_decode($response, true);
    }
}

if (!function_exists('GetPostRequest')) {
    /**
     * 发送 POST 请求
     *
     * @param string $url 请求的 URL
     * @param array $headers 请求头
     * @param array $data 发送的数据
     * @return array 响应结果
     * @throws Exception
     */
    function GetPostRequest($url,$headers, $data)
    {

        // 初始化 cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 禁用 SSL 验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // 执行请求
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error);
        }
        curl_close($ch);

        // 返回响应结果
        return json_decode($response, true);
    }
}

if (!function_exists('sendTeamMsg')) {
    /**
     * 发送班组审批消息
     *
     * @param string $toUser 名字的openid
     * @param string $name1 申请审批人员
     * @param string $time2 申请时间
     * @param string $thing4 申请项目
     * @param string $urls 跳转链接
     * @return string 响应结果
     */
     function sendTeamMsg($toUser, $name1, $time2, $thing4,$urls)
    {
        $token = get_xcx_token();
        // 微信公众平台的接口地址
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$token}";

        // 模板消息数据
        $data = [
            'touser' => $toUser,
            'template_id' => 'uP-Xjz-o6MtGS0oSu2jqARpsjfar5G83c9ImRrpiOq0',
            'page' => $urls,
            'miniprogram_state' => 'formal',
            'lang' => 'zh_CN',
            'data' => [
                'name1' => [
                    'value' => $name1
                ],
                'time2' => [
                    'value' => $time2
                ],
                'thing4' => [
                    'value' => $thing4
                ]
            ]
        ];

        // 使用封装的方法发送 POST 请求
        $result = sendPostRequest($url, $data);

        // 处理响应
        // dump($result);
        if ($result['errcode'] == 0) {
            return "Message sent successfully.";
        } else {
            return "Failed to send message: " . $result['errmsg'];
        }
    }
}

if (!function_exists('sendClockMsg')) {
    /**
     * 发送打卡提醒消息
     *
     * @param string $toUser 名字的openid
     * @param string $thing1 姓名
     * @param string $phrase3 打卡状态
     * @param string $time6 提醒时间
     * @param string $urls 跳转链接
     * @return string 响应结果
     */
    function sendClockMsg($toUser, $thing1,$phrase3, $time6,$urls)
    {
        $token = get_xcx_token();
        // 微信公众平台的接口地址
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$token}";

        // 模板消息数据
        $data = [
            'touser' => $toUser,
            'template_id' => 'bn1XLIlvAT-3sDT6h67lYW73PWzU2GMW92rjvbqCnls',
            'page' => $urls,
            'miniprogram_state' => 'formal',
            'lang' => 'zh_CN',
            'data' => [
                'thing1' => [
                    'value' => $thing1
                ],
                'phrase3' => [
                    'value' => $phrase3
                ],
                'time6' => [
                    'value' => $time6
                ]
            ]
        ];

        // 使用封装的方法发送 POST 请求
        $result = sendPostRequest($url, $data);

        // 处理响应
        //dump($result);
        if ($result['errcode'] == 0) {
            return "Message sent successfully.";
        } else {
            return "Failed to send message: " . $result['errmsg'];
        }
    }
}
if (!function_exists('sendAuditMsg')) {
    /**
     * 审核驳回消息提醒消息
     *
     * @param string $toUser 名字的openid
     * @param string $thing2 审核类型
     * @param string $thing3 审批人
     * @param string $time1 驳回时间
     * @param string $urls 跳转链接
     * @return string 响应结果
     */
    function sendAuditMsg($toUser, $thing2,$thing3, $time1, $urls)
    {
        $token = get_xcx_token();
        // 微信公众平台的接口地址
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$token}";

        // 模板消息数据
        $data = [
            'touser' => $toUser,
            'template_id' => 'bn1XLIlvAT-3sDT6h67lYW73PWzU2GMW92rjvbqCnls',
            'page' => $urls,
            'miniprogram_state' => 'formal',
            'lang' => 'zh_CN',
            'data' => [
                'thing2' => [
                    'value' => $thing2
                ],
                'thing3' => [
                    'value' => $thing3
                ],
                'time1' => [
                    'value' => $time1
                ]
            ]
        ];

        // 使用封装的方法发送 POST 请求
        $result = sendPostRequest($url, $data);

        // 处理响应
        //dump($result);
        if ($result['errcode'] == 0) {
            return "Message sent successfully.";
        } else {
            return "Failed to send message: " . $result['errmsg'];
        }
    }
}
