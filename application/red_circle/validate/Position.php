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
class Position extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|岗位名称' => 'require|unique:red_circle_position',
        'status|岗位状态' => 'require',
    ];

    // 定义验证场景
    protected $scene = [
        //编辑
//        'edit'  =>  ['name'],
    ];

}
