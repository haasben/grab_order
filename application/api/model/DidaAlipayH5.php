<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class DidaAlipayH5 extends CommonWithdrawal{


//嘀嗒支付通道
    public function by_order($back_data,$mch_data,$pay_bankcode){
    	

      $get_data = $this->get_data;

      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);

  	  $pay_url = $mch_data['pay_url'].'/Pay_Index.html';


  	  $pay_data = array(
		    "pay_memberid" => $mch_data['mch_id'],
		    "pay_orderid" => $order_id,
		    "pay_amount" => number_format($get_data['pay_amount']/100,2),
		    "pay_applydate" =>date("Y-m-d H:i:s"),
		    "pay_bankcode" => $pay_bankcode,
		    "pay_notifyurl" => THIS_URL.'/api/Notify/dida_notify_url.html',
		    "pay_callbackurl" => THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',
		);

  	  $pay_data['pay_md5sign'] = self::dida_sign_str($pay_data,$mch_data['private_key']);
      
      if($pay_bankcode == 932){
      	 $pay_data['bank_code'] = 'BOC';
      }
      
  	  //dump($pay_data);die;

  	  $html_str = '<form id="myForm" action="'.$pay_url.'" method="post">';

      foreach ($pay_data as $key => $value) {
            $html_str .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }

      $html_str .= '</form><script>document.getElementById("myForm").submit();</script>';

     //dump($html_str);die;
      echo $html_str;
      die;


   }
  
  
  
	
      public function dida_notify_url(){

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

                $order_data = Db::table('mch_order')->field('id,uid,pay_amount,pay_status,trade_no,pay_type,tcid,type')->where('id',$order_id)->find();

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
//嘀嗒订单查询接口
    public function dida_query_order($order_data,$key){
    

    $get_data = $this->get_data;
    $this->notify_child_account($order_data['id']);
    
    $order_id = $this->set_order_id($order_data['accept_time'],$order_data['id']);
      
     
      
    $query_url = $this->pay_url.'/Pay_Trade_query.html';

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
  
  
  	//嘀嗒代付通道
    public function dida_withdrawal(){
       
        $get_data = $this->get_data;
        if ($get_data['pay_amount']<100000||$get_data['pay_amount']>5000000) {
             $ret['code'] = 10001;
             $ret['info'] = '金额限制（1000-50000）元';
             echo json_encode($ret,JSON_UNESCAPED_UNICODE);
             exit;
        }

        $pay_type = $get_data['pay_type'];
     	
        $taid = Db::table('top_account_assets')->where('type',$pay_type)->limit(1)->value('id');
		
        $fee = 200;
        //当前代付手续费(分为单位)
        
        $this->available_money($fee);

        $ret_data = $this->add_withdrawal_order($taid,$fee);
        
        $pay_url = $this->pay_url.'/Payment_Dfpay_add.html';
        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);
        //订单号之前加上了该订单的生成时间

        $pay_data = [
            'mchid'=>$this->mch_id,//商户支付KEY

            'out_trade_no'=>$order_id,

            'money'=>number_format(($get_data['pay_amount']/100+$fee/100),2),//订单金额单位元

            'bankname'=>$get_data['bank_name'],

            'subbranch'=>$get_data['subbranch'],//支行名称

            //'bankcode'=>$get_data['bank_no'],//银行编码

            'accountname'=>$get_data['account_name'],//账户名

            'cardnumber'=>$get_data['account_id'],//银行卡号

            'province'=>$get_data['province'],

            'city'=>$get_data['city'],

            'extends'=>'',

           
            //'notifyurl'=>THIS_URL.'/api/Withdrawal_notify/pay_alipay.html',

        ];

        $pay_data['pay_md5sign'] = self::dida_sign_str($pay_data,$this->private_key);
      	//dump($pay_data);die;
        $data = json_decode(self::dida_curl_post($pay_url,$pay_data),true);
		//dump($data);
        if($data['status']=='success'){
          	    Db::commit();
          	 	$order_data = Db::table('mch_order')->field('uid,order_num,pay_amount,pay_time,notify_url,pay_status,trade_no,this_fee')->where('id',$ret_data['order_id'])->find();
                $key_data  = Db::table('users')->field('id,merchant_cname,key,email')->where('id',$order_data['uid'])->find();

                $key = encryption($key_data['key'].$key_data['merchant_cname'].$key_data['id']).base64_encode($key_data['email']);
                Db::table('mch_order')->where('id',$order_id)->setInc('notice_num');
              	//收款账户当时余额
              	$this_money = Db::name('assets')->where('uid',$order_data['uid'])->value('money');
              
                $return = [
                    'mch_id'=>$get_data['mch_id'],

                    'order_num'=>$order_data['order_num'],
                    //下级商户订单号
                   // 'pay_time'=>time(),
                    //支付时间

                    'code'=>'0000',

                    'info'=>'提交成功',

                    'pay_status'=>'success',
                  
                  	'this_money'=>$this_money,
                  	
                  	//代付金额
                      'pay_amount'=>$order_data['pay_amount'],
                  	
                  	//手续费
                     'fee'=>$order_data['this_fee'],
                ];


                $return['sign'] = $this->get_sign_mch($return,$key);

                echo json_encode($return,JSON_UNESCAPED_UNICODE);die;
          	
            
        }else{
              Db::rollback();
              $ret['code'] = '1111';
              $ret['info'] = $data['msg'];

          }

          echo json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;    

    }



  //嘀嗒代付订单查询
      public function dida_query_withdrawal_order($ret_data){

        $this->notify_child_account($ret_data['order_id']);

        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);

        $pay_url = $this->pay_url.'/Payment_Dfpay_query.html';

         $pay_data = [
            'mchid'=>$this->mch_id,//商户支付KEY
            'out_trade_no'=>$order_id,//订单ID
        ];

        $pay_data['pay_md5sign'] = self::dida_sign_str($pay_data,$this->private_key);
      
        $data = json_decode(self::dida_curl_post($pay_url,$pay_data),true);

        if($data['status']=='success'){

              $order_id = $ret_data['order_id'];
              $order_data = Db::table('mch_order')->field('uid,order_num,pay_amount,pay_time,notify_url,pay_status,trade_no,this_fee')->where('id',$order_id)->find();
              if ($data['refCode'] == '1') {
                  	$code = '0000';
                    $data['remitStatus'] = 'SUCCESS';
                  	$info = '打款成功';
                    $pay_time = time();
                    $this->withdrawal_notify_update($order_id,$pay_time);
                    Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>1]);
                    

                }else{
                  $code = '1111';
                  if($data['refCode'] == '3'){
                      $info = '打款中';
                      $data['remitStatus'] = 'REMITTING';
                    }elseif(in_array($data['refCode'],[2,5,7,8])){
                      $info = '打款失败';
                      $data['remitStatus'] = 'REMIT_FAIL';
                    }elseif($data['refCode'] == '6' || $data['refCode'] == '4'){
                      $info = '审核中';
                      $data['remitStatus'] = 'WAIT_CONFIRM';
                    }else{

                    }
                
                    $trade_no = Db::table('mch_order')->where('id',$order_id)->where('order_type',2)->value('trade_no');
                    Db::table('withdrawal')->where('id',$trade_no)->where('status',3)->update(['ext'=>$data['refMsg']]);

                }
              
                $key_data  = Db::table('users')->field('id,merchant_cname,key,email')->where('id',$order_data['uid'])->find();

                $key = encryption($key_data['key'].$key_data['merchant_cname'].$key_data['id']);
                $this_money = Db::name('assets')->where('uid',$order_data['uid'])->value('money');
                $return = [
           
                    'code'=>$code,
                  
                    'info'=>$info,

                    'order_num'=>$order_data['order_num'],
                    //下级商户订单号
                    'pay_time'=>$pay_time,
                    //支付时间
                    'pay_status'=>$data['remitStatus'],
                    
                  //该账户平台剩余余额
                    'this_money'=>$this_money,
                  
                    //代付金额
                    'pay_amount'=>$order_data['pay_amount'],
                    
                    //手续费
                     'fee'=>$order_data['this_fee'],
                  
                ];


                $return['sign'] = $this->get_sign_mch($return,$key);

                echo json_encode($return,JSON_UNESCAPED_UNICODE);die;
              
            
            }else{
            
              	$ret['code'] = 10005;
                $ret['info'] = '服务器异常';
                echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                exit;
            
            }

    }
  
  
    //嘀嗒轮循查询代付订单付款状态
  	    public function dida_query_withdrawal_order_status($ret_data){

        $this->notify_child_account($ret_data['order_id']);

        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);

       	$pay_url = $this->pay_url.'/Payment_Dfpay_query.html';

         $pay_data = [
            'mchid'=>$this->mch_id,//商户支付KEY
            'out_trade_no'=>$order_id,//订单ID
        ];

        $pay_data['pay_md5sign'] = self::dida_sign_str($pay_data,$this->private_key);
      
        $data = json_decode(self::dida_curl_post($pay_url,$pay_data),true);
          
         if($data['status']=='success'){
          
              $order_id = $ret_data['order_id'];
              if ($data['refCode'] == 1) {
                  	$code = '0000';
                    $pay_time = strtotime($data['success_time']);
                    $this->withdrawal_notify_update($order_id,$pay_time);
                    Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>1]);
                }

            }

    } 
  
  
  //查询资金是否充足
    public function available_money($fee){
        //判断资金是否充足
        $get_data = $this->get_data;

        //当前时间

        $uid = explode('_', $get_data['mch_id'])[1];

        //该用户当前余额
        $user_amount = Db::name('assets')->where('uid',$uid)->value('money');

        $user_freeze_amount = Db::table('user_fee')
            ->field('freeze_amount')
            ->where('uid',$uid)
            ->select();
        $freeze_amount = 0;
        foreach ($user_freeze_amount as $k => $v) {
            $freeze_amount +=$v['freeze_amount'];
        }
        
        //该用户当前通道上游冻结金额
     
        $sum_deductions = $freeze_amount+$fee;
        //该用户当前通道上游冻结金额
        
        //总扣款
        if ($user_amount-$sum_deductions<0) {
            $ret['code'] = 80008;
            $ret['info'] = '可用余额不足';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

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