<?php

namespace app\clock\admin;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\clock\model\Member as MemberModel;
use app\clock\model\Group as GroupModel;

class Person extends Admin
{
    public function index($id = null){

        if($id === null) $this->error('参数错误');

        $name = GroupModel::where('id',$id)->value('name');
        $map = $this->getMap();
        $data_list = MemberModel::where('group_id',$id)->where($map)->field('id,member,gender,idcard,mobile,station,status')->paginate();

        return ZBuilder::make('table')
            ->setPageTitle('【'.$name.'】班组人员列表')
            ->setSearch(['member' => '姓名','mobile'=>'手机号']) // 设置搜索框
            ->addFilter('clock_member.station')//筛选
            ->addFilterList('status',['班组审核中','驳回','项目经理确认中','进场中','已退场'],[0,1,2,3,4])//筛选
            ->addColumns([ // 批量添加数据列
                ['member','姓名'],
                ['gender', '性别',[0=>'男',1=>'女']],
                ['idcard', '身份证'],
                ['mobile', '联系号码'],
                ['station', '岗位'],
                ['status', '当前状态','status','',['班组审核中','驳回','项目经理确认中','进场中','已退场']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板



    }



}