<?php
namespace app\madmin\controller;
use think\Controller;
use think\Db;
use think\Model;
class Recharge extends UserCommon{
    public function index(){
        $order_num = input('get.order_num');
        //商户订单号

        $id = input('get.id');
        //机构订单号

        $trade_no = input('get.trade_no');
        //机构订单号

        if ($order_num||$id||$trade_no){
            $where1 = 1;
            $where2 = 1;
            $where3 = 1;
            $where4 = 1;
            $where7 = 1;
            $where8 = 1;
            if ($order_num) {
                $where5['order_num'] = $order_num;
                $where6 = 1;
                $where10 = 1;
            }elseif($trade_no){
                $where10['trade_no'] = $trade_no;
                $where6 = 1;
                $where5 = 1;
            }else{
                $where5 = 1;
                $where10 = 1;
                $id_arr = explode('_',$id);
                if (!isset($id_arr[1])) {
                    $where6['m.id'] = $id;
                }else{
                    $where6['m.id'] = $id_arr[1];
                }
            }
        }else{
            $where = $this->sql_where();
            $where1 = $where['where1'];
            $where2 = $where['where2'];
            $where3 = $where['where3']; 
            $where4 = $where['where4'];
            $where7 = $where['where7'];
            $where8 = $where['where8'];
            $where5 = 1;
            $where6 = 1;
            $where10 = 1;
        }

        $uid_str = $this->login_user['subordinate'].','.$this->login_user['id'];
        $where11['m.uid'] = ['in',$uid_str];



        $order_data = Db::table('mch_order')
            ->alias('m')
            ->field('m.*,u.merchant_cname,t.channel_name,ta.name')
            ->join('users u','m.uid=u.id')
            ->join('top_account_assets ta','ta.id=m.pay_type')
            ->join('channel_type t','m.type=t.id','left')
           // ->join('top_account_assets t','m.pay_type=t.id','left')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where5)
            ->where($where6)
            ->where($where7)
            ->where($where8)
            ->where($where10)
            ->where($where11)
            ->order('id desc')
            ->paginate(15,false,[
                'query' => request()->param()
                ]);


        $sum_amount = Db::table('mch_order')
            ->alias('m')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where5)
            ->where($where6)
            ->where($where7)
            ->where($where8)
            ->where($where10)
            ->where('order_type',1)
            ->sum('pay_amount');

        $get_select_data = [
            //传入input框默认参数
            'pay_status'=>input('get.pay_status'),

            'notify_url_info'=>input('get.notify_url_info'),

            'pay_type'=>input('get.pay_type'),
        ];


        $top_account_assets = Db::table('top_account_assets')
            ->field('name,id')
            ->select();

        

        $html_data = [
            'top_account_assets'=>$top_account_assets,
            'order_data'=>$order_data,
            'sum_amount'=>$sum_amount,
            'get_select_data'=>$get_select_data,
        ];
        return $this->fetch('index',$html_data);
    }
    public function sql_where(){
        //拼接where语句
        
        $start_time = input('get.start_time');
        //获取开始时间与结束时间
        $end_time = input('get.end_time');

        if ($start_time&&$end_time) {
            $where1['m.accept_time'] = ['>',strtotime($start_time)];
            $where2['m.accept_time'] = ['<',strtotime($end_time)];
        }else{
            $where1 = 1;
            $where2 = 1;
        }

        $pay_status = input('get.pay_status');
        //订单状态
        if ($pay_status==='0') {
            $where3 = 1;
        }elseif ($pay_status==1) {
            $where3['m.pay_status'] = 1;
        }elseif ($pay_status==2) {
            $where3['m.pay_status'] = 2;
        }else{
            $where3['m.pay_status'] = 1;
        }

        $notify_url_info = input('get.notify_url_info');
        //回调状态
        
        if (is_numeric($notify_url_info)) {
            $where7['m.notify_url_info'] = $notify_url_info;
        }else{
            $where7 = 1;
        }

        $uid = input('get.uid');
        //商户编号
        if ($uid) {
            $uid_arr = explode('_',$uid);
            if (!isset($uid_arr[1])||!is_numeric($uid_arr[1])) {
                echo '<script>alert("商户号错误");window.history.back(-1);</script>';
                die;
            }
            $where4['uid'] = $uid_arr[1];
        }else{
            $where4 = 1;
        }

        $pay_type = input('get.pay_type');

        if ($pay_type) {
            $where8['pay_type'] = $pay_type;
        }else{
            $where8 = 1;
        }

        $where['where1'] = $where1;
        $where['where2'] = $where2;
        $where['where3'] = $where3;
        $where['where4'] = $where4;
        $where['where7'] = $where7;
        $where['where8'] = $where8;
        return $where;
    }
}