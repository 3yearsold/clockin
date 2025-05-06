<?php

namespace app\api\controller;
use app\common\controller\Common;
use app\common\builder\ZBuilder;
use app\admin\controller\Admin;
use app\red_circle\model\Position as PositionModel;
use app\red_circle\model\PositionDocument as PositionDocumentModel;
use think\Db;

class PositionDocument extends common
{

    /**
     * 获取左侧导航数据
     * @return mixed
     * User: zheng
     * Date: 2025/4/8 13:37
     */
    public function index()
    {
        //岗位列表
        $position_list = PositionModel::where(['status' => 1])->column('name','id');
        //文档列表
        $data = [];
        if (!empty($position_list)) {
            $map[] = ['position_id', 'in', array_keys($position_list)];
            $map[] = ['status', '=', 1];
            $document_list = PositionDocumentModel::field('id,title,position_id')->where($map)->select()->toArray();
            //导航数据处理
            foreach ($document_list as $document) {
                $position_id = $document['position_id'];
                if (!empty($data[$position_id])) {
                    $data[$position_id]['list'][] = [
                        'id' => $document['id'],
                        'title' => $document['title']
                    ];
                } else {
                    $data[$position_id] = [
                        'position_id' => $position_id,
                        'position_name' => $position_list[$position_id],
                        'list' => [
                            [
                                'id' => $document['id'],
                                'title' => $document['title'],
                            ]
                        ]
                    ];

                }
            }
        }

        $homeContent = [
            'id' => 0 ,
            'title' => '红圈系统操作手册说明' ,
            'content' => '为不同岗位定制专属操作手册，实现：快速握核心流程，统一执行标准，降低人为错误'
        ];
        $this->assign('menuData', $data);
        $this->assign('homeContent', $homeContent);
        return $this->fetch();
    }


    /**
     * 搜索手册
     * @return null
     * User: zheng
     * Date: 2025/4/8 13:38
     */
    public function search()
    {
        $keyword = $this->request->param('keyword');
        $map = [
            ['status', '=', 1],
        ];
        $data = PositionDocumentModel::field('id,title')->where($map)
            ->where("MATCH(title,clean_html_content) AGAINST(:keyword IN BOOLEAN MODE)")
            ->bind(['keyword' => $keyword,])
            ->select()
            ->toArray();
        return $this->result($data);
    }

    /**
     * 手册详情
     * @return null
     * User: zheng
     * Date: 2025/4/8 13:39
     */
    public function detail()
    {
        $id = $this->request->param('id');
        $info = PositionDocumentModel::where('id', $id)->find();
        if (empty($info)) {
            $this->error('岗位手册不存在');
        }
        return $this->result($info->toArray());
    }

    /**
     * 手册说明页
     * @return null
     * User: zheng
     * Date: 2025/4/9 8:37
     */
    public function home()
    {
        $homeContent = [
            'id' => 0 ,
            'title' => '红圈系统操作手册说明' ,
            'content' => '为不同岗位定制专属操作手册，实现：快速握核心流程，统一执行标准，降低人为错误'
        ];
        return $this->result($homeContent);
    }

}