<?php
namespace app\sadmin\controller;

/**
*PHP是世界上最好的语言
*@param 上传文件控制器
**/
class Upfiles extends UserCommon
{
    public function _initialize()
    {
        
        parent::_initialize();
    }


    public function upload(){
    // 获取表单上传文件 例如上传了001.jpg
     $fileKey = array_keys(request()->file());
        // 获取表单上传文件 例如上传了001.jpg
     $file = request()->file($fileKey['0']);
    // 移动到框架应用根目录/public/uploads/ 目录下
    $info = $file->validate(['ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'code'.DS.'solid');
      
      
    if($info){

        $info_name = '/public' . DS . 'code'.DS.'solid'.DS.$info->getSaveName();
        
        $src = '/code/solid'.DS.$info->getSaveName();
        
        $data = json_encode(['code'=>'0000','msg'=>'上传成功','src'=>$src]);

    }else{
        // 上传失败获取错误信息
        $data = json_encode(['code'=>'60003','msg'=>$file->getError()]);
    }
    echo $data;
}



    
}