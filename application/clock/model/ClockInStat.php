<?php

namespace app\clock\model;
use think\Model;
use think\Db;

class ClockInStat extends Model
{

    protected $name = 'clock_in_stat';
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $type = ['create_time' => 'timestamp:Y-m-d H:i:s'];


    public static function getClockInStat($map=[]){
        $list = self::view('clock_in_stat','id,project_id,member_id,member_name,station,group_id,pic_on,pic_off,clock_date,on_time,off_time,on_lat,on_lng,on_location,off_lat,off_lng,off_location')
            ->view('clock_project','project_name,project_manager','clock_project.pid=clock_in_stat.project_id','LEFT')
            ->view('clock_group','name','clock_group.id=clock_in_stat.group_id','LEFT')
            ->view('clock_member','mobile','clock_member.id=clock_in_stat.member_id','LEFT')
            ->where($map)
            ->order('clock_date desc,name asc')
            ->paginate();
        return $list;
    }



    public static function getClockInStatNoPage($map=[]){
        $list = self::view('clock_in_stat','id,project_id,member_id,member_name,station,group_id,pic_on,pic_off,clock_date,on_time,off_time,on_lat,on_lng,on_location,off_lat,off_lng,off_location')
            ->view('clock_project','project_name,project_manager','clock_project.pid=clock_in_stat.project_id','LEFT')
            ->view('clock_group','name','clock_group.id=clock_in_stat.group_id','LEFT')
            ->view('clock_member','mobile','clock_member.id=clock_in_stat.member_id','LEFT')
            ->where($map)
            ->order('clock_date desc,name asc')
            ->select()
            ->toArray();
        return $list;
    }














}