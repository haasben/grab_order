<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Sywgdf extends CommonWithdrawal{

//生银万国支付接口    
    public function by_order($back_data,$mch_data,$productType){
    
        //向上级接口发送数据
        $get_data = $this->get_data;
        

        $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);

        //调用支付接口开始H5支付

        $pay_url = $mch_data['pay_url'].'/pay-web-gateway/cnpPay/initPay';

        $pay_data = [
            'payKey'=>$mch_data['mch_id'],//商户支付KEY
            'orderPrice'=>$get_data['pay_amount']/100,//订单金额单位元
            'outTradeNo'=>$order_id,//订单ID
            'productType'=>$productType,
            'orderTime'=>date('YmdHis'),//下单时间
            'productName'=>'商品订单',//支付产品名称
            'orderIp'=>getRealIp(),//支付IP
            'returnUrl'=>THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',//同步跳转地址
            'notifyUrl'=>THIS_URL.'/api/Notify/sywg_notify.html',//异步回调地址
            'remark'=>'商品订单',
        ];

      
        $pay_data['sign'] = $this->get_sign_str($pay_data,$mch_data['private_key']);

        $data = json_decode($this->sq_curl($pay_url,$pay_data),true);
	
      	Db::table('linshi')->insert(['info1'=>time('YmdHis'),'info2'=>json_encode($data)]);

        if($data['resultCode'] == '0000'){
            $url = $data['payMessage'];

            $ret['code'] = '0000';
            $ret['info'] = '创建订单成功';
            $ret['pay_amount'] = $get_data['pay_amount'];
          
          	if($get_data['pay_method'] == 'mobile'){
              	$ret['data'] = $data['payMessage'];
            	
            
            }else{
            	 $ret['data'] = THIS_URL.'/cash_index/order_id/'.$order_id.'.html';
				 $url = create_code($data['payMessage']);
          		 Db::table('mch_order')->where('id',$back_data['id'])->update(['img_url'=>$url]);
            }
          
           
            $mch_id_arr = explode('_',$get_data['mch_id']);  
            $id = $mch_id_arr[1];
            $sql_data  = Db::table('users')->field('merchant_cname,key')->where('id',$id)->find();
            $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$id);
            $ret['sign'] = $this->mch_sign_str($ret,$key);
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);exit;

        }else{
            $ret['code'] = '1111';
            $ret['info'] = $data['errMsg'];
            //$ret['pay_amount'] = $get_data['pay_amount'];
            //$ret['data'] = '';

        }

        echo json_encode($ret,JSON_UNESCAPED_UNICODE);die;
        die;


    }

//生银万国支付回调  
     public function sywg_notify()
    {
		die;
        $post_data = input();
        $order_id = $this->get_order_id($post_data['outTradeNo']);

        $this->notify_child_account($order_id);


        $sign = $this->get_sign_str($post_data,$this->private_key);

        if($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号


            //支付宝交易号/支付宝交易凭证号
            $trade_no = $post_data['trxNo'];

            //交易状态
            $trade_status = $post_data['tradeStatus'];

            $pay_time = strtotime($post_data['successTime']);
            //付款时间
            
            $total_amount = $post_data['orderPrice']*100;
            //订单金额
           
            if($trade_status == 'FINISH') {

                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序
                        
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($trade_status == 'SUCCESS') {
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
                }elseif($order_data['type'] == 9){
                	$order_data['pay_amount'] = $total_amount;
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










   
//生银万国代付系统代付通道
    
    public function sywg_withdrawal(){

        $this->taid = 185;
        $get_data = $this->get_data;
		if ($get_data['pay_amount']<1000||$get_data['pay_amount']>2000000) {
            $ret['code'] = 10001;
            $ret['info'] = '金额限制（10-20000）元';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        $fee = 300;
        //当前代付手续费(分为单位)
        $this->available_money($fee);
    
        $ret_data = $this->sywg_add_withdrawal_order($this->taid,$fee);

        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);
  

        $login_user = base64_encode($this->public_key);

        $key = $this->private_key;
        //商户key，API平台提供
        $key .= $login_user;

        $data = array(
            'mch_id'=>$this->mch_id,
            // Y,商户号，API平台提供
            
            'pay_type'=>'sq',
            // Y，网银支付

            'order_num'=>$order_id,

            'account_name'=>$get_data['account_name'],
            //持卡人姓名

            'account_id'=>$get_data['account_id'],
            //银行卡号码

            'pay_amount'=>$get_data['pay_amount'],
            // Y,提现金额 分为单位,最低10元

            'bank_name'=>$get_data['bank_name'],
            //银行行号
            'bank_no'=>$get_data['bank_no'],

            'phone_num'=>$get_data['phone_num'],

            'cert_no'=>$get_data['cert_no'],

            'notify_url'=>THIS_URL.'/api/Withdrawal_notify/sq_with_notify.html',

        );

        $data['sign'] = $this->get_sign_mch($data,$key);
        //生成sign

        $url_str = $this->arrayToKeyValueString($data);
        //拼接url参数
        
        $location_href = $this->pay_url.'/api/Withdrawal/sywg_index';
        //请求地址，API平台提供

        $location_href .= '?'.$url_str;

        // header("Location: $location_href");die;
        $data = file_get_contents($location_href);

        $result = json_decode($data,true);
        
        if($result['code'] == '0000' || $result['code'] == '1111'){
				Db::commit();
            if($result['pay_status'] == 'success'){
                
                $code = '0000';
            }else{
                $code = '1111';
            }
            $order_id = $this->get_order_id($order_id);
            $order_data = Db::table('mch_order')->field('uid,order_num,pay_amount,pay_time,notify_url,pay_status,trade_no,this_fee')->where('id',$order_id)->find();
            $key_data  = Db::table('users')->field('id,merchant_cname,key,email')->where('id',$order_data['uid'])->find();

            $key = encryption($key_data['key'].$key_data['merchant_cname'].$key_data['id']).base64_encode($key_data['email']);
            //收款账户当时余额
            $this_money = Db::name('assets')->where('uid',$order_data['uid'])->value('money');
              
            $return = [
                    'mch_id'=>$get_data['mch_id'],

                    'order_num'=>$order_data['order_num'],

                    'code'=>$code,

                    'info'=>$result['info'],

                    'pay_status'=>$result['pay_status'],
                  
                    'this_money'=>$this_money,
                    
                    //代付金额
                    'pay_amount'=>$order_data['pay_amount'],
                    
                    //手续费
                     'fee'=>$order_data['this_fee'],
                ];

           $return['sign'] = $this->get_sign_mch($return,$key);
           
        }else{

            $return['code'] = $result['code'];
            $return['info'] = $result['info'];

        }
        echo json_encode($return,JSON_UNESCAPED_UNICODE);die; 
   
    }

        //代付回调
    public function sq_with_notify(){

        $data = input();  
        $order_id = $this->get_order_id($data['order_num']);
        $this->notify_child_account($order_id);

        $login_user = base64_encode($this->public_key);

        $key = $this->private_key;
        //商户key，API平台提供
        
        $key .= $login_user;

        $sign = $data['sign'];
        $return_sign = $this->get_sign_mch($data,$key);
  

        if($sign == $return_sign){

            $order_id = $this->get_order_id($data['order_num']);
            //订单号
            $statusDesc = $data['info'];
            //中文描述
            $status = $data['code'];
            //订单状态  判断是否交易成功
            if ($status == '0000') {
                $responseCode = '0000';
                $this->withdrawal_notify_update($order_id,time());
                return [
                    'order_id'=>$order_id,
                    'code'=>'0000',
                    'info'=>'交易成功',
                    'pay_type'=>'success',
                ];
            }elseif($status == '1111'){

                $responseCode = '1111';
                $trade_no = Db::table('mch_order')->where('id',$order_id)->where('order_type',2)->value('trade_no');

                Db::table('withdrawal')->where('id',$trade_no)->where('status',2)->update(['ext'=>$statusDesc]);
                $this->del_withfrawal($trade_no);
                return [
                    'order_id'=>$order_id,
                    'code'=>$responseCode,
                    'info'=>$statusDesc,
                    'pay_type'=>'fail',
                ];

            }
        }

    }

       //代付订单查询
    public function query_withdrawal_order($ret_data){
    
        $this->notify_child_account($ret_data['order_id']);

        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);

        $key = '88b89e50c5941dacfda1290e30c2664a';
        //商户key，API平台提供

        $data = array(
            'mch_id'=>'cloud3_8',
            // Y,商户号，API平台提供

            'order_num'=>$order_id,
            // Y,订单号，全局唯一

        );

        $data['sign'] = $this->get_sign_mch($data,$key);
        //生成sign

        $url_str = $this->arrayToKeyValueString($data);
        //拼接url参数

        $location_href = 'http://payout.cloud-esc.com/api/query_order/api_widthdrawal_order.html';
        //请求地址，API平台提供

        $location_href .= '?'.$url_str;
        $ret = file_get_contents($location_href);

      
        $data = json_decode($ret,true);
  
        if($data['code']=='0000' || $data['code']=='1111'){
          
            $sign = $data['sign'];
            $return_sign = $this->get_sign_mch($data,$key);
            if($sign == $return_sign){

                $order_id = $this->get_order_id($data['order_num']);
                //订单号
                $statusDesc = $data['info'];
                //中文描述
                $status = $data['code'];
                //订单状态  判断是否交易成功

                if ($status == '0000') {
                    $responseCode = '0000';
                    $this->withdrawal_notify_update($order_id,time());
                    return [
                        'order_id'=>$order_id,
                        'code'=>'0000',
                        'info'=>'交易成功',
                        'status'=>'success',
                    ];
                }elseif($status == '1111'){

                    $responseCode = '1111';
                    $trade_no = Db::table('mch_order')->where('id',$order_id)->where('order_type',2)->value('trade_no');

                    Db::table('withdrawal')->where('id',$trade_no)->where('status',2)->update(['ext'=>$statusDesc]);
                    $this->del_withfrawal($trade_no);
                    return [
                        'order_id'=>$order_id,
                        'code'=>$responseCode,
                        'info'=>$statusDesc,
                        'status'=>'fail',
                    ];

                }else{

                    $responseCode = '2222';
                    return [
                        'order_id'=>$order_id,
                        'code'=>$responseCode,
                        'info'=>$statusDesc,
                        'status'=>'fail',
                    ];
                }
            }
        
        }else{
          $return['code'] = $data['code'];
            $return['info'] = $data['info'];

        }

        echo json_encode($return,JSON_UNESCAPED_UNICODE);die; 
       
    }


//代付失败回退金额
    public function del_withfrawal($id){
    
      Db::startTrans();
        //开启事务
      
        $withdrawalModel = Db::table('withdrawal');
        $withdrawal_data = $withdrawalModel->field('uid,w_amount,add_user,fee,pay_type,tcid,status')->where('id',$id)->find();
    
        if($withdrawal_data['status'] == 3){
          //拦截已经回调失败的订单
            return true;die;
        
        }
        
      
        $bool = $withdrawalModel
            ->where('id',$id)
            ->where('status',2)
            ->update([
                'status'=>3,
            ]);
        //更新提现订单

        if ($bool) {
          
            $assetsModel = Db::table('assets');
            $top_account_assetsModel = Db::table('top_account_assets'); 
            $top_child_accountModel = Db::table('top_child_account'); 
          
            $assets_data = $assetsModel->where('uid',$withdrawal_data['uid'])->find();
            //当前用户资金数据

            $top_account_assets_data = $top_account_assetsModel->where('id',$withdrawal_data['pay_type'])->value('money');
            //当前付款账户余额
            
            $sum_deductions = $withdrawal_data['w_amount']+$withdrawal_data['fee'];
            //总扣款

            $insert_mch_order = [
                'uid'=>$withdrawal_data['uid'],
                'order_num'=>'提现',
                'pay_amount'=>$sum_deductions,
                'pay_status'=>1,
                'pay_type'=>$withdrawal_data['pay_type'],
                'tcid'=>$withdrawal_data['tcid'],
                'pay_time'=>time(),
                'accept_time'=>time(),
                'trade_no'=>$id,
                'this_money'=>$assets_data['money']+$sum_deductions,
                'this_received_money'=>$top_account_assets_data+$sum_deductions,
                'this_profits_money'=>Db::table('assets')->where('uid',1)->value('money'),
                'this_fee'=>0,
                'this_channel_fee'=>0,
                'fee'=>0,
                'ext'=>'提现单号'.$id.',金额'.($sum_deductions/100).'元-作废',
                'order_type'=>2,
                'note_ext'=>'付款失败',
            ];

            $bool1 = Db::table('mch_order')->insert($insert_mch_order);
            //添加订单表

            
            $bool2 = $assetsModel->where('uid',$withdrawal_data['uid'])->setInc('money',$sum_deductions);
            //下级余额增加

            $bool3 = $assetsModel->where('uid',$withdrawal_data['uid'])->setDec('withdrawal_sum',$sum_deductions);
            //下级累计提现减少



            $bool4 = $top_account_assetsModel->where('id',$withdrawal_data['pay_type'])->setInc('money',$sum_deductions);
            //上级余额增加

            $bool5 = $top_account_assetsModel->where('id',$withdrawal_data['pay_type'])->setDec('withdrawal_sum',$sum_deductions);
            //上级累计提现总和减少


            $bool6 = $top_child_accountModel->where('id',$withdrawal_data['tcid'])->setInc('money',$sum_deductions);
            //上级收款子账户余额增加

            $bool7 = $top_child_accountModel->where('id',$withdrawal_data['tcid'])->setDec('withdrawal_sum',$sum_deductions);
            //上级收款子账户累计提现总和减少

            $bool8 = Db::table('user_fee')->where('uid',$withdrawal_data['uid'])->limit(1)->setInc('money',$sum_deductions);
            //增加用户对应该渠道资金

            if($bool1&$bool2&$bool3&$bool4&$bool5&$bool6&$bool7&$bool8){
              Db::commit();
              return true;

            }else{
              Db::rollback();
              return false;

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


     //生银万国支付签名
      protected function get_sign_str($data, $key){
        
        if(isset($data['sign'])) {
            unset($data['sign']);
        }

        ksort($data);
        $sign_str = '';
        foreach($data as $k => $v) {
            $sign_str .= $k . '='.$v.'&';
        }
        
        $sign_str = substr($sign_str,0,strlen($sign_str)-1);
        
        $sign_str = strtoupper(md5($sign_str."&paySecret=".$key));

        return $sign_str;
    }






    public function arrayToKeyValueString($data){
      $url_str = '';
      foreach($data as $key => $value) {
        $url_str .= $key .'=' . $value . '&';
      }
      $url_str = substr($url_str,0,strlen($url_str)-1);
      return $url_str;
    }
  
  
    protected function sq_curl($url,$post_data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
        curl_setopt($ch, CURLOPT_POST, TRUE); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);

        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;


    }


    protected function joinMapValue($sign_params){
        $sign_str = "";
        //ksort($sign_params);
        foreach ($sign_params as $key => $val) {
            $sign_str .= sprintf("%s=%s&", $key, $val);                
        }
        return substr($sign_str, 0, -1);
    }
  
  //商户自己的加密方式  
    public function get_sign_mch($data, $key){
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