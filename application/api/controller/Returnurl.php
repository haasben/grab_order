<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
class Returnurl extends ApiDemo{
    public function PayRzzf2(){
        $back_data = input();

        $order_id = $this->get_order_id($back_data['orderId']);

        $order_data = Db::table('mch_order')->field('order_num,return_url')->where('id',$order_id)->find();
        
        $back_url = $order_data['return_url'].'?order_num='.$order_data['order_num'];

        Header("Location: $back_url"); 
    }

    public function PayWyzf4(){

        $order_id = $this->get_order_id($_POST['orderNo']);
        $this->returnurl($order_id);
    }

    public function returnurl($order_id){
    	$order_data = Db::table('mch_order')->field('order_num,return_url')->where('id',$order_id)->find();
        
        $back_url = $order_data['return_url'].'?order_num='.$order_data['order_num'];

        Header("Location: $back_url"); 
    }

}