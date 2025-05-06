<?php

namespace app\clock\admin;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\clock\model\Blacklist as BlacklistModel;
use app\clock\model\Member as MemberModel;

class Blacklist extends Admin
{
    public function index(){
        $map = $this->getMap();
        $data_list = BlacklistModel::where($map)->paginate();
        return ZBuilder::make('table')
            ->setPageTitle('黑名单列表')
            ->setSearch(['name' => '姓名','idcard'=>'身份证号']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['name','姓名'],
                ['idcard', '身份证'],
                ['notes', '备注'],
                ['username', '添加人'],
        ])
            ->addTopButton('add')
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板

    }

    public function add(){
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Blacklist');
            if(true !== $result) $this->error($result);

            $data['username'] = get_nickname(UID);

            if ($project =BlacklistModel::create($data)) {
                // 记录行为
                action_log('blacklist_add', 'clock_blacklist', $project['id'], UID, $data['name']);
                // 修改member表的status字段
                MemberModel::where('idcard',$data['idcard'])->update(['status'=>9]);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        $js  = <<<EOF
                 <script> 
                    function showPopup(id){
                        layer.open({
                            type:2,
                            title: '<i class="fa fa-sitemap"></i>  民工列表选择人员',
                            shadeClose: true,
                            shade: 0.8,
                            area: ['950px', '850px'],
                            content: 'choose?_pop=1'
                        });
                    }           
                 </script>
                 EOF;

        // 显示添加页面
        $html = '<div class="form-group toolbar-btn-action pop"><a title="选择人员" icon="fa fa-sitemap" class="btn btn-default" href="javascript:showPopup(1);"><i class="fa fa-sitemap"></i> 民工列表选择人员</a></div>';
        return ZBuilder::make('form')
            ->setPageTips('添加黑名单后，将不能加入任何项目更不能打卡！', 'danger')
            ->addFormItems([
                ['text', 'name', '姓名'],
                ['text', 'idcard', '身份证号'],
                ['textarea', 'notes', '加入黑名单原因'],
                ])
            ->setExtraHtml($html,'form_top')
            ->setExtraJs($js)
            ->fetch();
    }



    public function choose(){

        $map = $this->getMap();
        $data_list = MemberModel::group('id,member,idcard')->field('id,member,idcard')->where($map)->paginate();

       $btn_use = [
            'title' => '选择',
            'icon'  => 'fa fa-check',
            'class' => 'btn btn-xs btn-success use',
            'href'  => "javascript:addBlacklist('" . '__member__'. "','".'__idcard__'."')",
        ];
       $js = <<<EOF
            <script>            
            function addBlacklist(member,idcard){
                parent.$('#name').val(member);
                parent.$('#idcard').val(idcard);
                parent.layer.closeAll();
            }
            </script>
            EOF;


        return ZBuilder::make('table')
            ->setPageTitle('选择人员')
            ->setSearch(['member' => '姓名','idcard'=>'身份证号']) // 设置搜索框
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['member', '姓名'],
                ['idcard', '身份证'],
                ['right_button', '操作', 'btn']
        ])

            ->addRightButton('use',$btn_use)
            ->setRowList($data_list) // 设置表格数据
            ->setExtraJs($js)
            ->fetch(); // 渲染模板

    }
}