<?php
namespace app\clock\validate;
use think\Validate;

class Team extends Validate
{
    protected $rule = [
        'name|班组名称'  => 'require|unique:clock_team',
    ];

    protected $message = [
        'name.require' => '班组名称不能为空！',
        'name.unique'  => '班组名称已存在！如果存在同名班组，可以加修饰名，比如：杭州张三',
    ];



}