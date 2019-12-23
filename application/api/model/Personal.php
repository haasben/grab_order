<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
use GatewayClient\Gateway;
class Personal extends CommonWithdrawal{


//查询是否有对应的二维码提供
    public function by_order($type){
     
      $get_data = $this->get_data;
      
      $mch_id_arr = explode('_',$get_data['mch_id']);
      $uid = $mch_id_arr[1];
        
      $merchant_cname = $mch_id_arr[0];

      $sql_data = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->find();

      if ($sql_data!=null) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

      //判断用户是否有足够的保证金来收款
      $pay_amount = $get_data['pay_amount'];

      $users = Db::table('assets')->field('uid')->where('is_open',1)->where('margin','>',$pay_amount)->select();
      $users_arr_id = array_column($users, 'uid');
      $users_arr_id[] = 1;
	
//查询可用的收款账号
      $group = Db::table('group')->field('id')->where('status',1)->where('type',$type)->where('uid','in',$users_arr_id)->select();
      $arr_id = array_column($group, 'id');
      $top_account_assets = Db::table('top_account_assets')
        ->alias('ta')
        ->field('tc.id,tc.pid,ta.uid,ta.app_id,tc.public_key')
        ->join('top_child_account tc','ta.id=tc.pid')
         ->where('ta.status',1)
        ->where('ta.group_id','in',$arr_id)
        ->where('ta.type',$type)
        ->where('tc.amount',$pay_amount)
        ->select();
    shuffle($top_account_assets);

    $pid_data = '';
//cache::clear();die;
//查询该账号三分钟内是否有收过款并筛选出可用账号
     foreach ($top_account_assets as $k => $v) {

            if(!cache::get(THIS_URL.$v['app_id'].'_'.$pay_amount.'_'.$type)){

                  //cache($v['app_id'].'_'.$pay_amount.'_'.$type,$pay_amount,180);
                         cache::store('redis')->set(THIS_URL.$v['app_id'].'_'.$pay_amount.'_'.$type,$pay_amount,180);
                        $pid_data = $v;
                        break;
            }

        }

        if(empty($pid_data)){

            $ret['code'] = 10003;
            $ret['info'] = '操作过于频繁，请稍后再试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);die;

        }


        $add_data = [
            'uid'=>$uid,
            //用户id
            
            'order_num'=>$get_data['order_num'],
            //下级商户订单号
            
            'pay_amount'=>$get_data['pay_amount'],
            //订单金额

            'notify_url'=>$get_data['notify_url'],
            //异步回调地址

            'return_url'=>$get_data['return_url'],
            //同步跳转地址

            'ext'=>$get_data['ext'],
            //扩展信息，备注

            'pay_type'=>$pid_data['pid'],
            //支付方式，上级接口

            'tcid'=>$pid_data['id'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值
            'type'=>$type,

            'ip'=>getRealIp(),
            //用户真实IP
            'app_id'=>$pid_data['app_id'],

            'proxy_id'=>$pid_data['uid'],
        ];
        
        $result_id = Db::table('mch_order')->insertGetId($add_data);
        if (!$result_id) {
            $ret['code'] = '1111';
            $ret['info'] = '创建订单失败，请稍后再试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }


        $order_id = $this->set_order_id($add_data['accept_time'],$result_id);

        $ret['code'] = '0000';
        $ret['info'] = '创建订单成功';
        $ret['pay_amount'] = $get_data['pay_amount'];
        $ret['data'] = THIS_URL.'/station/order_id/'.$order_id.'.html';

        $sql_data  = Db::table('users')->field('merchant_cname,key')->where('id',$uid)->find();
        $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$uid);
          
        $ret['sign'] = $this->mch_sign_str($ret,$key);

        echo json_encode($ret,JSON_UNESCAPED_UNICODE);

   }
  
  //查询是否有对应的二维码提供
    public function by_random_order($type){
      //cache::clear();
      $get_data = $this->get_data;
      $mch_id_arr = explode('_',$get_data['mch_id']);
      $uid = $mch_id_arr[1];
        
      $merchant_cname = $mch_id_arr[0];

      $sql_data = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->find();

      if ($sql_data!=null) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
      //判断用户是否有足够的保证金来收款
      $pay_amount = $get_data['pay_amount'];
      
     // if($get_data['mch_id'] == 'XBF_368'){
        //切换到440
       // $users_arr_id = 491;
       // $users = Db::table('assets')->where('uid',$users_arr_id)->find();
       // if($users['is_open'] == 0){
        	// $ret['code'] = 10003;
          //   $ret['info'] = '操作过于频繁，请稍后再试';
         //    echo json_encode($ret,JSON_UNESCAPED_UNICODE);die;
       // }
      //}else{
      	 $users = Db::table('assets')
         	 ->alias('a')
             ->field('a.uid')
           	 ->join('user_fee uf','uf.uid = a.uid')
           	 ->where('uf.status',1)
             ->where('a.is_open',1)
             ->where('a.margin','>',$pay_amount)
           	// ->where('a.uid','not in',[493])
			 //->where('a.uid','in',[489,493])
           	 ->order('rand()')
      		 ->limit(10)
             ->select();
         $users_arr_id = array_column($users, 'uid');
      	//Db::table('linshi')->insert(['info1'=>'收款账号数组 '.date('Y-m-d H:i:s'),'info2'=>json_encode($users_arr_id)]);
      	//$mt_rand = mt_rand(2,10);
      
      	  if($pay_amount>149900){
           	//指定多少金额订单那个码商来收
           $our_app_id = cache::get('our_app_id'.$type);
           //if(!$our_app_id){
           if(!$our_app_id && $type == 1023){
             	cache::set('our_app_id'.$type,1,10);
             	//$users_arr_id = [493];
           }

      //}
       

      if($type == 1022){
           		
          //$users_arr_id = [409,410,412,414,415,416,417,418,419,421,427,430,442,1,435];
        //$users_arr_id = [1,435,479];

     }
}
     
    $users_arr_id[] = 1;
      
    if(!in_array($type,[1032,1022,1033])){
        if($pay_amount <50000){
            $where['ta.low_mode'] = 1;
        }else{
            $where['ta.low_mode'] = 2;

        }
    }else{
      	$where = '';
    
    }

//查询可用的收款账号
      $group = Db::table('group')->field('id,child_id,uid')->where('status',1)->where('type',$type)->where('uid','in',$users_arr_id)->select();
      $arr_id = array_column($group, 'id');

      $top_account_assets = Db::table('top_account_assets')
      ->alias('ta')
      ->field('tc.id,tc.pid,ta.uid,ta.app_id,tc.public_key,tc.fee,ta.group_id')
      ->join('top_child_account tc','ta.id=tc.pid')
      ->where('ta.status',1)
      ->where('ta.type',$type)
      ->where($where)
      ->where('ta.is_clerk','<>',1)
      ->where('ta.group_id','in',$arr_id)
      ->order('rand()')
      ->limit(16)
      ->select();
     //dump($top_account_assets);die;
    shuffle($top_account_assets);



    $pid_data = '';
	$con_mode = Db::table('channel_type')->where('code',$type)->limit(1)->value('con_mode');
//查询该账号三分钟内是否有收过款并筛选出可用账号
     foreach ($top_account_assets as $k => $v) {
       		//高并发模式
       		if($con_mode == 1){
            	 if(!cache::get(THIS_URL.$v['app_id'].$pay_amount.$type) && !cache::get(THIS_URL.$v['app_id'].$type)){
                   		
                     cache::store('redis')->set(THIS_URL.$v['app_id'].$pay_amount.$type,$pay_amount,180);
                       if($type == 1033){
                          cache::store('redis')->set(THIS_URL.$v['app_id'].$pay_amount.$type,$pay_amount,300);
                       }
                      
                      $pid_data = $v;
                      break;
                  
           		}
            
            }else{
            //低并发模式
              if(!cache::get(THIS_URL.$v['app_id'].$pay_amount.$type) && !cache::get(THIS_URL.$v['app_id'].$type) ){
                // 
                    cache::store('redis')->set(THIS_URL.$v['app_id'].$type,1,180);
                    $pid_data = $v;
                    break;
              }
            }
       	
       
        }
        if(empty($pid_data)){

            $ret['code'] = 10003;
            $ret['info'] = '操作过于频繁，请稍后再试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);die;

        }


        $add_data = [
            'uid'=>$uid,
            //用户id
            
            'order_num'=>$get_data['order_num'],
            //下级商户订单号
            
            'pay_amount'=>$get_data['pay_amount'],
            //订单金额

            'notify_url'=>$get_data['notify_url'],
            //异步回调地址

            'return_url'=>$get_data['return_url'],
            //同步跳转地址

            'ext'=>$get_data['ext'],
            //扩展信息，备注

            'pay_type'=>$pid_data['pid'],
            //支付方式，上级接口

            'tcid'=>$pid_data['id'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值
            'type'=>$type,

            //'ip'=>getRealIp(),
            //用户真实IP
            'app_id'=>$pid_data['app_id'],

            'proxy_id'=>$pid_data['uid'],
        ];

       Db::startTrans();
        $result_id = Db::table('mch_order')->insertGetId($add_data);
        if (!$result_id) {
            $ret['code'] = '1111';
            $ret['info'] = '创建订单失败，请稍后再试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
      	if($type == 1032){
        	
          	Db::table('content')->insert([
            	'chatcontent'=>'您好，您已进入客服模式充值，我们支持微信、支付宝、银行卡转账等多种支付方式，您需要哪一种？',
              	'chatname'=>'客服：'.$pid_data['uid'],
              	'chatuserid'=>$pid_data['uid'],
              	'ctime'=>time(),
              	'group_id'=>$result_id
            ]);
          	Gateway::$registerAddress = '127.0.0.1:1238';
  			Gateway::sendToUid($pid_data['uid'], json_encode(['type'=>'new_order','order_id'=>$result_id]));
          	
        
        }
      
      
      	if($pid_data['uid'] != 1){
          //收单便扣除用户的保证金
            // $channel_fee = $pid_data['fee'];
            // $this_channel_fee = round($add_data['pay_amount']*$channel_fee);
            // $received = $add_data['pay_amount'] - $this_channel_fee;
            $assetsModel = Db::name('assets');
            //扣除保证金
            $bool11 = $assetsModel->where('uid',$pid_data['uid'])->setDec('margin',$add_data['pay_amount']);
            //增加冻结金额
            $bool12 = $assetsModel->where('uid',$pid_data['uid'])->setInc('freeze',$add_data['pay_amount']);
          	$bool10 = Db::name('record')->insert([
                'date'=>date('Y-m-d H:i:s'),
                'content'=>'派发订单',
                'time'=>time(),
                'operator'=>1,
                'child_id'=>$add_data['proxy_id'],
                'money'=>-$add_data['pay_amount'],
               	'freeze_money'=>$add_data['pay_amount'],
               	'type'=>7
                ]);

            //$bool13 = $assetsModel->where('uid',$pid_data['uid'])->setInc('recharge_sum',$add_data['pay_amount']);
            if($bool11&&$bool12&&$bool10){
                Db::commit(); 
            }else{
                Db::rollback();
              $ret['code'] = '1111';
              $ret['info'] = '创建订单失败，请稍后再试';
              echo json_encode($ret,JSON_UNESCAPED_UNICODE);
              exit;

            }

        }else{
            Db::commit(); 
        }

		Db::table('top_account_assets')->where('id',$pid_data['pid'])->setInc('receipt');
        $order_id = $this->set_order_id($add_data['accept_time'],$result_id);

        $ret['code'] = '0000';
        $ret['info'] = '创建订单成功';
        $ret['pay_amount'] = $get_data['pay_amount'];
      
      	//if($get_data['pay_method'] == 'mobile'){
        	
         // $ret['data'] = THIS_URL.$pid_data['public_key'];
        
        //}else{
        	
      	  if($type == 1032){
          	 $ret['data'] = THIS_URL.'/chat/'.$result_id.'.html';
          }else{
          	 $ret['data'] = THIS_URL.'/station/order_id/'.$order_id.'.html';
          }
          
        
        //}
        

        $sql_data  = Db::table('users')->field('merchant_cname,key')->where('id',$uid)->find();
        $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$uid);
          
        $ret['sign'] = $this->mch_sign_str($ret,$key);

        echo json_encode($ret,JSON_UNESCAPED_UNICODE);

   }
  
  

  
//个人产码回调
    public function person_notify_url(){

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
	//dump($back_data);die;
        if(empty($order_data)){
          	
          	if($pay_money < 100){
            	return 'fail';
            }
          
            Db::table('drop_order')->insert([
                'money'=>$pay_money,
                'pay_time'=>date('Y-m-d H:i:s',$pay_time),
                'app_id'=>$back_data['app_id'],
              	'type'=>$type

            ]);die;
        }

        $order_id = $order_data['id'];

        $trade_no = '0000';
		
      	$receipt_amount = $pay_money;
          	//实际收款金额
        $order_data['receipt_amount'] = $receipt_amount;
      	
        $channel_fee = Db::table('top_child_account')->where('id',$order_data['tcid'])->value('fee');
                //该通道手续费率
        $this_channel_fee = round($order_data['pay_amount']*$channel_fee);

        $received = $order_data['pay_amount'] - $this_channel_fee;

        $notify_update = $this->notify_update($order_data,$received,$trade_no,$pay_time);
      	
        if ($notify_update['code']) {
            if(in_array($order_data['type'],[2,3,5])){
              
              	cache::store('redis')->set(THIS_URL.$order_data['app_id'].'_'.$pay_money.'_'.$type,NULL);
            	
            }else{
            
            	cache::store('redis')->set(THIS_URL.$order_data['app_id'].$pay_money.$type,NULL);
            }
            return $order_id;

        }else{
            $this->linshi_spl(date('Y-m-d H:i:s').'写入数据失败','写入数据失败');
            echo 'FAIL';
            die;
        }
    }
  	

  //商户自己的机密方式
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