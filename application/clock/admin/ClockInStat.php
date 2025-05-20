<?php
namespace app\clock\admin;
use app\admin\controller\Admin;
use app\clock\model\Member as MemberModel;
use app\common\builder\ZBuilder;
use app\clock\model\ClockInStat as ClockInStatModel;
use app\clock\model\Project as ProjectModel;
use app\clock\model\Group as GroupModel;
use app\clock\model\Team as TeamModel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;



class ClockInStat extends Admin
{

    public function index()
    {
        $map = $this->getMap();
        if(session('user_auth.role') == 2){
            $map[] = ['clock_project.project_manager','=',NICKNAME];
            $data_list = ClockInStatModel::getClockInStat($map);
            $list_project=ProjectModel::where('project_manager',NICKNAME)->column('pid,project_name');
        }else{
            $data_list = ClockInStatModel::getClockInStat($map);
            $list_project=ProjectModel::column('pid,project_name');
        }


        $btn = [
            'title' => '导出记录',
            'icon'  => 'fa fa-fw fa-level-down',
            'class' => 'btn btn-primary',
            'href'  => 'report'.'?'.http_build_query($this->request->param())

        ];
        //班组列表
        $team_list = [];
        $map_data = array_column($map,null,0);
        if (!empty($map_data['clock_in_stat.project_id'])) {
            $project_id = $map_data['clock_in_stat.project_id'][2];
            $team_list = GroupModel::where('project_id','=',$project_id)->column('id,name');
        }
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('打卡记录')
            ->setSearch(['member_name' => '民工姓名']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['member_name','打卡民工'],
                ['project_name', '项目'],
                ['project_manager', '项目经理'],
                ['name', '班组'],
                ['station', '工种'],
                ['on_time', '上班打卡时间','datetime'],
                ['off_time', '下班打卡时间','datetime'],
                ['pic_on', '上班打卡照片','img_url'],
                ['pic_off', '下班打卡照片','img_url'],
                ['on_location', '上班打卡定位'],
                ['off_location', '下班打卡定位'],
            ])
            ->setColumnWidth(['project_name'=>220,'on_location'=>190,'off_location'=>190,'on_time'=>150,'off_time'=>150])
            ->addTopSelect('clock_in_stat.project_id', '项目列表', $list_project)
            ->addTopSelect('clock_in_stat.group_id', '班组列表', $team_list)
            ->addTopSelect('clock_member.status', '进场状态',[0 => '班组审核中', 1 =>'驳回', 2 =>'项目经理确认中',3 => '进场中', 4 => '已退场'])
            ->addTimeFilter('clock_in_stat.clock_date')
            ->addTopButton('report',$btn)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }


    public function report(){
        $map= $this->getMap();
        if (empty($map)) $this->error('请选择要导出的项目和时间区间');
        if(!$this->getConditions($map,'clock_in_stat.project_id') ) $this->error('请选择要导出的项目');
        if(!$this->getConditions($map,'clock_in_stat.clock_date') ) $this->error('请选择要导出的时间区间');
        if(!$this->getConditions($map,'clock_in_stat.group_id') ) $this->error('请选择要导出的班组');
        $arr =$this->request->param();
        $start_date = $arr['_filter_time_from'];
        $end_date = $arr['_filter_time_to'];
        $sdateTimestamp = strtotime($start_date);
        $edateTimestamp = strtotime($end_date) + 3600 * 24;
        $diffInSeconds = abs($edateTimestamp-$sdateTimestamp);
        $diffInDays = floor($diffInSeconds / (60 * 60 * 24));
        if($diffInDays>31) $this->error('为减轻服务器压力，时间区间不能超过31天');
        // 获取参数
        $select_fields = explode('|',$arr['_select_field']);
        $select_values = explode('|',$arr['_select_value']);
        $project_id_key = array_search('clock_in_stat.project_id',$select_fields);
        $group_id_key = array_search('clock_in_stat.group_id',$select_fields);
        $projectName=ProjectModel::where('pid',$select_values[$project_id_key])->value('project_name');
        $groupName=TeamModel::where('id',$select_values[$group_id_key])->value('name');

        //打卡记录按照日期分组
        $records = ClockInStatModel::getClockInStatNoPage($map);
        $date_record = [];
        foreach ($records as $record) {
            $member_id = $record['member_id'];
            $recordDate = date('Y-m-d',$record['clock_date']);
            $date_record[$member_id][$recordDate] = $record;
        }
        $days = ($edateTimestamp - $sdateTimestamp) / 86400;
        // 汇总信息
        $summary = [
            'project_name' => $projectName,
            'title' => '                  杭州市建筑施工现场务工人员考勤表'.$start_date.'~'.$end_date,
            'signText' => '项目负责人：                                    班组负责人：                                  考勤员：                               制表日期：',
            'group_name' => $groupName,
            'company' => "杭州聚友建筑劳务有限公司",
            'days' => $days
            //统计天数
        ];
        //导出数据处理 班组打卡人员同班组入场人员数据合并
        //获取班组进场人员
        $members = MemberModel::where('status','=',3)
            ->where('project_id','=',$select_values[$project_id_key])
            ->where('group_id','=',$select_values[$group_id_key])
            ->column('member','id');
        //打卡人员
        $clockMembers = array_column($records,'member_name','member_id');
        //所有成员
        $allMembers = $members + $clockMembers;
        //获取成员信息
        $allMembersInfo = MemberModel::field('id,member,mobile')->where('id','in',array_keys($allMembers))
            ->order('id asc')
            ->select()
            ->toArray();

        //按照日期处理打卡数据
        //打卡数据处理
        $dateArr = $dateTitle = [];//title
        $data = [];
        for ($i = 1; $i <= $summary['days']; $i++) {
            $dateArr[] = date('Y-m-d', strtotime($start_date) + ($i-1) * 86400);;
            $dateTitle[] =  date('m-d', strtotime($start_date) + ($i-1) * 86400);;
        }
        foreach ($allMembersInfo as $k => $memberInfo) {
            $row = [
                'number' => $k + 1,
//                'member_name' => $memberInfo['member']."({$memberInfo['mobile']})",
                'member_name' => $memberInfo['member'],
            ];
            foreach ($dateArr as $date) {
                $clockData = $date_record[$memberInfo['id']][$date] ?? [];
                if ($clockData) {
                    $on_time = $clockData['on_time'] ? '上班：'.date('H:i:s',$clockData['on_time']) : '';
                    $off_time = $clockData['off_time'] ? '下班：'.date('H:i:s',$clockData['off_time']) : '';
                    $row[$date] = !empty($on_time) && !empty($off_time) ? $on_time."\n".$off_time : $on_time.$off_time;
                } else {
                    $row[$date] = '';
                }
            }
            $row['days'] = count(array_filter($row)) - 2;
            $data[] = $row;
        }
        // 创建一个新的 Spreadsheet 对象
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置三行表头
        //获取列数设置标题
        $columnCount  = 3 + count($dateArr);
        $lastColumn = $this->numberToExcelColumn($columnCount);
        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $style = $sheet->getStyle('A1');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('A1', $summary['title']);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->mergeCells('A2:'.$lastColumn.'2');
        $style = $sheet->getStyle('A2');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->setCellValue('A2', '单位名称：'.$summary['company']."              ".'项目部名称：'.$summary['project_name'] ."         ".'班组：'.$summary['group_name']);
        // 应用字体和填充样式到 A1 到 A3
        for ($i = 1; $i <= 2; $i++) {
            $cellRange = "A{$i}";
            if ($i == 1) {
                $size = 18;
            } else  {
                $size = 12;
            }
            $sheet->getStyle($cellRange)->applyFromArray([
                'font' => [
                    'name'=> '宋体',
                    'bold' => true,
                    'size' => $size,
                ]
            ]);
        }
        // 设置列标题
        $columnTitles = ['序号', '名称|日期'];
        $columnTitles = array_merge($columnTitles, $dateTitle,['小计']);
        // 将列标题写入 Excel
        $columnIndex = 1;
        foreach ($columnTitles as $title) {
            $sheet->getRowDimension(3)->setRowHeight(20);
            $sheet->setCellValueByColumnAndRow($columnIndex, 3, $title);
            $column = $this->numberToExcelColumn($columnIndex);
            $sheet->getColumnDimension($column)->setWidth(16);
            $style = $sheet->getStyle("{$column}3");
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $columnIndex++;
        }
        // 填充数据
        $rowIndex = 4; // 从第四行开始填充数据
        foreach ($data as $rowData) {
            $sheet->getRowDimension($rowIndex)->setRowHeight(40);
            $columnIndex = 1;
            $this->setCellForm($sheet,$columnIndex,$rowIndex);
            $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $rowData['number']);
            $this->setCellForm($sheet,$columnIndex,$rowIndex);
            $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $rowData['member_name']);
            // 写入考勤明细
            foreach ($rowData as $key => $value) {
                if (!in_array($key,['number','member_name']) && !empty($value)) { // 检查键是否为数字，且为非负数
                    $this->setCellForm($sheet,$columnIndex,$rowIndex);
                    $column = $this->numberToExcelColumn($columnIndex);
                    $this->setCellForm($sheet,$columnIndex,$rowIndex);
                    $sheet->getStyle($column.$rowIndex)->getAlignment()->setWrapText(true);
                    $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
                    if (($key != 'days') && !empty($value) && (strpos($value,'上班') === false || strpos($value,'下班') == false)) {
                        $sheet->getStyle($column.$rowIndex)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)       // 填充类型：纯色
                            ->getStartColor()->setARGB(Color::COLOR_RED); // 颜色值
                    }
                    $columnIndex++;
                }
            }
            $rowIndex++;
        }
        //最后一行签名处理
        $sheet->mergeCells("A{$rowIndex}:$lastColumn{$rowIndex}");
        $sheet->getStyle("A{$rowIndex}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_TEXT);
        // 显式写入内容（使用 setCellValueExplicit 强制文本类型）
        $sheet->setCellValueExplicit("A{$rowIndex}", $summary['signText'],DataType::TYPE_STRING);

        // 设置响应头
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename= attendance_report.xlsx');
        header('Cache-Control: max-age=0');
        // 保存并输出 Excel 文件
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }



    private function setCellForm(&$sheet,$columnIndex,$rowIndex) {
        $column = $this->numberToExcelColumn($columnIndex);
        $style = $sheet->getStyle("{$column}$rowIndex");
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    /**
     * excel数字转列
     * @param $number
     * @return string
     * User: zheng
     * Date: 2025/4/23 16:42\
     */
    private function numberToExcelColumn($number) {
        $column = '';
        while ($number > 0) {
            $number--; // 因为 Excel 列是从 1 开始的，而我们使用的是从 0 开始的下标
            $mod = $number % 26;
            $column = chr(65 + $mod) . $column;
            $number = intval($number / 26);
        }
        return $column;
    }

    /**
     * excel列转数字
     * @param $column
     * @return float|int
     * User: zheng
     * Date: 2025/4/23 16:41
     */
    private function excelColumnToNumber($column) {
        $columnLength = strlen($column);
        $number = 0;
        for ($i = 0; $i < $columnLength; $i++) {
            $number += (ord($column[$i]) - ord('A') + 1) * pow(26, $columnLength - $i - 1);
        }
        return $number;
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