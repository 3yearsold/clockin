<?php

namespace app\api\controller;
use app\clock\model\Member as MemberModel;
use app\common\controller\Common;
use app\common\builder\ZBuilder;
use app\admin\controller\Admin;
use app\clock\model\ClockInStat as ClockInStatModel;
use app\clock\model\Clockin as ClockInModel;
use think\Db;
use think\Image;

class ClockInStat extends common
{
    /**
     * 生成前一天的考勤统计
     * @return mixed
     * User: zheng
     * Date: 2025/4/24 11:02
     */
    public function generateYesterdayClockStat()
    {
        $map = [
            'clock_date' => strtotime(date('Y-m-d', strtotime('-1 day'))),
        ];
        $clock_datas = ClockInModel::where($map)->select()->toArray();
        //按照日期分组
        if ($clock_datas) {
            $date_clock = [];
            foreach ($clock_datas as $clock_data) {
                $project_id = $clock_data['project_id'];
                $group_id = $clock_data['group_id'];
                $date = $clock_data['clock_date'];
                $member_id = $clock_data['member_id'];
                $clock_data['create_time'] = strtotime($clock_data['create_time']);
                $date_clock[$project_id][$group_id][$date][$member_id][] = $clock_data;
            }
            $update_data = [];
            $create_time = time();
            foreach ($date_clock as $project_id => $groups) {
                foreach ($groups as $group_id => $dates) {
                    foreach ($dates as $date => $members) {
                        foreach ($members as $member_id => $member) {
                            $row = [
                                'clock_date' => $date,
                                'member_id' => $member_id,
                                'project_id' => $project_id,
                                'group_id' => $group_id,
                                'create_time' => $create_time,
                            ];
                            //按照打卡时间正向排序
                            $member_clocks = $member;
                            $clock_times = array_column($member_clocks, 'create_time');
                            array_multisort($clock_times, SORT_ASC, $member_clocks);
                            $count = count($member_clocks);
                            $on_clock = $off_clock  = [];
                            if ($count > 1) {
                                $on_clock = $member_clocks[0];
                                $off_clock = $member_clocks[$count-1];
                                $row['station'] = $on_clock['station'] ?? '';
                                $row['member_name'] = $on_clock['member_name'];
                                $row['uid'] = $on_clock['uid'];
                                $row['on_time'] = $on_clock['create_time'];
                                $row['on_location']     = $on_clock['location'];
                                $row['pic_on'] =   $on_clock['pic'];
                                $row['on_lat'] = $on_clock['lat'];;
                                $row['on_lng'] = $on_clock['lng'];;;
                                $row['off_time'] = $off_clock['create_time'];;
                                $row['off_location'] = $off_clock['location'];
                                $row['pic_off'] = $off_clock['pic'];
                                $row['off_lat'] = $off_clock['lat'];;
                                $row['off_lng'] = $off_clock['lng'];;
                            } else  {
                                //暂定17点后为下班打卡
                                $clock = $member_clocks[0];
                                if (date('H',$clock['create_time']) >= 17) {
                                    $off_clock = $clock;
                                } else {
                                    $on_clock = $clock;
                                }
                                $row['station'] = $clock['station'] ?? '';
                                $row['member_name'] = $clock['member_name'] ?? '';
                                $row['uid'] = $clock['uid'] ?? '';
                                $row['on_time'] = $on_clock['create_time'] ?? '';
                                $row['on_location']     = $on_clock['location']  ?? '';
                                $row['pic_on'] =   $on_clock['pic'] ?? '';
                                $row['on_lat'] = $on_clock['lat'] ?? '';
                                $row['on_lng'] = $on_clock['lng'] ?? '';
                                $row['off_time'] = $off_clock['create_time'] ?? '';
                                $row['off_location'] = $off_clock['location'] ?? '';
                                $row['pic_off'] = $off_clock['pic'] ?? '';
                                $row['off_lat'] = $off_clock['lat'] ?? '';
                                $row['off_lng'] = $off_clock['lng'] ?? '';
                            }
                            $update_data[] = $row;
                        }
                    }
                }
            }
            $clockInStat = new ClockInStatModel();
            $clockInStat->saveAll($update_data);
        }
    }


    /**
     * 生成老的打卡数据
     * @return null
     * User: zheng
     * Date: 2025/4/8 13:38
     */
    public function generateHistoryClockStat()
    {
        $clock_datas = ClockInModel::where('clock_date','<>',strtotime(date('Y-m-d',time())))->select()->toArray();
        //按照日期分组
        $date_clock = [];
        foreach ($clock_datas as $clock_data) {
            $project_id = $clock_data['project_id'];
            $group_id = $clock_data['group_id'];
            $date = $clock_data['clock_date'];
            $member_id = $clock_data['member_id'];
            $clock_data['create_time'] = strtotime($clock_data['create_time']);
            $date_clock[$project_id][$group_id][$date][$member_id][] = $clock_data;
        }
        $update_data = [];
        $create_time = time();
        foreach ($date_clock as $project_id => $groups) {
            foreach ($groups as $group_id => $dates) {
                foreach ($dates as $date => $members) {
                    foreach ($members as $member_id => $member) {
                        $row = [
                            'clock_date' => $date,
                            'member_id' => $member_id,
                            'project_id' => $project_id,
                            'group_id' => $group_id,
                            'create_time' => $create_time,
                        ];
                        //按照打卡时间正向排序
                        $member_clocks = $member;
                        $clock_times = array_column($member_clocks, 'create_time');
                        array_multisort($clock_times, SORT_ASC, $member_clocks);
                        $count = count($member_clocks);
                        $on_clock = $off_clock  = [];
                        if ($count > 1) {
                            $on_clock = $member_clocks[0];
                            $off_clock = $member_clocks[$count-1];
                            $row['station'] = $on_clock['station'];
                            $row['member_name'] = $on_clock['member_name'];
                            $row['uid'] = $on_clock['uid'];
                            $row['on_time'] = $on_clock['create_time'];
                            $row['on_location']     = $on_clock['location'];
                            $row['pic_on'] =   $on_clock['pic'];
                            $row['on_lat'] = $on_clock['lat'];;
                            $row['on_lng'] = $on_clock['lng'];;;
                            $row['off_time'] = $off_clock['create_time'];;
                            $row['off_location'] = $off_clock['location'];
                            $row['pic_off'] = $off_clock['pic'];
                            $row['off_lat'] = $off_clock['lat'];;
                            $row['off_lng'] = $off_clock['lng'];;
                        } else  {
                            //暂定17点后为下班打卡
                            $clock = $member_clocks[0];
                            if (date('H',$clock['create_time']) >= 17) {
                                $off_clock = $clock;
                            } else {
                                $on_clock = $clock;
                            }
                            $row['station'] = $clock['station'] ?? '';
                            $row['member_name'] = $clock['member_name'] ?? '';
                            $row['uid'] = $clock['uid'] ?? '';
                            $row['on_time'] = $on_clock['create_time'] ?? '';
                            $row['on_location']     = $on_clock['location']  ?? '';
                            $row['pic_on'] =   $on_clock['pic'] ?? '';
                            $row['on_lat'] = $on_clock['lat'] ?? '';
                            $row['on_lng'] = $on_clock['lng'] ?? '';
                            $row['off_time'] = $off_clock['create_time'] ?? '';
                            $row['off_location'] = $off_clock['location'] ?? '';
                            $row['pic_off'] = $off_clock['pic'] ?? '';
                            $row['off_lat'] = $off_clock['lat'] ?? '';
                            $row['off_lng'] = $off_clock['lng'] ?? '';
                        }
                        $update_data[] = $row;
                    }
                }
            }
        }
        $clockInStat = new ClockInStatModel();
        $clockInStat->saveAll($update_data);
        return json(['status' => 200,'massage' => '操作成功']);

    }


    /**
     * 处理老数据的班组信息
     * @return null
     * User: zheng
     * Date: 2025/4/8 13:38
     */
    public function dealHistoryClockGroupData()
    {
        $clock_datas = ClockInModel::field('id,member_id,project_id,create_time')->select()->toArray();
        //数据处理
        //获取所有打卡员工的信息
        $member_ids = array_column($clock_datas, 'member_id');
        $members = MemberModel::where('id', 'in', $member_ids)->select()->toArray();
        $members = array_column($members, null,'id');
        //根据project_id member_id分组
        $update_data = [];
        foreach ($clock_datas as $clock_data) {
            $member_id = $clock_data['member_id'];
            $member = $members[$member_id] ??[];
            $clock_date = strtotime(date('Y-m-d',strtotime($clock_data['create_time'])));
            $update_data[] = ['id' => $clock_data['id'],'group_id' => $member['group_id'],'clock_date' => $clock_date,'station' => $member['station'] ];
        }
        $clockIn = new ClockInModel();
        $clockIn->saveAll($update_data);
        return json(['status' => 200,'massage' => '操作成功']);
    }


}