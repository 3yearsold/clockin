<?php

namespace app\clock\admin;

use app\admin\controller\Admin;
use think\Db;

/**
 * 项目打卡首页统计
 * @package app\cms\admin
 */
class Index extends Admin
{
    public function index()
    {
        $projectStats = Db::name('project_stats')->select();
        foreach ($projectStats as &$stat) {
            $stat['yesterday_check_in_rate'] = $stat['status3_count'] > 0
                ? round($stat['yesterday_check_in_count'] / $stat['status3_count'] * 100,2)
                : 0;

            $stat['today_check_in_rate'] = $stat['status3_count'] > 0
                ? round($stat['today_check_in_count'] / $stat['status3_count'] * 100,2)
                : 0;
        }
       // dump($projectStats);

        $this->assign('projectStats', $projectStats);
        $this->assign('project', Db::name('clock_project')->where('status',0)->count());
        $this->assign('status_0', Db::name('clock_member')->where('status',0)->count());
        $this->assign('status_2', Db::name('clock_member')->where('status',2)->count());
        $this->assign('status_3', Db::name('clock_member')->where('status',3)->count());
        $this->assign('page_title', '仪表盘');
        return $this->fetch();
    }



}