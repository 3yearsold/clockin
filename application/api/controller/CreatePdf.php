<?php

namespace app\api\controller;
use app\common\controller\Common;
use app\clock\model\Member as MemberModel;
use setasign\Fpdi\Fpdi;
use think\facade\Hook;


class CreatePdf extends Common
{

    public function createPdf($id){

        $member = MemberModel::getMemberInfo($id);
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->setSourceFile('static/clock/pdf/demo.pdf');

        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId);

        $pdf->AddGBFont('simfang','仿宋');
        $pdf->SetFont('simfang', '', 11);

        $pdf->SetXY(40, 41);
        $pdf->Write(0,  iconv('utf-8',"GBK",$member['member']));

        $pdf->SetXY(85, 41);
        $gender = $member['gender'] == 0 ? '男' : '女';
        $pdf->Write(0,  iconv('utf-8',"GBK",$gender));

        $pdf->SetXY(130, 41);
        $pdf->Write(0,  iconv('utf-8',"GBK",$member['idcard']));

        $pdf->SetXY(52, 67);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('Y',$member['reg_date'])));

        $pdf->SetXY(75, 67);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('m',$member['reg_date'])));

        $pdf->SetXY(90, 67);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('d',$member['reg_date'])));


        $project_name = $member['project_name'];
        $length = mb_strlen($project_name, 'utf-8');

        if ($length <= 20) {
            $pdf->SetXY(43, 83);
            $pdf->Write(0,  iconv('utf-8',"GBK",$project_name));
        }else{
            $pdf->SetXY(43, 77);
            //$pdf->Write(0,  iconv('utf-8',"GBK",$project_name));
            $pdf->MultiCell(72, 4, iconv('utf-8',"GBK",$project_name));
        }

        $pdf->SetXY(130, 83);
        $pdf->Write(0,  iconv('utf-8',"GBK",$member['station']));

        $pdf->SetXY(33, 247);
        $pdf->Write(0,  iconv('utf-8',"GBK",$member['account_name']));

        $pdf->SetXY(115, 247);
        $pdf->Write(0,  iconv('utf-8',"GBK",$member['mobile']));

        $pdf->SetXY(33, 254);
        $pdf->Write(0,  iconv('utf-8',"GBK",$member['account']));

        $pdf->SetXY(120, 254);
        $pdf->Write(0,  iconv('utf-8',"GBK",$member['bank']));

        $pdf->AddPage();
        $tplId = $pdf->importPage(2);
        $pdf->useTemplate($tplId);

        $pdf->SetXY(20, 244);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('Y',$member['create_time'])));
        $pdf->SetXY(38, 244);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('m',$member['create_time'])));
        $pdf->SetXY(53, 244);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('d',$member['create_time'])));


        $pdf->SetXY(147, 230);
        $pdf->Write(0,  iconv('utf-8',"GBK",$member['member']));
        $pdf->SetXY(120, 244);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('Y',$member['create_time'])));
        $pdf->SetXY(138, 244);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('m',$member['create_time'])));
        $pdf->SetXY(152, 244);
        $pdf->Write(0,  iconv('utf-8',"GBK",date('d',$member['create_time'])));

        $key = $member['project_id'].'_'.strval($member['group_id']).'_'.strval($id).'.pdf';




        // 附件上传钩子，用于第三方文件上传扩展
//        if (config('upload_driver') != 'local') {
//            $file_path = config('upload_path') . DIRECTORY_SEPARATOR .'temp'.DIRECTORY_SEPARATOR.date('Ymd').DIRECTORY_SEPARATOR ;
//            if(! is_dir($file_path)){
//                mkdir($file_path,0755,true);
//            }
//            $pdf->Output('F',$file_path.$key); // 'F' 表示保存到文件
//            $hook_result = Hook::listen('upload_attachment_url', ['file_path' => $file_path.$key, 'key' => $key]);
//            //dump($hook_result);
//            if (false !== $hook_result) {
//                return $hook_result;
//            }
//        }


        $file_path = config('upload_path') . DIRECTORY_SEPARATOR .'pdf'.DIRECTORY_SEPARATOR.date('Ymd').DIRECTORY_SEPARATOR;
        if(! is_dir($file_path)){
            mkdir($file_path,0755,true);  }

        $pdf->Output('F',$file_path.$key); // 'F' 表示保存到文件
        $url = "https://".$_SERVER['HTTP_HOST']."/uploads/pdf/".date('Ymd')."/".$key;
        MemberModel::where(['id'=>$id])->update(['sign_pdf'=>$url]);




}


}