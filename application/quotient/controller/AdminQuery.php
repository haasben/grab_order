<?php
namespace app\sadmin\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Validate;
class AdminQuery extends UserCommon{
    public function index(){

        $id = input('get.id');
        $order_num = input('get.order_num');
        $trade_no = input('get.trade_no');
        $info1 = input('get.info1');
        $info2 = input('get.info2');
        $linshi = input('get.linshi');

        if ($info1) {
            $data = Db::table('linshi')->where('info1','like','%'.$info1.'%')->order('id desc')->limit(100)->select();
        }

        if ($linshi) {
            $linshi = (int)$linshi;
            if ($linshi==0) {
                $linshi = 10;
            }
            if ($linshi>5000) {
                $linshi = 5000;
            }
            $data = Db::table('linshi')->order('id desc')->limit($linshi)->select();
        }

        if ($info2) {
            $data = Db::table('linshi')->where('info2','like','%'.$info2.'%')->order('id desc')->limit(100)->select();
        }

        if ($trade_no) {
            $data = Db::table('mch_order')->where('trade_no',$trade_no)->order('id desc')->limit(100)->select();
        }
        if ($order_num) {
            $data = Db::table('mch_order')->where('order_num',$order_num)->order('id desc')->limit(100)->select();
        }
        if ($id) {
            $data = Db::table('mch_order')->where('id',$id)->order('id desc')->limit(100)->select();
        }


        if (!isset($data)) {
            $data = null;
        }

        $open_time = Recently_working_day(time());

        $ztwy = Db::table('mch_order')->where('pay_time','<',$open_time)->where('tcid',4)->where('pay_status',1)->order('id','desc')->value('this_received_money');

        $ztkj = Db::table('mch_order')->where('pay_time','<',$open_time)->where('tcid',5)->where('pay_status',1)->order('id','desc')->value('this_received_money');

        $sum_money = $ztwy+$ztkj;

        $sum_w = Db::table('withdrawal')->where('add_time','>',$open_time)->where('status',1)->sum('w_amount');

        $sum_fee = Db::table('withdrawal')->where('add_time','>',$open_time)->where('status',1)->sum('fee');

        return $this->fetch('index',['data'=>$data,'kailiantongyue'=>($sum_money-$sum_fee-$sum_w)/100]);
    }


}