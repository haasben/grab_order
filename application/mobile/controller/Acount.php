<?php
namespace app\mobile\controller;

use think\Db;
class Acount extends Common{


	public function _initialize(){
        parent::_initialize();
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

    //账户详情
    public function account_des(){

        $id = input('id');
        if(empty($id)){
            echo '<script>alert("非法请求");window.history.back(-1);</script>';
            die;
        }

        $user_data = Db::table('users')
            ->alias('u')
            ->field('u.name,u.merchant_cname,u.company,u.state,u.login_time,u.join_time,a.*')
            ->join('assets a','a.uid=u.id')
            ->where('u.id',$id)
            ->limit(1)
            ->find();
        $user_fee_data = Db::table('user_fee uf')
            ->field('user_fee.fee,uf.uid,uf.status,ta.name,uf.money,ta.settlement_way,uf.taid')
            ->join('top_account_assets ta','ta.id=uf.taid')
            ->where('uf.uid',$id)
            ->select();

        $this->assign('user_fee_data',$user_fee_data);
        $this->assign('user_data',$user_data);

        return $this->fetch();
    }







}