<?php
namespace app\api\controller;

use app\common\controller\Common;
use think\facade\Log;

class DingTalk extends Common
{
    private $clientId = 'dingir8d0pj7hcc22cxr';
    private $clientSecret = 'gW0hFCnR0nU1ZHnw5GOJWuIFFdB2PV3A9RwS2zdacvBZDefZ7ArvlihYi2p4y0YR';

    /**
     * 获取钉钉访问令牌
     */
    public function getAccessToken()
    {
        try {
            $url = 'https://api.dingtalk.com/v1.0/oauth2/accessToken';
            $data = [
                'appKey' => $this->clientId,
                'appSecret' => $this->clientSecret
            ];

            $result = $this->httpPost($url, $data);
            
            if (!isset($result['accessToken'])) {
                Log::error('钉钉凭证获取失败：'.json_encode($result));
                return $this->error('服务暂不可用');
            }

            return $this->success($result);
        } catch (\Exception $e) {
            Log::error('钉钉接口异常：'.$e->getMessage());
            return $this->error('系统繁忙');
        }
    }

    /**
     * 通用HTTP POST请求
     */
    private function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * 获取部门列表
     */
    public function getDepartments()
    {
        try {
            $token = $this->getAccessToken();
            $url = 'https://oapi.dingtalk.com/topapi/v2/department/listsub';
            $params = ['access_token' => $token['accessToken']];
            
            $result = $this->httpGet($url, $params);
            
            if ($result['errcode'] !== 0) {
                Log::error('部门获取失败：'.json_encode($result));
                return $this->error('通讯录同步失败');
            }
            return $this->success($result['result']);
        } catch (\Exception $e) {
            Log::error('部门接口异常：'.$e->getMessage());
            return $this->error('系统繁忙');
        }
    }

    /**
     * 获取部门成员详情
     */
    public function getDepartmentMembers()
    {
        try {
            $deptId = input('dept_id', 0);
            $page = input('page', 1);
            $size = input('size', 20);

            if (!$deptId) {
                return $this->error('请选择部门');
            }

            $token = $this->getAccessToken();
            $url = 'https://oapi.dingtalk.com/topapi/v2/user/list';
            $params = [
                'access_token' => $token['accessToken'],
                'dept_id' => $deptId,
                'cursor' => ($page - 1) * $size,
                'size' => $size
            ];

            $result = $this->httpGet($url, $params);

            if ($result['errcode'] !== 0) {
                Log::error('成员获取失败：'.json_encode($result));
                return $this->error('成员同步失败');
            }

            // 字段映射和数据处理
            $members = array_map(function($user) {
                return [
                    'id' => $user['userid'],
                    'name' => $user['name'],
                    'position' => $user['title'] ?? '',
                    'mobile' => $user['mobile'] ?? '',
                    'avatar' => $user['avatar'] ?? '',
                    'job_number' => $user['job_number'] ?? ''
                ];
            }, $result['result']['list'] ?? []);

            return $this->success([
                'total' => $result['result']['has_more'] ? $page * $size + 1 : $page * $size,
                'items' => $members
            ]);

        } catch (\Exception $e) {
            Log::error('成员接口异常：'.$e->getMessage());
            return $this->error('系统繁忙');
        }
    }

    /**
     * 通用HTTP GET请求
     */
    private function httpGet($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * 获取考勤记录
     */
    public function getAttendance()
    {
        try {
            $token = $this->getAccessToken();
            $url = 'https://oapi.dingtalk.com/topapi/attendance/getattends';
            
            $data = [
                'access_token' => $token['accessToken'],
                'workDateFrom' => date('Y-m-d', strtotime('-7 days')),
                'workDateTo' => date('Y-m-d'),
                'offset' => 0,
                'limit' => 50
            ];

            $result = $this->httpPost($url, $data);

            if ($result['errcode'] !== 0) {
                Log::error('考勤获取失败：'.json_encode($result));
                return $this->error('考勤同步失败');
            }

            // 处理分页数据
            $attendanceList = [];
            while (!empty($result['recordresult'])) {
                $attendanceList = array_merge($attendanceList, $result['recordresult']);
                
                if (count($result['recordresult']) < $data['limit']) break;
                
                $data['offset'] += $data['limit'];
                $result = $this->httpPost($url, $data);
            }

            return $this->success($attendanceList);
        } catch (\Exception $e) {
            Log::error('考勤接口异常：'.$e->getMessage());
            return $this->error('系统繁忙');
        }
    }

    /**
     * 自动同步入口方法
     */
    public function autoSync()
    {
        try {
            // 创建三种类型的同步任务
            $this->createSyncTask('department');
            $this->createSyncTask('member');
            $this->createSyncTask('attendance');

            // 触发异步任务处理
            $this->dispatchSyncTasks();

            return $this->success('同步任务已启动');
        } catch (\Exception $e) {
            Log::error('自动同步异常：'.$e->getMessage());
            return $this->error('同步启动失败');
        }
    }

    /**
     * 创建同步任务记录
     */
    private function createSyncTask($type)
    {
        \think\Db::name('sync_tasks')->insert([
            'type' => $type,
            'status' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 分发同步任务到队列
     */
    private function dispatchSyncTasks()
    {
        // 实际项目中这里应该调用队列服务
        // 示例使用简单多进程模拟
        $this->executeParallel([
            ['type' => 'department', 'method' => 'getDepartments'],
            ['type' => 'member', 'method' => 'getDepartmentMembers'],
            ['type' => 'attendance', 'method' => 'getAttendance']
        ]);
    }

    /**
     * 并行执行任务
     */
    private function executeParallel($tasks)
    {
        foreach ($tasks as $task) {
            try {
                // 更新任务状态为执行中
                \think\Db::name('sync_tasks')
                    ->where('type', $task['type'])
                    ->update([
                        'status' => 1,
                        'last_run_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                // 执行具体同步任务
                $this->handleSyncTask($task);

                // 更新任务状态为成功
                $this->updateTaskStatus($task['type'], 2);

            } catch (\Exception $e) {
                // 处理任务失败
                $this->handleTaskError($task['type'], $e);
            }
        }
    }

    /**
     * 处理具体同步任务
     */
    private function handleSyncTask($task)
    {
        switch ($task['type']) {
            case 'department':
                $this->syncDepartments();
                break;
            case 'member':
                $this->syncMembers();
                break;
            case 'attendance':
                $this->syncAttendance();
                break;
        }
    }

    /**
     * 同步部门数据
     */
    private function syncDepartments()
    {
        $result = $this->getDepartments();
        if ($result['code'] !== 200) {
            throw new \Exception('部门同步失败：' . $result['msg']);
        }
        // 实际存储逻辑...
        Log::info('部门同步成功，共同步'.count($result['data']).'个部门');
    }

    /**
     * 同步成员数据
     */
    private function syncMembers()
    {
        // 获取所有部门后同步成员
        $depts = $this->getDepartments()['data'];
        foreach ($depts as $dept) {
            $result = $this->getDepartmentMembers($dept['id']);
            if ($result['code'] !== 200) {
                throw new \Exception("部门{$dept['id']}成员同步失败");
            }
            // 实际存储逻辑...
        }
        Log::info('成员同步完成，共处理'.count($depts).'个部门');
    }

    /**
     * 处理任务错误
     */
    private function handleTaskError($type, $e)
    {
        $task = \think\Db::name('sync_tasks')
            ->where('type', $type)
            ->find();

        $retryCount = $task['retry_count'] + 1;
        $maxRetries = 3;

        $updateData = [
            'status' => $retryCount < $maxRetries ? 0 : 3,
            'retry_count' => $retryCount,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        \think\Db::name('sync_tasks')
            ->where('id', $task['id'])
            ->update($updateData);

        Log::error("同步任务失败：{$type} ".$e->getMessage());
    }

    /**
     * 更新任务状态
     */
    private function updateTaskStatus($type, $status)
    {
        \think\Db::name('sync_tasks')
            ->where('type', $type)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
}

