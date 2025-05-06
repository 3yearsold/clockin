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



class PositionDocument extends Admin
{

    /**
     * @return mixed
     * @throws \think\Exception
     */
    public function index()
    {
        //岗位列表
        $position_list = PositionModel::where(['status' => 1])->column('name', 'id');
        $status_list = [
            '0' => '关闭',
            '1' => '开启',
        ];
        // 使用ZBuilder构建数据表格
        $map = $this->getMap();

        $map[] = ['position_id', 'in', array_keys($position_list)];
        $data_list = PositionDocumentModel::where($map)
            ->paginate();
        return ZBuilder::make('table')
            ->addTopSelect('status', '状态',$status_list)
            ->setSearch(['title' => '手册名称'], '手册名称')
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['title', '手册名称'],
                ['position_id', '岗位名称', $position_list],
                ['status', '状态', 'status'],
                ['remark', '备注'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '编辑时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->setColumnWidth([
                'id'  => 30,
                'title' => 100,
                'position_id' => '50',
//                'position_name' => '岗位名称',
                'status' => 50,
                'remark' => 160,
                'create_time' => 110,
                'update_time' => 110,
                'right_button' => 50,
            ])
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($data_list->render()) // 设置表格数据
            ->fetch();

    }

    public function add()
    {
        // 保存数据
        if (!empty($this->request->isPost())) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'PositionDocument');
            // 验证失败 输出错误信息
            if(true !== $result) {
                $this->error($result);
            }
            $doc_list = PositionDocumentModel::where('position_id', '=', $data['position_id'])
                ->where('title', '=', $data['title'])
                ->select()
                ->toArray();
            if ($doc_list) {
                $this->error('新增失败,该岗位下已存在相同名称手册');
            }
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['clean_html_content'] = strip_tags($data['content']);
            if (PositionDocumentModel::create($data)) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        } else {
            $position_list = PositionModel::where(['status' => 1])->column('name', 'id');
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('新增') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'title', '手册名称', '必填，手册名称'],
                    ['select', 'position_id', '岗位名称', '', $position_list],
                    ['radio', 'status', '手册状态', '', ['禁用', '启用'], 0],
                    ['ueditor', 'content', '手册内容'],
                    ['textarea', 'remark', '手册备注', ''],
                ])
                ->fetch();
        }


    }



    public function edit($id = null)
    {
        if (empty($id)) {
            $this->error('缺少参数');
        }
        $info = PositionDocumentModel::where('id', $id)->find();
        if (empty($info)) {
            $this->error('岗位手册不存在');
        }
        // 保存数据
        if (!empty($this->request->isPost())) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'PositionDocument');
            // 验证失败 输出错误信息
            if(true !== $result) {
                $this->error($result);
            }
            $doc_list = PositionDocumentModel::where('position_id', '=', $data['position_id'])
                ->where('title', '=', $data['title'])
                ->where('id', '<>', $id)
                ->select()
                ->toArray();
            if ($doc_list) {
                $this->error('编辑失败,该岗位下已存在相同名称手册');
            }
            $data['clean_html_content'] = strip_tags($data['content']);
            $data['update_time'] = time();
            if (PositionDocumentModel::update($data)) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        } else {
            // 获取数据

            $position_list = PositionModel::where(['status' => 1])->column('name', 'id');
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('编辑岗位') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['hidden', 'id'],
                    ['text', 'title', '手册名称', '必填，岗位手册名称'],
                    ['select', 'position_id', '岗位名称', '', $position_list],
                    ['radio', 'status', '手册状态', '', ['禁用', '启用']],
                    ['ueditor', 'content', '手册内容'],
                    ['textarea', 'remark', '手册备注'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }
    }
}
