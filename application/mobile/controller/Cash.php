<?php
namespace app\mobile\controller;

use think\Db;
class Cash extends Common{


	public function _initialize(){
        parent::_initialize();
    }


    public function cash_list(){

    	 $num = input('num');

        $where = '';
        $data = [];
        if(!empty($num)){

            $where['w.uid'] = $num;
        }
        $data['num'] = $num;

        $order_num = input('order_num');
        if(!empty($order_num)){
            $where['w.order_num'] = ['like','%'.$order_num.'%'];
        }
        $data['order_num'] = $order_num;


        $begin_time = input('begin_time');
        $end_time = input('end_time');

        $data['begin_time'] = $begin_time;
        $data['end_time'] = $end_time;

        if(!empty($begin_time) && !empty($end_time)){

            $where['w.add_time'] = ['between time',[$begin_time,$end_time]];

        }



        $order_data = Db::table('withdrawal')
            ->alias('w')
            ->field('w.*,u.merchant_cname,t.channel_name as t_name')
            ->join('users u','w.uid=u.id')
            ->join('channel_type t','w.withdrawal_type=t.code','left')
            ->where($where)
            ->order('id desc')
            ->paginate(15,false,[
                'query' => request()->param(),

                ]);

           //dump($order_data);die;
        $this->assign([
            'order_data'=>$order_data,
            'data'=>$data,

        ]);


        return $this->fetch();
        
        

    }


//提现订单详情
    public function cash_des(){

        $id = input('id');

        $order_data = Db::table('withdrawal')
            ->alias('w')
            ->field('w.*,u.merchant_cname,t.channel_name as t_name')
            ->join('users u','w.uid=u.id')
            ->join('channel_type t','w.withdrawal_type=t.code','left')
            ->where('w.id',$id)
            ->limit(1)
            ->find();



        $this->assign('order_data',$order_data);

        return $this->fetch();

    }






}