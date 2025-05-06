<?php

namespace app\api\controller;
use app\common\controller\Common;
use app\clock\model\Member as MemberModel;
use think\Db;

class Tasks extends Common
{

    public function pushMsg(){

        $sql="SELECT m.member, m.project_id, w.openid FROM  dp_clock_member m
                LEFT JOIN  dp_clock_in c ON m.id = c.member_id AND c.create_time >= UNIX_TIMESTAMP(CURRENT_DATE) AND c.create_time < UNIX_TIMESTAMP(CURRENT_DATE) + 86400
                LEFT JOIN  dp_wx_user w ON m.uid = w.uid
                WHERE  c.uid IS NULL AND m.status = 3";
        $records = Db::query($sql);
// 检查查询结果
        if ($records) {
            foreach ($records as $record) {
                // 提取需要的信息
                $openid = $record['openid'];
                $memberName = $record['member'];
                $projectId = $record['project_id'];
                $currentTime = date('Y-m-d H:i:s'); // 获取当前时间作为提醒时间pages/start/start?toPage=clock&pid=
                $url = "pages/start/start?toPage=clock&pid={$projectId}"; // 跳转的页面
                echo $openid.'--';
                //echo "openid:{$openid}, memberName:{$memberName}, projectId:{$projectId}, currentTime:{$currentTime}, url:{$url}<br>";
                // 发送打卡提醒消息
                if($openid){
                 $res = sendClockMsg($openid, $memberName, "未打卡", $currentTime, $url);
                 echo $res."\n";
                }
            }
        }
    }
}