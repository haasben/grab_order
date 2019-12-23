<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Cache;
class Cash extends ApiDemo{
   


  //服务器有图片的生成base64图片到收银台
      public function cash_index(){
//https://pay.swiftpass.cn/pay/qrcode?uuid=weixin%3A%2F%2Fwxpay%2Fbizpayurl%3Fpr%3Dp86PFTI
       $order_id = input('order_id');
       if(empty($order_id)){
           die;
        }
        $order_id = substr($order_id,14);
        $order_status = Db::name('mch_order')->where('id',$order_id)->limit(1)->find();
// dump($order_status);die;
        if(!empty($order_status)){
            if($order_status['pay_status'] == 1){
               echo "<script>alert('支付链接已失效，请重新获取');window.history.back(-1);</script>";
               die;
            }elseif($order_status['pay_status'] == 2){
                $uid = $order_status['uid'];
                $time = time()-$order_status['accept_time']-300;
                if($time > 0){
                    echo "<script>alert('支付链接已失效，请重新获取');window.history.back(-1);</script>";
                    die;
                }
            }   
        }else{
            echo "<script>alert('无效二维码');window.history.back(-1);</script>";
             die;
        }
        
        $order_data['order_num'] = $order_status['order_num'];
        $order_data['time'] = 300-(time()-$order_status['accept_time']);
        $order_data['pay_amount'] = $order_status['pay_amount']/100;
        
        if($order_status['type'] == 1029){
          	$order_data['img'] = create_code($order_status['img_url']);
            $order_data['img'] = imgtobase64($order_data['img']);
            $this->assign('order_data',$order_data);
            return $this->fetch('bank_code');

        }elseif($order_status['type'] == 1024){
            $order_data['img'] = $order_status['img_url'];
            $order_data['img'] = imgtobase64($order_data['img']);
            $this->assign('order_data',$order_data);
            return $this->fetch('solid_code1');

        }else{
            echo "<script>alert('无效配置');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
            die;

        }

  }

//支付宝打开页面
     public function alipay_cash(){
//https://pay.swiftpass.cn/pay/qrcode?uuid=weixin%3A%2F%2Fwxpay%2Fbizpayurl%3Fpr%3Dp86PFTI
       $order_id = input('ord');
       if(empty($order_id)){
           die;
        }
        $order_id = substr($order_id,14);
        $order_status = Db::name('mch_order')->where('id',$order_id)->limit(1)->find();

         if(!empty($order_status)){
             if($order_status['pay_status'] == 1){
               echo "<script>alert('支付链接已失效，请重新获取');window.history.back(-1);</script>";
                die;
             }elseif($order_status['pay_status'] == 2){
                 $uid = $order_status['uid'];
                 $time = time()-$order_status['accept_time']-600;
                if($time > 0){
                     echo "<script>alert('支付链接已失效，请重新获取');window.history.back(-1);</script>";
                     die;
                 }
             }   
         }else{
             echo "<script>alert('无效二维码');window.history.back(-1);</script>";
              die;
        }

          $this->assign('order_status',$order_status);
          return $this->fetch('alipay_cash');



  }
  //农信自动产码跳转
  public function nongxin_order(){

    //接收订单
    $order_id = input('order_id');

    $mch_order_id = substr($order_id,14);
    $order = Db::name('mch_order')->where('id',$mch_order_id)->limit(1)->find();
    if(!empty($order)){
         if($order['pay_status'] == 1){
           echo "<script>alert('该订单已支付完成，请勿重复支付');window.history.back(-1);</script>";
            die;
         }elseif($order['pay_status'] == 2){
             $uid = $order['uid'];
             $time = time()-$order['accept_time']-600;
            if($time > 0){
                 echo "<script>alert('支付链接已失效，请重新获取');window.history.back(-1);</script>";
                 die;
             }
         }   
     }else{
         echo "<script>alert('无效的支付链接');window.history.back(-1);</script>";
          die;
    }

      //获取秘钥
      $mch_id = Db::table('top_child_account')->where('id',$order['tcid'])->limit(1)->value('mch_id');

      //下单
      $pay_url = 'http://hook.se-pay.com/pay';

      $pay_data = array(
        'sdk'=>$mch_id,
        // Y,商户号，API平台提供
        
        'money'=>number_format($order['pay_amount']/100, 2, '.',''),
        // Y，网银支付

        'record'=>$order_id,

        'refer'=>THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',

        //'type'=>'json',

        'notify_url'=>THIS_URL.'/api/Notify/freepayment_notify_url.html',

      );

      $pay_data['sign'] = md5(Number_format($pay_data['money'], 2, '.','') . trim($pay_data['record']).$pay_data['sdk']);
      $url_str = arrayToKeyValueString($pay_data);

      //请求地址，API平台提供

      $pay_url .= '?'.$url_str;

      header("Location: $pay_url");die;


  }
  


}