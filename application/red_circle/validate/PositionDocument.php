<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\red_circle\validate;

use think\Validate;

/**
 * 岗位验证
 * @package app\admin\validate
 * @author zqk
 */
class PositionDocument extends Validate
{
    // 定义验证规则
    protected $rule = [
        'title|手册名称' => 'require',
        'content|手册内容' => 'require',
        'position_id|岗位' => 'require',
        'status|手册状态' => 'require',
    ];

    // 定义验证场景
    protected $scene = [
        //编辑
//        'edit'  =>  ['name'],
    ];

}
