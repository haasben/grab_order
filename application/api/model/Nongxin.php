<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Nongxin extends CommonWithdrawal{


//农信自动产码通道
    public function by_order($back_data,$mch_data,$pay_bankcode){
      

      $get_data = $this->get_data;
      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);
      $pay_url = $mch_data['pay_url'];

      $pay_data = array(
        "merchantid" => $mch_data['mch_id'],
        "orderid" => $order_id,
        "amount" => number_format($get_data['pay_amount']/100,2),
        "paytype" =>$pay_bankcode,
        "client_ip" => $_SERVER['SERVER_ADDR'],
        "notify_url" => THIS_URL.'/api/Notify/nongxing_notify_url.html',
        "return_url" => THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',
    );

      $pay_data['sign'] = $this->yx_sign($pay_data,$mch_data['private_key']);
      $data = $this->skzf_curl_post($pay_url,$pay_data);

      $data = json_decode($data,true);
      
      if($data['code'] == '0'){
          $ret['code'] = '0000';
          $ret['info'] = '创建订单成功';
          $ret['pay_amount'] = $get_data['pay_amount'];
          $ret['data'] = $data['data']['qrcode'];
          $mch_id_arr = explode('_',$get_data['mch_id']);  
          $id = $mch_id_arr[1];
          $sql_data  = Db::table('users')->field('merchant_cname,key')->where('id',$id)->find();
          $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$id);
          
          $ret['sign'] = $this->mch_sign_str($pay_data,$key);
      }else{

          $ret['code'] = '1111';
          $ret['info'] = '创建订单失败，请稍后再试';
      }
      
      echo json_encode($ret,JSON_UNESCAPED_UNICODE);

   }
  
  
  
  
//农信自动产码回调  
      public function nongxing_notify_url(){

        $post_data = input();
        $order_id = $this->get_order_id($post_data['out_order_id']);

        $this->notify_child_account($order_id);
  
        $sign = $this->yx_sign($post_data,$this->private_key);
    
        if($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号

            //支付宝交易号/支付宝交易凭证号
            $trade_no = '0000';

            //交易状态
            $trade_status = '00';

            $pay_time = time();
            //付款时间
            
            $total_amount = $post_data['money']*100;
            //订单金额
           
            if ($trade_status == '00') {
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
  	
//curl请求
     public static function skzf_curl_post($url, $data_string) {
      
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
  
  
//签名验签方法
     public function yx_sign($data,$secret=''){
          if(isset($data['sign'])){
              unset($data['sign']);
          }
          $data = $this->removeEmpty($data);
          ksort($data);
          $str = '';
          foreach ($data as $k => $v) {
              if ($str) {
                  $str = $str . $k . $v;
              } else {
                  $str = $k . $v;
              }
          }
          return md5($str . $secret);
        }
        /**
        * 移除空值的key
        * @param $para
        * @return array
        * @author helei
        */
      public function removeEmpty($para) {
          $paraFilter = [];
          foreach ($para as $key => $val) {
              if ($val === '' || $val === null) {
                  continue;
              } else {
                  if (!is_array($val)) {
                      $para[$key] = is_bool($val) ? $val : trim($val);
                  }
                  $paraFilter[$key] = $val;
              }
          }
          return $paraFilter;
        }


  //商户自己的机密方式
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