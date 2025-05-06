<?php

namespace app\api\model;

use think\Model;
use think\Db;

class User extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'wx_user';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    // 获取用户所在项目公告
    public static function getNotify($uid){
        $project_id = Db::name('clock_member')->where(['uid'=>$uid , 'status'=>3])->value('project_id');
        if($project_id == null){
            return null;
        }else{
            return Db::name('clock_project')->where('pid',$project_id)->value('notify');
        }


    }






}