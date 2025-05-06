<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\red_circle\admin;

use app\common\builder\ZBuilder;
use app\admin\controller\Admin;
use app\red_circle\model\Position as PositionModel;
use app\red_circle\model\PositionDocument as PositionDocumentModel;
use think\Db;


class Position extends Admin
{

    /**
     * @return mixed
     * @throws \think\Exception
     */
    public function index()
    {
        $data_list = PositionModel::where($this->getMap())
            ->paginate();

        $status_list = [
            '0' => '关闭',
            '1' => '开启',
        ];
        // 使用ZBuilder构建数据表格
        $fields = [
            ['hidden', 'id'],
            ['text', 'name', '岗位名称', '必填，不能重复'],
            ['textarea', 'remark', '岗位备注'],
            ['radio', 'status', '状态', '', ['禁用', '启用'], 0]
        ];
        return ZBuilder::make('table')
            ->addTopSelect('status', '状态',$status_list)
            ->setSearch(['name' => '岗位名称'], '岗位名称')
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '岗位名称'],
                ['status', '状态', 'status'],
                ['remark', '备注'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '编辑时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->setColumnWidth([
                'id'  => 30,
                'name' => 60,
                'status' => 50,
                'remark' => 160,
                'create_time' => 110,
                'update_time' => 110,
                'right_button' => 50,
            ])
            ->autoAdd($fields, '', 'Position', true)
            ->addTopButtons('enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($data_list->render()) // 设置表格数据
            ->fetch();
    }


    public function edit($id = null)
    {
        if (empty($id)) {
            $this->error('缺少参数');
        }
        $info = PositionModel::where('id', $id)->find();
        if (empty($info)) {
            $this->error('岗位手册不存在');
        }
        // 保存数据
        if (!empty($this->request->isPost())) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Position');
            // 验证失败 输出错误信息
            if(true !== $result) {
                $this->error($result);
            }
            if (PositionModel::update($data)) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        } else {
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('编辑岗位') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['hidden', 'id'],
                    ['text', 'name', '岗位名称', '必填，不能重复'],
                    ['textarea', 'remark', '岗位备注'],
                    ['radio', 'status', '状态', '', ['禁用', '启用']]
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }
    }




}
