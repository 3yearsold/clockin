<?php

namespace app\clock\admin;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\clock\model\Member as MemberModel;
use app\clock\model\Project as ProjectModel;

class Admission extends Admin
{
    public function index()
    {

        // 获取查询条件
        $map = $this->getMap();


        if(empty($map)){
            //$map['clock_member.status'] = 2;
            $map[] =['clock_member.status','=',2];
        }

        // 数据列表

        if(session('user_auth.role') ==2){
            $map[] = ['clock_project.project_manager','=',NICKNAME];
            $data_list = MemberModel::getMembers($map);
            $list_project = ProjectModel::where('project_manager',NICKNAME)->column('pid,project_name');
        }else{
            $data_list = MemberModel::getMembers($map);
            $list_project = ProjectModel::column('pid,project_name');
        }


        $list_status=[
            0=>'待班组审核',
            1=>'班组驳回',
            2=>'待项目经理确认',
            3=>'进场中',
            4=>'已退场'
        ];


        //定义按钮
        $agrees = [
            'title' => '批量进场',
            'icon'  => 'fa fa-fw fa-level-down',
            'class' => 'btn btn-primary ajax-post confirm',
            'href'  => url('agrees',['status'=>2]),
            'data-title' => '确定要批量进场吗？',
            'data-tips' => '只有状态是【待项目经理确认】时才能进场！'
        ];
        $disagrees = [
            'title' => '批量退场',
            'icon'  => 'fa fa-fw fa-level-up',
            'class' => 'btn btn-danger ajax-post confirm',
            'href'  => url('agrees', ['status'=>3]),
            'data-title' => '确定要批量退场吗？',
            'data-tips' => '只有状态是【进场中】时才能退场！退场后不能再打卡！！'
        ];

        $agree = [
            'title' => '允许进场',
            'icon'  => 'fa fa-fw fa-level-down',
            'class' => 'btn btn-sm btn-primary ajax-get confirm',
            'href'  => url('agree',['id'=>'__id__']),
            'data-title' => '确定要进场吗？',
            'data-tips' => '只有状态是【待项目经理确认】【已退场】时才能进场！'
        ];
        $disagree = [
            'title' => '民工退场',
            'icon'  => 'fa fa-fw fa-level-up',
            'class' => 'btn btn-sm btn-danger ajax-get confirm',
            'href'  => url('disagree', ['id'=>'__id__']),
            'data-title' => '确定要退场吗？',
            'data-tips' => '只有状态是【进场中】时才能退场！退场后不能再打卡！！'
        ];


        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('班组人员进场确认')
            ->setSearch(['member' => '姓名','mobile'=>'手机号']) // 设置搜索框
            ->addFilter('clock_member.station')//筛选
           // ->addFilterList('clock_member.status',['班组审核中','驳回','项目经理确认中','进场中','已退场'],'0,2')
            ->addColumns([ // 批量添加数据列
                ['project_name', '项目'],
                ['group_name', '班组名'],
                ['member','姓名'],
                ['gender', '性别',[0=>'男',1=>'女']],
                ['idcard', '身份证'],
                ['mobile', '联系号码'],
                ['station', '岗位'],
                ['status', '当前状态','status','',['待班组审核','班组驳回','待项目经理确认','进场中','已退场']],
                ['right_button', '操作', 'btn'],
            ])
            ->setColumnWidth('project_name',240)
            ->addTopButton('agrees',$agrees)
            ->addTopButton('disagrees',$disagrees)
            ->addTopSelect('clock_member.project_id', '项目列表', $list_project)
            ->addTopSelect('clock_member.status', '人员状态', $list_status)
            ->addRightButton('agree', $agree )
            ->addRightButton('agree', $disagree )
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板

    }


    public function agrees($status= null){

        if($status===null)$this->error('缺少参数');
        $ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids   = (array)$ids;
        empty($ids) && $this->error('缺少主键');

        $res =MemberModel::checkAllStatus($ids,$status);
        if($res[0]['result']==0){
            if($status ==2){
                $this->error('您选的班组人员当前状态不全是【待项目经理确认】，不能进行批量进场操作！');
            }else{
                $this->error('您选的班组人员当前状态不全是【进场中】，不能进行批量退场操作！');
            }
        }else{
            $result = MemberModel::whereIn('id',$ids)->update(['status'=> $status + 1]);
            if($result){
                 $this->success('批量操作成功');
            }else{
                $this->error('批量操作失败');
            }
        }

    }

    public function agree($id = null){
        if($id === null)$this->error('缺少参数');

        $status = MemberModel::where('id',$id)->value('status');
        if($status != 2 && $status != 4) $this->error('当前状态不能进行进场操作！');

        $result = MemberModel::where('id',$id)->update(['status'=> 3]);
        if($result) {
            $this->success('进场成功');
        }else{
            $this->error('进场失败');
        }

    }

    public function disagree($id = null){
        if($id === null)$this->error('缺少参数');

        $status = MemberModel::where('id',$id)->value('status');
        if($status != 3) $this->error('当前状态不能进行退场操作！');
        $result = MemberModel::where('id',$id)->update(['status'=> 4]);
        if($result) {
            $this->success('退场成功');
        }else{
            $this->error('退场失败');
        }
    }

}