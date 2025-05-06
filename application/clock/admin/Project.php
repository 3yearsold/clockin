<?php

namespace app\clock\admin;

use app\admin\controller\Admin;
use app\admin\model\Icon;
use app\clock\model\Area;
use app\clock\model\Member as MemberModel;
use app\common\builder\ZBuilder;
use think\Db;
use app\clock\model\Attr as AttrModel;
use app\clock\model\Area as AreaModel;
use app\clock\model\Project as ProjectModel;


/**
 * 项目控制器
 * @package app\clock\project
 */
class Project extends Admin
{
    public function index()
    {
        //dump(NICKNAME);


        // 查询
        $map = $this->getMap();

        if(session('user_auth.role') ==2){
            $map['project_manager'] = NICKNAME;
        }


        // 获取排序
        $order = $this->getOrder('project_date desc');


        // 数据列表
        $data_list = ProjectModel::where($map)->order($order)->paginate();
        //dump($data_list);

        $js  = <<<EOF
                 <script>
                    function showPopup(id){
                        layer.open({
                            type: 2,
                            title: '<i class="fa fa-qrcode"></i>  项目二维码',
                            shadeClose: true,
                            shade: 0.8,
                            area: ['450px', '360px'],
                            content: 'getQrcode/id/'+id
                        });
                    }                 
                 </script>
                 EOF;
        $btn_access = [
            'title' => '更新二维码',
            'icon'  => 'fa fa-fw fa-retweet',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href'  => url('createQrcode', ['pid' => '__pid__']),
            'data-title' => '确定要更新二维码吗？',
        ];


        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['project_name' => '项目名称','project_manager'=>'项目经理']) // 设置搜索框
            ->addOrder('project_date')//排序
            ->addFilter('project_manager')//筛选
            ->addColumns([ // 批量添加数据列
                ['pid', '项目编号'],
                ['project_name', '项目名称','popover',20,'right'],
                ['project_customer', '客户名称'],
                ['project_date', '立项日期'],
                ['project_manager', '项目经理'],
                ['manager_tel', '经理号码'],
                ['status', '状态修改', 'select', ['施工中','已完工','停工']],
                ['right_button', '操作', 'btn'],
            ])
            ->setColumnWidth([
                'project_name' => 260,
                'project_customer'=> 180,
            ])
            ->addTopButtons('add') // 批量添加顶部按钮
            ->addRightButton('edit') // 添加右侧按钮
            ->addRightButton('delete') // 添加右侧按钮
            ->addRightButton('qrcode',['title' => '项目二维码','icon' => 'fa fa-fw fa-qrcode','href'  => 'javascript:showPopup(__id__);']) // 添加右侧按钮
            ->addRightButton('diy', $btn_access) // 添加右侧按钮
            ->addRightButton('editNotify',['href'=>url('editNotify',['id'=>'__id__']),'title'=>'项目公告','icon'=>'fa fa-fw fa-volume-up'],['area' => ['800px', '380px'], 'title' => '<i class="fa fa-fw fa-volume-up"></i> 项目公告'])
            ->setColumnWidth('right_button',110)
            ->setRowList($data_list) // 设置表格数据
            ->setExtraJs($js)
            ->fetch(); // 渲染模板
    }
    public function getQrcode($id){
        $p_qrcode= ProjectModel::where('id',$id)->value('p_qrcode');
        $r_qrcode= ProjectModel::where('id',$id)->value('c_qrcode');
        $this->assign('p_qrcode',$p_qrcode);
        $this->assign('c_qrcode',$r_qrcode);
       return $this->fetch();
    }


    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Project');
            if(true !== $result) $this->error($result);
            $pid = get_udf_pid();
            $data['pid'] = $pid;
            if ($project =ProjectModel::create($data)) {
                //cache('clock_project_list', null);
                // 记录行为
                action_log('project_add', 'clock_project', $project['id'], UID, $data['project_name']);
                $this->createQrcode($pid);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTips('地图位置必须准确，否则会影响打卡功能', 'danger')
            ->addFormItems([
                ['bmap','map','地图位置','tKmIVpUAcffbsrTF5zmZDUhE','项目打卡地点根据地图定位，一定要准确','','浙江省','16','300'],
                ['static','pid','项目编号','','系统自动生成'],
                ['text', 'project_name', '项目名称'],
                ['text', 'project_customer', '客户名称'],
                ['select', 'type', '项目属性[:选择项目属性]', '',AttrModel::getAttrList()],
                ['number', 'build_area', '建筑面积m²'],
                ['textarea','work_limit', '施工范围'],
                ['date', 'project_date', '立项日期' ],
                ['text', 'project_manager', '项目经理'],

            ])
            ->layout(['map'=> 6,'pid'=>6,'project_name'=>6,'project_customer'=>6,'type'=>3,'build_area'=>3,'work_limit'=>6,'project_date'=>3,'project_manager'=>3])
            ->fetch();
    }



    /**
     * 自动创建编辑页面
     * @param string $id 主键值
     * @author Mr.c
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */

    public function editNotify($id = null){
        if ($id === null) $this->error('缺少参数');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if(ProjectModel::update($data)){
                $this->success('编辑成功',null, '_close_pop');
            }else{
                $this->error('编辑失败');
            }
        }

        $notify = ProjectModel::get($id);

        //显示修改公告页面
        return ZBuilder::make('form')
            ->setPageTitle('编辑公告')
            ->addFormItems([
                ['hidden', 'id'],
                ['textarea','notify','公告内容']
            ])
            ->setFormData($notify)
            ->fetch();
    }


    public function edit($id = null){
        if ($id === null) $this->error('缺少参数');
        //dump($id);

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Project');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            if (ProjectModel::update($data)) {
                // 记录行为
                action_log('project_edit', 'clock_project', $id, UID, $data['project_name']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = ProjectModel::get($id);


        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['bmap','map','地图位置','tKmIVpUAcffbsrTF5zmZDUhE','项目打卡地点根据地图定位，一定要准确','','','18'],
                ['static','pid','项目编号'],
                ['text', 'project_name', '项目名称'],
                ['text', 'project_customer', '客户名称'],
                ['select', 'type', '项目属性[:选择项目属性]', '',AttrModel::getAttrList()],
                ['number', 'build_area', '建筑面积m²'],
                ['textarea','work_limit', '施工范围'],
                ['date', 'project_date', '立项日期' ],
                ['text', 'project_manager', '项目经理'],
            ])
            ->layout(['map'=> 6,'pid'=>6,'project_name'=>6,'project_customer'=>6,'type'=>3,'build_area'=>3,'work_limit'=>6,'project_date'=>3,'project_manager'=>3])
            ->setFormData($info)
            ->fetch();

    }

    public function createQrcode($pid)
    {
        if ($pid === null) $this->error('缺少参数');

        $q_qrcode = down_qrcode($pid,'p');  //生成并保存项目二维码动态
        if ($q_qrcode['code'] == 0) {
            $url = $q_qrcode['data']['image'];
            ProjectModel::where('pid',$pid)->setField('p_qrcode','/'.$url);
            $c_qrcode = down_qrcode($pid,'c');  //打卡二维码
            if ($c_qrcode['code'] == 0) {
                $url = $c_qrcode['data']['image'];
                ProjectModel::where('pid',$pid)->setField('c_qrcode','/'.$url);
                $this->success('生成二维码成功');
            }
        }
    }


    /**
     * 变更项目状态
     * @param $record
     * User: zheng
     * Date: 2025/4/16 16:42
     */
    public function quickEdit($record = [])
    {
        //项目完成则所有项目民工退场
        $data = $this->request->post();
        if (!empty($data['name']) && $data['name'] == "status" && isset($data['value']) && $data['value'] == 1) {
            Db::startTrans();
            $id = $data['pk'];
            $project = ProjectModel::get($id);
            $project->status  = $data['value'];
            $project->save();
            if (!empty($project)) {
                $pid = $project->pid;
                MemberModel::where('project_id',$pid)->where('status','=',3)->update(['status'=> 4]);
            } else {
                Db::rollback();
                $this->error('项目不存在');
            }
            Db::commit();
            $this->success('操作成功');
        }
        parent::quickEdit($record);

    }



}