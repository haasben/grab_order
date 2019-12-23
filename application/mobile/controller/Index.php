<?php
namespace app\mobile\controller;

use think\Db;
class Index extends Common{


	public function _initialize(){
        parent::_initialize();
    }

//首页
    public function index(){

     

    // //通道名称
    // $aisle = Db::name('top_account_assets')->order('id desc')->limit(1)->value('sql_name');
    // $data['aisle'] = $aisle;
    //今日成功金额，笔数
    $mch_orderModel = Db::name('mch_order');



    $data['today_amount'] = $mch_orderModel->where('pay_status',1)->where('order_type',1)->whereTime('pay_time','today')->sum('pay_amount');
    $data['today_count'] = $mch_orderModel->where('pay_status',1)->where('order_type',1)->whereTime('pay_time','today')->count();

    //昨日成功金额、笔数
    $data['yesterday_amount'] = $mch_orderModel->where('pay_status',1)->where('order_type',1)->whereTime('pay_time','yesterday')->sum('pay_amount');
    $data['yesterday_count'] = $mch_orderModel->where('pay_status',1)->where('order_type',1)->whereTime('pay_time','yesterday')->count();

    //当月成功金额、笔数
    $data['month_amount'] = $mch_orderModel->where('pay_status',1)->where('order_type',1)->whereTime('pay_time','month')->sum('pay_amount');
    $data['month_count'] = $mch_orderModel->where('pay_status',1)->where('order_type',1)->whereTime('pay_time','month')->count();

    //总数据
    $data['amount'] = $mch_orderModel->where('pay_status',1)->sum('pay_amount');
    $data['count'] = $mch_orderModel->where('pay_status',1)->count();


    $this->assign('data',$data);

     return $this->fetch();

    }

    //账户总览
    public function account_list(){

    	$state = input('state');

    	$where = '';
    	if(!empty($state)){
    		$where['u.state'] = $state;
    	}
    	

    	$user_data = Db::table('users')
    		->alias('u')
    		->field('u.name,u.merchant_cname,u.company,u.state,a.*')
    		->join('assets a','a.uid=u.id')
    		->where($where)
    		->order('id desc')
    		->select();

            // ->paginate(15,false,[
            //     'query' => request()->param()
            //     ]);
    	$this->assign('user_data',$user_data);



    	return $this->fetch();


    }








}