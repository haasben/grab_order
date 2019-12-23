<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Freepayment extends CommonWithdrawal{


//农信免签支付通道
    public function by_order($back_data,$mch_data){
      
	  $get_data = $this->get_data;
      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);
      $mch_id_arr = explode('_',$get_data['mch_id']);
      $uid = $mch_id_arr[1];
      $ret['code'] = '0000';
      $ret['info'] = '创建订单成功';
      $ret['pay_amount'] = $get_data['pay_amount'];
      $ret['data'] = THIS_URL.'/nongxin_order/'.$order_id.'.html';

      $sql_data  = Db::table('users')->field('merchant_cname,key')->where('id',$uid)->find();
      $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$uid);
        
      $ret['sign'] = mch_sign_str($ret,$key);

      echo json_encode($ret,JSON_UNESCAPED_UNICODE);die;
      
      $get_data = $this->get_data;

      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);

      $pay_url = 'http://hook.se-pay.com/pay';

      $pay_data = array(
        'sdk'=>$mch_data['mch_id'],
        // Y,商户号，API平台提供
        
        'money'=>number_format($get_data['pay_amount']/100, 2, '.',''),
        // Y，网银支付

        'record'=>$order_id,

        'refer'=>THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',

        //'type'=>'json',

        'notify_url'=>THIS_URL.'/api/Notify/freepayment_notify_url.html',


      );

      $pay_data['sign'] = $this->free_payment_sign($pay_data['money'],$pay_data['record'],$pay_data['sdk']);
      $url_str = $this->arrayToKeyValueString($pay_data);
      //拼接url参数

      $pay_url = 'http://hook.se-pay.com/pay';
      //请求地址，API平台提供

      $pay_url .= '?'.$url_str;

      header("Location: $pay_url");die;
      
      $html_str = '<form id="myForm" action="'.$pay_url.'" method="post">';

      foreach ($pay_data as $key => $value) {
            $html_str .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }

      $html_str .= '</form><script>document.getElementById("myForm").submit();</script>';

     //dump($html_str);die;
      echo $html_str;
      die;


   }
  public function arrayToKeyValueString($data){
	$url_str = '';
	foreach($data as $key => $value) {
		$url_str .= $key .'=' . $value . '&';
	}
	$url_str = substr($url_str,0,strlen($url_str)-1);
	return $url_str;
}
  
  
//农信免签支付回调  
      public function freepayment_notify_url(){

        $post_data = input();
        $order_id = $this->get_order_id($post_data['record']);

        $this->notify_child_account($order_id);

        
        $sign = md5($post_data['money'] .trim($post_data['record']) .'bf14a8ea1ce29d553f');

        if($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号

            //支付宝交易号/支付宝交易凭证号
            $trade_no = $post_data['order'];

            //交易状态
            $trade_status = '00';

            $pay_time = time();
            //付款时间
            
            $total_amount = $post_data['amount']*100;
            //订单金额
          		
            if ($trade_status == '00') {
                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序            
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                $order_data = Db::table('mch_order')->field('id,uid,pay_amount,pay_status,trade_no,pay_type,tcid,type,proxy_id')->where('id',$order_id)->find();

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
              	$receipt_amount = $post_data['money']*100;
          	//实际收款金额
              	$order_data['receipt_amount'] = $receipt_amount;
              
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
     public function free_payment_sign($money, $record, $sdk) {
        $sign = md5(Number_format($money, 2, '.','') . trim($record) . $sdk);
        return $sign;
    }


}