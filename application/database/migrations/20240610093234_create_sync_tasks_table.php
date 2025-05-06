<?php
use think\migration\db\Table;
use think\migration\Migration;

class CreateSyncTasksTable extends Migration
{
    public function up()
    {
        $table = $this->table('sync_tasks', ['comment' => '同步任务表']);
        $table
            ->addColumn('type', 'string', ['limit' => 20, 'comment' => '任务类型 department/member/attendance'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 0, 'comment' => '0待处理 1执行中 2成功 3失败'])
            ->addColumn('retry_count', 'integer', ['default' => 0, 'comment' => '重试次数'])
            ->addColumn('last_run_at', 'datetime', ['null' => true, 'comment' => '最后执行时间'])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->create();
    }

    public function down()
    {
        $this->dropTable('sync_tasks');
    }
}