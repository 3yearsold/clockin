<?php

namespace app\api\model;
use think\Model;
use think\Db;

class Feedback extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'feedback';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}