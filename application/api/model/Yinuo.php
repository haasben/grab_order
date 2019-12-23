<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Yinuo extends CommonWithdrawal{


//嘀嗒支付通道
    public function by_order($back_data,$mch_data){
      
      $get_data = $this->get_data;
      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);
      $pay_url = $mch_data['pay_url'].'/services/onlineBank/onlineBank_v1';
      $pay_data = array(
        "merchant_id" => $mch_data['mch_id'],
        "order_id" => $order_id,
        "order_amt" => $get_data['pay_amount'],
            'card_type'=>'1',
        "biz_code" => 1007,
        "bg_url" => THIS_URL.'/api/Notify/yinuo_notify_url.html',
        "return_url" => THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',
    );
    
      $pay_data['sign'] = self::dida_sign_str($pay_data,$mch_data['private_key']);
      $pay_data['account_no'] = '';

      $html_str = '<form id="myForm" action="'.$pay_url.'" method="post">';
      foreach ($pay_data as $key => $value) {
            $html_str .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }
      $html_str .= '</form><script>document.getElementById("myForm").submit();</script>';
      echo $html_str;
      die;


   }
  
  
  
  
      public function yinuo_notify_url(){

        $post_data = input();
        $order_id = $this->get_order_id($post_data['order_id']);

        $this->notify_child_account($order_id);

        $sign = self::dida_sign_str($post_data,$this->private_key);

        if($sign == $post_data['sign'] && $post_data['merchant_id'] == $this->mch_id) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号

            //支付宝交易号/支付宝交易凭证号
            $trade_no = $post_data['up_order_id'];

            //交易状态
            $trade_status = $post_data['state'];

            $pay_time = time();
            //付款时间
            
            $total_amount = $post_data['order_amt'];
            //订单金额
           

            if ($trade_status == '0') {
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
              
              	$receipt_amount = $total_amount;
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

    //代付通道
    public function yinuo_withdrawal(){
       
        $get_data = $this->get_data;
        if ($get_data['pay_amount']<1000||$get_data['pay_amount']>5000000) {
             $ret['code'] = 10001;
             $ret['info'] = '金额限制（10-50000）元';
             echo json_encode($ret,JSON_UNESCAPED_UNICODE);
             exit;
        }

        $pay_type = $get_data['pay_type'];
      
        $taid = Db::table('top_account_assets')->where('type',$pay_type)->limit(1)->value('id');
    
        $fee = 300;
        //当前代付手续费(分为单位)
        
        $this->available_money($fee);

        $ret_data = $this->add_withdrawal_order($taid,$fee);
        
        $pay_url = $this->pay_url.'/services/order/daifu';
        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);
        //订单号之前加上了该订单的生成时间

        $pay_data = [
            'merchant_id'=>$this->mch_id,//商户支付KEY

            'order_id'=>$order_id,

            'order_amt'=>$get_data['pay_amount'],//订单金额单位元

            'bank_name'=>$get_data['bank_name'],

            'bank_code'=>$get_data['bank_no'],//银行编码

            'account_no'=>$get_data['account_id'],//银行卡号

            'account_name'=>$get_data['account_name'],//账户名

            'mobile'=>'13111111111',


        ];

        $pay_data['sign'] = self::dida_sign_str($pay_data,$this->private_key);
		
      	$pay_data['type'] = '1';
        $pay_data['subBranch'] = '城南支行';
        $pay_data['pay_type'] ='1';
      	$pay_data['id_card'] = '370883199012225821';
      	$pay_data['province'] = '四川省';
      	$pay_data['city'] = '成都市';
      	$pay_data['bank_firm_no'] = '111111';
      	
     

        $data = json_decode($this->http_post_data($pay_url,$pay_data),true);
        if(isset($data['rsp_code']) && $data['rsp_code']=='00'){
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


                $return['sign'] = $this->mch_sign_str($return,$key);

                echo json_encode($return,JSON_UNESCAPED_UNICODE);die;
            
            
        }else{
              Db::rollback();
              $ret['code'] = '1111';
              $ret['info'] = $data['rsp_msg'];

          }

          echo json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;    

    }

  	 public function yinuo_query_withdrawal_order($ret_data){

        $this->notify_child_account($ret_data['order_id']);

        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);

        $pay_url = $this->pay_url.'/services/order/orderQuery';

         $pay_data = [
            'merchant_id'=>$this->mch_id,//商户支付KEY
            'order_id'=>$order_id,//订单ID
        ];

        $pay_data['sign'] = self::dida_sign_str($pay_data,$this->private_key);
      	
        $data = json_decode($this->http_post_data($pay_url,$pay_data),true);

        if($data['rsp_code']=='00'){
          	  $pay_time = '0';
              $order_id = $ret_data['order_id'];
              $order_data = Db::table('mch_order')->field('uid,order_num,pay_amount,pay_time,notify_url,pay_status,trade_no,this_fee')->where('id',$order_id)->find();
              if ($data['state'] == '0') {
                  	$code = '0000';
                    $data['remitStatus'] = 'SUCCESS';
                  	$info = '打款成功';
                    $pay_time = time();
                    $this->withdrawal_notify_update($order_id,$pay_time);
                    Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>1]);
                    

                }else{
                  $code = '1111';
                  if($data['state'] == '2'){
                      $info = '打款中';
                      $data['remitStatus'] = 'REMITTING';
                    }elseif($data['state'] == '1'){
                      $info = '打款失败';
                      $data['remitStatus'] = 'REMIT_FAIL';
                    }
                
                    $trade_no = Db::table('mch_order')->where('id',$order_id)->where('order_type',2)->value('trade_no');
                    Db::table('withdrawal')->where('id',$trade_no)->where('status',3)->update(['ext'=>$data['rsp_msg']]);

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


                $return['sign'] = $this->mch_sign_str($return,$key);

                echo json_encode($return,JSON_UNESCAPED_UNICODE);die;
              
            
            }else{
            
              	$ret['code'] = 10005;
                $ret['rsp_msg'] = '服务器异常';
                echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                exit;
            
            }

    }

  public function yinuo_query_withdrawal_order_status($ret_data){

        $this->notify_child_account($ret_data['order_id']);

        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);

       	$pay_url = $this->pay_url.'/services/order/orderQuery';

         $pay_data = [
            'merchant_id'=>$this->mch_id,//商户支付KEY
            'order_id'=>$order_id,//订单ID
        ];

        $pay_data['sign'] = self::dida_sign_str($pay_data,$this->private_key);
      	
        $data = json_decode($this->http_post_data($pay_url,$pay_data),true);
          Db::table('linshi')->insert(['info1'=>1,'info2'=>json_encode($data)]);
         if($data['rsp_code']=='00'){
          
              $order_id = $ret_data['order_id'];
              if ($data['state'] == 0) {
                  	$code = '0000';
                    $pay_time = time();
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
  
  
    public function http_post_data($url, $data_string) {
    $data_string = json_encode($data_string);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        return $return_content;
}









  //上游加密方式
  public static function dida_sign_str($data,$private_key){
    if(isset($data['sign'])) {
      unset($data['sign']);
    }
      ksort($data);
    $md5str = "";
    foreach ($data as $key => $val) {
          //if(!empty($val)){
             $md5str .= $key . "=" . $val . "&";
          //}  
    }
    //echo $md5str.$private_key;die;
    $md5str = strtoupper(md5($md5str.$private_key));
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