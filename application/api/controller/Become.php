<?php

namespace app\api\controller;
use app\common\controller\Common;
use app\clock\model\Area;
use app\clock\model\Project;

class Become extends Common
{
    public function sync(){
        $logintype = isset($_SERVER['HTTP_LOGINTYPE']) ? $_SERVER['HTTP_LOGINTYPE'] : null;
        if($logintype != 'hecom')   return json(['status' => 500,'massage' => '非法请求']);
        if(!$this->request->isPost()) return json(['status' => 500,'massage' => '非法请求']);
        $data = $this->request->post();
        //$data = json_decode($data,true);
        $name = $data['name'];
        $street = $data['street'];
        $project_manager = $data['field4__c'];
        $project_customer = $data['customer']['name'];
        $work_limit = $data['projectOverview'] ?? '';

        $pid = get_udf_pid();

        $project =[
            'pid' =>$pid,
            'project_name' => $name,
            'project_customer'=> $project_customer,
            'map_address' => $street,
            'map_radius'=> 200,
            'project_manager'=> $project_manager,
            'work_limit' => $work_limit,
        ];


        $have = Project::where(['project_name'=>$name,'map_address' => $street])->find();
        if($have) return json(['status' => 200,'massage' => '该项目已存在']);

        if(Project::create($project)){
            return json(['status' => 200,'massage' => '同步成功']);
        }else{
            return json(['status' => 200,'massage' => '同步失败']);
        }








    }


}