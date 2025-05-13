<?php

namespace app\api\controller;
use app\common\controller\Common;
use think\Db;
use app\user\model\User as UserModel;

class Login extends Common {

    public function index(){
        if(!empty($this->request->get('auth_code'))){
            $auth_code = $this->request->get('auth_code');
            $phone = $this->GetHecomUserName($auth_code);
            if($phone == '13600520970'){
                $phone = '15057465733';
            }
            $user = UserModel::get(['mobile' => $phone]);
            if($user){
                $usermodel = new UserModel();
                $id = $usermodel->autoLogin($user);
                if($id){
                    return redirect('/admin.php/clock?token='.session_id());
                }
            }else{
                 $this->error('用户不存在');
            }
            
        }
    }

    private function GetHecomUserName($auth_code){
        $token = $this->GetHecomToken();
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer ". $token. "\r\n". "Content-Type: application/json \r\n",
            ]
        ]);
        $uid_url ="https://api.cloud.hecom.cn/oapi/v1/oauth/user/uid/".$auth_code;
        $result = file_get_contents($uid_url,false,$context);
        $result = json_decode($result,true);        $uid = $result['data']['uid'];
        $user_url ="https://api.cloud.hecom.cn/oapi/v1/oauth/user/detail/".$uid;
        $result = file_get_contents($user_url,false,$context);
        $result = json_decode($result,true);
        // $user_name = $result['data']['name'];
        $phone = $result['data']['phone'];
        return $phone;

    }


    private function GetHecomToken(){
        $now = time();
        $expires = config('expires_time');
        if ($now > $expires) {
            $url = 'https://tc.cloud.hecom.cn/hecom-tenancy/oauth/token';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Basic R3NtQm9wQ0hTRHQxMHNHUzpJcUQ5Q1A5Q0RvSjVmRVJoZHZGSktFNjNyaTRnaUl3Qw=='
            ];
            $data = [
                'grant_type' => 'client_credentials',
                'username' => '18005851121'
            ];
            $result = GetPostRequest($url, $headers, $data);
            $token = $result['access_token'];
            $expire_time = $now + 40000;
            \app\admin\model\Config::setValues('access_token', $token);
            \app\admin\model\Config::setValues('expires_time', $expire_time);
            return $token;
        } else {
            return config('access_token');
        }
    }


}