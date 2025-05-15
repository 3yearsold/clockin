<?php

namespace app\http\middleware;

class Cors
{
    public function handle($request, \Closure $next)
    {
        ini_set('session.cookie_secure', true);
        ini_set('session.cookie_samesite', 'None');
        $response = $next($request);
        // 指定允许的第三方域名（需动态配置）
        $allowedOrigin = 'https://cloud.hecom.cn';
        $response->header([
            'Access-Control-Allow-Origin'      => $allowedOrigin,
            'Access-Control-Allow-Headers'    => 'Origin, Content-Type, Authorization, X-Requested-With',
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true', // 允许携带Cookie
            'Access-Control-Max-Age'           => 1800,
            'X-Content-Type-Options'           => 'nosniff',
        ]);
        // 处理OPTIONS预检请求
        if ($request->isOptions()) {
            $response->code(204);
        }

        return $response;
    }


}
