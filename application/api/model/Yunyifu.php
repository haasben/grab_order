<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Yunyifu extends CommonWithdrawal{


//云易付通道
    public function by_order($back_data,$mch_data){
      

      $get_data = $this->get_data;

      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);

      $pay_url = $mch_data['pay_url'].'/order/getActivePayQrCode';


      $pay_data = array(
        "uid" => $mch_data['mch_id'],
        "order_id" => $order_id,
        "price" => number_format($get_data['pay_amount']/100,2),
        'type'=>2,
        "notify_url" => THIS_URL.'/api/Notify/yunyifu_notify_url.html',
        "return_url" => THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',
    );

      $pay_data['key'] = md5($pay_data['notify_url'].$pay_data['order_id'].$pay_data['price'].$pay_data['return_url'].$mch_data['private_key'].$pay_data['type'].$pay_data['uid']);
      
      $data = $this->yunyifu_curl_post($pay_url,$pay_data);
      if(strpos($data,'ttps:')){
           $ret['code'] = '0000';
           $ret['info'] = '创建订单成功';
           $ret['pay_amount'] = $get_data['pay_amount'];
           $url = create_code($data,ROOT_PATH.'public/static/alipay.png');
        if($get_data['pay_method'] == 'cash'){
              	$key = 'yunyikey';
                $urlcode = yun_encrypt($url.'###'.$order_id,$key);
                $url = THIS_URL.'/cash_reg/token/'.$urlcode;
            }
      
        $ret['data'] = $url;
        
      }else{
      	   $ret['code'] = '1111';
           $ret['info'] = '订单创建失败，请联系管理员';
      
      }
		echo json_encode($ret,JSON_UNESCAPED_UNICODE);
        exit;

   }
  
  
  
//回调
      public function yunyifu_notify_url(){

        $post_data = input();
        $order_id = $this->get_order_id($post_data['order_id']);

        $this->notify_child_account($order_id);
  
        
        $key=md5($post_data['code'].$post_data['order_id'].$post_data['pay_num'].$post_data['price'].$this->private_key.$post_data['transaction_id']);

        if($key == $post_data['key']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号

            //支付宝交易号/支付宝交易凭证号
            $trade_no = $post_data['transaction_id'];

            //交易状态
            $trade_status = $post_data['code'];

            $pay_time = time();
            //付款时间
            
            $total_amount = $post_data['price']*100;
            //订单金额
           

            if ($trade_status == '0000') {
                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序            
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                $order_data = Db::table('mch_order')->field('id,uid,pay_amount,pay_status,trade_no,pay_type,tcid,type,is_proxy')->where('id',$order_id)->find();

                if (!isset($order_data)) {

                    $this->linshi_spl('ID不存在 '.$order_id,date('Y-m-d H:i:s'));

                    // send_code(get_admin_phone(),'pay31');
                    echo 'FAIL';
                    die;
                }
        
                if((string)$order_data['pay_amount']!=(string)$total_amount){
                    $this->linshi_spl('金额不相等 '.$order_id,date('Y-m-d H:i:s').'数据库金额：'.$order_data['pay_amount'].' 收到金额:'.$total_amount);
                    // send_code(get_admin_phone(),'pay32');
                    echo 'FAIL';
                    die;
                }
                $channel_fee = Db::table('top_child_account')->where('id',$order_data['tcid'])->value('fee');
                //该通道手续费率

                $this_channel_fee = round($order_data['pay_amount']*$channel_fee);


                $received = $order_data['pay_amount'] - $this_channel_fee;

                // 收款账户实际收到的金额（减去上级收取的手续费后）

                $notify_update = $this->notify_update($order_data,$received,$trade_no,$pay_time);

                if ($notify_update) {
                    return $order_id;

                }else{
                    $this->linshi_spl(date('Y-m-d H:i:s').'写入数据失败','写入数据失败');
                    echo 'FAIL';
                    die;
                }
            // echo "success";      //请不要修改或删除
          }else {
              //验证失败
              echo "fail";    //请不要修改或删除
              die;
          }
        }else{
              
            echo 'fail';die;

        }


    }

  
  

  //上游加密方式
     public static function yunyifu_sign_str($data,$private_key){
      if(isset($data['key'])) {
        unset($data['key']);
      }
      ksort($data);
    $md5str = "";
    foreach ($data as $key => $val) {
        $md5str = $md5str . $key . "=" . $val . "&";
    }
    $md5str = md5($md5str . "key=" . $private_key);
      return $md5str;
  }

      //上游curl方法
   public static function yunyifu_curl_post($url, $data_string) {
      
      $data_string = http_build_query($data_string);
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
      $data = curl_exec($ch);
      
      curl_close($ch);
      return $data;
  }
  
    function mch_sign_str($data, $key){
      if(isset($data['sign'])) {
          unset($data['sign']);
      }
      ksort($data);
      $sign_str = '';
      foreach($data as $k => $v) {
          $sign_str .= $k . '='.$v.'&';
      }
      $sign_str = substr($sign_str,0,strlen($sign_str)-1);
      $sign_str = strtoupper(md5( $sign_str."&key=".$key));
      return $sign_str;
  }
  

}