<?php
namespace app\clock\model;

use think\Model as ThinkModel;
use think\Db;
use app\clock\model\Member;

/**
 * 项目模型
 * @package app\cms\model
 */
class Project extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'clock_project';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $type = ['project_date' => 'timestamp:Y-m-d'];


    public static function getProjectDynamic($map)
    {
        // 构建聚合查询
        $results = Db::table('dp_clock_project')
            ->alias('p')
            ->join('dp_clock_member m', 'p.pid = m.project_id', 'LEFT')
            ->field([
                'p.id',
                'p.pid',
                'p.project_name',
                'p.project_manager',
                Db::raw('COUNT(CASE WHEN m.status IN (0, 2) THEN 1 END) AS status_02'),
                Db::raw('COUNT(CASE WHEN m.status = 3 THEN 1 END) AS status_3'),
                Db::raw('COUNT(CASE WHEN m.status = 4 THEN 1 END) AS status_4'),
            ])
            ->where($map)
            ->group('p.id,p.pid,p.project_name, p.project_manager')
            ->paginate();

        return $results;
    }



//    public function getStatusAttr($value)
//    {
//        $status = [
//            0=>'<span style="color:green">施工中</span>',
//            1=>'已完工',
//            2=>'<span style="color:red">停工</span>'
//        ];
//        return $status[$value];
//    }




}