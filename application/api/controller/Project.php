<?php

namespace app\api\controller;
use app\common\controller\Common;
use app\clock\model\Project as ProjectModel;
use app\clock\model\Team as TeamModel;
use app\api\model\User as UserModel;
use app\clock\model\Member as MemberModel;
use app\clock\model\Clockin as ClockinModel;
use app\clock\model\Group as GroupModel;
use app\clock\model\Station as StationModel;
use app\clock\model\Blacklist as BlacklistModel;
use app\clock\model\WxUser as WxUserModel;
use think\Db;

class Project extends common
{
    public function search()
    {
        if(!$this->request->isPost()) return res([],400,'请求方式错误');
        $data = $this->request->post();
        if(!isset($data['pid'])) return res([],400,'缺少参数');
        $pid = $data['pid'];
        $project_name= ProjectModel::where('pid',$pid)->value('project_name');
        return res(['pid'=>$pid,'project_name'=>$project_name]);

    }

    public function getTeam(){
        if(!$this->request->isPost()) return res([],400,'请求方式错误');
        $teamList = TeamModel::where(['uid' => NULL])->field('id,name')->select();
        return res($teamList);
    }


    public function setTeam(){
        if(!$this->request->isPost()) return res([],400,'请求方式错误');
        $uid = isset($_SERVER['HTTP_UID']) ? $_SERVER['HTTP_UID'] : null;
        if(!$uid) return res([],400,'未登陆');
        $data = $this->request->post();
        if(!isset($data['mobile']) || !isset($data['groupId'])) return res([],400,'缺少参数');
        $mobile = $data['mobile'];
        $groupId= $data['groupId'];
        $team = UserModel::where('uid',$uid)->setField(['mobile'=>$mobile,'team_id'=>$groupId,'is_group'=>2]);
        return res([],0,'绑定申请成功');
    }


    public function useAudit(){
        if(!$this->checkHander()) return res([],400,'未登录或请求方式错误');

        $data = $this->request->post();
        if(!isset($data['teamid'])) return res([],400,'缺少参数');
        $teamId = $data['teamid'];

        $list = DB::query("select id,member,gender,mobile,station from dp_clock_member where group_id in (select id from dp_clock_group where team_id = ".$teamId.") and status=0");

        return res($list);

    }

    public function getMemberInfo(){
        if(!$this->checkHander()) return res([],400,'未登录或请求方式错误');
        $data = $this->request->post();
        if(!isset($data['id'])) return res([],400,'缺少参数');
        $id = $data['id'];
        $info = MemberModel::getMemberById($id);
        return res($info);
    }
    //班组审核民工
    public function memberAudit(){
        if(!$this->checkHander()) return res([],400,'未登录或请求方式错误');
        $data = $this->request->post();
        if(!isset($data['memberId']) || !isset($data['opera'])) return res([],400,'缺少参数');
        $memberId = $data['memberId'];
        $opera = $data['opera'];
        if(MemberModel::where(['id'=>$memberId])->update(['status'=>$opera])) {
            if($opera==2){
                $info = MemberModel::where('id',$memberId)->field('uid,member')->find();
                UserModel::where('uid',$info['uid'])->setField('nickname',$info['member']);
                return res();
             }else{
                $this->auditMsg($memberId);
                return res();
            }
        }else{
            return res([],400,'操作失败');
        }
    }

    public function auditMsg($memberId){
        $uid = MemberModel::where('id',$memberId)->value('uid');
        $group_id = MemberModel::where('id',$memberId)->value('group_id');
        $pid = MemberModel::where('id',$memberId)->value('project_id');
        $name = GroupModel::where('id',$group_id)->value('name');
        $openid = WxUserModel::where('uid',$uid)->value('openid');
        sendAuditMsg($openid,'班组审核',$name,date('Y-m-d H:i:s'),"pages/start/start?toPage=userinfo&pid={$pid}");

    }


    public function checkHander(){
        if(!$this->request->isPost()) return false;
        $uid = isset($_SERVER['HTTP_UID']) ? $_SERVER['HTTP_UID'] : null;
        if(!$uid) return false;

        return true;
    }



    public function getNowTime(){
        if(!$this->checkHander()) return res([],400,'未登录或请求方式错误');
        $time = microtime(true);
        return res(['time'=>$time]);
    }

    public function checkRadius($uid,$lat,$lng){
        $project_id =MemberModel::where(['uid'=>$uid,'status'=>3])->value('project_id');
        $info = ProjectModel::where('Pid',$project_id)->Field('map,map_radius')->find();
        $map = $info['map'];
        if($map == null) return false;
        $map_radius = $info['map_radius'];
        list($longitude, $latitude) = explode(',', $map);
        //以下为百度坐标系转换成腾讯坐标系  BD09 转换为 GCJ02
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $longitude - 0.0065;
        $y = $latitude - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $longitude = $z * cos($theta);
        $latitude = $z * sin($theta);
        //转换完成
        //计算距离
        $earthRadius = 6371000; // Radius of the Earth in meters
        $dLat = deg2rad($lat - $latitude);
        $dLng = deg2rad($lng - $longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($latitude)) * cos(deg2rad($lat)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return $distance <= $map_radius;
    }

    public function checkClock(){
        if(!$this->request->isPost()) return res([],400,'请求方式错误');
        $uid = isset($_SERVER['HTTP_UID']) ? $_SERVER['HTTP_UID'] : null;
        if(!$uid) return res([],400,'未登陆');
        $data = $this->request->post();
        if(!isset($data['lat']) || !isset($data['lng'])) return res([],400,'缺少参数');
        $lat = $data['lat'];
        $lng = $data['lng'];

        //通过民工状态判断能不能打卡
        $status  = MemberModel::where('uid',$uid)->value('status');
        if($status == 0) return res([],400,'待班组审核，不能打卡');
        if($status == 1) return res([],400,'审核未通过，不能打卡');
        if($status == 2) return res([],400,'待项目经理确认，不能打卡');
        if($status == 4) return res([],400,'已退场，不能打卡');
        //通过定位判断能不能打卡
        if(!$this->checkRadius($uid,$lat,$lng)) return res([],400,'不在打卡范围内，不能打卡');
        //通过打卡时间判断能不能打卡
        $currentTime = time(); // 当前时间戳
        $todayStartTime = strtotime(date('Y-m-d', $currentTime));  //今天开始时间戳
        $lastCheckInTime  = ClockinModel::where('uid',$uid)->where('create_time','>=',$todayStartTime)->order('create_time','desc')->value('create_time');
        if($lastCheckInTime == null){ // 没有打卡记录
            return res([],0,'可以打卡');
        }else {

            $count = ClockinModel::where('uid',$uid)->where('create_time','>=',$todayStartTime)->count();
            if($count >= 5) return res([],400,'今天已经打卡5次，不能再打卡了');

            $interval = $currentTime - $lastCheckInTime;
            if ($interval < 3600) { // 间隔小于1小时
                return res([], 400, '距离上次打卡时间不足1小时，上次打卡时间：'.date("Y-m-d H:i:s", $lastCheckInTime));
            }else{
                return res([],0,'可以打卡');
            }
        }

    }


    public function getGroup(){
        if(!$this->checkHander()) return res([],400,'未登录或请求方式错误');
        $data = $this->request->post();
        if(!isset($data['project_id'])) return res([],400,'缺少参数');
        $project_id = $data['project_id'];
        $group = GroupModel::where('project_id',$project_id)->field('id,name')->select();
        return res($group);
    }

    public function getStation(){
        if(!$this->checkHander()) return res([],400,'未登录或请求方式错误');
        $list = StationModel::where('type',0)->field('id,name')->select();
        return res($list);
    }

    public function getSpecDocument(){
        if(!$this->checkHander()) return res([],400,'未登录或请求方式错误');
        $list = StationModel::where('type',1)->field('id,name')->select();
        return res($list);
    }

    public function memberInfo(){
        if(!$this->request->isPost()) return res([],400,'请求方式错误');
        $uid = isset($_SERVER['HTTP_UID']) ? $_SERVER['HTTP_UID'] : null;
        if(!$uid) return res([],400,'未登陆');
        $data = $this->request->post();
        if(!isset($data['pid'])) return res([],400,'缺少参数');
        $pid = $data['pid'];
        $info = MemberModel::where(['uid'=>$uid,'project_id'=>$pid])->order('id','desc')->find();
        if($info['member'] == null) {
            $list = MemberModel::where('uid',$uid)->order('id','desc')->find();
            if($list['member'] == null) {
                return res([]);
            }else {
                return res($list);
            }
        }else {
            return res($info);
        }
    }
    //新增&编辑民工信息
    public function updateMember(){
        if(!$this->request->isPost()) return res([],400,'请求方式错误');
        $uid = isset($_SERVER['HTTP_UID']) ? $_SERVER['HTTP_UID'] : null;
        if(!$uid) return res([],400,'未登陆');
        $data = $this->request->post();
        if(!isset($data['projectId']) || !isset($data['groupId'])) return res([],400,'缺少参数');
        $member = $data['member'];
        $idcard = $data['idcard'];
        //判断是否在黑名单中
        $blacklist_id = BlacklistModel::where(['name'=>$member,'idcard' => $idcard])->value('id');
        if($blacklist_id) return res([],400,'该民工已在黑名单中，请联系项目经理');
        $project_id = $data['projectId'];
        //项目状态确认
        $project = ProjectModel::where('pid',$project_id)->find();
        if (empty($project)) {
            return res([],400,'项目信息错误！');
        } else {
            //已完工或者停工
            if (in_array($project->status,[1,2])) {
                $msg = $project->status == 1 ? "项目已完工" : "项目已停工";
                return res([],400,$msg);
            }
        }
        $project_id = $data['projectId'];
        $group_id= $data['groupId'];
        $idcard_url = $data['idcardUrl'];
        $gender = $data['gender'];
        $mobile = $data['mobile'];
        $account_name = $data['accountName'];
        $account = $data['account'];
        $bank = $data['bank'];
        $reg_date = $data['regDate'];
        $station = $data['station'];
        $document_name = $data['documentName'];
        $spec_document = $data['specDocument'];
        $reg_date =strtotime($reg_date);
        $member_id = MemberModel::where(['project_id'=>$project_id,'uid'=>$uid,'group_id' => $group_id])->value('id');
        if($member_id == null) {
            //退场状态确认如果没有退场则失败
            $status_arr = MemberModel::where('uid','=',$uid)->column('status');
            if (in_array(3, $status_arr)) {
                return res([],400,'请先在其他项目或班组退场');
            }
            if (!empty(array_intersect($status_arr,[0,2]))) {
                return res([],400,'您有其他入场申请还在审批中');
            }
            $result = MemberModel::create([
                'uid'=>$uid,
                'project_id'=>$project_id,
                'member'=>$member,
                'idcard'=>$idcard,
                'group_id'=>$group_id,
                'idcard_url'=>$idcard_url,
                'gender'=>$gender,
                'mobile'=>$mobile,
                'account_name'=>$account_name,
                'account'=>$account,
                'bank'=>$bank,
                'station'=>$station,
                'reg_date'=>$reg_date,
                'document_name'=>$document_name,
                'spec_document'=>$spec_document,
            ]);
            if($result) {
                $pdf =new \app\api\controller\CreatePdf();
                $sign_pdf = $pdf->createpdf($result['id']);
                $this->regeditMsg($member,$project_id,$group_id);
                return res();
            }else{
                return res([],300,'保存数据失败！');
            }

        }else{
            $result= MemberModel::where(['id'=>$member_id])->update([
                'project_id'=>$project_id,
                'member'=>$member,
                'idcard'=>$idcard,
                'group_id'=>$group_id,
                'idcard_url'=>$idcard_url,
                'gender'=>$gender,
                'mobile'=>$mobile,
                'account_name'=>$account_name,
                'account'=>$account,
                'bank'=>$bank,
                'station'=>$station,
                'reg_date'=>$reg_date,
                'document_name'=>$document_name,
                'spec_document'=>$spec_document,
                'status'=>0
            ]);
            if($result){
                $pdf =new \app\api\controller\CreatePdf();
                $sign_pdf=$pdf->createpdf($member_id);

                $this->regeditMsg($member,$project_id,$group_id);
                return res();
            }else{
                return res([],300,'保存数据失败！');
            }
        }

    }

    public function regeditMsg($member,$project_id,$group_id){
        $team_id = GroupModel::where('id',$group_id)->value('team_id');
        $team_uid = TeamModel::where('id',$team_id)->value('uid');
        $openid = WxUserModel::where('uid',$team_uid)->value('openid');
        $project_name = ProjectModel::where('pid',$project_id)->value('project_name');
        sendTeamMsg($openid,$member,date('Y-m-d H:i:s'),$project_name,'pages/start/start?toPage=member');
    }

    //项目打卡
    public function clockIn(){
        if(!$this->request->isPost()) return res([],400,'请求方式错误');
        $uid = isset($_SERVER['HTTP_UID']) ? $_SERVER['HTTP_UID'] : null;
        if(!$uid) return res([],400,'未登陆');
        $data = $this->request->post();
        if(!isset($data['pic']) || !isset($data['lat']) || !isset($data['lng'])) return res([],400,'参数错误');
        $pic = $data['pic'];
        $lat = $data['lat'];
        $lng = $data['lng'];
        $info = MemberModel::where(['uid'=>$uid ,'status' => 3])->field('id,project_id,member,station,group_id')->find();
        if(!$info) return res([],400,'未找到打卡项目信息');
        $location= config('location_api').$lat.','.$lng;
        $result = json_decode(file_get_contents($location),true);
        if($result['status'] == 0) {
            $address = $result['result']['address'];
            $clock = [
                'uid'=>$uid,
                'member_id'=>$info['id'],
                'member_name'=>$info['member'],
                'project_id'=>$info['project_id'],
                'group_id'=>$info['group_id'],// 班组id
                'clock_date'=>strtotime(date('Y-m-d')),// 班组id
                'pic'=>$pic,
                'station'=>$info['station'],
                'lat'=>$lat,
                'lng'=>$lng,
                'location'=>$address,
            ];
            if(ClockinModel::create($clock)) {
                //删除原图
                $needle = "upload";
                $pos = strpos($pic, $needle);
                if ($pos !== false) {
                    $path = substr($pic, $pos); // 从逗号后开始截取
                    //判断是否缩略图
                    if (strpos($path, '/thumb') !== false) {
                        $path = str_replace('/thumb','',$path);
                        $real_path = realpath($path);
                        if (is_file($real_path)) {
                            unlink($real_path);
                        }
                    }
                }
                return res([],0,'打卡成功');
            }
        }else{
            return res([],400,'获取地址失败');
        }
    }



}