<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Alipay extends CommonWithdrawal{
    public $taid;
    public $top_data;

 //下单分发控制器
    public function index(){

        //存入数据
        $get_data = $this->get_data;
      	//if($get_data['mch_id'] == 'M_376'){
        	
         	//$get_data['pay_type'] = '1023';
        	
       // }
      	
      
      	if(in_array($get_data['mch_id'],[ 'M_376','YBB_461','PAWLKJ_370','WZW_464','BS_463','HFDY_453','BJHZF_371','JSZG_469','WNS_473','TMYL_462','XBF_368','ZS_2'])){
          	if($get_data['pay_type'] == 1029 || $get_data['pay_type'] == 1025){
            	$get_data['pay_type'] = '1023';
            }
          
          	if(in_array($get_data['mch_id'],['PAWLKJ_370','M_376'])){
            		
              if($get_data['pay_amount']>399900){
                  $our_app_id = cache::get('our_type_app_id1032');
                 //if(!$our_app_id){
                 if(!$our_app_id && $get_data['pay_type'] == 1023){
                      cache::set('our_type_app_id1032',1,200);
                      //$users_arr_id = [493];
                      //$get_data['pay_type'] = 1032;
                 }
              }
          }
          
         
        }elseif($get_data['mch_id'] == 'KKZFPT_475'){

          	if($get_data['pay_amount']>499900){
                  $our_app_id = cache::get('our_type_app_id103');
                 if(!$our_app_id && $get_data['pay_type'] == 1023){
                      cache::set('our_type_app_id103',1,60);
                      //$get_data['pay_type'] = 1032;
                 }
              }


        
        }
 //Db::startTrans();        
        if($get_data['pay_type'] == 'wechat'){
            $type = 1;
            $this->wechat_scan($type,$get_data);exit;
            

        }elseif(in_array($get_data['pay_type'], ['1025'])){
			 switch ($get_data['pay_type']) {
                  case '1025':
                      $pay_type = '1023';
                      break;
            	}	
          
          
            $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

            $mch_data = [
                'mch_id'=>$this->mch_id,
                'private_key'=>$this->private_key,
                'pay_url'=>$this->pay_url,
            ];
			
            $didaAlipayH5 = Model('Yunyi');
            $didaAlipayH5->by_order($pay_data,$mch_data,$pay_type);exit;

        }elseif(in_array($get_data['pay_type'], ['1022','1023','4','1032','1033'])){
				$skzf = Model('Personal');
       
            	$skzf->by_random_order($get_data['pay_type']);exit;
        }elseif($get_data['pay_type'] == '1024'){
          //Db::startTrans();
                switch ($get_data['pay_type']) {
                  case '1024':
                    $productType = '14000301';
                    break;

                }
                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $skzf = Model('Sywgdf');
                $skzf->by_order($pay_data,$mch_data,$productType);exit;


        }elseif(in_array($get_data['pay_type'], ['2','3','5'])){
				$skzf = Model('Personal');
            	$skzf->by_order($get_data['pay_type']);exit;
        }elseif($get_data['pay_type'] == '1031'){

                $pay_data = $this->add_official_public_data($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                ];
                $skzf = Model('Freepayment');
                $skzf->by_order($pay_data,$mch_data);exit;


        }elseif($get_data['pay_type'] == '1030'){

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];
                $skzf = Model('Yinuo');
                $skzf->by_order($pay_data,$mch_data);exit;


        }elseif($get_data['pay_type'] == '1012'){

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];
                $skzf = Model('Redbag');
                $skzf->by_bag_order($pay_data,$mch_data);exit;


        }elseif($get_data['pay_type'] == '1011'){
          //Db::startTrans();
                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $skzf = Model('Redbag');
                $skzf->by_order($pay_data,$mch_data);exit;


        }elseif($get_data['pay_type'] == '1021'){

          		$pay_bankcode = '1';
                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];
                $skzf = Model('Cntpay');
                $skzf->by_order($pay_data,$mch_data,$pay_bankcode);exit;


        }elseif(in_array($get_data['pay_type'], ['1017','1018'])){
				 switch ($get_data['pay_type']) {
                  case '1017':
                  $pay_bankcode = '904';
                      break;
                  case '1018':
                     
                      $pay_bankcode = '901';
                      break;
            	}

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $didaAlipayH5 = Model('Baoxun');
                $didaAlipayH5->by_order($pay_data,$mch_data,$pay_bankcode);exit;

        }elseif($get_data['pay_type'] == '1019'){
          //Db::startTrans();

                $pay_bankcode = 'weixin_qr';

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $skzf = Model('Yifu97pay');
                $skzf->by_order($pay_data,$mch_data,$pay_bankcode);exit;


        }elseif(in_array($get_data['pay_type'], ['1013','1014'])){
				 switch ($get_data['pay_type']) {
                  case '1013':
                  $pay_bankcode = 'NXYSWX';
                      break;
                  case '1014':
                      $pay_bankcode = 'NXYS';
                      break;
            	}

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $didaAlipayH5 = Model('Nongxin');
                $didaAlipayH5->by_order($pay_data,$mch_data,$pay_bankcode);exit;

        }elseif($get_data['pay_type'] == '1029'){
          //Db::startTrans();
          		 switch ($get_data['pay_type']) {
                  case '1029':
                  $pay_bankcode = 'unified.trade.native';
                      break;

            	}
          
                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);
          		$mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];
                $skzf = Model('Hwcpay');
                $skzf->by_order($pay_data,$mch_data,$pay_bankcode);exit;


        }elseif($get_data['pay_type'] == 'alipay_transf'){
            $type = 2;
            $order_data = $this->add_public_data_alipay($type);
            $order_id = $this->set_order_id($order_data['accept_time'],$order_data['id']);
            //$order_id = $order_data['id'];
            

                $ret['code'] = '0000';
                $ret['info'] = '创建订单成功';
                $ret['pay_amount'] = $get_data['pay_amount'];

                $key = 'yunyikey';

                $urlcode = yun_encrypt($this->mch_id.'###'.$order_id,$key);

                $url = 'https://ds.alipay.com/?from=mobilecodec&scheme='.urlencode('alipays://platformapi/startapp?appId=10000007&qrcode='.THIS_URL.'/api/recharge/pre_order/token/'.$urlcode);

             // if($get_data['pay_method'] == 'pc'){
                //   $url = create_code($url,ROOT_PATH.'public/static/alipay.png');
                  
               // }elseif($get_data['pay_method'] == 'both'){

                   // $ret['url'] = $url;
                   // $url = create_code($url,ROOT_PATH.'public/static/alipay.png');

               // }elseif($get_data['pay_method'] == 'cash'){
                    $url = create_code($url,ROOT_PATH.'public/static/alipay.png');
                    $urlcode = yun_encrypt($url.'###'.$order_id,$key);
                    $url = THIS_URL.'/cash_reg/token/'.$urlcode;
 

                //}
                
                $ret['data'] = $url;
                echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                exit;

        }elseif($get_data['pay_type'] == 'solid_code'){
          
           //Db::startTrans();
          $type = 3;

          $pay_top = $this->rand_app($get_data['pay_amount'],$type);

          $order_data = $this->add_public_data($pay_top['id'],$get_data,$type,$pay_top['app_id'],$pay_top['pay_amount']);
          
   
          //$order_id = $order_data['id'];
           $order_id = $this->set_order_id($order_data['accept_time'],$order_data['id']);
          $key = 'yunyikey';
	
          
          //if($get_data['pay_method'] == 'mobile'){
          	
      			//$url = 'https://ds.alipay.com/?from=mobilecodec&scheme='.urlencode('alipays://platformapi/startapp?appId=10000007&qrcode='.$this->private_key);
          
         // }else{
            
          $url = THIS_URL.'/station/order_id/'.$order_id.'.html';

         // }

          $ret['code'] = '0000';
          $ret['info'] = '创建订单成功';
          $ret['pay_amount'] = $pay_top['pay_amount'];
          $ret['data'] = $url;
          echo json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;


        }elseif($get_data['pay_type'] == 'wechat_native'){
          
           //Db::startTrans();
          $type = 5;
          $pay_top = $this->rand_app($get_data['pay_amount'],$type);

          $order_data = $this->add_public_data($pay_top['id'],$get_data,$type,$pay_top['app_id'],$pay_top['pay_amount']);

          $order_id = $this->set_order_id($order_data['accept_time'],$order_data['id']);

          $url = THIS_URL.'/station/order_id/'.$order_id.'.html';

          $ret['code'] = '0000';
          $ret['info'] = '创建订单成功';
          $ret['pay_amount'] = $pay_top['pay_amount'];
          $ret['data'] = $url;
          echo json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;


        }elseif($get_data['pay_type'] == '1009'){
          //Db::startTrans();

                $pay_bankcode = 903;

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $skzf = Model('Skzf');
                $skzf->by_order($pay_data,$mch_data,$pay_bankcode);exit;


        }elseif($get_data['pay_type'] == '1010'){
          //Db::startTrans();
				
          		$money_arr = [1000,2000,3000,4000,5000,6000,7000,8000,9000,10000,20000,30000,40000,50000,60000,70000,80000,90000,100000,200000,300000];
          
          		if(!in_array($get_data['pay_amount'],$money_arr)){
                	$ret['code'] = '1002';
                    $ret['info'] = '金额范围在'.json_encode($money_arr).' 分';
                    echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                    exit;
                
                }
          
                $pay_bankcode = 904;

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $dspay = Model('Dspay');
                $dspay->by_order($pay_data,$mch_data,$pay_bankcode);exit;


        }elseif($get_data['pay_type'] == 'bank_code'){
          // Db::startTrans();
          $type = 4;
          $pay_top = $this->solid_bank_code_get_appid($type,$get_data['pay_amount']);

          $order_data = $this->add_solid_bank_public_data($pay_top['id'],$get_data,$type,$pay_top['app_id']);
          //$order_id = $order_data['id'];
          $order_id = $this->set_order_id($order_data['accept_time'],$order_data['id']);
          $key = 'yunyikey';

          $url = THIS_URL.'/station/order_id/'.$order_id.'.html';
          $ret['code'] = '0000';
          $ret['info'] = '创建订单成功';
          $ret['pay_amount'] = $get_data['pay_amount'];
          $ret['data'] = $url;
          echo json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;


        }elseif(in_array($get_data['pay_type'], [1016,1026,1027,1028])){
          		
          	
				 switch ($get_data['pay_type']) {
                  case '1016':
                      $pay_bankcode = 902;
                      break;
                  case '1026':
                      $pay_bankcode = 903;
                      break;
                  case '1027':
                      $pay_bankcode = 904;
                      break;
                  case '1028':
                      $pay_bankcode = 917;
                      break;

            	}

                //Db::startTrans();
                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $didaAlipayH5 = Model('Yzpay');
                $didaAlipayH5->by_order($pay_data,$mch_data,$pay_bankcode);exit;

          
          
                  
          }elseif($get_data['pay_type'] == '1020'){
          //Db::startTrans();

                $pay_bankcode = 21;

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $skzf = Model('Glalipay');
                $skzf->by_order($pay_data,$mch_data,$pay_bankcode);exit;


        
          
          
          
        }elseif($get_data['pay_type'] == '1001'){

                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $this->alipay_coupon($pay_data);exit;
        
        
        }elseif($get_data['pay_type']=='1008') {
            	
          	  $pay_data = $this->add_official_public_data($get_data['pay_type']);
				
              $didaAlipayH5 = Model('OfficialAlipay');
              $didaAlipayH5->alipay_wap($pay_data);exit;
          		
              $this->alipay_wap($pay_data);
            
          
        }elseif(in_array($get_data['pay_type'], ['1002','1003','1004','1005','1006'])){
				 switch ($get_data['pay_type']) {
                  case '1002':
                     	
                      if ($get_data['pay_amount']<10000||$get_data['pay_amount']>499900) {
                        $ret['code'] = 10001;
                        $ret['info'] = '金额限制（100-4999）元';
                        echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                  $pay_bankcode = 927;
                      break;
                  case '1003':
                      $pay_bankcode = 926;
                      break;
                  case '1004':
                      $pay_bankcode = 928;
                      break;
                  case '1005':
                      $pay_bankcode = 929;
                  case '1006':
                      $pay_bankcode = 932;
                      break;
            	}

                //Db::startTrans();
                $pay_data = $this->add_public_data_other_asise($get_data['pay_type']);

                $mch_data = [
                    'mch_id'=>$this->mch_id,
                    'private_key'=>$this->private_key,
                    'pay_url'=>$this->pay_url,
                ];

                $didaAlipayH5 = Model('DidaAlipayH5');
                $didaAlipayH5->by_order($pay_data,$mch_data,$pay_bankcode);exit;

        }else{
        
           $ret['code'] = 10001;
             $ret['info'] = '支付方式配置错误';
             echo json_encode($ret,JSON_UNESCAPED_UNICODE);
             exit;
        
        }


    }

//查询可用的固码收款账号
    public function solid_bank_code_get_appid($type,$pay_amount){
        $top_account_assetsModel = Db::name('top_account_assets');
        $this->top_data = $top_account_assetsModel->where('status',1)->where('type',$type)->select();
        shuffle($this->top_data);
        $top_data  = $this->top_data;
        foreach ($top_data as $k => $v) {
            if(!cache::get($v['app_id'].'solid_bank_'.$type.$pay_amount)){
                cache::store('redis')->set($v['app_id'].'solid_bank_'.$type.$pay_amount,1,180);
                        $id = $v['id'];
                        $app_id = $v['app_id'];
                        break;
            }

        }
        if(empty($id)){

            $ret['code'] = '10003';
            $ret['info'] = '操作过于频繁，请稍后再试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);die;

          

        }else{

            return ['id'=>$id,'app_id'=>$app_id];

        }

    }
  
  
//个码微信扫码
    public function wechat_scan($type,$get_data){

        $order_data = $this->add_public_data_alipay($type);
        $order_id = $this->set_order_id($order_data['accept_time'],$order_data['id']);
        //$order_id = $order_data['id'];
        $this->set_wechat_redis($get_data['pay_amount'],$order_id,$this->app_id);
      	//dump($this->app_id);
        while(!cache($order_id)){};
        
        $url = cache($order_id);
		//dump($url);
        $ret['code'] = '0000';
        $ret['info'] = '创建订单成功';
        $ret['pay_amount'] = $get_data['pay_amount'];

        $url = create_code($url,ROOT_PATH.'public/static/wechat.png');
        //if($get_data['pay_method'] == 'cash'){
              $key = 'yunyikey';
                //$url = create_code($url,ROOT_PATH.'public/static/wechat.png');
                $urlcode = yun_encrypt($url.'###'.$order_id,$key);
                $url = THIS_URL.'/cash_reg/token/'.$urlcode;


           // }
      
        $ret['data'] = $url;
        echo json_encode($ret,JSON_UNESCAPED_UNICODE);
        exit;


    }
//虎云支付宝红包通道
    public function alipay_coupon($back_data){

      $get_data = $this->get_data;
      $order_id = $this->set_order_id($back_data['accept_time'],$back_data['id']);

      $pay_url = $this->pay_url;
      $pay_data = [
        'version'=>'1.0',
        'customerid'=>$this->mch_id,
        'total_fee'=>number_format($get_data['pay_amount']/100, 2, '.', ''),
        'sdorderno'=>$order_id,
        'userid'=>1,
        'paytype'=>'alipaywap',
        'notifyurl'=>THIS_URL.'/api/Notify/pay_alipay_coupon.html',//异步回调地址,
        'returnurl'=>THIS_URL."/api/Returnurl/PayRzzf2/orderId/".$order_id.'.html',//同步跳转地址,
      ];


      $pay_data['sign'] = md5('version=' . $pay_data['version'] . '&customerid=' . $pay_data['customerid']. '&userid='.$pay_data['userid'] . '&total_fee=' . $pay_data['total_fee'] . '&sdorderno=' . $pay_data['sdorderno'] . '&notifyurl=' . $pay_data['notifyurl'] . '&returnurl=' . $pay_data['returnurl'] . '&' . $this->private_key);


      $html_str = '<form id="myForm" action="'.$pay_url.'" method="post">';

      foreach ($pay_data as $key => $value) {
            $html_str .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }

      $html_str .= '</form><script>document.getElementById("myForm").submit();</script>';

      //dump($html_str);die;
      echo $html_str;
      die;


    }

    public function huyun_notify_url(){

        $post_data = input();
        $order_id = $this->get_order_id($post_data['sdorderno']);

        $this->notify_child_account($order_id);


        $sign = $this->get_sign_str_hy($post_data,$this->private_key);



        if($sign == $post_data['sign']) {//验证成功
            //请在这里加上商户的业务逻辑程序代码
            //商户订单号

            //支付宝交易号/支付宝交易凭证号
            $trade_no = '0000';

            //交易状态
            $trade_status = $post_data['status'];

            $pay_time = time();
            //付款时间
            
            $total_amount = $post_data['total_fee']*100;
            //订单金额
           

            if ($trade_status == '1') {
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

    public function huyun_query_order($order_data,$key){
    

    $get_data = $this->get_data;
    $this->notify_child_account($order_data['id']);
    $order_id = $this->set_order_id($order_data['accept_time'],$order_data['id']);
    $query_url = 'https://aa.39team.com/apiorderquery';

    $query_data = [
            'customerid'=>$this->mch_id,//商户支付KEY
          
            'sdorderno'=>$order_id,//订单ID

            'reqtime'=>date('Y-m-d H:i:s',$order_data['accept_time']),//订单请求时间

      ];

        $query_data['sign'] = $sign=md5('customerid='.$query_data['customerid'].'&sdorderno='.$query_data['sdorderno'].'&reqtime='.$query_data['reqtime'].'&'.$this->private_key);

        $data = json_decode($this->send_query_post_curl($query_url,http_build_query($query_data)),true);

        if($data['resultCode'] == '0000'){
      if($data['orderStatus'] == 'FAILED'){
              $code = '1111';
              $info = '支付超时';
            
            }elseif($data['orderStatus'] == 'WAITING_PAYMENT'){
              $code = '1111';
              $info = '支付中';
            
            }else{
              $code = '0000';
              $info = '支付成功';
            }
    
         $back_data = [
            'code' => $code,
            'info'=>$info,
            'mch_id'=>$get_data['mch_id'],
            'order_num'=>$get_data['order_num'],
            'pay_type'=>$order_data['sql_name'],
            'pay_amount'=>$order_data['pay_amount'],
            'fee'=>$order_data['this_fee'],
        ];
          
        $back_data['sign'] = $this->get_sign_str($back_data,$key);
          
        }else{
          $code = '1111';
            $info = $data['errMsg'];
            $back_data = [
              'code' => $code,
              'info'=>$info,

          ];
            
        }

        echo json_encode($back_data,JSON_UNESCAPED_UNICODE);
        exit;
    
    }


  
//虎云代付通道
    public function huyun_withdrawal(){
       
        $get_data = $this->get_data;
        if ($get_data['pay_amount']<10000||$get_data['pay_amount']>5000000) {
             $ret['code'] = 10001;
             $ret['info'] = '金额限制（100-50000）元';
             echo json_encode($ret,JSON_UNESCAPED_UNICODE);
             exit;
         }

        $pay_type = $get_data['pay_type'];
     	
        $taid = Db::table('top_account_assets')->where('type',$pay_type)->limit(1)->value('id');
      
        $productType = 'alipay_coupon';

        $fee = 200;
        //当前代付手续费(分为单位)
        
        $this->available_money($fee);

        $ret_data = $this->add_withdrawal_order($taid,$fee);
        
        $pay_url = 'https://aa.39team.com/apicash';
        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);
        //订单号之前加上了该订单的生成时间

        $pay_data = [
            'customerid'=>$this->mch_id,//商户支付KEY

            'sdorderno'=>$order_id,

            'total_fee'=>number_format(($get_data['pay_amount']/100+$fee/100),2),//订单金额单位元

            'bankcode'=>$get_data['bank_no'],//银行编码

            'accountname'=>$get_data['account_name'],//账户名

            'cardno'=>$get_data['account_id'],//银行卡号

            'province'=>$get_data['province'],

            'city'=>$get_data['city'],

            'branchname'=>$get_data['bank_name'],

            'notifyurl'=>THIS_URL.'/api/Withdrawal_notify/pay_alipay.html',

        ];

        $pay_data['sign'] = md5('customerid='.$pay_data['customerid'].'&sdorderno='.$pay_data['sdorderno'] .'&total_fee='.$pay_data['total_fee'].'&accountname='.$pay_data['accountname'].'&bankcode='.$pay_data['bankcode'] .'&cardno='.$pay_data['cardno'].'&province='.$pay_data['province'].'&city='.$pay_data['city'].'&branchname='.$pay_data['branchname'].'&notifyurl='.$pay_data['notifyurl'].'&'.$this->private_key);
      	
        $data = json_decode($this->send_query_post_curl($pay_url,http_build_query($pay_data)),true);
		
        if($data['status']=='200'){
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
  
  	public function withdrawal_notify(){
    	
     	$post_data = input();
        $order_id = $this->get_order_id($post_data['sdorderno']);

        $this->notify_child_account($order_id);

		$sign=md5('customerid='.$post_data['customerid'].'&status='.$post_data['status'].'&sdpayno='.$post_data['sdpayno'].'&sdorderno='.$post_data['sdorderno'].'&total_fee='.$post_data['total_fee'].'&'.$this->private_key);
		//dump($sign);die;
		
        if($sign == $post_data['sign']) {//验证成功
          	$order_id = $this->get_order_id($post_data['sdorderno']);
          	 if($post_data['status']=='1'){
              
                    $pay_time = time();
                    $this->withdrawal_notify_update($order_id,$pay_time);
                    Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>1]);
                    return [
                    'order_id'=>$order_id,
                    'code'=>'0000',
                    'info'=>'交易成功',
                ];
                    
            }else{
                    $trade_no = Db::table('mch_order')->where('id',$order_id)->where('order_type',2)->value('trade_no');
                    Db::table('withdrawal')->where('id',$trade_no)->where('status',2)->update(['ext'=>'交易失败']);
            		return [
                      'order_id'=>$order_id,
                      'code'=>'1111',
                      'info'=>'失败',
                  	];
        		}
          	
          
          
          
        }else{
        
        	echo 'fail';die;
        }
    
    
    }
  //虎云代付订单查询
      public function huyun_query_withdrawal_order($ret_data){

        $this->notify_child_account($ret_data['order_id']);

        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);

        $pay_url = 'https://aa.39team.com/apicashquery';

         $pay_data = [
            'customerid'=>$this->mch_id,//商户支付KEY
            'sdorderno'=>$order_id,//订单ID
        ];

        $pay_data['sign'] = md5('customerid='.$pay_data['customerid'].'&sdorderno='.$pay_data['sdorderno'].'&'.$this->private_key);
      
        $data = json_decode($this->send_query_post_curl($pay_url,http_build_query($pay_data)),true);

        if($data['status']=='200'){
          	  $data = $data['data'];
              $pay_time = '';
              $order_id = $ret_data['order_id'];
              $order_data = Db::table('mch_order')->field('uid,order_num,pay_amount,pay_time,notify_url,pay_status,trade_no,this_fee')->where('id',$order_id)->find();
              if ($data['is_state'] == '1') {
                  	$code = '0000';
                    $data['remitStatus'] = 'SUCCESS';
                  	$info = '打款成功';
                    $pay_time = time();
                    $this->withdrawal_notify_update($order_id,$pay_time);
                    Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>1]);
                    

                }else{
                  $code = '1111';
                  if($data['is_state'] == '4'){
                      $info = '打款中';
                      $data['remitStatus'] = 'REMITTING';
                    }elseif($data['is_state'] == '3'){
                      $info = '打款失败';
                      $data['remitStatus'] = 'REMIT_FAIL';
                    }elseif($data['is_state'] == '0'){
                      $info = '审核中';
                      $data['remitStatus'] = 'WAIT_CONFIRM';
                    }
                
                    $trade_no = Db::table('mch_order')->where('id',$order_id)->where('order_type',2)->value('trade_no');
                    Db::table('withdrawal')->where('id',$trade_no)->where('status',3)->update(['ext'=>$data['retmsg']]);

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
  //虎云轮循查询代付订单付款状态
  	    public function huyun_query_withdrawal_order_status($ret_data){

        $this->notify_child_account($ret_data['order_id']);

        $order_id = $this->set_order_id($ret_data['time'],$ret_data['order_id']);

        $pay_url = 'https://aa.39team.com/apicashquery';

         $pay_data = [
            'customerid'=>$this->mch_id,//商户支付KEY
            'sdorderno'=>$order_id,//订单ID
        ];

        $pay_data['sign'] = md5('customerid='.$pay_data['customerid'].'&sdorderno='.$pay_data['sdorderno'].'&'.$this->private_key);
      
        $data = json_decode($this->send_query_post_curl($pay_url,http_build_query($pay_data)),true);
          
         if($data['status']=='200'){
          	  $data = $data['data'];
              $order_id = $ret_data['order_id'];
              if ($data['is_state'] == '1') {
                  	$code = '0000';
                    $pay_time = time();
                    $this->withdrawal_notify_update($order_id,$pay_time);
                    Db::table('mch_order')->where('id',$order_id)->update(['notify_url_info'=>1]);
                }

            }

    } 
  
  
  public function curlPost($url, $postFields){
    $postFields = http_build_query($postFields);
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_POST, 1 );
    curl_setopt ( $ch, CURLOPT_HEADER, 0 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );
    $result = curl_exec ( $ch );
    curl_close ( $ch );
    return $result;
}



//虎云加密方式
public function get_sign_str_hy($data, $key){
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
  
  
  
      public function curl_post($url = '', $post_data = array())
      {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            //以文件流的形式返回，而不直接输出
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            $output = curl_exec($ch);
            curl_close($ch);
            //打印获得的数据
            return $output;

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
//查询可用的固码收款账号
    public function solid_code_get_appid($pay_amount,$type){

        $top_account_assetsModel = Db::name('top_account_assets');
        $this->top_data = $top_account_assetsModel->where('status',1)->where('type',$type)->order('rand()')->limit(10)->select();
        shuffle($this->top_data);

        $top_data  = $this->top_data;
        foreach ($top_data as $k => $v) {
            if(!cache::get($v['app_id'].'_solid_'.$type)){

                cache::store('redis')->set($v['app_id'].'_solid_'.$type,$pay_amount,180);
                        $id = $v['id'];
                        $app_id = $v['app_id'];

                        break;

            }

        }

        if(empty($id)){

            $ret['code'] = 10003;
            $ret['info'] = '操作过于频繁，请稍后再试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);die;

          

        }else{

            return ['id'=>$id,'app_id'=>$app_id,'pay_amount'=>$pay_amount];

        }




    }


//轮循查询找可用于收款的账户ID号
    public function rand_app($pay_amount,$type){

        $top_account_assetsModel = Db::name('top_account_assets');
        $this->top_data = $top_account_assetsModel->where('status',1)->where('type',$type)->select();
        shuffle($this->top_data);
        $return = $this->rand_app_money($pay_amount,$type);
    

        return $return;

    }

    public function rand_app_money($pay_amount,$type){

        $top_data  = $this->top_data;
        $id = '';
        
        foreach ($top_data as $k => $v) {
            if(!cache::get($v['app_id'].'_'.$pay_amount.'_'.$type)){
                $limit = $v['daily_limit']+$pay_amount;
                    //if($limit < $v['limit']){
                        cache::store('redis')->set($v['app_id'].'_'.$pay_amount.'_'.$type,$pay_amount,180);
                        //cache($v['app_id'].'_'.$pay_amount,$pay_amount,60);
                        $id = $v['id'];
                        $app_id = $v['app_id'];

                        break;
                   // }else{
                     //  $money = $v['limit']-$v['daily_limit'];
                    //   if($money < 50){
                        //  $top_account_assetsModel = Db::name('top_account_assets');
                         //   send_code(18408219941,substr($v['app_id'],15,10));
                          //  $top_account_assetsModel->where('id',$v['id'])->update(['status'=>2]);

                    //}
                //}
            }

        }

        if(empty($id)){
            $pay_amount -=1;
            $mant = substr($pay_amount,-1);
            if($mant == 0 ){
                $ret['code'] = 10003;
                $ret['info'] = '操作过于频繁，请稍后再试';
                echo json_encode($ret,JSON_UNESCAPED_UNICODE);die;
            
            }
    
             $data = $this->rand_app_money($pay_amount,$type);  
      		 return $data;
            //return ['id'=>$id,'app_id'=>$app_id,'pay_amount'=>$pay_amount];
          

        }else{
            //设备号已经金额
      //dump($app_id);dump($pay_amount);die;
            return ['id'=>$id,'app_id'=>$app_id,'pay_amount'=>$pay_amount];

        }

    }



    //分发金额
    public function rand_pay($pay_amount)
    {

        if(cache($v['app_id'].'_'.$pay_amount)){
            
                 switch ($mant) {
                    case '0':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                    case '9':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                    case '8':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                    case '7':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                    case '6':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                    case '5':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                     case '4':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                    case '3':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                     case '2':
                        $pay_amount -=1;
                        $data = $this->rand_app_money($pay_amount);
                        break;
                    case '1':
                        $data = '0000';
                        break;
                    default:
                        # code...
                        break;
                }
                return $data;

        }else{
            cache($pay_amount,$pay_amount,60);
            return cache($pay_amount);

        }

    }






//支付宝转账回调
    public function alipay_transfer_notify(){

        $back_data = input();

        //返回参数
        $pay_money = $back_data['money'];
        $pay_time = strtotime($back_data['time']);
        
        $id = $back_data['mark_sell'];
        $order_data = DB::table('mch_order')
            ->where('pay_status',2)
            ->where('id',$id)
            ->where('app_id',$back_data['app_id'])
            ->limit(1)
            ->find();
      // Db::table('linshi')->insert([ 'info1'=>date('YmdHis'),'info2'=>json_encode($order_data)]);
        if(empty($order_data)){
            die;
            Db::table('drop_order')->insert([
                'money'=>$pay_money,
                'pay_time'=>date('Y-m-d H:i:s',$pay_time),
                'app_id'=>$back_data['app_id']

            ]);die;
        }
    if((string)$order_data['pay_amount']!=(string)$pay_money){
                $this->linshi_spl('金额不相等 '.$id,date('Y-m-d H:i:s').'数据库金额：'.$order_data['pay_amount'].' 收到金额:'.$pay_money);
                //send_code(get_admin_phone(),'pay'.$this->taid.'2');
                echo 'FAIL';
                die;
            }
      
     if($order_data['is_proxy'] == 1){
     	$sql_data  = Db::table('users')->field('merchant_cname,state,key')->where('id',$order_data['proxy_id'])->find();
       
 	   $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$order_data['proxy_id']);
  
       $key = substr($key,(strlen($key)-8));
 
       if($key != $back_data['token']){
          $this->linshi_spl('密钥错误回调 '.$id,date('Y-m-d H:i:s').'数据库金额：'.$order_data['pay_amount'].' 收到金额:'.$pay_money);
                //send_code(get_admin_phone(),'pay'.$this->taid.'2');
                echo 'FAIL';
                die;
       }
       
     }
        $order_id = $order_data['id'];

        $trade_no = $back_data['order_id'];
        $channel_fee = Db::table('top_child_account')->where('id',$order_data['tcid'])->value('fee');
            //该通道手续费率

        $this_channel_fee = round($order_data['pay_amount']*$channel_fee);


        $received = $order_data['pay_amount'] - $this_channel_fee;
		 
        $notify_update = $this->notify_update($order_data,$received,$trade_no,$pay_time);

        if ($notify_update['code']) {

            if($order_data['is_enterprise'] == 1){
                if($notify_update['data'] == 1){
                    juhecurl(THIS_URL.'/api/recharge/trigger',['money'=>$pay_money/100,'id'=>$order_data['pay_type']],1);
                }
            }

            return $order_id;

        }else{
            $this->linshi_spl(date('Y-m-d H:i:s').'写入数据失败','写入数据失败');
            echo 'FAIL';
            die;
        }
    }
  
  //个码回调
    public function notify_url(){

        $back_data = input();

        //返回参数

        $pay_money = $back_data['money'];
        $pay_time = strtotime($back_data['time']);
        $bengin_time = $pay_time-180;
    
        $order_data = DB::table('mch_order')
            ->whereTime('accept_time','between',[$bengin_time,$pay_time])
            ->where('pay_status',2)
            ->where('app_id',$back_data['app_id'])
            ->where('pay_amount',$pay_money)
            ->order('accept_time desc')
            ->limit(1)
            ->find();
        if(empty($order_data)){
            Db::table('drop_order')->insert([
                'money'=>$pay_money,
                'pay_time'=>date('Y-m-d H:i:s',$pay_time),
                'app_id'=>$back_data['app_id']

            ]);die;
        }

        $order_id = $order_data['id'];

        $trade_no = '0000';
        
        $notify_update = $this->notify_update($order_data,$order_data['pay_amount'],$trade_no,$pay_time);
        if ($notify_update) {
            cache::store('redis')->set($order_data['app_id'].'_'.$pay_money,NULL);
           // cache($order_data['app_id'].'_'.$pay_money,NULL);
            return $order_id;

        }else{
            $this->linshi_spl(date('Y-m-d H:i:s').'写入数据失败','写入数据失败');
            echo 'FAIL';
            die;
        }
    }
   
    //固码不能固定金额回调
   public function solid_bank_notify_url(){

        $back_data = input();

        //返回参数
        $pay_money = $back_data['money'];
        $pay_time = strtotime($back_data['time']);
        $bengin_time = $pay_time-3680;
     	$type = $back_data['type'];

        $order_data = DB::table('mch_order')
            ->whereTime('accept_time','between',[$bengin_time,$pay_time])
            ->where('pay_status',2)
            ->where('app_id',$back_data['app_id'])
          	->where('pay_amount',$pay_money)
          	->where('type',$type)
            ->order('accept_time desc')
            ->limit(1)
            ->find();
        if(empty($order_data)){
            Db::table('drop_order')->insert([
                'money'=>$pay_money,
                'pay_time'=>date('Y-m-d H:i:s',$pay_time),
                'app_id'=>$back_data['app_id']

            ]);die;
        }

        $order_id = $order_data['id'];

        $trade_no = '0000';
        $order_data['pay_amount'] = $pay_money;
        $notify_update = $this->notify_update($order_data,$order_data['pay_amount'],$trade_no,$pay_time);
        if ($notify_update) {
            cache::store('redis')->set($order_data['app_id'].'solid_bank_'.$type.$order_data['pay_amount'],NULL);
            return $order_id;

        }else{
            $this->linshi_spl(date('Y-m-d H:i:s').'写入数据失败','写入数据失败');
            echo 'FAIL';
            die;
        }
    }
  
  
  //固码固定金额回调
   public function solid_notify_url(){

        $back_data = input();

        //返回参数

        $pay_money = $back_data['money'];
        $pay_time = strtotime($back_data['time']);
        $bengin_time = $pay_time-180;
     	$type = $back_data['type'];
    
        $order_data = DB::table('mch_order')
            ->whereTime('accept_time','between',[$bengin_time,$pay_time])
            ->where('pay_status',2)
            ->where('app_id',$back_data['app_id'])
            ->where('pay_amount',$pay_money)
          	->where('type',$type)
            ->order('accept_time desc')
            ->limit(1)
            ->find();
        if(empty($order_data)){
            Db::table('drop_order')->insert([
                'money'=>$pay_money,
                'pay_time'=>date('Y-m-d H:i:s',$pay_time),
                'app_id'=>$back_data['app_id']

            ]);die;
        }

        $order_id = $order_data['id'];

        $trade_no = '0000';
        
        $notify_update = $this->notify_update($order_data,$order_data['pay_amount'],$trade_no,$pay_time);
        if ($notify_update) {

            cache::store('redis')->set($order_data['app_id'].'_'.$order_data['pay_amount'].'_'.$type,NULL);
            return $order_id;

        }else{
            $this->linshi_spl(date('Y-m-d H:i:s').'写入数据失败','写入数据失败');
            echo 'FAIL';
            die;
        }
    }
  
  
  
  
  

//自动堆积到指定支付宝账户  
    public function transfer_money($total_amount,$config){

      
        //$config = Db::table('top_account_assets')->field('receive_account,mch_id,alipay_public_key,alipay_private_key')->where('id',$id)->limit(1)->find();
    

        require_once 'alipaypc/aop/AopClient.php';
        require_once 'alipaypc/aop/request/AlipayFundTransToaccountTransferRequest.php';
        $aop = new AopClient();
     // Db::name('linshi')->insert(['info1'=>5,'info2'=>json_encode($config)]);   
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $config['mch_id'];
        $aop->rsaPrivateKey = base64_decode(base64_decode($config['alipay_private_key']));
        $aop->alipayrsaPublicKey = base64_decode(base64_decode($config['alipay_public_key']));
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        

        $request = new AlipayFundTransToaccountTransferRequest ();

        $this_amount = $total_amount;
        //$total_amount = $total_amount - round($total_amount*0.006,2);
        $auto_transfer_id = Db::table('auto_transfer')->insertGetId([
                'app_id'=>$config['mch_id'],
                'total_amount'=>$total_amount,

            ]);
      
//Db::name('linshi')->insert(['info1'=>4,'info2'=>json_encode($auto_transfer_id)]); 
        $receive_account = explode('|',$config['receive_account']);
        $data=array();
        $data = [
            'out_biz_no'=>$auto_transfer_id,
            'payee_account'=>$receive_account[1],//收款账户
            'amount'=>$total_amount,
            'payee_real_name'=>$receive_account[0],//收款名
            'remark'=>'盘点',
        ];
//Db::name('linshi')->insert(['info1'=>3,'info2'=>json_encode($data)]); 
        $request->setBizContent("{" .
        "\"out_biz_no\":\"".$data['out_biz_no']."\"," .
        "\"payee_type\":\"ALIPAY_LOGONID\"," .
        "\"payee_account\":\"".$data['payee_account']."\"," .
        "\"amount\":\"".$data['amount']."\"," .
        "\"payee_real_name\":\"".$data['payee_real_name']."\"," .
        "\"remark\":\"".$data['remark']."\"" .
        "}");

        $result = $aop->execute ( $request);
Db::name('linshi')->insert(['info1'=>'企业码转账'.date('Y-m-d H:i:s'),'info2'=>json_encode($result)]);  
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
            $top_child_account = Db::name('top_account_assets');
            $top_child_account->where('mch_id',$config['mch_id'])->update(['status'=>2]);
            
          

        }
    }





  //手动收款回调方法
  public function edit_order(){
  
    $order_id = input('id');
    
    $order_data = DB::table('mch_order')
            ->where('id',$order_id)
            ->where('pay_status',2)
            ->find();
	
    if(empty($order_data)){
        $ret['code'] = 90001;
        $ret['info'] = '没有该笔未完成订单';
        echo json_encode($ret,JSON_UNESCAPED_UNICODE);
    }
	$uid = input('uid');
    if(!empty($uid)){
        Db::name('record')->insert([
            'date'=>date('Y-m-d H:i:s'),
            'content'=>'补发收款未回调订单',
            'time'=>time(),
            'operator'=>$uid,
            'child_id'=>$order_data['proxy_id'],
            'money'=>-$order_data['pay_amount'],
          	'type'=>4,
            ]);
    }
        $pay_time = time();

        $trade_no = '0000';

        $channel_fee = Db::table('top_child_account')->where('id',$order_data['tcid'])->value('fee');
            //该通道手续费率

        $this_channel_fee = round($order_data['pay_amount']*$channel_fee);


        $received = $order_data['pay_amount'] - $this_channel_fee;

        $notify_update = $this->notify_update_reissue($order_data,$received,$trade_no,$pay_time);
        
        if ($notify_update['code']) {
            if($notify_update['data'] == 1){
                if($order_data['is_enterprise'] == 1){
                   $received1 = $order_data['pay_amount']/100;
                   juhecurl(THIS_URL.'/api/recharge/trigger',['money'=>$received1,'id'=>$order_data['pay_type']],1);
                }
            }
            return $order_id;

        }else{

          echo 'FAIL';die;

        }
  }


 //设置产码数组 金额+备注  
  public function set_wechat_redis($money,$memo,$app_id){
    
    $data['money'] = $money;
    $data['memo'] = $memo;
    
    $pcode = $app_id.'_1';
    
    $this->room_result($data,$pcode);

  }  
  
  
      //缓存用户提交的数据
/**
*@param $data 需要储存的数组
*@param $param 键名
*/
public function room_result($data,$param){

    if(cache::get($param)){
            
            $result_data = cache($param);

            if(count($result_data) == count($result_data,1)){

                $result_data = [$result_data,$data];
                
            }else{

                $result_data[] = $data;
            }

            Cache::store('redis')->set($param,$result_data);
            
        }else{
            
            Cache::store('redis')->set($param,$data);
            
        }

        $data = cache::get($param);
        return $data;
}  
  
   public  function send_query_post_curl($url,$data = array()){
      $ch = curl_init();
      //设置选项，包括URL
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
      curl_setopt($ch,CURLOPT_TIMEOUT,5);
      // POST数据
      curl_setopt($ch, CURLOPT_POST, 1);
      // 把post的变量加上
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

      //执行并获取url地址的内容
      $output = curl_exec($ch);
      //释放curl句柄
      curl_close($ch);
      return $output;
  }
  
  
  
  
  
  
  
      
}