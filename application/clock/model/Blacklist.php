<?php

namespace app\clock\model;
use think\Model;

class Blacklist extends Model
{
    protected $name = 'clock_blacklist';
    protected $autoWriteTimestamp = true;

}