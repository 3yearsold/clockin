<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

// 为方便系统核心升级，二次开发中需要用到的公共函数请写在这个文件，不要去修改common.php文件


//生成一组随机数（时间戳+随机2位数模式）
 function getrandom(){
     $time = time();
     $random = rand(11,99);
     return strval($time).strval($random);
}


function get_xcx_token()
{
    $now = time();
    $expire_time = config('xcx_expires_time');
    if ($now > $expire_time) {
        $appid = config('WX_XIAOCHENGXU_ID');
        $appSecret = config('WX_XIAOCHENGXU_SECRET');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appSecret}";
        $res = file_get_contents($url);
        $res = json_decode($res, true);
        $token = $res['access_token'];
        $expire_time = $now + 7000;
        \app\admin\model\Config::setValues('xcx_access_token', $token);
        \app\admin\model\Config::setValues('xcx_expires_time', $expire_time);
        return $token;
    } else {
        return config('xcx_access_token');
    }
}

/**
 * 随机生成项目编号
 */
function get_udf_pid()
{
    $timePart = substr((string)(int)(microtime(true) * 1000), -8);
    $randomPart1 = (string)(mt_rand(0, 99));
    $randomPart2 = (string)(mt_rand(0, 99));
    return $timePart. $randomPart1. $randomPart2;
}
