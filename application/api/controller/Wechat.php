<?php

namespace app\api\controller;

use app\admin\model\Config;
use app\clock\model\Member;
use app\common\controller\Common;
use think\request;
use app\api\model\User as WxUserModel;
use app\api\controller\wxBizDataCrypt;
use app\clock\model\Blacklist as BlacklistModel;






class Wechat extends Common
{

    public function index()
    {
//
//        $pdf =new \app\api\controller\CreatePdf();
//        $sign_pdf = $pdf->createpdf(2);



    }




    public function wxLogin(Request $request)
    {

        $logintype = isset($_SERVER['HTTP_LOGINTYPE']) ? $_SERVER['HTTP_LOGINTYPE'] : null;
        $version = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : null;
        if (!in_array($logintype, ['ios', 'android', 'wxxcx', 'wxh5', 'gfweb'])) {
            return res([], 400, '无效登录类型');
        }
        if ($request->isPost()) {
            $data = $request->post();
            //dump($data);
            if (isset($data['code'])) {
                $code = $data['code'];
                $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . Config('WX_XIAOCHENGXU_ID') . '&secret=' . Config('WX_XIAOCHENGXU_SECRET') . '&js_code=' . $code . '&grant_type=authorization_code';
                $res = file_get_contents($url);
                $res = json_decode($res, true);
                if (isset($res['openid'])) {
                    $openId = $res['openid'];
                    $session_key = $res['session_key'];
                    $unionId = isset($res['unionid']) ? $res['unionid'] : '';
                    $back = WxUserModel::where('openid', $openId)->limit(1)->field('uid,nickname,headimg,project_id')->find();
                    if ($back == null) {
                        $uid = getrandom();
                        if (WxUserModel::create([
                            'uid' => $uid,
                            'openid' => $openId,
                            'session_key' => $session_key,
                            'unionId' => $unionId,
                            'logintype' => $logintype,
                            'version' => $version,
                            'last_login_time' => time()
                        ])) {
                            $info = [
                                'uid' => $uid,
                                'isNew' => 1,
                            ];
                            return res($info);
                        }
                    }
                    WxUserModel::where('uid', $back['uid'])->setField('last_login_time', time());
                    return res($back);
                }return res($res,300, '接口错误');
            }return res([],300, '参数错误');
        }return res([],400, '非法请求');

    }


    public function userInfo(){
        if(!$this->request->isPost()) return res([],400, '非法请求');
        $data = $this->request->post();
        if(!isset($data['uid'])) return res([],300, '未登录，不能操作');
        $uid = $data['uid'];
        $user = WxUserModel::where(['uid'=>$uid,'status'=>0])->field('nickname,headimg,project_id,is_group,team_id')->find();
        $notify = WxUserModel::getNotify($uid);
        if($user){
            $info = [
                'project_id' => $user['project_id'],
                'is_group' => $user['is_group'],
                'team_id' => $user['team_id'],
                'nickname' => $user['nickname'],
                'headimg' => $user['headimg'],
                'notify' => $notify
            ];
            return res($info);
        }
    }

    public function getMobile(){
        if(!$this->request->isPost()) return res([],400, '非法请求');
        $data = $this->request->post();
        if(!isset($data['encryptedData']) || !isset($data['iv']) || !isset($data['code'])) return res([],400, '缺少参数');
        $code = $data['code'];
        $encryptedData = $data['encryptedData'];
        $iv = $data['iv'];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.Config('WX_XIAOCHENGXU_ID').'&secret='.Config('WX_XIAOCHENGXU_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
        $res = file_get_contents($url);
        $res = json_decode($res, true);
        if(!isset($res['session_key'])) return res([],400, '接口错误');
        $session_key = $res['session_key'];
        $pc =  new wxBizDataCrypt(Config('WX_XIAOCHENGXU_ID'), $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv,$info);
        if ($errCode != 0) return res([],400, '解密失败');
        $info = json_decode($info, true);
        $mobile = $info['phoneNumber'] ?? '';
        if(!$mobile) return res([],400, '获取手机号失败');
        return res(['mobile'=>$mobile]);

    }


    public function checkIdCard(){
        if(!$this->request->isPost()) return res([],400, '请求方式错误');
        $data = $this->request->post();
        if(!$data['url']) return res([],400, '请上传身份证');
        $img_url = $data['url'];
        //$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.Config('WX_XIAOCHENGXU_ID').'&secret='.Config('WX_XIAOCHENGXU_SECRET');
        //$res = file_get_contents($url);
        //$res = json_decode($res, true);
        //if(!isset($res['access_token'])) return res([],400, '接口错误');

        $access_token = get_xcx_token();
       // dump($access_token);
        $url = 'https://api.weixin.qq.com/cv/ocr/idcard?access_token='.$access_token;
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "img_url=".$img_url,
            CURLOPT_HTTPHEADER => [
                "Accept: */*",
                "Accept-Encoding: gzip, deflate, br",
                "Connection: keep-alive",
                "User-Agent: PostmanRuntime-ApipostRuntime/1.1.0"
            ],
        ]);

        $res = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return res([],500,$err);
        } else {
            $res = json_decode($res, true);
            if($res['errcode'] != 0) return res([],$res['errcode'],$res['errmsg']);
            if(!isset($res['name'])) return res([],400,'请上传身份证正面');
            $data = [
                'name' => $res['name'],
                'idcard' => $res['id'],
                 ];
            //判断是否在黑名单中
            $blacklist_id = BlacklistModel::where(['name'=>$data['name'],'idcard' => $data['idcard']])->value('id');
            if($blacklist_id) return res([],400,'该民工已在黑名单中，请联系项目经理');
             return res($data);
        }


    }





}