<?php
namespace app\madmin\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Validate;
class Withdrawal extends UserCommon{
    public function index(){

        $id = input('get.id');

        if ($id) {
            $where1 = 1;
            $where2 = 1;
            $where3 = 1;
            $where4 = 1;
            $where5['w.id'] = $id;
        }else{
            $where = $this->sql_where();
            $where1 = $where['where1'];
            $where2 = $where['where2'];
            $where3 = $where['where3'];
            $where4 = $where['where4'];
            $where5 = 1;
        }
		if($this->login_user['state'] == 88){
            $uid_str = $this->login_user['subordinate'].','.$this->login_user['id'];
            $where11['w.uid'] = ['in',$uid_str];

        }else{
            $where11 = '';

        }
    	$order_data = Db::table('withdrawal')
            ->alias('w')
            ->field('w.*,u.merchant_cname,t.channel_name as s_name')
            ->join('users u','u.id=w.uid')
          	->join('channel_type t','w.pay_type=t.id','left')
           // ->join('top_account_assets s','w.pay_type=s.id')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where5)
          	->where($where11)
            ->order('id desc')
            ->paginate(7,false,[
                'query' => request()->param()
                ]);
    	$html_data = [
    		'order_data'=>$order_data,
    	];
	
      
        $sum_money = Db::table('withdrawal')
            ->alias('w')
            ->join('users u','u.id=w.uid')
            ->join('top_account_assets s','w.pay_type=s.id')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where5)
          	->where($where11)
           // ->where('w.status','<>',3)
            ->sum('w.w_amount');
		
        $html_data = [
            'order_data'=>$order_data,
            'sum_money'=>$sum_money,
        ];
        // dump($html_data);die;
    	return $this->fetch('index',$html_data);
    }

    protected function sql_where(){
        $start_time = input('get.start_time');
        //获取开始时间与结束时间
        $end_time = input('get.end_time');

        if ($start_time&&$end_time) {
            $where1['w.add_time'] = ['>',strtotime($start_time)];
            $where2['w.add_time'] = ['<',strtotime($end_time)];
        }else{
            $where1 = 1;
            $where2 = 1;
        }

        $uid = input('get.uid');
        //商户编号
        if ($uid) {
            $uid_arr = explode('_',$uid);
            if (!isset($uid_arr[1])||!is_numeric($uid_arr[1])) {
                echo '<script>alert("商户号错误");window.history.back(-1);</script>';
                die;
            }
            $where3['uid'] = $uid_arr[1];
        }else{
            $where3 = 1;
        }

        $status = input('get.status');

        if ($status) {
            $where4['w.status'] = $status;
        }else{
            $where4 = 1;
        }

        $where['where1'] = $where1;
        $where['where2'] = $where2;
        $where['where3'] = $where3;
        $where['where4'] = $where4;
        return $where;

    }
    public function add_withdrawal(){
        if (request()->isPost()) {
            $data = input('post.');

            $validate = new Validate([
                'uid' => 'require|gt:0',
                'pay_type'=>'require|gt:0',
                'name' => 'require',
                'withdrawal_type'=>'require',
                'id_num'=>'require|gt:0',
                //'tcid'=>'require|gt:0',
                'fee'=>'require',
                'user_account_id'=>'require',
                'w_amount'=>'require|gt:1',
            ]);

            if (!$validate->check($data)){
                echo '<script>alert("'.$validate->getError().'");window.history.back(-1);</script>';
                die;
            }

            $data['w_amount'] = $data['w_amount']*100;
            $data['fee'] = $data['fee']*100;
            

            $id = input('get.id');
            if ($id!=$data['uid']) {
                echo '<script>alert("请输入完整提现信息");window.history.back(-1);</script>';
                die;
            }

            $assets_data = Db::table('assets')->where('uid',$id)->find();
            //该用户资金表数据

            //$top_child_account_data = Db::table('top_child_account')->where('id',$data['tcid'])->value('money');
            //付款子账户付款前余额

            $sum_deductions = $data['w_amount']+$data['fee'];
            //总扣款

            //$data['this_received_money'] = $top_child_account_data-$sum_deductions;
            //付款子账户付款后余额

           // if ($data['this_received_money']<0) {
               // echo '<script>alert("付款账户余额不足");window.history.back(-1);</script>';
               // die;
          //  }

            $data['this_money'] = $assets_data['money']-$sum_deductions;
            //该用户结算后余额

            if ($data['this_money']<0) {
                echo '<script>alert("商户余额不足");window.history.back(-1);</script>';
                die;
            }


            $this->add_user_account($data);
            //添加、更新收款人数据
            unset($data['user_account_id']);

            $data['add_time'] = time();
            //提交时间
            
            $data['add_user'] = '提交：'.$this->login_user['id'].'-'.$this->login_user['name'];
            //操作人，管理员

            Db::startTrans();
            //开启事务

            $withdrawal_id = Db::table('withdrawal')->insertGetId($data);
            //存入提现表数据完成。然后存一条数据到订单表，减去金额，方便订单页面查询流水。

            if (!$withdrawal_id) {
                echo '<script>alert("提交失败，错误代码10001");window.history.back(-1);</script>';
                die;
            }
        
        
            $insert_mch_order = [
                'uid'=>$data['uid'],
                'order_num'=>'提现',
                'pay_amount'=>$sum_deductions,
                'pay_status'=>1,
                'pay_type'=>$data['pay_type'],
                'tcid'=>$data['pay_type'],
                'pay_time'=>$data['add_time'],
                'accept_time'=>$data['add_time'],

                'trade_no'=>$withdrawal_id,
                'this_money'=>$data['this_money'],
                'this_received_money'=>0,
                'this_profits_money'=>Db::table('assets')->where('uid',1)->value('money'),

                'this_fee'=>$data['fee'],
                'this_channel_fee'=>$data['fee'],
                'fee'=>0,
                'ext'=>'提现单号'.$withdrawal_id.',金额'.($data['w_amount']/100).'元',
                'order_type'=>2,
                'note_ext'=>$data['withdrawal_type'],
              	'type'=>$data['pay_type']
            ];



            $bool1 = Db::table('mch_order')->insert($insert_mch_order);
            //订单存入成功。



            $bool2 = Db::table('assets')->where('uid',$id)->setDec('money',$sum_deductions);
            //下级余额减少

            $bool3 = Db::table('assets')->where('uid',$id)->setInc('withdrawal_sum',$sum_deductions);
            //下级累计提现金额增加



           // $bool4 = Db::table('top_account_assets')->where('id',$data['pay_type'])->setDec('money',$sum_deductions);
            //上级余额减少

           // $bool5 = Db::table('top_account_assets')->where('id',$data['pay_type'])->setInc('withdrawal_sum',$sum_deductions);
            //上级累计提现总和增加



            //$bool6 = Db::table('top_child_account')->where('id',$data['tcid'])->setDec('money',$sum_deductions);
            //上级子账户余额减少

            //$bool7 = Db::table('top_child_account')->where('id',$data['tcid'])->setInc('withdrawal_sum',$sum_deductions);
            //上级子账户累计提现总和增加


            $bool8 = Db::table('user_fee')->where('uid',$id)->where('taid',$data['pay_type'])->setDec('money',$sum_deductions);
            //减少用户对应该渠道资金


            if (!$bool1||!$bool2||!$bool3||!$bool8) {
                echo '<script>alert("数据写入失败，请联系管理员处理");window.history.back(-1);</script>';
                die;
            }

            Db::commit();
            //提交事务
            
            $this->success('提交成功,请确认收款信息无误后为客户转账','index');
            exit;
        }


        //get访问
    	$id = input('get.id');
    	$assets_data = Db::table('assets')
            ->alias('a')
            ->field('a.*,u.merchant_cname,u.company')
            ->join('users u','u.id=a.uid')
            ->where('a.uid',$id)
            ->find();

        $top_account_assets_data = Db::table('user_fee uf')
            ->field('ta.id,ta.channel_name as name,uf.money,uf.uid')
            ->join('channel_type ta','ta.id=uf.taid')
            ->where('uid',$id)
            ->where('uf.money','>',0)
            ->select();

        $user_account_data = Db::table('user_account')->where('uid',$id)->select();

    	$html_data = [
    		'assets_data'=>$assets_data,
            'top_account_assets_data'=>$top_account_assets_data,
            'user_account_data'=>$user_account_data,
    	];
    	return $this->fetch('add_withdrawal',$html_data);
    }


    public function add_user_account($data){
        if ($data['user_account_id']==0) {
            Db::table('user_account')->insert([
                'user_name'=>$data['name'],
                'withdrawal_type'=>$data['withdrawal_type'],
                'id_num'=>$data['id_num'],
                'ext'=>$data['ext'],
                'uid'=>$data['uid'],
            ]);
        }else{
            Db::table('user_account')->where('id',$data['user_account_id'])->update([
                'user_name'=>$data['name'],
                'withdrawal_type'=>$data['withdrawal_type'],
                'id_num'=>$data['id_num'],
                'ext'=>$data['ext'],
                'uid'=>$data['uid'],
            ]);
        }

    }


    public function del_withdrawal(){
        if (!request()->isAjax()) {
            return $this->index();
        }
        // return "功能暂未开放，请联系管理员";

        
        $id = input('get.id');

        Db::startTrans();
        //开启事务
        $withdrawal_data = Db::table('withdrawal')->field('uid,w_amount,add_user,fee,pay_type,tcid,sub_method')->where('id',$id)->find();

        $bool = Db::table('withdrawal')
            ->where('id',$id)
            ->where('status',2)
            ->update([
                'status'=>3,
                'add_user'=>$withdrawal_data['add_user'].'；作废：'.$this->login_user['id'].'-'.$this->login_user['name'].'；资金已退'
            ]);
        //更新提现订单

        if ($bool) {
            $assets_data = Db::table('assets')->where('uid',$withdrawal_data['uid'])->find();
            //当前用户资金数据

           // $top_account_assets_data = Db::table('top_account_assets')->where('id',$withdrawal_data['pay_type'])->value('money');
            //当前付款账户余额
            
            $sum_deductions = $withdrawal_data['w_amount']+$withdrawal_data['fee'];
            //总扣款

            $insert_mch_order = [
                'uid'=>$withdrawal_data['uid'],
                'order_num'=>'提现',
                'pay_amount'=>$sum_deductions,
                'pay_status'=>1,
                'pay_type'=>$withdrawal_data['pay_type'],
                'tcid'=>$withdrawal_data['tcid'],
                'pay_time'=>time(),
                'accept_time'=>time(),
                'trade_no'=>$id,
                'this_money'=>$assets_data['money']+$sum_deductions,

                'this_received_money'=>$sum_deductions,
                'this_profits_money'=>Db::table('assets')->where('uid',1)->value('money'),
                'this_fee'=>0,
                'this_channel_fee'=>0,
                'fee'=>0,
                'ext'=>'提现单号'.$id.',金额'.($sum_deductions/100).'元-作废',
                'order_type'=>2,
                'note_ext'=>'退款',
            ];

            $bool1 = Db::table('mch_order')->insert($insert_mch_order);
            //添加订单表
			
          	if($withdrawal_data['sub_method'] == 3){
                $bool2 = Db::table('assets')->where('uid',$withdrawal_data['uid'])->setInc('margin',$sum_deductions);
            //下级余额增加
                if (!$bool1||!$bool2) {
                Db::rollback();
                return "提交失败,错误代码10008";
                die;
            }

                Db::commit();
                //提交事务
                return "操作成功，".($sum_deductions/100)."元已返还至账户";

            }
			

            $bool2 = Db::table('assets')->where('uid',$withdrawal_data['uid'])->setInc('money',$sum_deductions);
            //下级余额增加

            $bool3 = Db::table('assets')->where('uid',$withdrawal_data['uid'])->setDec('withdrawal_sum',$sum_deductions);
            //下级累计提现减少



            //$bool4 = Db::table('top_account_assets')->where('id',$withdrawal_data['pay_type'])->setInc('money',$sum_deductions);
            //上级余额增加

            //$bool5 = Db::table('top_account_assets')->where('id',$withdrawal_data['pay_type'])->setDec('withdrawal_sum',$sum_deductions);
            //上级累计提现总和减少



            //$bool6 = Db::table('top_child_account')->where('id',$withdrawal_data['tcid'])->setInc('money',$sum_deductions);
            //上级收款子账户余额增加

           // $bool7 = Db::table('top_child_account')->where('id',$withdrawal_data['tcid'])->setDec('withdrawal_sum',$sum_deductions);
            //上级收款子账户累计提现总和减少

            $bool8 = Db::table('user_fee')->where('uid',$withdrawal_data['uid'])->where('taid',$withdrawal_data['pay_type'])->setInc('money',$sum_deductions);
            //增加用户对应该渠道资金
			

            if (!$bool1||!$bool2||!$bool3||!$bool8) {
              	return "提交失败,错误代码10008";
                die;
            }

            Db::commit();
            //提交事务
            return "操作成功，".($sum_deductions/100)."元已返还至账户";
        }
    }
    public function confirm_withdrawal(){
        if (!request()->isAjax()) {
            return $this->index();
        }
        $id = input('post.id');
        $add_user = Db::table('withdrawal')->where('id',$id)->value('add_user');

        $bool = Db::table('withdrawal')
                ->where('id',$id)
                ->where('status',2)
                ->update([
                    'status'=>1,
                    'add_user'=>$add_user.'；确认：'.$this->login_user['id'].'-'.$this->login_user['name']
                ]);
        if ($bool) {
            return "已操作完成";
        }else{
            return "操作失败";
        }
    }

    public function get_alipay_money(){
        $id = input('get.id');
        $data =  Db::table('top_child_account')->field('login_user,id,money')->where('pid',$id)->select();
        return $data;
    }

    public function get_alipay_money2(){
        $id = input('get.id');

        $data =  Db::table('top_child_account')->where('id',$id)->value('money');
        echo $data/100;
    }

    public function get_user_account(){
        $id = input('get.id');
        return Db::table('user_account')->where('id',$id)->find();
    }
}