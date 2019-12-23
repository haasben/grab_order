<?php
namespace app\sadmin\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Valcodeate;
use think\Cache;
class System extends UserCommon{

//系统设置
    public function index(){

        if(request()->isAjax()){

            $data = input();
            $bool = Db::table('system')->update($data);

            return ['code'=>'0000','msg'=>'修改成功'];die;

        }else{
            $system = Db::table('system')->find();

            $this->assign('system',$system);

            return $this->fetch(); 
        }
        
    }



  
}   