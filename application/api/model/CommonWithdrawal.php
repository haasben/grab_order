<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class CommonWithdrawal extends CommonPay{
  
    public function mch_add_withdrawal_order($taid,$fee){
        $get_data = $this->get_data;

        $uid = explode('_',$get_data['mch_id'])[1];

        $assets_data = Db::table('assets')->where('uid',$uid)->find();
        //该用户资金表数据

        $sum_deductions = $get_data['pay_amount']+$fee;
        //总扣款

        $iw_this_money = $assets_data['money']-$sum_deductions;
        //该用户结算后余额

        if ($iw_this_money<0) {
            $ret['code'] = '80003';
            $ret['info'] = '商户余额不足';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $insert_withdrawal = [
            'uid' => $uid,
            //用户id

            'order_num' => $get_data['order_num'],
            //商户单号

            'pay_type' => $taid,
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
          	 'withdrawal_type' =>$taid,
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

             'tcid' => $taid,

            //付款子账户id
          
          	'bank_name'=>$get_data['bank_name'],
          //付款通道ID
          	//API提交默认为2
            'sub_method'=>2,
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


        $sql_mch_order = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->value('id');
        $sql_withdrawal = Db::table('withdrawal')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->value('id');
        if ($sql_withdrawal||$sql_mch_order) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        Db::startTrans();
        //开启事务

        $withdrawal_id = Db::table('withdrawal')->insertGetId($insert_withdrawal);
        //存入提现表数据完成。然后存一条数据到订单表，减去金额，方便订单页面查询流水。

        if (!$withdrawal_id) {
            $ret['code'] = '80006';
            $ret['info'] = '提交失败，请联系上级查询';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $insert_mch_order = [
            'uid'=>$insert_withdrawal['uid'],
            'order_num'=>$insert_withdrawal['order_num'],
            'pay_amount'=>$sum_deductions,
            'pay_status'=>1,
            'pay_type'=>$insert_withdrawal['pay_type'],
            'tcid'=>$insert_withdrawal['tcid'],
            'accept_time'=>$insert_withdrawal['add_time'],
            'trade_no'=>$withdrawal_id,
            'this_money'=>$insert_withdrawal['this_money'],
            'this_received_money'=>$insert_withdrawal['this_received_money'],
            'this_profits_money'=>Db::table('assets')->where('uid',1)->value('money'),
            'this_fee'=>$insert_withdrawal['fee'],
            'this_channel_fee'=>$insert_withdrawal['fee'],
            'fee'=>0,
            'ext'=>'代付单号'.$withdrawal_id.',金额'.($insert_withdrawal['w_amount']/100).'元,手续费'.($insert_withdrawal['fee']/100).'元',
            'order_type'=>2,
            'note_ext'=>$insert_withdrawal['withdrawal_type'],
          	'type'=>$taid,
        ];

        if (isset($get_data['notify_url'])) {
            //存在回调就存入
            $insert_mch_order['notify_url'] = $get_data['notify_url'];
        }

        $bool1 = Db::table('mch_order')->insertGetId($insert_mch_order);
        //订单存入成功。



        $bool2 = Db::table('assets')->where('uid',$uid)->setDec('money',$sum_deductions);
        //下级余额减少

        $bool3 = Db::table('assets')->where('uid',$uid)->setInc('withdrawal_sum',$sum_deductions);
        //下级累计提现金额增加

        $user_feeModel = Db::name('user_fee');
       // $bool8 = $user_feeModel->where('uid',$uid)->where('taid',$insert_withdrawal['pay_type'])->setDec('money',$sum_deductions);
        while ($sum_deductions > 0) {
          //循环减少对应通道的金额
            $user_fee_data = $user_feeModel->where('uid',$uid)->where('money','>',0)->limit(1)->find();
            if($user_fee_data['money'] < $sum_deductions){
                $bool8 = $user_feeModel->where('uid',$uid)->where('taid',$user_fee_data['taid'])->update(['money'=>0]);
                $sum_deductions -= $user_fee_data['money'];
            }else{
                $bool8 = $user_feeModel->where('uid',$uid)->where('taid',$user_fee_data['taid'])->setDec('money',$sum_deductions);
                $sum_deductions = 0;
            }
        }
        //减少用户对应该渠道资金

        if (!$bool1||!$bool2||!$bool3||!$bool8) {
            $ret['code'] = '80007';
            $ret['info'] = '提交失败，请联系上级查询';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Db::commit();
        //提交事务 移到提交代付订单后提交事务

        //$this->notify_child_account($bool1);

        $ret_data = [
            'order_id' => $bool1,
            'time' => $insert_withdrawal['add_time'],
        ];

        return $ret_data;
    }
  
//生银万国代付订单接口
    public function sywg_add_withdrawal_order($taid,$fee){
        $get_data = $this->get_data;

        $uid = explode('_',$get_data['mch_id'])[1];

                //实例化四张表
        $assetsModel = Db::name('assets');
        $top_account_assetsModel = Db::name('top_account_assets');
        $top_child_accountModel = Db::name('top_child_account');
        $user_feeModel = Db::name('user_fee');


        $assets_data = $assetsModel->where('uid',$uid)->find();
        //该用户资金表数据

        $sum_deductions = $get_data['pay_amount']+$fee;
        //总扣款

        $iw_this_money = $assets_data['money']-$sum_deductions;
        //该用户结算后余额

        if ($iw_this_money<0) {
            $ret['code'] = 80003;
            $ret['info'] = '商户余额不足';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ta_money = $top_account_assetsModel
             ->field('id,money,type')
             ->where('id',$taid)
             ->where('money','>=',$sum_deductions)
             ->find();
         //付款账户余额

         if (!isset($ta_money)) {
             //付款余额不足，正常情况绝不会触发
             $ret['code'] = 80004;
             $ret['info'] = '代付失败，请联系上级查询';
             echo  json_encode($ret,JSON_UNESCAPED_UNICODE);
             exit;
         }

         $iw_this_received_money = $ta_money['money']-$sum_deductions;

         //付款账户付款后余额

         $tc_data = $top_child_accountModel
             ->field('id,money')
             ->where('pid',$taid)
             ->where('money','>=',$sum_deductions)
             ->find();
         //付款子账户余额


         if (!isset($tc_data)) {
             //付款余额不足，正常情况绝不会触发,子账户存在多个的情况下可能触发
             $ret['code'] = 80005;
             $ret['info'] = '代付失败，请联系上级查询';
             echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
         }

		$withdrawal_type = Db::table('user_account')->where('id_num',$get_data['account_id'])->limit(1)->value('withdrawal_type');
        $insert_withdrawal = [
            'uid' => $uid,
            //用户id

            'order_num' => $get_data['order_num'],
            //商户单号

            'pay_type' => $taid,
            //付款账户id taid

            'name' => $get_data['account_name'],
            //收款人姓名

            'add_time' => time(),
            //提交时间

            'w_amount' => $get_data['pay_amount'],
            //提现金额

            'this_money' => $iw_this_money,
            //提现后当时的余额

            'this_received_money' => $iw_this_received_money,
            //付款账户当时余额

            'withdrawal_type' => $ta_money['type'],
            //提现类型
         	// 'withdrawal_type' =>$withdrawal_type,

            'id_num' => $get_data['account_id'],
            //卡号

            'fee' => $fee,
            // 提现手续费

            'add_user' => '提交：'.$get_data['mch_id'],
            // 操作人

            'status' => 2,
            // 提现手续费

            'tcid' => $tc_data['id'],
            //付款子账户id
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
        // if ($sql_withdrawal||$sql_mch_order||cache($uid.'_'.$get_data['order_num'])) {
        //     $ret['code'] = 10008;
        //     $ret['info'] = '订单号重复';
        //     echo json_encode($ret,JSON_UNESCAPED_UNICODE);
        //     exit;
        // }

        cache($uid.'_'.$get_data['order_num'],1,60*60);

        Db::startTrans();
        //开启事务

        $withdrawal_id = Db::table('withdrawal')->insertGetId($insert_withdrawal);
        //存入提现表数据完成。然后存一条数据到订单表，减去金额，方便订单页面查询流水。

        if (!$withdrawal_id) {
            $ret['code'] = 80006;
            $ret['info'] = '提交失败，请联系上级查询';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $insert_mch_order = [
            'uid'=>$insert_withdrawal['uid'],
            'order_num'=>$insert_withdrawal['order_num'],
            'pay_amount'=>$sum_deductions,
            'pay_status'=>1,
            'pay_type'=>$insert_withdrawal['pay_type'],
            'tcid'=>$insert_withdrawal['tcid'],
            'accept_time'=>$insert_withdrawal['add_time'],
            'trade_no'=>$withdrawal_id,
            'this_money'=>$insert_withdrawal['this_money'],
            'this_received_money'=>$insert_withdrawal['this_received_money'],
            'this_profits_money'=>Db::table('assets')->where('uid',1)->value('money'),
            'this_fee'=>$insert_withdrawal['fee'],
            'this_channel_fee'=>$insert_withdrawal['fee'],
            'fee'=>0,
            'ext'=>'代付单号'.$withdrawal_id.',金额'.($insert_withdrawal['w_amount']/100).'元,手续费'.($insert_withdrawal['fee']/100).'元',
            'order_type'=>2,
            'note_ext'=>$insert_withdrawal['withdrawal_type'],
          	'type'=>$ta_money['type'],
        ];

        if (isset($get_data['notify_url'])) {
            //存在回调就存入
            $insert_mch_order['notify_url'] = $get_data['notify_url'];
        }

        $bool1 = Db::table('mch_order')->insertGetId($insert_mch_order);
        //订单存入成功。


        $bool2 = $assetsModel->where('uid',$uid)->setDec('money',$sum_deductions);
        //下级余额减少

        $bool3 = $assetsModel->where('uid',$uid)->setInc('withdrawal_sum',$sum_deductions);
        //下级累计提现金额增加



         $bool4 = $top_account_assetsModel->where('id',$insert_withdrawal['pay_type'])->setDec('money',$sum_deductions);
        //上级余额减少

         $bool5 = $top_account_assetsModel->where('id',$insert_withdrawal['pay_type'])->setInc('withdrawal_sum',$sum_deductions);
        //上级累计提现总和增加



         $bool6 = Db::table('top_child_account')->where('id',$insert_withdrawal['tcid'])->setDec('money',$sum_deductions);
        //上级子账户余额减少

         $bool7 = Db::table('top_child_account')->where('id',$insert_withdrawal['tcid'])->setInc('withdrawal_sum',$sum_deductions);
        //上级子账户累计提现总和增加


        //减少用户费率表余额
        while ($sum_deductions > 0) {
            $user_fee_data = $user_feeModel->where('uid',$uid)->where('money','>',0)->limit(1)->find();
            if($user_fee_data['money'] < $sum_deductions){
                $bool8 = $user_feeModel->where('uid',$uid)->where('taid',$user_fee_data['taid'])->update(['money'=>0]);
                $sum_deductions -= $user_fee_data['money'];

            }else{
                $bool8 = $user_feeModel->where('uid',$uid)->where('taid',$user_fee_data['taid'])->setDec('money',$sum_deductions);
                $sum_deductions = 0;
            }
        }


        if (!$bool1||!$bool2||!$bool3||!$bool4||!$bool5||!$bool6||!$bool7||!$bool8) {
            $ret['code'] = 80007;
            $ret['info'] = '提交失败，请联系上级查询';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Db::commit();
        //提交事务 移到提交代付订单后提交事务

        $this->notify_child_account($bool1);

        $ret_data = [
            'order_id' => $bool1,
            'time' => $insert_withdrawal['add_time'],
        ];

        return $ret_data;
    }
	
//有代付通道商户添加代付订单
  public function add_withdrawal_order($taid,$fee){
        $get_data = $this->get_data;

        $uid = explode('_',$get_data['mch_id'])[1];

        $assets_data = Db::table('assets')->where('uid',$uid)->find();
        //该用户资金表数据

        $sum_deductions = $get_data['pay_amount']+$fee;
        //总扣款

        $iw_this_money = $assets_data['money']-$sum_deductions;
        //该用户结算后余额

        if ($iw_this_money<0) {
            $ret['code'] = 80003;
            $ret['info'] = '商户余额不足';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ta_money = Db::table('top_account_assets')
             ->field('id,money')
             ->where('id',$taid)
             ->where('money','>=',$sum_deductions)
             ->find();
         //付款账户余额
	
         if (!isset($ta_money)) {
             //付款余额不足，正常情况绝不会触发
             $ret['code'] = 80004;
             $ret['info'] = '代付失败，请联系上级查询';
             echo  json_encode($ret,JSON_UNESCAPED_UNICODE);
             exit;
         }

         $iw_this_received_money = $ta_money['money']-$sum_deductions;


         //付款账户付款后余额

         $tc_data = Db::table('top_child_account')
             ->field('id,money')
             ->where('pid',$taid)
             ->where('money','>=',$sum_deductions)
             ->find();
         //付款子账户余额

         if (!isset($tc_data)) {
             //付款余额不足，正常情况绝不会触发,子账户存在多个的情况下可能触发
             $ret['code'] = 80005;
             $ret['info'] = '代付失败，请联系上级查询';
             echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
         }

		$withdrawal_type = Db::table('user_account')->where('id_num',$get_data['account_id'])->limit(1)->value('withdrawal_type');
        $insert_withdrawal = [
            'uid' => $uid,
            //用户id

            'order_num' => $get_data['order_num'],
            //商户单号

            'pay_type' => $taid,
            //付款账户id taid

            'name' => $get_data['account_name'],
            //收款人姓名

            'add_time' => time(),
            //提交时间

            'w_amount' => $get_data['pay_amount'],
            //提现金额

            'this_money' => $iw_this_money,
            //提现后当时的余额

            'this_received_money' => $iw_this_received_money,
            //付款账户当时余额

            'withdrawal_type' => $get_data['pay_type'],
            //提现类型
         	// 'withdrawal_type' =>$withdrawal_type,

            'id_num' => $get_data['account_id'],
            //卡号

            'fee' => $fee,
            // 提现手续费

            'add_user' => '提交：'.$get_data['mch_id'],
            // 操作人

            'status' => 2,
            // 提现手续费

            'tcid' => $tc_data['id'],
            //付款子账户id
          	'sub_method'=>4
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
        if ($sql_withdrawal||$sql_mch_order||cache($uid.'_'.$get_data['order_num'])) {
            $ret['code'] = 10008;
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        cache($uid.'_'.$get_data['order_num'],1,60*60);

        Db::startTrans();
        //开启事务

        $withdrawal_id = Db::table('withdrawal')->insertGetId($insert_withdrawal);
        //存入提现表数据完成。然后存一条数据到订单表，减去金额，方便订单页面查询流水。

        if (!$withdrawal_id) {
            $ret['code'] = 80006;
            $ret['info'] = '提交失败，请联系上级查询';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $insert_mch_order = [
            'uid'=>$insert_withdrawal['uid'],
            'order_num'=>$insert_withdrawal['order_num'],
            'pay_amount'=>$sum_deductions,
            'pay_status'=>1,
            'pay_type'=>$insert_withdrawal['pay_type'],
            'tcid'=>$insert_withdrawal['tcid'],
            'accept_time'=>$insert_withdrawal['add_time'],
            'trade_no'=>$withdrawal_id,
            'this_money'=>$insert_withdrawal['this_money'],
            'this_received_money'=>$insert_withdrawal['this_received_money'],
            'this_profits_money'=>Db::table('assets')->where('uid',1)->value('money'),
            'this_fee'=>$insert_withdrawal['fee'],
            'this_channel_fee'=>$insert_withdrawal['fee'],
            'fee'=>0,
            'ext'=>'代付单号'.$withdrawal_id.',金额'.($insert_withdrawal['w_amount']/100).'元,手续费'.($insert_withdrawal['fee']/100).'元',
            'order_type'=>2,
            'note_ext'=>$insert_withdrawal['withdrawal_type'],
          	'type'=>$get_data['pay_type'],
        ];

        if (isset($get_data['notify_url'])) {
            //存在回调就存入
            $insert_mch_order['notify_url'] = $get_data['notify_url'];
        }

        $bool1 = Db::table('mch_order')->insertGetId($insert_mch_order);
        //订单存入成功。



        $bool2 = Db::table('assets')->where('uid',$uid)->setDec('money',$sum_deductions);
        //下级余额减少

        $bool3 = Db::table('assets')->where('uid',$uid)->setInc('withdrawal_sum',$sum_deductions);
        //下级累计提现金额增加



         $bool4 = Db::table('top_account_assets')->where('id',$insert_withdrawal['pay_type'])->setDec('money',$sum_deductions);
        //上级余额减少

         $bool5 = Db::table('top_account_assets')->where('id',$insert_withdrawal['pay_type'])->setInc('withdrawal_sum',$sum_deductions);
        //上级累计提现总和增加



         $bool6 = Db::table('top_child_account')->where('id',$insert_withdrawal['tcid'])->setDec('money',$sum_deductions);
        //上级子账户余额减少

         $bool7 = Db::table('top_child_account')->where('id',$insert_withdrawal['tcid'])->setInc('withdrawal_sum',$sum_deductions);
        //上级子账户累计提现总和增加

		
      	$type = Db::table('top_account_assets')->where('id',$taid)->value('type');
      
	

        $bool8 = Db::table('user_fee')->where('uid',$uid)->where('taid',$type)->setDec('money',$sum_deductions);
        //减少用户对应该渠道资金
		
        if (!$bool1||!$bool2||!$bool3||!$bool4||!$bool5||!$bool6||!$bool7||!$bool8) {
            $ret['code'] = 80007;
            $ret['info'] = '提交失败，请联系上级查询';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Db::commit();
        //提交事务 移到提交代付订单后提交事务

        $this->notify_child_account($bool1);

        $ret_data = [
            'order_id' => $bool1,
            'time' => $insert_withdrawal['add_time'],
        ];

        return $ret_data;
    }
  
  

    public function withdrawal_notify_update($order_id,$pay_time){
        //order_id订单id  pay_time付款时间

        $trade_no = Db::table('mch_order')->where('id',$order_id)->where('order_type',2)->value('trade_no');

        Db::table('mch_order')->where('id',$order_id)->where('order_type',2)->where('pay_time',0)->update(['pay_time'=>$pay_time]);

        Db::table('withdrawal')->where('id',$trade_no)->where('status',2)->update(['status'=>1]);

    }
}