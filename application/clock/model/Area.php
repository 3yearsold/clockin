<?php
namespace app\clock\model;
use think\Model;



class Area extends Model{

    protected $name = 'base_area';

    public static function getAreaList($parentid = 0)
    {
        return self::where(['parentid'=>$parentid])->column('areaid,name');
    }


}
