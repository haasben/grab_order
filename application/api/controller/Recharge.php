<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Cache;
class Recharge extends ApiDemo{
    public function pay(){

        header("Content-type: text/html; charset=utf-8"); 
        $get_data = input();
        $this->ceshi($get_data);
        if (!$this->is_array_key($get_data)) {
            //判断必选参；
            $ret['code'] = '10005';
            $ret['info'] = '缺少参数';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $mch_id_arr = explode('_',$get_data['mch_id']);
        if(!isset($mch_id_arr[1])){
            $ret['code'] = '10002';
            $ret['info'] = '商户不存在';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        $id = $mch_id_arr[1];
        $sql_data  = Db::table('users')->field('merchant_cname,state,key')->where('id',$id)->find();

        if($sql_data == null||$sql_data['merchant_cname']!=$mch_id_arr[0]){
            $ret['code'] = '10002';
            $ret['info'] = '商户不存在';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        if($sql_data['state']!=1){
            $ret['code'] = '10003';
            $ret['info'] = '商户暂未开通支付功能';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$id);
       
        $sign = $this->get_sign_str($get_data,$key);
        if ($sign!==$get_data['sign']) {
            $ret['code'] = '10004';
            $ret['info'] = '错误的签名，请检查参数';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            $url_str = $this->arrayToKeyValueString($get_data);
            Db::table('error_sign')->insert(['mch_id'=>$get_data['mch_id'],'get_data'=>$url_str]);
            exit;
        }
		
      	//判断商户是否有配置该条通道
       $this->is_have_pay_type($get_data['pay_type'],$id,$get_data['pay_amount']);

        $pay_model = model('alipay');

        $results = $pay_model->index();


        // }else{
        //     //后面做循环查询其他接口是否支持
        //     $ret['code'] = 10011;
        //     $ret['info'] = '暂不支持该银行';
        //     echo json_encode($ret,JSON_UNESCAPED_UNICODE);
        //     exit;
        // }
    }

    protected function is_array_key($get_data){
        //判断必选参；
        $key_array = ['mch_id','order_num','pay_amount','notify_url','sign','pay_type','return_url','pay_method','ext'];
        foreach ($key_array as $value) {
            if (!array_key_exists($value,$get_data)){
                return false;
            }
        }
        return true;
    }

  	    protected function is_have_pay_type($taid,$uid,$pay_amount){
        //判断支付方式是否正确
        if($taid == 'solid_code'){
            $taid = 3;
        }elseif($taid == 'alipay_scan'){
            $taid = 2;
        }elseif($taid == 'bank_code'){
            $taid = 4;
        }elseif($taid == 'wechat_native'){
            $taid = 5;
        }
		$value = Db::table('channel_type')->where('code',$taid)->limit(1)->find();
        if($value['status'] != 1){
            $ret['code'] = '10007';
            $ret['info'] = '通道临时维护中，请稍后再试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;

        }

        $amount_int = explode('-', $value['amount_int']);
        $count = count($amount_int);
        if($count == 1){
            if ($pay_amount<100||$pay_amount>$amount_int[0]*100) {
                    $ret['code'] = '10001';
                    $ret['info'] = '金额限制（1-'.$amount_int[0].'）元';
                    echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                    exit;
            }

        }elseif($count ==2){
            $min = $amount_int[0];
            $max = $amount_int[1];
            if ($pay_amount<$min*100||$pay_amount>$max*100) {
                    $ret['code'] = '10001';
                    $ret['info'] = '金额限制（'.$min.'-'.$max.'）元';
                    echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                    exit;
            }
        }elseif($count > 2){
			
            if (!in_array($pay_amount/100,$amount_int)) {
                    $ret['code'] = '10001';
                    $ret['info'] = '金额固定为（'.$value['amount_int'].'）元';
                    echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                    exit;
            }

        }

        $pay_type_status = Db::table('user_fee')
            ->where('taid',$taid)
            ->where('uid',$uid)
            ->value('status');

        if (!$pay_type_status) {

            $ret['code'] = '10006';
            $ret['info'] = '支付方式错误或未配置该支付方式';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
  
  
  
    protected function is_pay_type($pay_type,$uid){
        //判断支付方式是否正确
		
        $pay_type_status = Db::table('top_account_assets ta')
            ->field('ta.id as taid')
            ->join('user_fee uf','ta.type=uf.taid and uf.uid='.$uid,'left')
            ->where('sql_name',$pay_type)
            ->where('uf.status',1)
            ->select();

        if ($pay_type_status) {

            foreach ($pay_type_status as $value) {
                $this_taid_arr[] = $value['taid'];
            }
			
            $top_account_assets = Db::table('top_account_assets')
                ->field('id')
                ->where('id','in',$this_taid_arr)
                ->where('status',1)
                ->select();
			
          	

            if (!$top_account_assets) {
                $ret['code'] = '10007';
                $ret['info'] = '该通道维护中';
                echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                exit;
            }

            $top_account_assets = $top_account_assets[array_rand($top_account_assets)];

            return $top_account_assets['id'];
        }else{
            $ret['code'] = '10006';
            $ret['info'] = '支付方式错误或未配置该支付方式';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    public function ceshi($bank_data){
        $url_str = '';
        foreach($bank_data as $key => $value) {
            $url_str .= $key .'=' . $value . '&';
        }
        Db::table('linshi')->insert(['info1'=>'提交订单 '.date('Y-m-d H:i:s'),'info2'=>$url_str]);
    }
  
  	 public function trigger(){

        $money = input('money');
        $id = input('id');
		$config = Db::table('top_account_assets')->field('receive_account,mch_id,alipay_public_key,alipay_private_key')->where('id',$id)->limit(1)->find();
        $pay_model = model('alipay');

        $pay_model->transfer_money($money,$config);

        
    }

 //唤起支付宝转账支付
    public function pre_order(){

        $token = input('token');

        $data = decrypt($token,'yunyikey');
        $data = explode('###',$data);
        //dump($data);
        if(!isset($data[1])){
            die;
        }
	
       // $order_id = substr($data[1],14);
      	$order_id = $data[1];

        $order_status = Db::name('mch_order')->where('id',$order_id)->limit(1)->find();
        

        
        if(!empty($order_status)){
            if($order_status['pay_status'] == 1){
               echo "<script>alert('二维码已过期，请重新获取');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
               die;
            }elseif($order_status['pay_status'] == 2){
                $uid = $order_status['uid'];
              
                $time = time()-$order_status['accept_time']-600;

                if($time > 0){
                    echo "<script>alert('二维码已过期，请重新获取');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
                    die;
                }
            }   

        }else{
            
            echo "<script>alert('无效二维码');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
             die;
            
        }

		$pay_amount = $order_status['pay_amount']/100;
        $data = '{"s":"money","u":"'.$data[0].'","a":"'.$pay_amount.'","m":"'.$data[1].'"}';
      	
      	//if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
          	
            $url = 'alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data='.$data;

      	header("Location: $url");die;
 
          //}else{
           
            $this->assign([
              
                'data'=>$data,
              
                ]);
          	//$url = 'alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data='.$data;

      		//header("Location: $url");die;
            return $this->fetch('h5_order');
          
         // }
          die;
      	

  }

   public function h5_order(){

        $token = input('token');

        $data = decrypt($token,'yunyikey');
        $data = explode('###',$data);
        //dump($data);
        if(!isset($data[1])){
            die;
        }

        $order_id = substr($data[1],14);

        $order_status = Db::name('mch_order')->where('id',$order_id)->limit(1)->find();
        

        
        if(!empty($order_status)){
            if($order_status['pay_status'] == 1){
               echo "<script>alert('二维码已过期，请重新获取');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
               die;
            }elseif($order_status['pay_status'] == 2){
                $uid = $order_status['uid'];
              

                $time = time()-$order_status['accept_time']-600;

                if($time > 0){
                    echo "<script>alert('二维码已过期，请重新获取');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
                    die;
                }
            }   

        }else{
            
            echo "<script>alert('无效二维码');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
             die;
            
        }
      	$receive_account = Db::table('top_child_account')->where('id',$order_status['tcid'])->limit(1)->value('receive_account');
      	$receive_account = substr($receive_account,0,3);
		$pay_amount = $order_status['pay_amount']/100;
        $data = '{"s":"money","u":"'.$data[0].'","a":"'.$pay_amount.'","m":"'.'(收款人姓:'.$receive_account.')_'.$data[1].'"}';
      	
     
     	$this->assign([
            'data'=>$data,
            ]);

        return $this->fetch();
       // $url = 'alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data='.$data;
//dump($url);
      	//header("Location: $url");die;
        //echo '<script>window.location.href="'.$url.'"</script>';die;
  
  
  }
  
//服务器没有图片的临时生成的图片base64到收银台
    public function cash_reg(){

       	$token = input('token');
        $data = yun_decrypt($token,'yunyikey');
        $data = explode('###',$data);
        if(!isset($data[1])){
            die;
        }
        $order_id = substr($data[1],14);
      	//$order_id = $data[1];

        $order_status = Db::name('mch_order')->where('id',$order_id)->limit(1)->find();
        
        if(!empty($order_status)){
            if($order_status['pay_status'] == 1){
               echo "<script>alert('支付链接已失效，请重新获取');window.history.back(-1);</script>";
               die;
            }elseif($order_status['pay_status'] == 2){
                $uid = $order_status['uid'];
              

                $time = time()-$order_status['accept_time']-150;

                if($time > 0){
                    echo "<script>alert('支付链接已失效，请重新获取');window.history.back(-1);</script>";
                    die;
                }
            }   

        }else{
            
            echo "<script>alert('无效二维码');window.history.back(-1);</script>";
             die;
            
        }
        $order_data['img'] = $data[0];
        $order_data['order_num'] = $order_status['order_num'];
        $order_data['time'] = 150-(time()-$order_status['accept_time']);
      	$order_data['pay_amount'] = $order_status['pay_amount']/100;
		
        if($order_status['type'] == 1){
            $fetch = 'wechat';
        }elseif($order_status['type'] == 2){
            $fetch = 'alipay';
        }elseif($order_status['type'] == 1009){
            $fetch = 'alipay';
        }elseif($order_status['type'] == 4){
          	$fetch = 'bank_solid';

          
        }elseif($order_status['type'] == 1011){
          	$order_data['time'] = 300-(time()-$order_status['accept_time']);
          	$fetch = 'alipay_card';
          
        }elseif($order_status['type'] == 1012){
          	$order_data['time'] = 300-(time()-$order_status['accept_time']);
            $fetch = 'alipay';
          
        }else{

            echo "<script>alert('无效配置');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
             die;

        }
      $this->assign('order_data',$order_data);
      return $this->fetch($fetch);


  }
  
//服务器有图片的生成base64图片到收银台
      public function station(){

       $order_id = input('order_id');
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
                $time = time()-$order_status['accept_time']-150;
                if($time > 0){
                    echo "<script>alert('支付链接已失效，请重新获取');window.history.back(-1);</script>";
                    die;
                }
            }   
        }else{
            echo "<script>alert('无效二维码');window.history.back(-1);</script>";
             die;
        }
        $order_data['img'] = Db::table('top_child_account')->where('id',$order_status['tcid'])->value('public_key');
       // $order_data['img'] = imgtobase64(ROOT_PATH.'public/'.$order_data['img']);
        $order_data['order_num'] = $order_status['order_num'];
        $order_data['time'] = 150-(time()-$order_status['accept_time']);
      	$order_data['pay_amount'] = $order_status['pay_amount']/100;
		$this->assign('order_data',$order_data);
       if($order_status['type'] == 1022){
			
         //	if(isMobilePhone()){
            	//$name = Db::table('top_account_assets')->field('name,receive_account')->where('id',$order_status['pay_type'])->limit(1)->find();
              //	$this->assign('name',$name);
              //	return $this->fetch('enter_alipay1');
            
           // }else{
                $name = Db::table('top_account_assets')->where('id',$order_status['pay_type'])->value('name');
                $this->assign('name',$name);
              	return $this->fetch('enter_alipay');
           // }
         
          //$name = Db::table('top_account_assets')->where('id',$order_status['pay_type'])->value('name');
          //$this->assign('name',$name);

        }elseif($order_status['type'] == 1033){
		   $name = Db::table('top_account_assets')
             ->alias('t')
             ->field('t.receive_account,ta.private_key,ta.public_key')
             ->join('top_child_account ta','ta.pid = t.id')
             ->where('t.id',$order_status['pay_type'])
             ->limit(1)
             ->find();

           $this->assign('name',$name);		
           return $this->fetch('transf_card');

        }elseif($order_status['type'] == 3){

          return $this->fetch('solid2');

        }elseif($order_status['type'] == 1023){
          return $this->fetch('enter_wechat');

        }elseif($order_status['type'] == 4){
          return $this->fetch('enter_solid');

        }elseif($order_status['type'] == 2){
          return $this->fetch('alipay');

        }elseif($order_status['type'] == 5){
          return $this->fetch('wechat');

        }elseif($order_status['type'] == 1023){
          return $this->fetch('enter_wechat');

        }else{
            echo "<script>alert('无效配置');setTimeout(function(){document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow');},false);WeixinJSBridge.call('closeWindow');},100);</script>";
            die;

        }
  }

  
}