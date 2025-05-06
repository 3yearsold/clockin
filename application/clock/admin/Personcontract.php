<?php

namespace app\clock\admin;
use app\admin\controller\Admin;
use app\clock\model\Member as MemberModel;
use app\common\builder\ZBuilder;
use app\clock\model\Project as ProjectModel;
use app\clock\model\Group as GroupModel;
use think\Response;

class Personcontract extends Admin
{
    public function index(){
        $map = $this->getMap();

        if(session('user_auth.role') == 2){
            $map[] = ['clock_project.project_manager','=',NICKNAME];
            $data_list = MemberModel::getMembers($map);
            $project_list = ProjectModel::where('project_manager',NICKNAME)->column('pid,project_name');
        }else{
            $data_list = MemberModel::getMembers($map);
            $project_list = ProjectModel::column('pid,project_name');
        }


            if(isset($map[0][0])){
                $group_list = GroupModel::where('project_id',$map[0][2])->column('id,name');
                if(isset($map[1][0])){
                    if($map[1][0] = 'clock_member.group_id'){
                        $member_list = MemberModel::where('group_id',$map[1][2])->column('id,member');
                    }else{
                        $member_list=[];
                    }
                }else{
                    $member_list=[];
                }
            }else{
                $group_list=[];
                $member_list=[];
            }


        return ZBuilder::make('table')
            ->setPageTitle('劳务签署列表') // 设置页面标题
            ->addColumns([
                ['project_name', '项目名称'],
                ['member', '民工姓名'],
                ['gender', '性别',[0=>'男',1=>'女']],
                ['group_name', '班组名称'],
                ['idcard', '身份证号'],
                ['mobile', '手机号'],
                ['right_button', '操作', 'btn'],
            ])
            ->addRightButton('view', ['title' => '查看', 'icon' => 'iconfont icon-PDF', 'class' => 'btn btn-xs btn-warning','href'=>url('view',['id'=>'__id__'])],['area' => ['800px', '90%'], 'title' => '查看合同'])
            ->addRightButton('down', ['title' => '下载', 'icon' => 'iconfont icon-pdfxiazai', 'class' => 'btn btn-xs btn-success','href'=>url('view',['id'=>'__id__','down'=>1])])
            ->setColumnWidth(['project_name'=>280,'member'=>50,'gender'=>40,'group_name'=>50,])
            ->addTopSelect('clock_member.project_id', '项目列表', $project_list,'','clock_member.group_id,clock_member.id')
            ->addTopSelect('clock_member.group_id', '班组列表', $group_list,'','clock_member.id')
            ->addTopSelect('clock_member.id', '人员列表', $member_list)
            ->setRowList($data_list) // 数据列表
            ->fetch(); // 渲染模板

    }
    //查看&下载合同
    public function view($id = null,$down = 0){
        if($id === null)  $this->error('参数错误');
        $pdf = MemberModel::where('id',$id)->value('sign_pdf');
        $content = file_get_contents($pdf);
        if($down == 1){
            $file_name = MemberModel::where('id',$id)->value('member');
            $file_name = $file_name.'.pdf';
            return download($content,$file_name,true);
        }
        return Response::create($content, 'image', 200)->contentType('application/pdf');

    }
    //批量下载合同
    public function batch_down($ids =[]){


    }



}