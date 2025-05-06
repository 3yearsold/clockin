<?php

namespace app\common\traits;

use think\Cache;
use think\Config;
use think\Db;
use think\exception\HttpResponseException;
use think\Log;
use think\Request;
use think\Response;
use think\response\Redirect;
use think\Url;
use think\View as ViewTemplate;

trait ApiInit
{
    //数据集合！
    protected $_initialize = [];

    /**
     * 存储数据到数据集
     * @param mixed $name
     * @param string $value
     */
    protected function assign($name, $value = '')
    {
        $this->_initialize[$name] = $value;
        return $this;
    }

    /**
     * 输出数据
     * @param string $template
     * @param array $vars
     * @param array $replace
     * @param array $config
     * @return mixed|void
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        if ($template !== '')
        {
            $this->_initialize['template'] = $template;
        }


        $this->result($this->_initialize, 200, $msg = '', $type = 'json');
    }

    /**
     * 操作错误跳转的快捷方法
     * @param string $msg
     */
    protected function error($msg = 'error', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $this->result($data, 500, $msg, 'json');
    }

    /**
     * 操作成功跳转的快捷方法
     * @param string $msg
     */
    protected function success($msg = 'success', $url = null, $data = '', $wait = 3, array $header = [])
    {

        $this->result($data, 200, $msg, 'json');
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param int $code 返回的 code
     * @param mixed $msg 提示信息
     * @param string $type 返回数据格式
     * @param array $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($data = [], $code = 200, $msg = 'success', $type = 'json', array $header = [])
    {
        //Log::result(json_encode(['titile' => '日志', 'msg' => $msg, '']));
        //action_log('config_edit', 'admin_config', $config['id'], UID, $details);
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => time(),
            'data' => $data,
        ];

//        if (Config::get('app_debug'))
//        {
//            $result['debug'] = static::getLog();
//        }

        //$type = $type ?: Config::get('default_return_type');
        //$response = Response::create($result, $type)->header($header);
        //throw new HttpResponseException($response);
        return $result;
    }

    static public function getLog()
    {
        return	$Log_sql = \think\Log::getLog();
    }

}