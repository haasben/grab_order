<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Cache;
use Qbhy\CodeScanner\CodeScanner;
class Callback extends Controller{
  
  	public function get_order(){
    	
      $gangsta = new \Google\Authenticator\Authenticator();
    //  dump($gangsta->generateSecret());die;
       $code = $gangsta->getCode('F5ZINIZQEK5WHJFU');
      
      dump($code);die;
    $order_data = Db::table('mch_order')
      	->alias('m')
      	->field('m.proxy_id,t.name,m.id')
      	->join('top_account_assets t','t.id=m.pay_type')
      	->where('m.uid',376)
      	->where('m.pay_amount',60000)
      	->where('m.pay_status',1)
      	->select();
     dump($order_data);
    
    }	
  
  	public function device(){
      	
 		$data = input();
      //Db::table('linshi')->insert(['info1'=>'APP回调时间'.date('Y-m-d H:i:s'),'info2'=>json_encode($data)]);
      //{"DeviceNo":"52013","info":"ok"}
      
      cache('DeviceNoPcode_cloudesc_Bind_'.$data['DeviceNo'],time(),180);
      
      echo microtime(true);
      
    }
  	
    	public function pay_redis(){
    	
      		$mt_rand = mt_rand(1,10);
			
      		$time = strtotime('2019-5-7 17:26:00');
      	
      		$money = Db::table('mch_order')
              ->where('proxy_id',1)
              ->where('order_type',1)
              ->where('pay_status',1)
              ->where('accept_time','>',$time)
              ->sum('pay_amount');

            //扣除平台总览页面收款总金额
            $deduction = cache('exp_deduction'.date('Ymd'));
          
          	dump($deduction);die;
            cache('exp_deduction'.date('Ymd'),$deduction+$order_data['pay_amount'],10*24*60*60);

            //扣除单独每个在跑的客户的充值总金额
            $user_deduction = cache('exp_deduction_'.$order_data['uid']);

            cache('exp_deduction_'.$order_data['uid'],$user_deduction+$order_data['pay_amount'],10*24*60*60);

            //扣除单独每个在跑的客户的实际到账总金额
            $deduction_receive = cache('exp_deduction_receive_'.$order_data['uid']);
            cache('exp_deduction_receive_'.$order_data['uid'],$user_deduction+$real_money,10*24*60*60);
		
    	
    
    }
  public function get_redis(){
  	//cache::clear();
  	//echo  json_encode(['money'=>cache('exp_deduction_584'),'receive'=>cache('exp_deduction_receive_584'),'deduction'=>cache('exp_deduction20190519'),'yes_dedution'=>cache('exp_deduction20190518')]);
  
  }
	//关闭一小时内超过三笔未回调的收款号

    public function close_account(){
      	
    	$order_data = Db::table('top_account_assets')
    		->field('id')
    		->where('uid','<>',1)
    		->where('receipt','>',2)
    		->select();
    	$id = array_column($order_data, 'id');
    	if(!empty($id)){
    		Db::table('top_account_assets')
    			->where('id','in',$id)
    			->update(['status'=>2]);
    		Db::table('top_child_account')
    			->where('pid','in',$id)
    			->update(['status'=>2]);

    	}
    	echo 'success';

    }
  
  //清空收款为回调次数记录
    public function clear_receipt(){
      	
    	$order_data = Db::table('top_account_assets')
    		->where('id','>',1)
    		->update(['receipt'=>0]);
    	
    	echo 'success';

    }
  

//定时任务，日切数据
    public function daily_data(){

      $users = Db::table('users')
        ->alias('u')
        ->field('u.id')
        ->where('u.state',90)
        ->select();
		
      
      $mch_orderModel = Db::name('mch_order'); 
      $user_feeModel = Db::name('user_fee');
      $daily_dataModel = Db::name('daily_data');

      $date = date('Y-m-d',strtotime("-1 day"));

      $between_date = [$date,date("Y-m-d")];
      foreach ($users as $k => $v) {
        $id = $v['id'];
        $type = $user_feeModel->where('uid',$id)->select();
          if(!empty($type)){
             foreach ($type as $k1 => $v1) {
              $uid = $id.','.$this->getBottomUsers($id);

              $add_data[$k1]['order_sum'] = $mch_orderModel
                  ->whereTime('accept_time','between',$between_date)
                  ->where('proxy_id','in',$uid)
                  ->where('order_type',1)
                  ->where('type',$v1['taid'])
                  ->count();
               
              if($add_data[$k1]['order_sum'] != 0){
                
              	  $add_data[$k1]['type'] =$v1['taid'];
                  $add_data[$k1]['date'] =$date;
                  $add_data[$k1]['uid'] =$id;
                  $add_data[$k1]['succ_sum'] = $mch_orderModel
                      ->whereTime('accept_time','between',$between_date)
                      ->where('proxy_id','in',$uid)
                      ->where('order_type',1)
                      ->where('pay_status',1)
                      ->where('type',$v1['taid'])
                      ->count();
                  $add_data[$k1]['money_sum'] = $mch_orderModel
                      ->whereTime('accept_time','between',$between_date)
                      ->where('proxy_id','in',$uid)
                      ->where('type',$v1['taid'])
                      ->where('order_type',1)
                      ->sum('pay_amount');
                  $add_data[$k1]['succ_money_sum'] = $mch_orderModel
                      ->whereTime('accept_time','between',$between_date)
                      ->where('proxy_id','in',$uid)
                      ->where('type',$v1['taid'])
                      ->where('order_type',1)
                      ->where('pay_status',1)
                      ->sum('pay_amount');
                //dump($add_data);
                $daily_dataModel->insertAll($add_data); 
                unset($add_data);
                echo 'success';
              }else{
              	unset($add_data[$k1]);
              }
          }
      }
  }
}


    public function getBottomUsers($id,$uids=''){
            $userList = Db::table('users')
            ->field('id,superior')
            ->where('superior',$id)
            ->select();
           foreach ($userList as $key=>$value){
                $uids .= $value['id'].',';
                $user = Db::table('users')
                    ->field('id,superior')
                    ->where('superior',$id)
                    ->select();
                if($user){
                    $uids = $this->getBottomUsers($value['id'],$uids);
                }
            }
            return $uids;
    }
  	
  //给客户加钱
    public function add_money(){
		
      die;
      $money = 300*100;
      $type = 1023;
      $uid = 376;
      $fee = 0.032;
      $receive = $money*(1-$fee);
      $fee_num = $money-$receive;
      $assetsModel = Db::name('assets');
      Db::startTrans();
      $bool1 = $assetsModel->where('uid',$uid)->setInc('recharge_sum',$money);
      $bool2 = $assetsModel->where('uid',$uid)->setInc('money',$receive);
      $bool3 = $assetsModel->where('uid',$uid)->setInc('fee_sum',$fee_num);
      $bool4 = Db::table('user_fee')->where('uid',$uid)->where('taid',$type)->setInc('money',$receive);
      $bool5 = Db::table('record')->insert([
                'date'=>date('Y-m-d H:i:s'),
               // 'content'=>$uid.'商户客户订单208支付200，给加商户余额',
        		'content'=>$uid.'客户重复扫码300元',
                'time'=>time(),
                'operator'=>1,
                'child_id'=>$uid,
                'money'=>$money,
                'type'=>9
                ]);
      
      if($bool1&&$bool2&&$bool3&&$bool5){

        Db::commit();   
        echo 'success';
      }else{
         Db::rollback();
         echo 'fail';
      }
    }
  	//查询用户当前是否有新订单
  public function get_is_order(){
      $uid = input('uid');
     // Db::table('linshi')->insert(['info1'=>1,'info2'=>$uid]);
      $id = Db::name('mch_order')->where('proxy_id', $uid)
              ->whereTime('accept_time', '-180 seconds')
              ->where('pay_status', 2)
              ->order('accept_time', 'desc')
              ->value('id');
     if($id){
          $data = [ 'code' => 1, 'id' => $id, 'msg' => '您有一笔新的订单，请注意查收！' ];
      }else{
          $data = [ 'code' => 2 ];
      }
     echo json_encode($data);
  
  }
  
  

}