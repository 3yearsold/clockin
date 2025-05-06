<?php

namespace app\api\controller;
use app\common\controller\Common;

class Revice extends Common
{

    //token zcfix
    //EncodingAESKey   DzCby23dFmrNF852sNH45Afq03ChPbUaC6pVolITrAv
    //班组审批消息模板    uP-Xjz-o6MtGS0oSu2jqARpsjfar5G83c9ImRrpiOq0
    //提醒打卡消息模板    jmBuAUUOBRo-sokT_qVlFfXpt84IfSbZpLuehSaaSC8
    //驳回消息模板   dnhgVG4rPMhtIUQYkdu3AdEwOF2qT_Zm3ATmnBGEWxE

    public function msg()
    {
        // 微信服务器配置的 Token
        $token = 'zcfix';
        // 获取微信服务器发送的参数
        $signature = $_GET['signature'] ?? '';
        $timestamp = $_GET['timestamp'] ?? '';
        $nonce = $_GET['nonce'] ?? '';
        $echostr = $_GET['echostr'] ?? '';

        // 将 token、timestamp、nonce 按字典序排序
        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);

        // 将排序后的数组拼接成字符串并进行 sha1 加密
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        // 验证签名
        if ($tmpStr === $signature) {
            // 验证成功，返回 echostr
            echo $echostr;
        } else {
            // 验证失败，返回错误信息
            echo 'Verification failed';
        }
    }


}