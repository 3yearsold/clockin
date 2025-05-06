<?php
namespace app\api\controller;
use app\common\controller\Common;
use app\api\model\feedback;



class Contract extends Common
{
    public function index()
    {

        return $this->fetch('contract/index');

    }

    public function upload()
    {

        // 获取上传的文件
        $file = $this->request->file('file'); // 修改文件名以匹配前端
        if (!$file) {
            return json(['status' => 'error', 'message' => '请选择文件上传']);
        }

            // 获取原始文件扩展名
            $originalName = $file->getInfo('name');
            $extension = strtolower(substr($originalName, strrpos($originalName, '.') + 1));

            // 验证文件大小和扩展名
            $allowedExtensions = ['doc', 'docx', 'pdf'];
            $maxFileSize = 10 * 1024 * 1024; // 5MB
            if ($file->getSize() === 0) {
                return json(['status' => 'error', 'message' => '文件大小为0，请选择有效文件']);
            }
            if (!in_array($extension, $allowedExtensions)) {
                return json(['status' => 'error', 'message' => '仅支持 doc, docx, pdf 格式的文件']);
            }
            if ($file->getSize() > $maxFileSize) {
                return json(['status' => 'error', 'message' => '文件大小超过限制（5MB）']);
            }

            // 确保上传目录存在
            $uploadDir = config('upload_path') . DIRECTORY_SEPARATOR . 'temp';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // 移动文件到指定目录
            $info = $file->move($uploadDir);
            $path = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/temp/' . str_replace('\\', '/', $info->getSaveName()); // 修改路径

            //$auth_code = session('auth_code');
            if(!empty($this->request->get('auth_code'))){
                $auth_code = $this->request->get('auth_code');
                $user_name = $this->GetHecomUserName($auth_code);
                action_log('clock.use_ai', 'contract', 0, 1, $user_name.'审查了合同：'.$originalName);
            }
        return json(['code' => 0, 'url' => $path,'token'=> config('coze_token'),'flowId'=>config('coze_workflow')]);




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
        $result = json_decode($result,true);
        $uid = $result['data']['uid'];
        $user_url ="api.cloud.hecom.cn/oapi/v1/oauth/user/detail/".$uid;
        $result = file_get_contents($user_url,false,$context);
        $result = json_decode($result,true);
        $user_name = $result['data']['name'];
        return $user_name;

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


    public function feedback(){
        if(!$this->request->isPost()) return json(['code' => 1, 'msg' => '非法请求']);
        $content = $this->request->post('content');
        // 基础验证
        if (empty($content)) {
            return json(['code' => 2, 'msg' => '反馈内容不能为空']);
        }

        if (mb_strlen($content) > 500) {
            return json(['code' => 3, 'msg' => '反馈内容不超过500字']);
        }
        $userName = '匿名用户';
        if(!empty($this->request->get('auth_code'))){
            $auth_code = $this->request->get('auth_code');
            $user_name = $this->GetHecomUserName($auth_code);}

        feedback::create(['user_name' => $userName, 'content' => $content]);
        return json(['code' => 0,'msg' => '反馈成功']);




    }






}