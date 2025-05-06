<?php

namespace app\clock\model;
use think\Model;
use think\Db;

class Member extends Model
{
    protected $name = 'clock_member';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function getMembers($map=[]){
        $list = self::view('clock_member','id,project_id,member,gender,idcard,group_id,mobile,station,status,sign_pdf')
            ->view('clock_project','project_name','clock_project.pid=clock_member.project_id','LEFT')
            ->view('clock_group','name as group_name','clock_group.id = clock_member.group_id','LEFT')
            ->where($map)
            ->paginate();
        return $list;
    }

    public static function getMemberInfo($id){
        $info = self::view('clock_member','project_id,member,gender,idcard,group_id,mobile,account_name,account,bank,station,reg_date,create_time')
            ->view('clock_project','project_name','clock_project.pid=clock_member.project_id','LEFT')
            ->where('clock_member.id',$id)
            ->find();
        return $info;
    }


    public static function checkAllStatus($ids, $status = 3)
    {
        // 将ID列表转换为SQL中IN操作符所需的格式，例如'(1,2,3,...)'
        $idList = implode(',', $ids);
        // 执行原生SQL查询
        $result = Db::query("
            SELECT
                CASE
                    WHEN NOT EXISTS (
                        SELECT 1
                        FROM dp_clock_member
                        WHERE id IN ($idList)
                          AND status <> $status
                    ) THEN 1
                    ELSE 0
                END AS result
        ");
        return $result;
    }


    public static function getMemberById($id){

        $list = self::view('clock_member','id,member,gender,idcard,idcard_url,mobile,account_name,account,bank,station,reg_date,spec_document')
            ->view('clock_project','project_name','clock_project.pid=clock_member.project_id','LEFT')
            ->view('clock_group','name','clock_group.id=clock_member.group_id','LEFT')
            ->where('clock_member.id',$id)
            ->find();
        return $list;
    }


}