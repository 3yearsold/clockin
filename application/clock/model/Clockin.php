<?php

namespace app\clock\model;
use think\Model;
use think\Db;

class Clockin extends Model
{

    protected $name = 'clock_in';
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $type = ['create_time' => 'timestamp:Y-m-d H:i:s'];


    public static function getClockInLog($map=[]){
        $list = self::view('clock_in','id,project_id,member_id,member_name,pic,location,create_time')
            ->view('clock_project','project_name,project_manager','clock_project.pid=clock_in.project_id','LEFT')
            ->view('clock_member','station','clock_member.id=clock_in.member_id','LEFT')
            ->view('clock_group','name','clock_group.id=clock_member.group_id','LEFT')
            ->where($map)
            ->order('create_time desc')
            ->paginate();
        return $list;
    }














}