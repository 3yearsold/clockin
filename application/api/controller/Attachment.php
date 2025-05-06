<?php

namespace app\api\controller;
use app\common\controller\Common;
use think\File;
use think\Image;

class Attachment  extends Common
{
    public function upload(){
        $file =$this->request->file('file');
        if($file === null){
            return res([], 2, 'upload file is empty');
        }
        $type = $file->getMime();
        if(strpos($type, 'image/') === 0){
            $dir = 'images';
        }else{
            $dir = 'files';
        }
        // 附件大小限制
        $size_limit = $dir == 'images' ? config('upload_image_size') : config('upload_file_size');
        $size_limit = $size_limit * 1024;
        // 附件类型限制
        $ext_limit = $dir == 'images' ? config('upload_image_ext') : config('upload_file_ext');
        $ext_limit = $ext_limit != '' ? parse_attr($ext_limit) : '';

        // 判断附件大小是否超过限制
        if ($size_limit > 0 && ($file->getInfo('size') > $size_limit)) {
            return res([], 3, 'upload file size is too large');
        }
        // 判断附件格式是否符合
        $file_name = $file->getInfo('name');
        $file_ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
        $error_msg = '';
        if ($ext_limit == '') {
            $error_msg = 'upload file is not allowed';
        }
        if ($file->getMime() == 'text/x-php' || $file->getMime() == 'text/html') {
            $error_msg = 'upload html file is not allowed';
        }
        if (preg_grep("/php/i", $ext_limit)) {
            $error_msg = 'upload php file is not allowed';
        }
        if (!preg_grep("/$file_ext/i", $ext_limit)) {
            $error_msg = 'upload illegal file type';
        }

        if ($error_msg != '') {
            // 上传错误
            return res([], 2,$error_msg);
        }
        $info = $file->move(config('upload_path') . DIRECTORY_SEPARATOR . $dir);
        if ($info) {
            // 缩略图路径
            $thumb_path_name = '';
            // 图片宽度
            if ($dir == 'images') {
                // 生成缩略图需要在后台配置
                if (config('upload_image_thumb') != '') {
                    $thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename());
                }
            }
        }
        if (!empty($thumb_path_name)) {
            $path = 'https://' . $_SERVER['HTTP_HOST'] . '/'.$thumb_path_name;
        } else {
            $path = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $dir . '/' . str_replace('\\', '/', $info->getSaveName());
        }
        return res(['path' => $path]);
    }

    /**
     * 创建缩略图
     * @param string $file 目标文件，可以是文件对象或文件路径
     * @param string $dir 保存目录，即目标文件所在的目录名
     * @param string $save_name 缩略图名
     * @param string $thumb_size 尺寸
     * @param string $thumb_type 裁剪类型
     * @author 蔡伟明 <314013107@qq.com>
     * @return string 缩略图路径
     */
    private function create_thumb($file = '', $dir = '', $save_name = '', $thumb_size = '', $thumb_type = '') {
        // 获取要生成的缩略图最大宽度和高度
        $thumb_size = $thumb_size == '' ? config('upload_image_thumb') : $thumb_size;
        list($thumb_max_width, $thumb_max_height) = explode(',', $thumb_size);
        // 读取图片
        $image = Image::open($file);
        // 生成缩略图
        $thumb_type = $thumb_type == '' ? config('upload_image_thumb_type') : $thumb_type;
        $image->thumb($thumb_max_width, $thumb_max_height, $thumb_type);
        // 保存缩略图
        $thumb_path = config('upload_path') . DIRECTORY_SEPARATOR . 'images/' . $dir . '/thumb/';
        if (!is_dir($thumb_path)) {
            mkdir($thumb_path, 0766, true);
        }
        $thumb_path_name = $thumb_path . $save_name;
        $image->save($thumb_path_name);
        $thumb_path_name = 'uploads/images/' . $dir . '/thumb/' . $save_name;
        return $thumb_path_name;
    }



}