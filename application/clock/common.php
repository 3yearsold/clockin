<?php



if (!function_exists('down_qrcode')) {
    /**
     * 随机生成项目编号
     * @param string $pid 项目编号
     * @param string $type 生成类型
     * @return array|string
     * @author Mr.CHENG
     */
    function down_qrcode(string $pid, string $type = 'p')
    {

        $token = get_xcx_token();
        $createUrl = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={$token}";
        $data = [
            'path' => ($type === 'p') ? "pages/start/start?toPage=userinfo&pid={$pid}" : "pages/start/start?toPage=clock&pid={$pid}",
            'width' => 430,
        ];
        $opts = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ],
        ];
        $context = stream_context_create($opts);
        $result = file_get_contents($createUrl, false, $context);
        if ($result === false)  {
            return [
                'code' => 2,
                'msg' => '无法创建二维码'
            ];
        }
        $imageDir =  'uploads/qrcode/' .  date('Ymd', time());
        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0755, true); // 注意：0777权限可能不安全，根据实际情况调整
        }
        $imageFile = "{$imageDir}/{$type}_{$pid}.png";
        if (file_put_contents($imageFile, $result) === false) {
            return [
                'code' => 2,
                'msg' => '无法写入二维码文件'
            ];
        }
        return [
            'code' => 0,
            'msg' => '成功',
            'data' => ['image' => $imageFile]
        ];

    }
}
