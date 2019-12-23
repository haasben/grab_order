<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Hwcpay extends CommonWithdrawal{
      private $resHandler = null;
      private $reqHandler = null;
      private $pay = null;
      private $cfg = null;

//银联支付通道
    public function by_order($back_data,$mch_data,$pay_type){
      
      	$get_data = $this->get_data;
        $order_id = $this->set_order_id($back_data['accept_time'], $back_data['id']);
        $pay_url = $mch_data['pay_url'] . '/pay/gateway';
        $pay_data = array(
            "service" => $pay_type,
            "mch_id" => $mch_data['mch_id'],
          	'sign_type'=>'MD5',
            "out_trade_no" => $order_id,
            "body" => "商品订单",
            "total_fee" => $get_data['pay_amount'],
            "mch_create_ip" => getRealIp(),
            "notify_url" => THIS_URL . "/api/Notify/hwc_notify_url.html",
            "nonce_str" => mt_rand(time(), time() + rand(1000, 9999)),
        );
        $pay_data['sign'] = $this->get_sign_str($pay_data, $mch_data['private_key']);
        $pay_data = toXml($pay_data);
        $xml = $this->send_query_post_curl($pay_url,$pay_data);// xml格式
        $data = xml_to_array($xml);

		 if ($data['status'] == 0 && $data['result_code'] == 0) {
            $ret['code'] = '0000';
            $ret['info'] = '创建订单成功';
            $ret['pay_amount'] = $get_data['pay_amount'];
            $mch_id_arr = explode('_',$get_data['mch_id']);  
            $id = $mch_id_arr[1];
            $sql_data  = Db::table('users')->field('merchant_cname,key')->where('id',$id)->find();
            $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$id);
            $url = $data['code_url'];
           	//$url = 'https://ds.alipay.com/?from=mobilecodec&scheme='.urlencode('alipays://platformapi/startapp?appId=10000007&qrcode='.$url);
            if($get_data['pay_method'] == 'cash'){
                //$url = create_code($url);
                Db::table('mch_order')->where('uid',$id)->where('order_num',$get_data['order_num'])->update(['img_url'=>$url]);
                $url = THIS_URL.'/cash_index/order_id/'.$order_id.'.html';
              	

            }
           //header("Location: $url");die;
            $ret['data'] = $url;
            $ret['sign'] = $this->mch_sign_str($ret,$key);
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);exit;
        } else {
            $ret['code'] = '10001';
            $ret['info'] = '创建订单失败';
            exit(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $ret['code'] = '10001';
        $ret['info'] = '创建订单失败';
        exit(json_encode($ret, JSON_UNESCAPED_UNICODE));	


   }
  
  
  
//银联支付通道 
      public function hwc_notify_url()
    {
        $xml = file_get_contents("php://input");
        // Db::table('linshi')->insert(['info1'=>1,'info2'=>json_encode($xml)]);
        libxml_disable_entity_loader(true);
        $post_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
       // Db::table('linshi')->insert(['info1'=>2,'info2'=>json_encode($post_data)]);
        $order_id = $this->get_order_id($post_data['out_trade_no']);
        $this->notify_child_account($order_id);
        $sign = $this->get_sign_str($post_data, $this->private_key);
        if ($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //平台订单号
            //$transaction_id = $post_data['out_trade_no'];
            if ($post_data['status'] == 0 && $post_data['result_code'] == 0) {
                //支付宝交易记录账单详情中的订单号
                //$out_transaction_id = $post_data['out_transaction_id'];
                //支付宝交易记录账单详情中的商家订单号
                //$third_order_no = $post_data['third_order_no'];
                //商户订单号
                $trade_no = $post_data['transaction_id'];
                //交易状态
                $trade_status = $post_data['pay_result'];

                $pay_time = strtotime($post_data['time_end']);
                //付款时间

                $total_amount = $post_data['total_fee'];
                //订单金额

                if ($trade_status == 0) {
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序
                    //注意：
                    //付款完成后，支付宝系统发送该交易状态通知

                    $order_data = Db::table('mch_order')->field('id,uid,pay_amount,pay_status,trade_no,pay_type,tcid,type,is_proxy,proxy_id')->where('id', $order_id)->find();
                    if (!isset($order_data)) {
                        $this->linshi_spl('ID不存在 ' . $order_id, date('Y-m-d H:i:s'));
                        // send_code(get_admin_phone(),'pay31');
                        echo 'FAIL1';
                        die;
                    }
                    if ((string)$order_data['pay_amount'] != (string)$total_amount) {
                        $this->linshi_spl('金额不相等 ' . $order_id, date('Y-m-d H:i:s') . '数据库金额：' . $order_data['pay_amount'] . ' 收到金额:' . $total_amount);
                        // send_code(get_admin_phone(),'pay32');
                        echo 'FAIL2';
                        die;
                    }
                    $channel_fee = Db::table('top_child_account')->where('id', $order_data['tcid'])->value('fee');
                    //该通道手续费率

                    $this_channel_fee = round($order_data['pay_amount'] * $channel_fee);


                    $received = $order_data['pay_amount'] - $this_channel_fee;
                    // 收款账户实际收到的金额（减去上级收取的手续费后）
                    $notify_update = $this->notify_update($order_data, $received, $trade_no, $pay_time);
                    if ($notify_update) {
                        return $order_id;

                    } else {
                        $this->linshi_spl(date('Y-m-d H:i:s') . '写入数据失败', '写入数据失败');
                        echo 'FAIL3';
                        die;
                    }
                    // echo "success";      //请不要修改或删除
                } else {
                    //验证失败
                    echo "fail1";    //请不要修改或删除
                    die;
                }
            } else {
                echo 'fail2';
                die;
            }
        } else {
            echo 'failure1';
            die;
        }
    }
	
     public  function send_query_post_curl($url,$data = array()){
      $ch = curl_init();
      //设置选项，包括URL
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
      curl_setopt($ch,CURLOPT_TIMEOUT,5);
      // POST数据
      curl_setopt($ch, CURLOPT_POST, 1);
      // 把post的变量加上
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

      //执行并获取url地址的内容
      $output = curl_exec($ch);
      //释放curl句柄
      curl_close($ch);
      return $output;
  }
	
  function get_sign_str($data, $key)
{
    if (isset($data['sign'])) {
        unset($data['sign']);
    }
    ksort($data);
    $sign_str = '';
    foreach ($data as $k => $v) {
        $sign_str .= $k . '=' . $v . '&';
    }
    $sign_str .= "key=" . $key;
    $sign_str = strtoupper(md5($sign_str));
    return $sign_str;
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