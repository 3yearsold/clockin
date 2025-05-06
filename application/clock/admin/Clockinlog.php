<?php
namespace app\clock\admin;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\clock\model\Clockin as ClockinModel;
use app\clock\model\Project as ProjectModel;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class Clockinlog extends Admin
{

    public function index()
    {
        $map = $this->getMap();

        if(session('user_auth.role') == 2){
            $map[] = ['clock_project.project_manager','=',NICKNAME];
            $data_list = ClockinModel::getClockInLog($map);
            $list_project=ProjectModel::where('project_manager',NICKNAME)->column('pid,project_name');
        }else{
            $data_list = ClockinModel::getClockInLog($map);
            $list_project=ProjectModel::column('pid,project_name');          }


        $btn = [
            'title' => '导出记录',
            'icon'  => 'fa fa-fw fa-level-down',
            'class' => 'btn btn-primary',
            'href'  => url('report'.'?'.http_build_query($this->request->param()))
        ];


        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('打卡记录')
            ->setSearch(['member_name' => '民工姓名']) // 设置搜索框
            ->addFilter('clock_project.project_manager')//筛选
            ->addColumns([ // 批量添加数据列
                ['project_name', '项目'],
                ['project_manager', '项目经理'],
                ['name', '班组'],
                ['member_name','打卡民工'],
                ['station', '工种'],
                ['location', '打卡定位'],
                ['pic', '打卡照片','img_url'],
                ['create_time', '打卡时间']
            ])
            ->setColumnWidth(['project_name'=>220,'location'=>150])
            ->addTopSelect('clock_in.project_id', '项目列表', $list_project)
            ->addTimeFilter('clock_in.create_time')
            ->addTopButton('report',$btn)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板



    }


    public function report(){
        $map= $this->getMap();
        if (empty($map)) $this->error('请选择要导出的项目和时间区间');
        if(!$this->getConditions($map,'clock_in.project_id') ) $this->error('请选择要导出的项目');
        if(!$this->getConditions($map,'clock_in.create_time') ) $this->error('请选择要导出的时间区间');
        // 获取参数

        $arr =$this->request->param();
        $project_id = $arr['_select_value'];
        $projectName=ProjectModel::where('pid',$project_id)->value('project_name');
        $sdate = $arr['_filter_time_from'];
        $edate = $arr['_filter_time_to'];

        // 转换日期为时间戳
        $sdateTimestamp = strtotime($sdate);
        $edateTimestamp = strtotime($edate.'23:59:59');

        $diffInSeconds = abs($edateTimestamp-$sdateTimestamp);
        $diffInDays = floor($diffInSeconds / (60 * 60 * 24));
        if($diffInDays>31) $this->error('为减轻服务器压力，时间区间不能超过31天');

        //dump($project_id);
        //dump($projectName);
        //dump($sdate);
        // dump($edate);

        $records = Db::query("
            SELECT
                a.member_id,
                a.member_name,
                c.station,
                b.name,
                GROUP_CONCAT(DATE_FORMAT(FROM_UNIXTIME(a.create_time), '%Y-%m-%d')) AS crt_dates
            FROM
                (SELECT member_id,member_name, create_time FROM dp_clock_in WHERE project_id = $project_id AND create_time BETWEEN $sdateTimestamp AND $edateTimestamp) a
            JOIN
                dp_clock_member c ON a.member_id = c.id
            JOIN dp_clock_group b on c.group_id = b.id
            GROUP BY
                a.member_id, a.member_name, c.station, b.name
        ");

        //dump($records);die;

        // 汇总信息
        $days = ($edateTimestamp - $sdateTimestamp) / 86400 + 1;
        $summary = [
            'date_range' => $sdate . '至' . $edate,
            'report_time' => date('Y-m-d H:i:s'),
            'project_name' => $projectName,
            'days' => $days
        ];

        $data= [];
        foreach($records as $record){
            $crt_dates = explode(',',$record['crt_dates']);
            $attendance = $this->getClock($sdateTimestamp, $edateTimestamp, $crt_dates);
            $data[] = [
                    'member_name' => $record['member_name'],
                    'station' => $record['station'],
                    'name' => $record['name'],
                    'clock_count' =>  count($crt_dates),
                ] +  array_combine(range(0, $summary['days'] - 1), $attendance);

        }


        // 创建一个新的 Spreadsheet 对象
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置三行表头
        $sheet->setCellValue('A1', "考勤汇总 统计日期：{$summary['date_range']}");
        $sheet->setCellValue('A2', "报表生成时间：{$summary['report_time']}");
        $sheet->setCellValue('A3', "项目名称：{$summary['project_name']}");

        // 应用字体和填充样式到 A1 到 A4
        for ($i = 1; $i <= 3; $i++) {
            $cellRange = "A{$i}";
            $sheet->getStyle($cellRange)->applyFromArray([
                'font' => [
                    'name'=> '微软雅黑',
                    'bold' => true,
                    'color' => ['argb' => 'FF5083EA'],
                    'size' => 12,
                ]
            ]);
        }

        // 设置列标题
        $columnTitles = ['姓名', '班组', '岗位', '出勤天数'];
        $dateTitles = [];
        for ($i = 0; $i < $summary['days']-1; $i++) {
            $dateTitles[] = date('m-d', strtotime($sdate) + $i * 86400);
        }
        $columnTitles = array_merge($columnTitles, $dateTitles);
        // dump($data);
        //dump($dateTitles);die;

        // 将列标题写入 Excel
        $columnIndex = 1;
        foreach ($columnTitles as $title) {
            $sheet->setCellValueByColumnAndRow($columnIndex, 4, $title);
            $columnIndex++;
        }

        // 填充数据
        $rowIndex = 5; // 从第五行开始填充数据
        foreach ($data as $rowData) {
            $columnIndex = 1;
            $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $rowData['member_name']);
            $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $rowData['name']);
            $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $rowData['station']);
            $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $rowData['clock_count']);

            // 写入考勤明细
            foreach ($rowData as $key => $value) {
                if (is_numeric($key) && $key >= 0) { // 检查键是否为数字，且为非负数
                    $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $value);
                }
            }

            $rowIndex++;
        }

        // 设置响应头
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename= attendance_report.xlsx');
        header('Cache-Control: max-age=0');

        // 保存并输出 Excel 文件
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();





        //dump($cellName);die;


//以下代码可以把结果渲染成网页
//            $data[] = [
//                'member_id' => $record['member_id'],
//                'member_name' => $record['member_name'],
//                'station' => $record['station'],
//                'name' => $record['name'],
//                'clock' => $attendance['clock'],
//                'clock_count' => $attendance['clock_count']
//            ];
//        }
//        // 汇总信息
//
//        $summary = [
//            'date_range' => $sdate.'至'.$edate,
//            'report_time' => date('Y-m-d H:i:s'),
//            'project_name' => $projectName,
//            'start_date' => $sdate,
//            'end_date' => $edate,
//            'days' => ($edateTimestamp - $sdateTimestamp) / 86400
//        ];
//
//        $this->assign('summary', $summary);
//        $this->assign('records', $data);
//        return $this->fetch('clock_log');

    }


    // 获取考勤结果

    private function getClock($sdate, $edate, $crt_dates)
    {
        $s = $sdate;
        $e = $edate;
        $attendance = [];
        for ($i = $s; $i <= $e; $i = strtotime('+1 day', $i)) {

            $date = strval(date('Y-m-d', $i));
            $attendance[] = in_array($date, $crt_dates) ? '正常' : '';
        }
        return $attendance;
    }



    public function getConditions($map,$conditions){
        $containsProjectId = false;
        foreach($map as $condition){
            if($condition[0] === $conditions){
                $containsProjectId = true;
                break;
            }
        }
        return $containsProjectId;

    }






}