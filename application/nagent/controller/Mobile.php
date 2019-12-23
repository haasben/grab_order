<?php
namespace app\nagent\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
use think\Cache;
class Mobile extends Controller{

	public function _initialize(){

		$login_user = session('login_user','','nagent');
    	if ($login_user==null) {

            if(isMobilePhone()){
                
               return $this->redirect('nagent/login/login'); 
            }else{
               return $this->redirect('index/login/login'); 
            }


    		
    	}else{
            
            if(!isMobilePhone()){
                    
                return $this->redirect('nagent/user/index'); 
            }
        }
        $this->login_user = $login_user;
    	$this->assign('login_user',$login_user);


	}

	public function index(){

    $uid = $this->login_user['id'];
      	// //通道名称
    // $aisle = Db::name('top_account_assets')->order('id desc')->limit(1)->value('sql_name');
    // $data['aisle'] = $aisle;
    //今日成功金额，笔数
    $mch_orderModel = Db::name('mch_order');



    $data['today_amount'] = $mch_orderModel
        ->where('pay_status',1)
        ->where('order_type',1)
        ->where('proxy_id',$uid)
        ->whereTime('pay_time','today')
        ->sum('pay_amount');
    $data['today_count'] = $mch_orderModel
        ->where('pay_status',1)
        ->where('order_type',1)
        ->where('proxy_id',$uid)
        ->whereTime('pay_time','today')
        ->count();

    //昨日成功金额、笔数
    $data['yesterday_amount'] = $mch_orderModel
        ->where('pay_status',1)
        ->where('order_type',1)
        ->where('proxy_id',$uid)
        ->whereTime('pay_time','yesterday')
        ->sum('pay_amount');
    $data['yesterday_count'] = $mch_orderModel
        ->where('pay_status',1)
        ->where('order_type',1)
        ->where('proxy_id',$uid)
        ->whereTime('pay_time','yesterday')
        ->count();

    //当月成功金额、笔数
    $data['month_amount'] = $mch_orderModel
        ->where('pay_status',1)
        ->where('order_type',1)
        ->where('proxy_id',$uid)
        ->whereTime('pay_time','month')
        ->sum('pay_amount');
    $data['month_count'] = $mch_orderModel
        ->where('pay_status',1)
        ->where('order_type',1)
        ->where('proxy_id',$uid)
        ->whereTime('pay_time','month')
        ->count();

    //总数据
    $data['amount'] = $mch_orderModel
        ->where('pay_status',1)
        ->where('proxy_id',$uid)
        ->sum('pay_amount');
    $data['count'] = $mch_orderModel
        ->where('pay_status',1)
        ->where('proxy_id',$uid)
        ->count();


    $this->assign('data',$data);

     return $this->fetch();
    }


  public function add_code(){


        $uid = $this->login_user['id'];
        if(request()->isAjax()){
            
            $data = input();
            $channel_data = Db::table('channel_type')->where('code',$data['pay_type'])->limit(1)->find();

            $add_data = [
                'uid'=>$uid,
                'sql_name'=>$channel_data['show_code'],
                'name'=>$data['name'].'_'.$data['show_name'],
                'show_name'=>$channel_data['show_code'],
                'type'=>$data['pay_type'],
                'group_id'=>$data['group_id'],
                //'app_id'=>'Pcode_cloudesc_Bind_'.substr(md5(time().mt_rand(100,10000)),0,6),

            ];


             $bool = Db::table('top_account_assets')->insertGetId($add_data);
            if($bool){
                Db::table('top_account_assets')->where('id',$bool)->update(['app_id'=>'Pcode_cloudesc_Bind_'.strtoupper(substr(md5($bool),0,6))]);
                $return_data = ['code'=>'0000','msg'=>'添加成功'];

            }else{
                $return_data = ['code'=>'1111','添加失败，请稍后再试'];
            }

            return $return_data;

        }else{


            

            $channel = Db::table('channel_type')->where('id','in',[2,5,1022,1023,4])->where('status',1)->select();
            $this->assign('channel',$channel);


            $group_name = Db::table('group')->where('uid',$uid)->order('id desc')->select();
            $this->assign('group_name',$group_name);
            $users = Db::table('users')->field('id,name')->where('state','66')->select();
            $this->assign('users',$users);
            return $this->fetch();


        }


       
  }









//添加二维码图片
  public function add_qrcode(){

    if(request()->isAjax()){

        $data = input();
        $uid = $this->login_user['id'];
 
        $bool = Db::table('top_child_account')->where('pid',$data['id'])->where('amount',$data['amount']*100)->limit(1)->find();
        if($bool){
            $return_data = ['code'=>'1111','msg'=>'该账户已添加过金额为'.$data['amount'].'元的二维码'];
            return $return_data;
        }

        
        $user_fee = Db::table('user_fee')->where('uid',$uid)->where('taid',$data['pay_type'])->value('fee');
        if(empty($user_fee)){
            $return_data = ['code'=>'1111','msg'=>'账户暂未配置费率，请联系管理员'];
            return $return_data;
        }   
        
        $add_data = [
            'pid'=>$data['id'],
            'public_key'=>$data['img'],
            'fee'=>$user_fee,

        ];
        if(in_array($data['pay_type'],[2,5])){
            $add_data['amount'] = $data['amount']*100;
        
        }else{
            Db::table('top_child_account')->where('pid',$data['id'])->delete();
        
        }
        //dump($add_data);die;
        $bool = Db::table('top_child_account')->insert($add_data);
        if($bool){
                $return_data = ['code'=>'0000','msg'=>'添加成功'];

        }else{
            $return_data = ['code'=>'1111','添加失败，请稍后再试'];
        }

        return $return_data;

    }else{


        $id = input('id');

        $account_assets = Db::table('top_account_assets')->where('id',$id)->limit(1)->find();
        $this->assign('account_assets',$account_assets);
        $channel = Db::table('channel_type')->where('id','in',[2,5,1022,1023,4])->where('status',1)->select();
     // dump($channel);die;
        $this->assign('channel',$channel);
        return $this->fetch();

    }



    
    

  }


    //添加分组
    public function add_group(){

        if(request()->isAjax()){

           $group_name = input('group_name');
           $remark = input('remark');


            $uid = $this->login_user['id'];



           $bool = Db::name('group')->insert([

                'uid'=>$uid,
                'group_name'=>$group_name,
                'remark'=>$remark,
                'type'=>input('type')

           ]);
           if($bool){
                return ['code'=>'0000','msg'=>'添加成功'];
           }else{
                return ['code'=>'1111','msg'=>'添加失败，请稍后再试'];
           }

        }else{

            $pay_type = Db::table('channel_type')->where('code','in',[2,5,1022,1023,4])->where('status',1)->select();
            $this->assign('pay_type',$pay_type);
            
            return $this->fetch();


        }  



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
            ->field('m.*,u.merchant_cname,t.name')
            ->join('users u','m.uid=u.id')
            ->join('top_account_assets t','m.pay_type=t.id','left')
            ->where($where)
            ->where('proxy_id',$this->login_user['id'])
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

//账户总览
     public function account_list(){

        $state = input('state');

        $where = '';
        if(!empty($state)){
            $where['u.state'] = $state;
        }
     

        $user_data = Db::table('users')
            ->alias('u')
            ->field('u.name,u.merchant_cname,u.company,u.phone_num,u.email,u.login_time,u.state,u.bans_num,a.*')
            ->join('assets a','a.uid=u.id')
            ->where($where)
            ->where('u.id',$this->login_user['id'])
            ->order('id desc')
            ->find();

            // ->paginate(15,false,[
            //     'query' => request()->param()
            //     ]);
        $this->assign('user_data',$user_data);



        return $this->fetch();


    }

//收款账户列表
    public function receive_index(){
        $start_time = input('start_time');
        $end_time = input('end_time');
        $channel_type = input('channel_type');
        $name = input('name');

        $where1 = '';
        if ($name) {
            $where1['s.name'] = ['like','%'.$name.'%'];
        }
        if($channel_type){
            $where1['s.type']= $channel_type;
        }


        if ($start_time==null||$end_time==null) {
            $start_time = date('Y-m-d',time());
            $end_time = date('Y-m-d',time()+86400);
        }
        $join_where = 'm.accept_time>'.strtotime($start_time).' and m.accept_time<'.strtotime($end_time);

        $secret_key_data = Db::table('top_account_assets')
            ->alias('s')
            ->field('s.id,s.fee_sum,s.server_url,s.name,s.withdrawal_sum,s.recharge_sum,s.money,s.type,sum(m.pay_amount) as sum_amount,s.status,s.receive_account,s.type,s.app_id,c.show_name')
            ->join('mch_order m','m.pay_status=1 and order_type=1 and m.pay_type=s.id and '.$join_where,'left')
            ->join('channel_type c','c.code = s.type')
            ->where($where1)
            ->where('s.uid',$this->login_user['id'])
            ->order('s.id desc')
            ->group('s.id')
            ->paginate(15,false,[
                'query' => request()->param()
                ])
            ->each(function($item, $key){
                $item['count'] = Db::table('mch_order')->where('pay_type',$item['id'])->where('order_type',1)->count();
                $item['succ_count'] = Db::table('mch_order')->where('pay_type',$item['id'])->where('pay_status',1)->where('order_type',1)->count();
                return $item;
            });
        $channel = Db::table('channel_type')
            ->alias('c')
            ->join('user_fee uf','uf.taid=c.code')
            ->where('uf.uid',$this->login_user['id'])
            ->where('c.status',1)
            ->select();

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

    //修改账户状态
    public function update_status(){
        //开、关收款账户
        if (!request()->isAjax()) {
            return $this->index();
        }

        $id = input('get.id');
        $status = input('get.status');
        if ($status==1) {
            $bool = Db::table('top_account_assets')->where('id',$id)->update(['status'=>2]);
            $bool2 = Db::table('top_child_account')->where('pid',$id)->update(['status'=>2]);
            if ($bool) {

                $ret['success'] = 1;
                $ret['hint'] = '操作成功';
            }else{
                $ret['success'] = 2;
                $ret['hint'] = '操作失败';
            }
        }elseif($status==2){
            $bool = Db::table('top_account_assets')->where('id',$id)->update(['status'=>1]);
            $bool2 = Db::table('top_child_account')->where('pid',$id)->update(['status'=>1]);

            $ret['success'] = 1;
            $ret['hint'] = '操作成功';
        }

        return $ret;
    }



}