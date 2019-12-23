<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Glalipay extends CommonWithdrawal{


//鼎盛支付通道
    public function by_order($back_data,$mch_data,$pay_bankcode){
      

      $get_data = $this->get_data;

      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);

      $pay_url = $mch_data['pay_url'].'/index.php/Api/Index/createOrder';


     header("Content-type: text/html; charset=utf-8"); 

        $data = [
        "orderAmount"=>$get_data['pay_amount']/100, //金额
        "orderId"=>$order_id,//订单号
        "merchant"=>$mch_data['mch_id'], //商户号
        'payMethod'=>'2',
        "payType"=>$pay_bankcode,
        "signType"=>"MD5",
        "version"=>"1.0",
        "outcome"=>"no",
    ];
    $key = $mch_data['private_key']; //key
    ksort($data);
    $postString = http_build_query($data);//返回一个 URL 编码后的字符串。
    $signMyself = strtoupper(md5($postString.$key));
    $data["sign"] = $signMyself;
    $data['productName'] = $order_id;
    $data['productDesc'] = $order_id;
    $data['createTime'] = time();
    $data['returnUrl'] = THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html';
    $data['notifyUrl'] = THIS_URL.'/api/Notify/glalipay_notify_url.html';
    $postString = http_build_query($data);
    $url=$pay_url."?".$postString;
    header('Location:'.$url);


   }
  
  
  
//桂林支付回调  
      public function glalipay_notify_url(){

        $json = file_get_contents('php://input');;
        $post_data = json_decode($json,true);

        $order_id = $this->get_order_id($post_data['paramsJson']['data']['orderId']);

        $this->notify_child_account($order_id);
  
       
        $key = $this->private_key;
        
        $jsonBase64 = base64_encode(json_encode($post_data['paramsJson']));
        $jsonBase64Md5 = md5($jsonBase64);
        $sign = strtoupper(md5($key.$jsonBase64Md5));
    
        if($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号

            //支付宝交易号/支付宝交易凭证号
            $trade_no = $post_data['paramsJson']['data']['outTradeNo'];

            //交易状态
            $trade_status = $post_data['paramsJson']['code'];

            $pay_time = strtotime($post_data['paramsJson']['data']['dateTime']);
            //付款时间
            
            $total_amount = $post_data['paramsJson']['data']['orderAmount']*100;
            //订单金额
           

            if ($trade_status == '000000') {
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
     public static function skzf_sign_str($data,$private_key){
        if(isset($data['pay_md5sign'])) {
            unset($data['pay_md5sign']);
        }elseif(isset($data['sign'])) {
            unset($data['sign']);
        }
          ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str = strtoupper(md5($md5str . "key=" . $private_key));
          return $md5str;
  }

      //上游curl方法
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