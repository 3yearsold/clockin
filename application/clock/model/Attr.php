<?php
namespace app\clock\model;
use think\Model;



class Attr extends Model{

    protected $name = 'clock_project_attr';

    public static function getAttrList($map = [])
    {
        return self::where($map)->column('id,name');
    }


}
