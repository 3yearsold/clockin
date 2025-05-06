<?php

namespace app\clock\validate;
use think\Validate;

class Blacklist extends Validate
{
    protected $rule = [
        'name|民工姓名'  => 'require',
        'idcard|身份证号' => 'require',
    ];



}