<?php
namespace app\clock\admin;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\clock\model\Project as ProjectModel;

/**
 * 项目动态控制器
 * @package app\clock\dynamic
 */
class Dynamic extends Admin
{
    public function index(){
        $map = $this->getMap();
        if(session('user_auth.role') ==2){
            $map['project_manager'] = NICKNAME;
        }
        $data_list = ProjectModel::getProjectDynamic($map);

        $team = [
            'title' => '班组管理',
            'icon'  => 'glyphicon glyphicon-user',
            'class' => 'btn btn-sm btn-info btn-square',
            'href'  => url('clock/group/index', ['pid' => '__pid__']),
        ];

        return ZBuilder::make('table')
            ->setSearch(['project_name' => '项目名称','project_manager'=>'项目经理']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['pid','项目编号'],
                ['project_name', '项目名称'],
                ['project_manager', '项目经理'],
                ['status_3', '驻场人数'],
                ['status_02', '待审核人数'],
                ['status_4', '退场人数'],
                ['right_button', '操作', 'btn'],
            ])
            ->setColumnWidth('project_name', 280)
            ->addRightButton('team',$team) // 通过
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板


    }



}