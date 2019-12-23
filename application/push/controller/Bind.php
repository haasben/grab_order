<?php
namespace app\push\controller;

use think\Controller;
use think\Db;
use GatewayClient\Gateway;
class Bind extends Controller
{
     public function bind_user()
    {

        Gateway::$registerAddress = '127.0.0.1:1238';
        // 假设用户已经登录，用户uid和群组id在session中
        $uid = 2;
        $client_id = input('client_id');


        
        // client_id与uid绑定

        // 加入某个群组（可调用多次加入多个群组）
        //$group_id = 1;
        //Gateway::joinGroup($client_id, $group_id);
        Gateway::bindUid($client_id,$uid);
        $_SESSION['uid'] = $uid;

        $return_data = ['code'=>'0000','msg'=>'绑定成功'];

        return $return_data;
        

        
    }
    public function send_message()
    {
        if(request()->isAjax()){
            Gateway::$registerAddress = '127.0.0.1:1238';
            $uid = 1;
            $msg = date('Y-m-d H:i:s',time());

            if(Gateway::isUidOnline($uid) == 0){

                echo json(['code'=>400,'msg'=>'用户已经下线: ']);die;
            }
            $message = ['type'=>'active','msg'=>$msg];
            // 向任意uid的网站页面发送数据
            $send_message = Gateway::sendToUid($uid, json_encode($message,JSON_UNESCAPED_UNICODE));

                return ['code'=>'0000','msg'=>'发送成功: '.$msg];

        }else{
            return $this->fetch();
        }


        // 向任意群组的网站页面发送数据
        //Gateway::sendToGroup($group, $message);
    }


    public function send_room_message()
    {
        if(request()->isAjax()){

            Gateway::$registerAddress = '127.0.0.1:1238';
            $room_id = 1;
            $msg = date('Y-m-d H:i:s',time());

            $message = ['type'=>'active','msg'=>$msg];
            // 向任意uid的网站页面发送数据
            $send_message = Gateway::sendToGroup($room_id, json_encode($message,JSON_UNESCAPED_UNICODE));

            return ['code'=>'0000','msg'=>'发送成功: '.$msg];

        }else{
            return $this->fetch();
        }


        // 向任意群组的网站页面发送数据
        //Gateway::sendToGroup($group, $message);
    }






    


}
