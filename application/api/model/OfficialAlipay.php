<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class OfficialAlipay extends CommonWithdrawal{


//支付宝官方H5支付
    public function alipay_wap($data){
        /* *
         * 功能：支付宝手机网站支付接口(alipay.trade.wap.pay)接口调试入口页面
         * 版本：2.0
         * 修改日期：2016-11-01
         * 说明：
         * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
         请确保项目文件有可写权限，不然打印不了日志。
         */

        header("Content-type: text/html; charset=utf-8");


        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'alipaywap/wappay/service/AlipayTradeService.php';
        
        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'alipaywap/wappay/buildermodel/AlipayTradeWapPayContentBuilder.php';

        require_once 'config.php';

        unset($data['accept_time']);
        unset($data['id']);


        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $data['out_trade_no'];

        //订单名称，必填
        $subject = $data['subject'];

        //付款金额，必填
        $total_amount = $data['total_amount'];

        //商品描述，可空
        $body = $data['body'];

        //超时时间
        $timeout_express="1m";

        $payRequestBuilder = new AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);

        $payResponse = new AlipayTradeService($config);
        $result=$payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);

    }
	
	//支付宝H5回调
    public function notify_url(){
        
       $data = $_POST;
       header("Content-type: text/html; charset=utf-8");
 /**     
 * 功能：支付宝服务器异步通知页面
 * 版本：2.0
 * 修改日期：2016-11-01
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。

 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */
		
        require dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'config.php';

        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'alipaypc/pagepay/service/AlipayTradeService.php';
          
        $alipaySevice = new AlipayTradeService($config);
		
        $alipaySevice->writeLog(var_export($data,true));
    
        $result = $alipaySevice->check($data);
/* 实际验证过程建议商户添加以下校验。
1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
4、验证app_id是否为该商户本身。
*/
        if($result) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];

            $order_id_arr = explode('_',$out_trade_no);
            //因为加了商户缩写，用下划线分割后得到订单id
            $order_id = $order_id_arr[1];

            //支付宝交易号/支付宝交易凭证号
            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];

            $pay_time = strtotime($_POST['gmt_payment']);
            //付款时间
            
            $total_amount = $_POST['total_amount']*100;
            //订单金额
            


            if($_POST['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序
                        
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序            
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                $order_data = Db::table('mch_order')->field('id,uid,pay_amount,pay_status,trade_no,pay_type,tcid,type,proxy_id,accept_time')->where('id',$order_id)->find();

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

               if ($notify_update['code']) {
                    if($notify_update['data'] == 1){
                       $received1 = $total_amount/100;
                       //$this->transfer_money($received1,$data,$config);
                    }
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
   //订单查询
  public function query_order($data){

    header("Content-type: text/html; charset=utf-8");

    $out_trade_no = $data['mch_name'].'_'.$data['order_id'];
    $this->notify_child_account($data['order_id']);
    $data['this_secret_key'] = [
        'mch_id'=>$this->mch_id,
        'private_key'=>$this->private_key,
        'public_key'=>$this->public_key,

    ];
    
        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'/alipaywap/wappay/service/AlipayTradeService.php';
        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'/alipaywap/wappay/buildermodel/AlipayTradeQueryContentBuilder.php';
        require_once 'config.php';

        // $out_trade_no = trim($_POST['out_trade_no']);
        $RequestBuilder = new AlipayTradeQueryContentBuilder();
      
        //$RequestBuilder->setTradeNo($out_trade_no);
         $RequestBuilder->setOutTradeNo($out_trade_no);

        $Response = new AlipayTradeService($config);

        $result=$Response->Query($RequestBuilder);

        if($result->code == '10000'){


        //查询结果
        if($result->trade_status == 'TRADE_SUCCESS'){

            $order_id = $data['order_id'];

            $trade_status = $result->trade_status;

            //支付宝交易号/支付宝交易凭证号
            $trade_no = $result->trade_no;

            $pay_time = strtotime($result->send_pay_date);
            //付款时间
            
            $total_amount = $result->total_amount*100;


        //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
            //如果有做过处理，不执行商户的业务程序            
        //注意：
        //付款完成后，支付宝系统发送该交易状态通知

                $order_data = Db::table('mch_order')->field('id,uid,pay_amount,pay_status,trade_no,pay_type,tcid,accept_time')->where('id',$order_id)->find();
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
                    
                    $return_data = ['code'=>'0000','info'=>'支付成功','status'=>1];
                    return $return_data;

                }else{
                    $this->linshi_spl(date('Y-m-d H:i:s').'写入数据失败','写入数据失败');
                    $return_data = ['code'=>'1111','info'=>'查询失败','status'=>0];
                    return $return_data;
                }
            // echo "success";      //请不要修改或删除
          }else {
              $return_data = ['code'=>'1111','info'=>'未支付','status'=>0];
              return $return_data;
          }

    }else{


        $return_data = ['code'=>'2222','info'=>'该付款方式无此订单','status'=>0];
        return $return_data;



    }


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
  
//自动堆积到指定支付宝账户  
    private function transfer_money($total_amount,$data,$config){
        //require 'config.php';
        
        require_once 'alipaypc/aop/AopClient.php';
        require_once 'alipaypc/aop/request/AlipayFundTransToaccountTransferRequest.php';
        $aop = new AopClient();
      	
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $config['app_id'];
        $aop->rsaPrivateKey = $config['merchant_private_key'];
        $aop->alipayrsaPublicKey = $config['alipay_public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
      	

        $request = new AlipayFundTransToaccountTransferRequest ();

        $this_amount = $total_amount;
        $total_amount = $total_amount - round($total_amount*0.006,2);
        $auto_transfer_id = Db::table('auto_transfer')->insertGetId([
                'app_id'=>$config['app_id'],
                'total_amount'=>$total_amount,

            ]);
      
        $receive_account = Db::name('top_child_account')->where('mch_id',$config['app_id'])->limit(1)->value('receive_account');
        $receive_account = explode('|',$receive_account);
        $data=array();
        $data = [
            'out_biz_no'=>$auto_transfer_id,
            'payee_account'=>$receive_account[1],//收款账户
            'amount'=>$total_amount,
            'payee_real_name'=>$receive_account[0],//收款名
            'remark'=>'盘点',
        ];
		//Db::table('linshi')->insert(['info1'=>3,'info2'=>json_encode($data)]);
        $request->setBizContent("{" .
        "\"out_biz_no\":\"".$data['out_biz_no']."\"," .
        "\"payee_type\":\"ALIPAY_LOGONID\"," .
        "\"payee_account\":\"".$data['payee_account']."\"," .
        "\"amount\":\"".$data['amount']."\"," .
        "\"payee_real_name\":\"".$data['payee_real_name']."\"," .
        "\"remark\":\"".$data['remark']."\"" .
        "}");
      	
        $result = $aop->execute ( $request);
      	
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $resultCode = $result->$responseNode->code;
        //code（返回码） 10000成功 20000服务不可用  20001授权权限不足 40001缺少必选参数  40002非法的参数   40004业务处理失败  40006权限不足

        if(!empty($resultCode)&&$resultCode == 10000){
            Db::table('auto_transfer')->where('id',$auto_transfer_id)->update(['pay_status'=>1,'ext'=>date('Y-m-d H:i:s')]);
        } else {
            $resultSub_msg = $result->$responseNode->sub_msg;
            //sub_code（明细返回码） 英文
            Db::table('auto_transfer')->where('id',$auto_transfer_id)->update([
                    'pay_status'=>3,
                    'ext'=>$resultCode.'  '.$resultSub_msg.' 原资金为：'.$this_amount.' '.date('Y-m-d H:i:s')
                ]);
      send_code(18408219941,'A'.$resultCode);
            $top_child_account = Db::name('top_child_account');
            $top_child_account->where('mch_id',$config['app_id'])->update(['status'=>2]);
            
            $pid = $top_child_account->where('mch_id',$config['app_id'])->value('pid');
            Db::name('top_account_assets')->where('id',$pid)->update(['status'=>2]);
          
            cache('secret_key_arr',NULL);
            cache('secret_key_arr_key',NULL);
            cache('secret_key_arr_key_i',NULL);
        }
    }
  

}