<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
class Notify extends ApiDemo{
	
  	 public function return_url(){
        $order_id_arr = explode('_',$_GET['out_trade_no']);

        $order_id = $order_id_arr[1];

        $order_data = Db::table('mch_order')->field('order_num,return_url')->where('id',$order_id)->find();
        
        $back_url = $order_data['return_url'].'?order_num='.$order_data['order_num'];
        
        Header("Location: $back_url"); 
    }
  
  
    public function paywyzf3(){
        //认证支付 回调地址
        
        $bank_data = $_POST;
        
        $this->ceshi($bank_data);
        
        $PayWyzf3 = Model('PayWyzf3');
        
        $order_id = $PayWyzf3->notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo "响应code".$_POST["returnCode"];
        }else{
            echo 'FAIL';
        }
    }
  
 //生银万国回调    
      public function sywg_notify()
      {
          $bank_data = input();
          $this->ceshi($bank_data);

          $alipay = Model('Sywgdf');

          $order_id = $alipay->sywg_notify();

          $notify_data = $this->notify_under($order_id);

          if ($notify_data=='success') {
              echo 'SUCCESS';
          }else{
              echo 'FAIL';
          }


      }
   //生银万国回调    
      public function yunyi_notify_url()
      {
          $bank_data = input();
          $this->ceshi($bank_data);
		 // Db::table('linshi')->insert(['info1'=>'接收参数 '.date('YmdHis'),'info2'=>json_encode($bank_data)]);
          $alipay = Model('Yunyi');

          $order_id = $alipay->yunyi_notify_url();

          $notify_data = $this->notify_under($order_id);

          if ($notify_data=='success') {
              echo 'success';
          }else{
              echo 'FAIL';
          }


      }
  	  //一诺支付回调    
      public function yinuo_notify_url()
      {
          $bank_data = input();
          $this->ceshi($bank_data);
		 // Db::table('linshi')->insert(['info1'=>'接收参数 '.date('YmdHis'),'info2'=>json_encode($bank_data)]);
          $alipay = Model('Yinuo');

          $order_id = $alipay->yinuo_notify_url();

          $notify_data = $this->notify_under($order_id);

          if ($notify_data=='success') {
              echo 'success';
          }else{
              echo 'FAIL';
          }


      }
  	//免签系统回调
  	  public function freepayment_notify_url()
      {
          $bank_data = input();
          $this->ceshi($bank_data);
		 // Db::table('linshi')->insert(['info1'=>'接收参数 '.date('YmdHis'),'info2'=>json_encode($bank_data)]);
          $alipay = Model('Freepayment');

          $order_id = $alipay->freepayment_notify_url();

          $notify_data = $this->notify_under($order_id);

          if ($notify_data=='success') {
              echo 'success';
          }else{
              echo 'FAIL';
          }


      }
  
  
  
  //虎云红包回调
    public function pay_alipay_coupon(){

        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('alipay');
        
        $order_id = $PayWyzf4->huyun_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }


    }

  //嘀嗒红包回调
    public function dida_notify_url(){

        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('DidaAlipayH5');
        
        $order_id = $PayWyzf4->dida_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'OK';
        }else{
            echo 'FAIL';
        }


    }
    //桂林支付宝回调
    public function glalipay_notify_url(){

        $xml = file_get_contents('php://input');
       	$this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Glalipay');
        
        $order_id = $PayWyzf4->glalipay_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }


    }
  
      //桂林支付宝回调
    public function yifu97pay_notify_url(){

       	$bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Yifu97pay');
        
        $order_id = $PayWyzf4->yifu97pay_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }


    }
//熊猫CNT支付回调
      public function cnt_notify_url(){

       	$bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Cntpay');
        
        $order_id = $PayWyzf4->cnt_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }


    }
  
  
  

    //嘀嗒红包回调
    public function baoxun_notify_url(){

        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Baoxun');
        
        $order_id = $PayWyzf4->baoxun_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'OK';
        }else{
            echo 'FAIL';
        }


    }
  
  
  
  
//汇旺财回调
      public function hwc_notify_url(){

        $xml = file_get_contents('php://input');
       	$this->ceshi($xml);

        $PayWyzf4 = Model('Hwcpay');
        
        $order_id = $PayWyzf4->hwc_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }


    }
  

  //支付宝淘宝现金红包回调地址
       public function alipay_tbxjhb(){
          
      
        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Redbag');
        
        $order_id = $PayWyzf4->alipay_tbxjhb();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'OK';
        }else{
            echo 'FAIL';
        }
    }
 //支付宝红包回调地址
     public function alipay_bag(){
          
      
        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Redbag');
        
        $order_id = $PayWyzf4->alipay_bag();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'OK';
        }else{
            echo 'FAIL';
        }


    }
  //农信回调地址
       public function nongxing_notify_url(){
          
      
        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Nongxin');
        
        $order_id = $PayWyzf4->nongxing_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }


    }
  

  
  //闪快支付回调
    public function skzf_notify_url(){

        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Skzf');
        
        $order_id = $PayWyzf4->skzf_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'OK';
        }else{
            echo 'FAIL';
        }


    }
  
    //Yz支付
    public function dspay_notify_url(){

        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Yzpay');
        
        $order_id = $PayWyzf4->dspay_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'OK';
        }else{
            echo 'FAIL';
        }


    }
  
  
  	  //云易付
    public function yunyifu_notify_url(){

        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Yunyifu');
        
        $order_id = $PayWyzf4->yunyifu_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }


    }
  
  //支付宝微信个人产码回调
    public function person_notify_url(){
        $bank_data = input();
        
        $this->ceshi($bank_data);
        
        $PayWyzf4 = Model('Personal');
        
        $order_id = $PayWyzf4->person_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }

    }
  
  
  //不固定金额固码回调
  	
  	    public function solid_bank_notify_url(){
        $bank_data = input();
       
        $this->ceshi($bank_data);

        $PayWyzf4 = Model('alipay');
        
        $order_id = $PayWyzf4->solid_bank_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }
        
    }
  
  
  //固定金额固码回调
    public function solid_notify_url(){
        $bank_data = input();
       
        $this->ceshi($bank_data);

        $PayWyzf4 = Model('alipay');
        
        $order_id = $PayWyzf4->solid_notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }
        
    }
  
//支付宝个码回调
    public function alipay(){
        $bank_data = input();
       
        $this->ceshi($bank_data);

        $PayWyzf4 = Model('OfficialAlipay');
        
        $order_id = $PayWyzf4->notify_url();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }
    }
    //支付宝银行卡转账回调
   public function notify_url_card(){
        $bank_data = input();
       
        $this->ceshi($bank_data);

        $PayWyzf4 = Model('alipay');
        
        $order_id = $PayWyzf4->notify_url_card();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }
    }
  
//支付宝个人转账回调
   public function alipay_transfer_notify(){
        $bank_data = input();
       
        $this->ceshi($bank_data);

        $PayWyzf4 = Model('alipay');
        
        $order_id = $PayWyzf4->alipay_transfer_notify();
        
        $notify_data = $this->notify_under($order_id);
        
        if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }
    }
  
  
  
  
  
 //手动修改已经收款未触发支付的订单
  	public function edit_order(){

      	$PayWyzf4 = Model('alipay');
        
      	
        $order_id = $PayWyzf4->edit_order();
       
        $notify_data = $this->notify_under($order_id);
      	if ($notify_data=='success') {
            echo 'success';
        }else{
            echo 'FAIL';
        }

    }
  	
  


    public function ceshi($bank_data){

        Db::table('linshi')->insert(['info1'=>'接收参数 '.date('Y-m-d H:i:s'),'info2'=>json_encode($bank_data)]);
    }

    protected function notify_under($order_id){
        // 向下通知
		
        $order_data = Db::table('mch_order')->field('uid,order_num,pay_amount,pay_time,notify_url,pay_status,trade_no,ext')->where('id',$order_id)->find();
		
        if ($order_data['pay_status']!=1||$order_data['pay_time']==0) {
            Db::table('linshi')->insert([
                'info1'=>'非法回调，订单未付款 id:'.$order_id,
                'info2'=>date('Y-m-d H:i:s')
            ]);
            exit;
        }

        $key_data  = Db::table('users')->field('id,merchant_cname,key')->where('id',$order_data['uid'])->find();

        $key = encryption($key_data['key'].$key_data['merchant_cname'].$key_data['id']);

        $return_data = [
            'mch_id'=>$key_data['merchant_cname'].'_'.$key_data['id'],

            'order_num'=>$order_data['order_num'],
            //下级商户订单号
            
            'pay_time'=>$order_data['pay_time'],
            //支付时间
            
            'trade_no'=>$key_data['merchant_cname'].'_'.$order_id,
            //红云平台流水号

            'pay_status'=>'success',
          	
          	'pay_amount'=>$order_data['pay_amount'],
          	
          	'ext'=>$order_data['ext'],
        ];


        $return_data['sign'] = $this->get_sign_str($return_data,$key);
		Db::table('linshi')->insert(['info1'=>'通知下游参数 '.date('Y-m-d H:i:s'),'info2'=>json_encode($return_data)]);
        Db::table('mch_order')->where('id',$order_id)->setInc('notice_num');
		
        $notify_data = $this->curl_callServerByPost($order_data['notify_url'],$return_data);
        if ($notify_data=='success') {
            //回调完成

            Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>1]);
        }elseif ($notify_data=='fail') {

            Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>2]);
        }else{

            Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>3]);

           // Db::table('linshi')->insert(['info1'=>'ID: '.$order_id.' 商户回调错误 '.date('Y-m-d H:i:s'),'info2'=>'回调信息:'.$notify_data]);
        }

        return $notify_data;

    }

    public function callServerByPost($url, $data){
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
    public function curl_callServerByPost($url, $post_data,$this_url=null){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11'); 
        //设置为POST
        curl_setopt($ch, CURLOPT_POST, 1);
        //把POST的变量加上

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
      	if ($output === FALSE){
          Db::table('linshi')->insert(['info1'=>'商户返回信息 '.date('Y-m-d H:i:s'),'info2'=>json_encode(curl_error($ch))]);
         
      }
      
        curl_close($ch);
      	Db::table('linshi')->insert(['info1'=>'商户返回信息 '.date('Y-m-d H:i:s'),'info2'=>json_encode($output)]);
        return $output;
    }
    public function repeat_notify_url(){


        if (!request()->isAjax()) {
            echo 3;
            die;
        }

        $id = input('id');
		
        return $this->notify_under($id);
    }
  
  //清空通道当日收款额度
  	public function di_limit(){
    	
      Db::table('top_account_assets')->where('id','>',0)->update(['daily_limit'=>0]);
    	
    }
  
  
  
}