<?php

namespace app\clock\model;
use think\Model;
use think\Db;

class Group extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'clock_group';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function getGroupNum($pid)
    {
        // 构建聚合查询
        $results = Db::table('dp_clock_group')
            ->alias('g')
            ->join('dp_clock_member m', 'g.id = m.group_id', 'LEFT')
            ->field([
                'g.id',
                'g.name',
                Db::raw('COUNT(CASE WHEN m.status IN (0, 2) THEN 1 END) AS status_02'),
                Db::raw('COUNT(CASE WHEN m.status = 3 THEN 1 END) AS status_3'),
                Db::raw('COUNT(CASE WHEN m.status = 4 THEN 1 END) AS status_4'),
            ])
            ->where('g.project_id', $pid)
            ->group('g.id,g.name')
            ->paginate();

        return $results;
    }



}