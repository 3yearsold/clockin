<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 会话设置
// +----------------------------------------------------------------------
//
//return [
//    'id'             => 'bh',
//    // SESSION_ID的提交变量,解决flash上传跨域
//    'var_session_id' => '',
//    // SESSION 前缀
//    'prefix'         => 'bohong_',
//    // 驱动方式 支持redis memcache memcached
//    'type'           => '',
//    // 是否自动开启 SESSION
//    'auto_start'     => true,
//    //过期时间
//    'expire' => 24*3600*7,
//    'domain' => 'cmf.com', //关键点
//];



return [
    'id'             => '',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // SESSION 前缀
    'prefix'         => 'bohong_',
    // 驱动方式 支持redis memcache memcached
    'type'           => '',
    // 是否自动开启 SESSION
    'auto_start'     => true,
];
