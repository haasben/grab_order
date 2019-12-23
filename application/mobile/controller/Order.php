<?php
namespace app\mobile\controller;

use think\Db;
class Order extends Common{


	public function _initialize(){
        parent::_initialize();
    }


//订单列表
    public function order_list(){

        $num = input('num');

        $where = '';
        $data = [];
        if(!empty($num)){

            $where['m.uid'] = $num;
        }
        $data['num'] = $num;

        $order_num = input('order_num');
        if(!empty($order_num)){
            $where['m.order_num'] = ['like','%'.$order_num.'%'];
        }
        $data['order_num'] = $order_num;


        $begin_time = input('begin_time');
        $end_time = input('end_time');

        $data['begin_time'] = $begin_time;
        $data['end_time'] = $end_time;

        if(!empty($begin_time) && !empty($end_time)){

            $where['m.accept_time'] = ['between time',[$begin_time,$end_time]];

        }

        $pay_status = input('pay_status');
        if(!empty(($pay_status))){
            $where['pay_status'] = $pay_status;
        }
        $notify_url_info = input('notify_url_info');
        if(!empty($notify_url_info)){
            $where['notify_url_info'] = $notify_url_info;
        }



        $order_data = Db::table('mch_order')
            ->alias('m')
            ->field('m.*,u.merchant_cname,t.channel_name as name')
            ->join('users u','m.uid=u.id')
            ->join('channel_type t','m.type=t.id','left')
            // ->join('top_account_assets t','m.pay_type=t.id','left')
            ->where($where)
            ->order('id desc')
            ->paginate(15,false,[
                'query' => request()->param(),

                ]);


        $this->assign([
            'order_data'=>$order_data,
            'data'=>$data,

        ]);


        return $this->fetch();
    }

//订单详情 
    public function order_des(){

        $id = input('id');

        $order_data = Db::table('mch_order')
            ->alias('m')
            ->field('m.*,u.merchant_cname,t.name')
            ->join('users u','m.uid=u.id')
            ->join('top_account_assets t','m.pay_type=t.id','left')
            ->where('m.id',$id)
            ->limit(1)
            ->find();

        //dump($order_data);die;

        $this->assign('order_data',$order_data);

        return $this->fetch();



    }







}