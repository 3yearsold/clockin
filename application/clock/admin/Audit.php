<?php
namespace app\clock\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\clock\model\WxUser as WxUserModel;
use app\clock\model\Team as TeamModel;



/**
 * 班组审核
 * @package app\clock\project
 */
class Audit extends Admin
{

    public function index(){

        $map = $this->getMap();
        $data_list = WxUserModel::getAuditUser($map);
       // dump($data_list);

        $btn_agree = [
            'title' => '审核通过',
            'icon'  => 'glyphicon glyphicon-ok',
            'class' => 'btn btn-sm btn-success btn-rounded ajax-get confirm',
            'href'  => url('agree', ['id' => '__id__']),
            'data-title' => '您确定审核通过吗？',
            'data-tips' => '建议与申请人核对手机号码和绑定的班组！',
        ];

        $btn_disagree = [
            'title' => '驳回审核',
            'icon'  => 'glyphicon glyphicon-remove',
            'class' => 'btn btn-sm btn-danger btn-rounded ajax-get confirm',
            'href'  => url('disagree', ['id' => '__id__']),
            'data-title' => '您确定要驳回该绑定申请吗？',
            'data-tips' => '清除后要重新审核才能再次绑定！',
        ];

        return ZBuilder::make('table')
            ->setSearch(['name' => '班组名称','mobile'=>'申请人手机']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['name', '申请绑定班组'],
                ['uid','绑定微信ID'],
                ['mobile', '申请人手机号'],
                ['is_group', '审核状态','status','',['未申请','绑定完成','待审核']],
                ['right_button', '操作', 'btn'],
            ])
            ->addRightButton('agree',$btn_agree) // 通过
            ->addRightButton('disagree',$btn_disagree) // 驳回
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板

    }


    public function agree($id = null){

        if($id === null) $this->error('参数错误');

            $info = WxUserModel::get($id);
            if(TeamModel::where('id',$info['team_id'])->setField(['uid'=>$info['uid'],'mobile'=>$info['mobile'],'status'=>1])){
                $name = TeamModel::where('id',$info['team_id'])->value('name');
                WxUserModel::where('id',$id)->setField(['is_group'=>1,'nickname'=>$name]);
                action_log('audit_agree', 'clock_audit', $info['team_id'], UID, $info['uid'].'成功绑定班组:'.$info['team_id']);
                $this->success('班组绑定成功', url('index'));
            }else{
                $this->error('班组绑定失败');
            }

    }


    public function disagree($id = null){
        if($id === null) $this->error('参数错误');
        if(WxUserModel::where('id',$id)->setField(['is_group'=>0,'team_id'=>0])) {
            action_log('audit_disagree', 'clock_audit', $id, UID, '驳回绑定');
            $this->success('驳回成功', url('index'));
        }else{
            $this->error('驳回失败');
        }

    }



}