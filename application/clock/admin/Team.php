<?php
namespace app\clock\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\clock\model\Team as TeamModel;
use app\clock\model\WxUser as WxUserModel;

/**
 * 班组库控制器
 * @package app\clock\Team
 */
class Team extends Admin
{
    public function index()
    {
        // 查询
        $map = $this->getMap();
        // 获取排序
        $order = $this->getOrder();
        // 数据列表
        $data_list = TeamModel::getTeam($map);

//        $data_list = TeamModel::with('userinfo')->where($map)->order($order)->paginate();
//        dump($data_list[0]->userinfo->last_login_time);


        $btn_access = [
            'title' => '清除绑定微信',
            'icon'  => 'fa fa-fw fa-wechat',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href'  => url('clear_bind', ['id' => '__id__']),
            'data-title' => '确定要清除绑定微信吗？',
            'data-tips' => '清除绑定无法恢复,需班组再次绑定',
        ];



        return ZBuilder::make('table')
            ->setSearch(['name' => '班组名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '班组名称'],
                ['uid','绑定微信ID'],
                ['mobile', '联系电话'],
                ['last_login_time', '最后登陆时间','callback','format_time'],
                ['status', '状态','status',['0'=>'正常','1'=>'禁用']],
                ['right_button', '操作', 'btn'],
            ])
            ->addTopButtons('add') // 批量添加顶部按钮
            ->addRightButtons('edit,disable,enable,delete') // 添加右侧按钮
            ->addRightButton('wecaht',$btn_access)
            ->addRightButton('qrcode',['href'=>url('getQrcode'),'title'=>'班组绑定码','icon'=>'fa fa-qrcode'],['area' => ['450px', '360px'], 'title' => '<i class="fa fa-qrcode"></i> 班组绑定码'])
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板

    }

    public function getQrcode($id = null){
        return $this->fetch();
    }

    public function add(){

        if($this->request->isPost()){
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Team');
            if(true !== $result) $this->error($result);

            $result = TeamModel::create($data);
            if($result){
                action_log('team_add', 'clock_team', $result['id'], UID, $data['name']);
                $this->success('添加成功','index');
            }else{
                $this->error('添加失败');
            }
        }
        return ZBuilder::make('form')
            ->addText('name','班组名称')
            ->fetch();
    }


    public function edit($id = null){
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Team');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            if (TeamModel::update($data)) {
                // 记录行为
                action_log('team_edit', 'clock_team', $id, UID, $data['name']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = TeamModel::get($id);
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text','name','班组名称']
            ])
            ->setFormData($info)
            ->fetch();
    }


    public function clear_bind($id = null){
        if($id === null) $this->error('缺少参数');
        $info = TeamModel::get($id);
        if($info['uid'] == null) $this->error('该班组未绑定微信');
        if(TeamModel::where('id',$id)->setField(['uid'=> null,'mobile'=> null,'status'=>0])){
            if(WxUserModel::where('uid',$info['uid'])->setField(['is_group'=>0,'team_id'=>0,'nickname'=>null])){
                action_log('clear_bind', 'clock_team', $id, UID, $info['name'].'成功解除微信绑定:');
                $this->success('解绑成功', 'index');
            }else{
                $this->error('解绑失败');
            }
        }
    }


    public function enable($ids = null)
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (empty($ids)) {
            $this->error('请选择班组');
        }
        $ids = $ids ? explode(',', $ids) : [];
        $team = TeamModel::where('id','in',$ids)->whereNull('uid')->select()->toArray();
        if (!empty($team)) {
            $this->error('班组尚未绑定负责人');
        }
        parent::enable();
    }




}