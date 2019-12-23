<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Yunyi extends CommonWithdrawal{


//云易聚合码支付通道
    public function by_order($back_data,$mch_data,$pay_type){
    	
      $get_data = $this->get_data;
		
      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);
		
      //if($get_data['pay_method'] == 'mobile'){
      	
        	//$pay_method = 'mobile';
      
      //}else{
      		$pay_method = 'cash';
     // }
      
      
  	  $pay_data = array(
		'mch_id'=>$mch_data['mch_id'],
		// Y,商户号，API平台提供
		
		'order_num'=>$order_id,
		// Y,订单号，全局唯一
		
		'pay_amount'=>$get_data['pay_amount'],
		// Y,订单金额 分为单位,最低一元																		
		
		'notify_url'=>THIS_URL.'/api/Notify/yunyi_notify_url.html',
		// Y,填写你自己的服务器异步回调地址（通知地址,post访问携带参数），不可以为本地地址。

		'return_url'=>THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',
		//目前暂时可以不配，但是不能为空
		
		'ext'=>'',
		// Y备注信息，可以为空，但不可删除
		
       	'pay_type'=>$pay_type,

      	//支付方式 pc 二维码支付 mobile H5支付 目前暂不支持微信H5
      	'pay_method'=>$pay_method,
      
	  );

     $pay_data['sign'] = $this->mch_sign_str($pay_data,$mch_data['private_key']);
     $url_str = $this->arrayToKeyValueString($pay_data);
	//拼接url参数


	$location_href = $mch_data['pay_url'].'/api/Recharge/pay.html';
	//请求地址，API平台提供

	$location_href .= '?'.$url_str;

	$data = file_get_contents($location_href);

    $data = json_decode($data,true);
    if($data['code'] == '0000'){
    	  $ret['code'] = '0000';
          $ret['info'] = '创建订单成功';
          $ret['pay_amount'] = $get_data['pay_amount'];  
          $ret['data'] = $data['data'];
      		
          $mch_id_arr = explode('_',$get_data['mch_id']);  
          $id = $mch_id_arr[1];
          $sql_data  = Db::table('users')->field('merchant_cname,key')->where('id',$id)->find();
          $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$id);
          $ret['sign'] = $this->mch_sign_str($pay_data,$key);

    }else{
    	 $ret['code'] = '1111';
         $ret['info'] = $data['info'];
    
    }
    echo json_encode($ret,JSON_UNESCAPED_UNICODE); 
   

   }
  	

      public function yunyi_notify_url(){

        $post_data = input();
        $order_id = $this->get_order_id($post_data['order_num']);

        $this->notify_child_account($order_id);
	   
        $sign = $this->mch_sign_str($post_data,$this->private_key);
        
		
        if($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号
		
            //支付宝交易号/支付宝交易凭证号
            $trade_no = $post_data['trade_no'];

            //交易状态
            $trade_status = $post_data['pay_status'];

            $pay_time = $post_data['pay_time'];
            //付款时间
            
            $total_amount = $post_data['pay_amount'];
            //订单金额
           

            if ($trade_status == 'success') {
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
        
                //if((string)$order_data['pay_amount']!=(string)$total_amount){
                    //$this->linshi_spl('金额不相等 '.$order_id,date('Y-m-d H:i:s').'数据库金额：'.$order_data['pay_amount'].' 收到金额:'.$total_amount);
                    // send_code(get_admin_phone(),'pay32');
                    //echo 'FAIL';
                    //die;
                //}
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

  public function arrayToKeyValueString($data){
	$url_str = '';
	foreach($data as $key => $value) {
		$url_str .= $key .'=' . $value . '&';
	}
	$url_str = substr($url_str,0,strlen($url_str)-1);
	return $url_str;
}
  public function mch_sign_str($data, $key){
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