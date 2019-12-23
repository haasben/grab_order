<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Redbag extends CommonWithdrawal{

	
//鑫鑫支付下单
  public function by_bag_order($back_data,$mch_data){
    	

      $get_data = $this->get_data;

      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);

  	  $pay_url = $mch_data['pay_url'].'/Pay_Index.html';


  	  $pay_data = array(
		    "pay_memberid" => $mch_data['mch_id'],
		    "pay_orderid" => $order_id,
		    "pay_amount" => number_format($get_data['pay_amount']/100,2),
		    "pay_applydate" =>date("Y-m-d H:i:s"),
		    "pay_bankcode" => 903,
		    "pay_notifyurl" => THIS_URL.'/api/Notify/alipay_tbxjhb.html',
		    "pay_callbackurl" => THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',
		);

  	  $pay_data['pay_md5sign'] = self::dida_sign_str($pay_data,$mch_data['private_key']);

      $pay_data['pay_productname'] = '商品订单';
      

  	  $html_str = '<form id="myForm" action="'.$pay_url.'" method="post">';

      foreach ($pay_data as $key => $value) {
            $html_str .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }

      $html_str .= '</form><script>document.getElementById("myForm").submit();</script>';

      echo $html_str;
      die;


   }


//鑫鑫支付回调
    public function alipay_tbxjhb(){

        $post_data = input();
        $order_id = $this->get_order_id($post_data['orderid']);

        $this->notify_child_account($order_id);
	
        unset($post_data['attach']);
        
        $sign = self::dida_sign_str($post_data,$this->private_key);
		
        if($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号

            //支付宝交易号/支付宝交易凭证号
            $trade_no = $post_data['transaction_id'];

            //交易状态
            $trade_status = $post_data['returncode'];

            $pay_time = strtotime($post_data['datetime']);
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


















//支付宝普通红包支付通道
    public function by_order($back_data,$mch_data){
      

      $get_data = $this->get_data;

      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);

      $pay_url = $mch_data['pay_url'].'/Pay_Index.html';


      $pay_data = array(
        "pay_memberid" => $mch_data['mch_id'],
        "pay_orderid" => $order_id,
        "pay_amount" => number_format($get_data['pay_amount']/100,2),
        "pay_applydate" =>date("Y-m-d H:i:s"),
        "pay_bankcode" => 'alipay',
        "pay_notifyurl" => THIS_URL.'/api/Notify/alipay_bag.html',
        "pay_callbackurl" => THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',
    );

      $pay_data['pay_md5sign'] = self::skzf_sign_str($pay_data,$mch_data['private_key']);
      $pay_data['pay_tongdao'] = '商品订单';
      
      $data = $this->skzf_curl_post($pay_url,$pay_data);

      $data = json_decode($data,true);
      if(isset($data['mingzi'])){
      		$key = 'yunyikey';
            $url="https://www.alipay.com/?appId=09999988&actionType=toCard&sourceId=bill&bankAccount=".urldecode($data['mingzi'])."&cardNo=".$data['ka']."&bankMark=".$data['bankmark']."&cardIndex=".$data['ppid']."&cardNoHidden=true&cardChannel=HISTORY_CARD&orderSource=from&amount=".$data['money']."&money=".$data['money']."";
			
        	Db::table('mch_order')->where('id',$back_data['id'])->update(['img_url'=>$url]);
        	$url = THIS_URL.'/alipay_cash/ord/'.$order_id.'.html';
            $url = create_code($url,ROOT_PATH.'public/static/alipay.png');
            $urlcode = yun_encrypt($url.'###'.$order_id,$key);
            $url = THIS_URL.'/cash_reg/token/'.$urlcode;
            $ret['code'] = '0000';
            $ret['info'] = '创建订单成功';
            $ret['pay_amount'] = $data['money']*100;
            $ret['data'] = $url;
        	
        	Db::table('mch_order')->where('id',$back_data['id'])->update(['pay_amount'=>$data['money']*100]);
        
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
      exit;
      

   }
  
  
  
//支付宝转卡支付回调  
      public function alipay_bag(){

        $post_data = input();
        $order_id = $this->get_order_id($post_data['orderid']);

        $this->notify_child_account($order_id);
  
        unset($post_data['reserved1']);
        unset($post_data['reserved2']);
        unset($post_data['reserved3']);
        
        $sign = self::skzf_sign_str($post_data,$this->private_key);

        if($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号

            //支付宝交易号/支付宝交易凭证号
            $trade_no = '0000';

            //交易状态
            $trade_status = $post_data['returncode'];

            $pay_time = strtotime($post_data['datetime']);
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

                $order_data = Db::table('mch_order')->field('id,uid,pay_amount,pay_status,trade_no,pay_type,tcid,type,is_proxy')->where('id',$order_id)->find();

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
               // }
                $channel_fee = Db::table('top_child_account')->where('id',$order_data['tcid'])->value('fee');
                //该通道手续费率
				$order_data['pay_amount'] = $post_data['amount']*100;
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
//嘀嗒订单查询接口
    public function skzf_query_order($order_data,$key){
    

    $get_data = $this->get_data;
    $this->notify_child_account($order_data['id']);
    
    $order_id = $this->set_order_id($order_data['accept_time'],$order_data['id']);
      
     
      
    $query_url = $this->pay_url.'/pay/trade/query.html';

    $query_data = [
            'pay_memberid'=>$this->mch_id,//商户支付KEY
          
            'pay_orderid'=>$order_id,//订单ID

      ];

        $query_data['pay_md5sign'] = self::dida_sign_str($query_data,$this->private_key);
      
        

        $data = json_decode(self::dida_curl_post($query_url,$query_data),true);
        
        $sign = self::dida_sign_str($data,$this->private_key);
      
    

        if($data['returncode'] == '00'){
            
            if($sign == $data['sign']){
                if($data['trade_state'] == 'SUCCESS'){
                     $code = '0000';
                     $info = '支付成功';

                }else{
                    $code = '1111';
                    $info = '未支付';
                }

               $back_data = [
                  'code' => $code,
                  'info'=>$info,
                  'mch_id'=>$get_data['mch_id'],
                  'order_num'=>$get_data['order_num'],
                 // 'add_time'=>$order_data['sql_name'],
                  'pay_amount'=>$order_data['pay_amount'],
                  'fee'=>$order_data['this_fee'],
              ];

              $back_data['sign'] = $this->mch_sign_str($back_data,$key);
          
          }else{

                $code = '1111';
                $info = '请求失败';
                $back_data = [
                  'code' => $code,
                  'info'=>$info,
              ];

            }
            
         }else{
            
              $code = '1111';
                $info = '请求失败';
                $back_data = [
                  'code' => $code,
                  'info'=>$info,
              ];

         }
          
          

        echo json_encode($back_data,JSON_UNESCAPED_UNICODE);
        exit;
    
    }
  
  
    
  //上游加密方式
     public static function dida_sign_str($data,$private_key){
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
    public static function dida_curl_post($url, $data_string) {
      
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