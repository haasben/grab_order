<?php
namespace app\mobile\controller;

use think\Db;
class Receive extends Common{


	public function _initialize(){
        parent::_initialize();
    }

//首页
    public function index(){

        $start_time = input('start_time');
        $end_time = input('end_time');
        $channel_type = input('channel_type');
        $name = input('name');

        // $order_str = input('get.order');
        // $email = input('get.email');
        // $type = input('get.type');
        $where1 = '';
        if ($name) {
            $where1['s.name'] = ['like','%'.$name.'%'];
        }
        if($channel_type){
            $where1['s.type']= $channel_type;
        }
        // if (!$order_str) {
        //     $order_str = 'id';
        // }
        // if ($order_str!='id'&&$order_str!='status') {
        //     $order_str = $order_str.' desc';
        // }

        if ($start_time==null||$end_time==null) {
            $start_time = date('Y-m-d',time());
            $end_time = date('Y-m-d',time()+86400);
        }
        $join_where = 'm.accept_time>'.strtotime($start_time).' and m.accept_time<'.strtotime($end_time);

        $secret_key_data = Db::table('top_account_assets')
            ->alias('s')
            ->field('s.id,s.fee_sum,s.server_url,s.name,s.withdrawal_sum,s.recharge_sum,s.money,s.type,sum(m.pay_amount) as sum_amount,s.status,s.receive_account,s.type,c.channel_name,s.app_id')
            ->join('mch_order m','m.pay_status=1 and order_type=1 and m.pay_type=s.id and '.$join_where,'left')
          	->join('channel_type c','c.id = s.type','left')
            ->where($where1)
            ->order('s.id desc')
            ->group('s.id')
            ->paginate(15,false,[
                'query' => request()->param()
                ]);
        $channel = Db::table('channel_type')->select();



        $sum_money = Db::table('top_account_assets')->sum('money');
        $html_data = [
            'secret_key_data'=>$secret_key_data,
            'sum_money'=>$sum_money,
            'channel'=>$channel
        ];
        //dump($secret_key_data);die;
        $this->assign($html_data);
        return $this->fetch();
    }


    public function index_des(){





        return $this->fetch();
    }
//修改账户状态
    public function update_status(){
        //开、关收款账户
        if (!request()->isAjax()) {
            return $this->index();
        }

        $id = input('get.id');
        $status = input('get.status');
        if ($this->login_user['state']!=100/*&&$this->login_user['state']!=99*/) {
            $ret['success'] = 3;
            $ret['hint'] = '权限不足';
            return $ret;
        }
        if ($status==1) {
            $bool = Db::table('top_account_assets')->where('id',$id)->update(['status'=>2]);
            $bool2 = Db::table('top_child_account')->where('pid',$id)->update(['status'=>2]);
            //$bool3 = Db::table('quata')->where('id',$id)->update(['status'=>1]);
            if ($bool) {
                cache("secret_key_arr",null);
                $ret['success'] = 1;
                $ret['hint'] = '操作成功';
            }else{
                $ret['success'] = 2;
                $ret['hint'] = '操作失败';
            }
        }elseif($status==2){
            $bool = Db::table('top_account_assets')->where('id',$id)->update(['status'=>1]);
            $bool2 = Db::table('top_child_account')->where('pid',$id)->update(['status'=>1]);
            //$bool3 = Db::table('quata')->where('id',$id)->update(['status'=>0]);
            cache("secret_key_arr",null);
            $ret['success'] = 1;
            $ret['hint'] = '操作成功';
        }

        return $ret;
    }




}