<?php
namespace app\push\controller;

use think\Controller;
use think\Db;
use GatewayClient\Gateway;
class Send extends Controller
{


    // public function _initialize(){
        
    //     Gateway::$registerAddress = '127.0.0.1:1238';

    // }


//添加定时器
    public function add_timer(){



        $data = input();

        $this->assign('data',$data);

        return $this->fetch();




        

    }



}
