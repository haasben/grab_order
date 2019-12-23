<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
class WithdrawalNotify extends ApiDemo{

    public function pay_alipay(){
        $bank_data = $_POST;
        
        $this->ceshi($bank_data);

        $PayWyzf4 = Model('PayAlipay4');
        
        $notify_info = $PayWyzf4->withdrawal_notify();

        $notify_data = $this->notify_under($notify_info['order_id'],$notify_info);
        
        if ($notify_data=='success') {
            echo "success";
        }else{
            echo 'FAIL';
        }
    }
  	
   //代付回调 
      public function sq_with_notify(){

        $bank_data = input();
        $this->ceshi($bank_data);
        

        $PayWyzf4 = Model('Sywgdf');
        
        $notify_info = $PayWyzf4->sq_with_notify();

        $notify_data = $this->notify_under($notify_info['order_id'],$notify_info);

        if ($notify_data=='success') {
            echo "success";
        }else{
            echo 'FAIL';
        }
    }
  
  
  

    public function ceshi($bank_data){
        $url_str = '';
        foreach($bank_data as $key => $value) {
            $url_str .= $key .'=' . $value . '&';
        }

        $this->linshi_spl('接收参数 '.date('Y-m-d H:i:s'),$url_str);
    }

    protected function notify_under($order_id,$notify_info=null){
        // 向下通知
        
        $order_data = Db::table('mch_order')->field('uid,order_num,pay_amount,pay_time,notify_url,pay_status,trade_no')->where('id',$order_id)->where('order_type',2)->find();

        if ($order_data['notify_url']==null) {
            return 'success';
        }
        

        $key_data  = Db::table('users')->field('id,merchant_cname,key')->where('id',$order_data['uid'])->find();

        $key = encryption($key_data['key'].$key_data['merchant_cname'].$key_data['id']);

        $return_data = [
            'mch_id'=>$key_data['merchant_cname'].'_'.$key_data['id'],

            'order_num'=>$order_data['order_num'],
            //下级商户订单号
            
            'pay_time'=>$order_data['pay_time'],
            //支付时间

            'code'=>$notify_info['code'],

            'info'=>$notify_info['info'],

            'pay_status'=>'success',
        ];


        $return_data['sign'] = $this->get_sign_str($return_data,$key);

        Db::table('mch_order')->where('id',$order_id)->setInc('notice_num');

        $notify_data = $this->curl_callServerByPost($order_data['notify_url'],$return_data);
        
        if ($notify_data=='success') {
            //回调完成

            Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>1]);
        }elseif ($notify_data=='fail') {

            Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>2]);
        }else{

            Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>3]);

            Db::table('linshi')->insert(['info1'=>'ID: '.$order_id.' 商户回调错误 '.date('Y-m-d H:i:s'),'info2'=>'回调信息:'.$notify_data]);
        }

        return $notify_data;

    }

    public function callServerByPost($url, $data){
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
    public function curl_callServerByPost($url, $post_data,$this_url=null){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //设置为POST
        curl_setopt($ch, CURLOPT_POST, 1);
        //把POST的变量加上

        if ($this_url!=null) {
            // curl_setopt($ch, CURLOPT_REFERER,$_SERVER['HTTP_REFERER']);
            //伪造HTTP_REFERER
            curl_setopt($ch, CURLOPT_REFERER,'http://www.ecomepay.com/sadmin/Recharge/index.html');
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    public function repeat_notify_url(){
        if (!request()->isAjax()) {
            echo 3;
            die;
        }

        $id = input('get.id');

        return $this->notify_under($id);
    }
}