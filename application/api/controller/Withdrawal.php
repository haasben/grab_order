<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Cache;


//代付接口自动切换，重点是判断当前通道的余额是否充足

class Withdrawal extends ApiDemo{
    public function index(){
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
            $ret['code'] = '10002';;
            $ret['info'] = '商户不存在';
          	
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        $id = $mch_id_arr[1];
        $sql_data  = Db::table('users')->field('merchant_cname,key,email,ip')->where('id',$id)->find();

        if($sql_data == null||$sql_data['merchant_cname']!=$mch_id_arr[0]){
            $ret['code'] = '10002';
            $ret['info'] = '商户不存在';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        

        $key = encryption($sql_data['key'].$sql_data['merchant_cname'].$id);
        
        $key .= base64_encode($sql_data['email']);

        $sign = $this->get_sign_str($get_data,$key);

        if ($sign!==$get_data['sign']) {
            $ret['code'] = '10004';
            $ret['info'] = '错误的签名，请检查参数';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            $url_str = $this->arrayToKeyValueString($get_data);
            Db::table('error_sign')->insert(['mch_id'=>$get_data['mch_id'],'get_data'=>$url_str]);
            exit;
        }
		$ip = getRealIp();
      	$ip_arr = explode(',',$sql_data['ip']);
        if(!in_array($ip,$ip_arr)){
            $ret['code'] = '10003';
            $ret['info'] = 'ip不在白名单内';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;

        }
      	
       
       	 $pay_type = $get_data['pay_type'];
		
        if(in_array($pay_type,[1030])){

            $Yinuo = Model('Yinuo');

            $Yinuo->yinuo_withdrawal();


        }else{


        //elseif($pay_type == 1001){

        //     $pay_model = Model('alipay');


        //     $pay_model->huyun_withdrawal();
        // }elseif(in_array($pay_type,['pay'])){

        //     $pay_model = Model('Sywgdf');

        //     $pay_model->sywg_withdrawal();

        // }elseif(in_array($pay_type,['solid_code','1012']){

            $pay_model = Model('Widthdrawal');

            $pay_model->mch_withdrawal();
        // }else{

        //     $ret['code'] = '10008';
        //     $ret['info'] = '代付渠道错误';
        //     echo json_encode($ret,JSON_UNESCAPED_UNICODE);
        //     exit;


        }
    }

    protected function is_array_key($get_data){
                //判断必选参；
      if(in_array($get_data['pay_type'],[1030])){
          	$key_array = ['mch_id','order_num','pay_amount','bank_name','pay_type','account_name','account_id','sign'];

        }else{
        
        	$key_array = ['mch_id','order_num','pay_amount','bank_name','pay_type','account_name','account_id','sign','bank_no','notify_url'];
         }
        foreach ($key_array as $value) {
            if (!array_key_exists($value,$get_data)){
                return false;
            }
        }
        return true;
    }

    protected function is_user_money($pay_amount,$pay_type,$uid){
        //判断支付方式是否正确

        if ($pay_amount<100) {
            $ret['code'] = '80002';
            $ret['info'] = '提现金额最少1元';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 该用户该通道剩余余额
        $user_fee_data = Db::table('user_fee uf')
            ->field('ta.id,uf.money')
            ->join('top_account_assets ta','ta.id=uf.taid')
            ->where('ta.sql_name',$pay_type)
            ->where('uf.uid',$uid)
            ->find();

        if ($user_fee_data) {
            if ($user_fee_data['money']<$pay_amount) {
                $ret['code'] = '80001';
                $ret['info'] = '商户余额不足';
                echo json_encode($ret,JSON_UNESCAPED_UNICODE);
                exit;
            }

            return $user_fee_data['id'];
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
        Db::table('linshi')->insert(['info1'=>'代付get接收参数 '.date('Y-m-d H:i:s'),'info2'=>$url_str]);
    }
}