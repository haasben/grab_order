<?php
namespace app\partner\model;
use think\Model;
use think\Db;
use think\Cache;
class Withdrawal extends Model{
    public function index($post_data,$user_account_data){

        $data = Db::table('users')->where('id',$post_data['uid'])->find();

        $key = encryption($data['key'].$data['merchant_cname'].$data['id']);

        $mch_id = $data['merchant_cname'].'_'.$data['id'];

        $login_user = base64_encode($data['email']);
        
        $pay_amount = $post_data['w_amount']*100;

        return $this->yl_withdrawal($key,$login_user,$mch_id,$user_account_data,$pay_amount);
    }



    public function yl_withdrawal($key,$login_user,$mch_id,$user_account_data,$pay_amount){

        $login_user = $login_user;
        //base64编码后的登陆邮箱
        // 如：base64_encode('123456@qq.com')为MTIzNDU2QHFxLmNvbQ==；

        $key = $key;
        //商户key，API平台提供
        
        $key .= $login_user;

        $data = array(
            'mch_id'=>$mch_id,
            // Y,商户号，API平台提供
            
            'order_num'=>$mch_id.'_'.date('YmdHis'),
            //订单号

            'pay_amount'=>$pay_amount,
            // Y,提现金额 分为单位,最低10元
            
            //'pay_type'=>$sql_name,
            // Y，网银支付

            'account_name'=>$user_account_data['user_name'],
            //持卡人姓名

           // 'cert_number'=>$user_account_data['name_id'],
            //证件号码

            'account_id'=>$user_account_data['id_num'],
            //银行卡号码

            //'mobile'=>$user_account_data['phone_num'],
            //绑定手机号

            'bank_no'=>$user_account_data['bank_no'],
            //开户行号

           // 'bank_name'=>$user_account_data['address'],
            //开户地址

            'purpose'=>'提现',
            //付款目的

            'summary'=>'提现',
            //付款人付款摘要

            'notify_url'=>'',
            //异步通知地址
        );
        


        // $data['sign'] = $this->get_sign_str($data,$key);
        // //生成sign

        // $url_str = $this->arrayToKeyValueString($data);
        // //拼接url参数
        
        // $location_href = THIS_URL.'/api/Withdrawal/index';
        // //请求地址，API平台提供

        // $location_href .= '?'.$url_str;
		$account_this_fee = $pay_amount/1000;
      	$fee = $account_this_fee<10?10:$account_this_fee;

        return $this->add_withdrawal_order($data,ceil($fee));

    }

        public function add_withdrawal_order($get_data,$fee){

            
        $uid = explode('_',$get_data['mch_id'])[1];

        $assets_data = Db::table('assets')->where('uid',$uid)->find();
        //该用户资金表数据

        $sum_deductions = ceil($get_data['pay_amount']+$fee);
        //总扣款
        
        $iw_this_money = $assets_data['margin']-$sum_deductions;
        //该用户结算后余额
        
        if ($iw_this_money<0) {
            $ret['code'] = 80003;
            $ret['info'] = '余额不足,本次提现需要扣除'.$iw_this_money.'元';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }


        $withdrawal_type = Db::table('user_account')->where('id_num',$get_data['account_id'])->limit(1)->value('withdrawal_type');

        $insert_withdrawal = [
            'uid' => $uid,
            //用户id

            'order_num' => $get_data['order_num'],
            //商户单号

            //'pay_type' => 0,
            //付款账户id taid

            'name' => $get_data['account_name'],
            //收款人姓名

            'add_time' => time(),
            //提交时间

            'w_amount' => $get_data['pay_amount'],
            //提现金额

            'this_money' => $iw_this_money,
            //提现后当时的余额

            //'this_received_money' => $iw_this_received_money,
            //付款账户当时余额

            'this_received_money' => 0,

            //'withdrawal_type' => $get_data['pay_type'],
          	 'withdrawal_type' =>$withdrawal_type,
            //提现类型

            'id_num' => $get_data['account_id'],
            //卡号

            'fee' => $fee,
            // 提现手续费

            'add_user' => '提交：'.$get_data['mch_id'],
            // 操作人

            'status' => 2,
            // 提现手续费

            // 'tcid' => $tc_data['id'],

             //'tcid' => $taid,

            //付款子账户id
          
          	//'type'=>$taid,
            'sub_method'=>3,

          //付款通道ID
        ];

        if (isset($get_data['cert_number'])) {
            $insert_withdrawal['name_id'] = $get_data['cert_number'];
        }

        if (isset($get_data['mobile'])) {
            $insert_withdrawal['phone_num'] = $get_data['mobile'];
        }

        if (isset($get_data['bank_no'])) {
            $insert_withdrawal['bank_no'] = $get_data['bank_no'];
        }

        if (isset($get_data['bank_name'])) {
            $insert_withdrawal['bank_name'] = $get_data['bank_name'];
        }


        $sql_mch_order = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->value('id');
        $sql_withdrawal = Db::table('withdrawal')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->value('id');
        if ($sql_withdrawal||$sql_mch_order) {
            $ret['code'] = 10008;
            $ret['info'] = '订单号重复';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        Db::startTrans();
        //开启事务

        $withdrawal_id = Db::table('withdrawal')->insertGetId($insert_withdrawal);
        //存入提现表数据完成。然后存一条数据到订单表，减去金额，方便订单页面查询流水。

        if (!$withdrawal_id) {
            $ret['code'] = 80006;
            $ret['info'] = '提交失败，请联系上级查询';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $insert_mch_order = [
            'uid'=>$insert_withdrawal['uid'],
            'order_num'=>$insert_withdrawal['order_num'],
            'pay_amount'=>$sum_deductions,
            'pay_status'=>1,
            // 'pay_type'=>$insert_withdrawal['pay_type'],
            // 'tcid'=>$insert_withdrawal['tcid'],
            'accept_time'=>$insert_withdrawal['add_time'],
            'trade_no'=>$withdrawal_id,
            'this_money'=>$insert_withdrawal['this_money'],
            'this_received_money'=>$insert_withdrawal['this_received_money'],
            'this_profits_money'=>Db::table('assets')->where('uid',1)->value('margin'),
            'this_fee'=>$insert_withdrawal['fee'],
            'this_channel_fee'=>$insert_withdrawal['fee'],
            'fee'=>0,
            'ext'=>'代付单号'.$withdrawal_id.',金额'.($insert_withdrawal['w_amount']/100).'元,手续费'.($insert_withdrawal['fee']/100).'元',
            'order_type'=>2,
            'note_ext'=>$insert_withdrawal['withdrawal_type'],
          	// 'type'=>$taid,
        ];

        if (isset($get_data['notify_url'])) {
            //存在回调就存入
            $insert_mch_order['notify_url'] = $get_data['notify_url'];
        }

        $bool1 = Db::table('mch_order')->insertGetId($insert_mch_order);
        //订单存入成功。



        $bool2 = Db::table('assets')->where('uid',$uid)->setDec('margin',$sum_deductions);
        //下级余额减少

        //减少用户对应该渠道资金

        if (!$bool1||!$bool2) {

            Db::rollback();
            $ret['code'] = 80003;
            $ret['info'] = '提交失败，请稍后再试';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }else{
           Db::commit();
            $ret['code'] = 0000;
            $ret['info'] = '提交成功';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;

        }

         
    }


    public function get_sign_str($data, $key){
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

    public function arrayToKeyValueString($data){
        $url_str = '';
        foreach($data as $key => $value) {
            $url_str .= $key .'=' . $value . '&';
        }
        $url_str = substr($url_str,0,strlen($url_str)-1);
        return $url_str;
    }












}