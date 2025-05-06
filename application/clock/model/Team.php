<?php

namespace app\clock\model;
use think\Model  as ThinkModel;
use think\Db;
use app\clock\model\Group as GroupModel;



class Team extends ThinkModel{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'clock_team';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function getTeam($map = []){
        $list = self::view('clock_team','id,name,uid,mobile,status')
            ->view('(SELECT last_login_time, team_id from dp_wx_user WHERE is_group = 1) AS subquery','subquery.last_login_time','subquery.team_id=clock_team.id','left')
            ->where($map)
            ->paginate();

        return $list;
    }

    public function userinfo()
    {
        return $this->hasOne('wx_user', 'team_id', 'id')->where('is_group', 1);
    }

    public static function getTeamList($pid)
    {
        $ids = GroupModel::where('project_id', $pid)->column('team_id');
        return self::where('status',1)->whereNotIn('id',$ids)->column('id,name');
    }


}
