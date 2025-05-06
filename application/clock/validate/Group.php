<?php

namespace app\clock\validate;
use think\Validate;

class Group extends Validate
{
    protected $rule = [
        'team_id|班组名称'  => 'require',
    ];


}