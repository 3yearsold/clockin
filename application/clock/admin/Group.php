<?php
namespace app\clock\admin;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\clock\model\Group as GroupModel;
use app\clock\model\Team as TeamModel;
use app\clock\model\Member as MemberModel;
use app\clock\model\Project as ProjectModel;


class Group extends Admin{

    public function index($pid = null){
        if ($pid === null) $this->error('缺少参数');
        $name = ProjectModel::where('pid',$pid)->value('project_name');
        $data_list = GroupModel::getGroupNum($pid);
        $person = [
            'title' => '班组人员',
            'icon'  => 'glyphicon glyphicon-user',
            'class' => 'btn btn-sm btn-warning btn-square',
            'href'  => url('clock/person/index', ['id' => '__id__']),
        ];
        $addx = [
            'title' => '添加班组',
            'icon'  => 'glyphicon glyphicon-plus',
            'class' => 'btn btn-sm btn-primary btn-square ajax-get',
            'href'  => url('add', ['pid' => $pid]),
        ];
        $delete = [
            'title' => '删除班组',
            'icon'  => 'glyphicon glyphicon-remove-circle',
            'class' => 'btn btn-sm btn-danger btn-square ajax-get',
            'href'  => url('del', ['id' => '__id__']),
        ];


        return ZBuilder::make('table')
            ->setPageTitle('【'.$name.'】项目班组列表')
            ->addColumns([ // 批量添加数据列
                ['name', '班组名称'],
                ['status_3', '驻场人数'],
                ['status_02', '待审核人数'],
                ['status_4', '退场人数'],
                ['right_button', '操作', 'btn'],
            ])
            ->addTopButton('addx',$addx,['area' => ['45%', '400px']]) // 添加
            ->addRightButton('personal',$person) // 人员
            ->addRightButton('delete',$delete) // 人员
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板

    }

    public function add($pid =null)
    {
        if($pid === null) $this->error('缺少参数');
        if($this->request->isPost()){
            $data = $this->request->post();
            //传统验证
            $result = $this->validate($data, 'Group');
            if(true !== $result) $this->error($result);

            $name = TeamModel::where(['id'=>$data['team_id']])->value('name');
            $data['name'] = $name;
            $data['project_id'] = $pid;
            $result = GroupModel::create($data);
            if($result){
                action_log('group_add', 'clock_group', $result['id'], UID, $pid.'下添加班组:'.$data['name']);
                $this->success('添加成功',null,'_parent_reload');
            }else{
                $this->error('添加失败');
            }
        }

        $info = TeamModel::getTeamList($pid);
        return ZBuilder::make('form')
            ->addSelect('team_id', '班组名称[:选择一个班组]', '', $info)
            ->fetch();

    }


    public function del($id){
        if($id === null) $this->error('缺少参数');
        $num = MemberModel::where(['group_id'=>$id])->count();
        if($num > 0) $this->error('该班组下有人员，无法删除');

        $result = GroupModel::destroy($id);
        if($result){
            action_log('group_del', 'clock_group', $id, UID, '删除班组');
            $this->success('删除班组成功');
        }else{
            $this->error('删除失败');
        }

    }


}

