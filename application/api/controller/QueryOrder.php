<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Cache;
class QueryOrder extends ApiDemo{
    public function sadmin_query_order(){
        $id = input('get.id');

        $admin_login_user = session('login_user','','admin');

        $sadmin_login_user = session('login_user','','sadmin');

        if (isset($admin_login_user)) {
            $uid_where['m.uid'] = $admin_login_user['id'];
        }elseif (isset($sadmin_login_user)){
            $uid_where = 1;
        }else{
            return '订单不存在';
        }

        $order_data = Db::table('mch_order m')
            ->field('m.id,m.accept_time,m.pay_type,m.tcid,ta.sql_name')
            ->join('top_account_assets ta','ta.id=m.pay_type')
            ->where($uid_where)
            ->where('m.id',$id)
            ->where('m.order_type',1)
            ->where('m.pay_status',2)
            ->find();

        if (!$order_data) {
            return '订单不存在';
            die;
        }
        $pay_model = model($order_data['sql_name'].$order_data['pay_type']);

        $return_data = $pay_model->query_order($order_data);

        if (isset($admin_login_user)) {
            unset($return_data->fee);
            return $return_data;
        }
        return $return_data;
    }

//支付查询订单
    public function api_query_order(){

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
        
        $order_data = Db::table('mch_order m')
            ->field('m.id,m.accept_time,m.pay_type,m.tcid,ta.sql_name,m.pay_amount,m.this_fee,m.pay_status,m.type')
            ->join('top_account_assets ta','ta.id=m.pay_type')
            ->where('m.uid',$id)
            ->where('m.order_num',$get_data['order_num'])
            ->where('m.order_type',1)
            ->find();
		if ($order_data['type'] == 1001) {
          	$model = Model('Alipay');
            $model->huyun_query_order($order_data,$key);die;
        }elseif (in_array($order_data['type'],[1002,1003,1004,1005,1006])) {
            $model = Model('DidaAlipayH5');
            $model->dida_query_order($order_data,$key);die;
        }
      
      
        if (!$order_data) {
            $ret['code'] = '90001';
            $ret['info'] = '订单号不存在';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
		
      	if($order_data['pay_status'] == 1){
        	$code = '0000';
          	$info = '支付成功';
        }else{
        	$code = '1111';
          	$time = time()-$order_data['accept_time'];
          	if($time > 60){
              $code = '2222';
              $info = '支付超时';
            }else{
              $info = '支付中';
            }	
        }
      
        $back_data = [
            'code' => $code,
            'info'=>$info,
            'mch_id'=>$get_data['mch_id'],
            'order_num'=>$get_data['order_num'],
            'pay_type'=>$order_data['type'],
            'pay_amount'=>$order_data['pay_amount'],
            'fee'=>$order_data['this_fee'],
        ];
      	$back_data['sign'] = $this->get_sign_str($back_data,$key);
      
        echo json_encode($back_data,JSON_UNESCAPED_UNICODE);
        exit;
    }

  	//代付查询订单
    public function api_widthdrawal_order(){

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
        
        $order_data = Db::table('mch_order m')
            ->field('m.id,m.accept_time,m.pay_type,m.tcid,m.pay_amount,m.this_fee,m.type,m.pay_time')
            //->join('top_account_assets ta','ta.id=m.pay_type')
            ->where('m.uid',$id)
            ->where('m.order_num',$get_data['order_num'])
            ->where('m.order_type',2)
            ->find();
        if (!$order_data) {
            $ret['code'] = '90001';
            $ret['info'] = '订单号不存在';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        

        $need_data = ['order_id'=>$order_data['id'],'time'=>$order_data['accept_time']];
		
      	if($order_data['type'] == 1030){
          	$pay_model = model('Yinuo');
          	$return_data = $pay_model->yinuo_query_withdrawal_order($need_data);
        
        
       // }elseif (in_array($order_data['type'],[1002,1003,1004,1005,1006])) {
        //    $model = Model('DidaAlipayH5');
        //    $model->dida_query_withdrawal_order($order_data,$key);die;
       // }elseif (in_array($order_data['type'],[1009,1010,1011,1012])) {
          //  $model = Model('DianJiFuPay');
          //  $model->djf_query_withdrawal_order($order_data,$key);die;
        }else{
         	$status = Db::table('withdrawal')->where('order_num',$get_data['order_num'])->limit(1)->value('status');
          	if($status == 2){
            	$info = '已提交';
              	$pay_status = 'transfer';
            }elseif($status == 1){
            	$info = '已操作转账';
              	$pay_status = 'success';
            
            }else{
            	$info = '订单作废金额退回';
              	$pay_status = 'fail';
            }
          
          	 $this_money = Db::name('assets')->where('uid',$id)->value('money');
        }
      
        $back_data = [
          
              'code'=>'0000',

              'info'=>$info,

              'order_num'=>$get_data['order_num'],
              //下级商户订单号
              'pay_time'=>$order_data['pay_time'],
              //支付时间
              'pay_status'=>$pay_status,

            //该账户平台剩余余额
              'this_money'=>$this_money,

              //代付金额
              'pay_amount'=>$order_data['pay_amount'],

              //手续费
               'fee'=>$order_data['this_fee'],
                  
           ];
          $back_data['sign'] = $this->get_sign_str($back_data,$key);
          echo json_encode($back_data,JSON_UNESCAPED_UNICODE);
          exit;
    }
  
  
  
    protected function is_array_key($get_data){
        //判断必选参；
        $key_array = ['mch_id','order_num','sign'];
        foreach ($key_array as $value) {
            if (!array_key_exists($value,$get_data)){
                return false;
            }
        }
        return true;
    }

    public function ceshi($bank_data){
        $url_str = '';
        foreach($bank_data as $key => $value) {
            $url_str .= $key .'=' . $value . '&';
        }
        Db::table('linshi')->insert(['info1'=>'下级订单查询订单参数 '.date('Y-m-d H:i:s'),'info2'=>$url_str]);
    }
	     //微信扫码订单查询
     public function order_status(){

        $order_num = input('order_num');

         if(request()->isAjax()){

            $order_data = Db::name('mch_order')->where('order_num',$order_num)->limit(1)->find();

            if(!empty($order_data)){

                if($order_data['pay_status'] == 1){

                    return ['code'=>'0000','msg'=>'已支付','data'=>['order_num'=>$order_num,'return_url'=>$order_data['return_url']]];
                }else{
                    return ['code'=>'1111','msg'=>'支付中'];
                }
                
            }else{
                return ['code'=>'1111','msg'=>'订单不存在'];
            }
        }

    }
  //定时任务，修改代付订单状态
    public function query_withdrawal_order_status(){
	
        $order_data = Db::table('mch_order m')
            ->field('m.id as order_id,m.accept_time as time,m.type')
            ->join('withdrawal w','w.id=m.trade_no')
            ->where('m.order_type',2)
            ->where('m.type','in',[1030])
            ->where('w.status',2)
            //->whereTime('w.add_time','today')
            ->limit(9)
            ->order('w.add_time')
            ->select();

        if(!empty($order_data)){
          	
            foreach ($order_data as $k => $v) {
              	
                $pay_model = model('Yinuo');
                $pay_model->yinuo_query_withdrawal_order_status($v);

                sleep(3);
                
            }

        }
       
    }
  
}