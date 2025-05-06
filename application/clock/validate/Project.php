<?php

namespace app\clock\validate;
use think\Validate;

class Project extends Validate
{
    protected $rule = [
        'project_name|项目名称'  => 'require',
        'project_customer|客户名称' => 'require',
        'project_manager|项目经理' => 'require',
        'map_address|地图位置' => 'require|min:10',
        'project_date|立项时间' => 'require',
    ];



 }