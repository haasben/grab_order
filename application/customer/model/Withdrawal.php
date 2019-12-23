<?php
namespace app\magent\model;
use think\Model;
use think\Db;
use think\Cache;
class Withdrawal extends Model{



      public function add_withdrawal_order($data,$uid){

      //提现手续费
      $fee = 0;

      $assets_data = Db::table('assets')->where('uid',$uid)->find();
      //该用户资金表数据

      $sum_deductions = ceil($data['amount']+$fee);
      //总扣款

      $iw_this_money = $assets_data['margin']-$sum_deductions;
      //该用户结算后余额

      if ($iw_this_money<0) {
          $ret['code'] = 80003;
          $ret['info'] = '余额不足,本次提现需要扣除'.$iw_this_money.'元';
          return json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;
      }

      $taid = 1033;
      $insert_withdrawal = [
          'uid' => $uid,
          //用户id
          'order_num' => date('YmdHis').mt_rand(1000,10000).$uid,
          //商户单号
          'pay_type' => $taid,
          //付款账户id taid

          'name' => $data['name'],
          //收款人姓名

          'add_time' => time(),
          //提交时间

          'w_amount' => $data['amount'],
          //提现金额

          'this_money' => $iw_this_money,
          //提现后当时的余额

          'this_received_money' => 0,

          'withdrawal_type' =>$taid,
          //提现类型

          'id_num' => $data['card_num'],
          //卡号

          'fee' => $fee,
          // 提现手续费

          'add_user' => '提交：'.$uid,
          // 操作人

          'status' => 2,
          // 提现手续费

          'tcid' => $taid,

          'sub_method'=>3,

        //付款通道ID
      ];

      if (isset($data['bank_name'])) {
          $insert_withdrawal['bank_name'] = $data['bank_name'];
      }


      $sql_mch_order = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$insert_withdrawal['order_num']])->value('id');
      $sql_withdrawal = Db::table('withdrawal')->where(['uid'=>$uid,'order_num'=>$insert_withdrawal['order_num']])->value('id');
      if ($sql_withdrawal||$sql_mch_order||cache('ms_withdral_order')) {
          $ret['code'] = 10008;
          $ret['info'] = '订单号重复';
          return json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;
      }
      cache('ms_withdral_order',1,30);
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
          'this_profits_money'=>Db::table('assets')->where('uid',$uid)->value('margin'),
          'this_fee'=>$insert_withdrawal['fee'],
          'this_channel_fee'=>$insert_withdrawal['fee'],
          'fee'=>0,
          'ext'=>'代付单号'.$withdrawal_id.',金额'.($insert_withdrawal['w_amount']/100).'元,手续费'.($insert_withdrawal['fee']/100).'元',
          'order_type'=>2,
          'type'=>$taid,
      ];

      if (isset($data['notify_url'])) {
          //存在回调就存入
          $insert_mch_order['notify_url'] = $data['notify_url'];
      }

      $bool1 = Db::table('mch_order')->insertGetId($insert_mch_order);
      //订单存入成功。


      $bool2 = Db::table('assets')->where('uid',$uid)->setDec('margin',$sum_deductions);
      //下级余额减少

      //减少用户对应该渠道资金

      if (!$bool1||!$bool2) {

          Db::rollback();
          $ret['code'] = '80003';
          $ret['info'] = '提交失败，请稍后再试';
          return json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;
      }else{
         Db::commit();
          $ret['code'] = '0000';
          $ret['info'] = '提交成功';
          return json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;

      }


  }













}