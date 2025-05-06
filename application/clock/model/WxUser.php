<?php

namespace app\clock\model;

use think\Model;
use think\Db;

class WxUser extends Model
{

    // 设置当前模型对应的完整数据表名称
    protected $name = 'wx_user';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    public static function getAuditUser($map=[]){
        $list = self::view('wx_user','id,uid,mobile,is_group,team_id')
            ->view('clock_team','name','clock_team.id=wx_user.team_id','LEFT')
            ->where('wx_user.is_group',2)
            ->where($map)
            ->paginate();
        return $list;
    }


}