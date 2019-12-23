<?php
namespace app\partner\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
class User extends Common{
    public function index(){

        return $this->fetch('index');
    }

    public function main(){
    	$user_data = Db::table('users')
    			->alias('u')
    			->field('a.*,u.email,u.name,u.join_time,u.company,u.merchant_cname,u.state,u.phone_num')
    			->join('assets a','a.uid=u.id')
    			->where('u.id',$this->login_user['id'])
    			->find();


        $user_fee_data = Db::table('user_fee uf')
            ->field('user_fee.fee,uf.uid,uf.status,ta.channel_name as show_name,uf.money,ta.settlement_way,uf.taid')
            ->join('channel_type ta','ta.id=uf.taid')
            // ->join('top_account_assets ta','ta.id=uf.taid')
            ->where('uid',$this->login_user['id'])
            ->where('ta.status',1)
            ->select();

        $user_fee_data = $this->freeze_amount($user_fee_data);

    

        $today_order_num = Db::table('mch_order')
            ->where('uid',$this->login_user['id'])
            ->where('accept_time','>',strtotime(date('Y-m-d 00:00:00')))
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count();

        $sum_order_num =   Db::table('mch_order')
            ->where('uid',$this->login_user['id'])
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count();

        $new_order = Db::table('mch_order')
            ->where('uid',$this->login_user['id'])
            ->order('id desc')
            ->limit(10)
            ->select();

        $weeks_order = $this->weeks_order($this->login_user['id']);
        
   



    	$html_data = [
    		'user_data'=>$user_data,
            'user_fee_data'=>$user_fee_data,
            'sum_order_num'=>$sum_order_num,
            'today_order_num'=>$today_order_num,
            'new_order'=>$new_order,
            'weeks_order'=>$weeks_order,

    	];
        return $this->fetch('main',$html_data);
    }




    public function update_trading_pass(){
        
        if (request()->isAjax()) {
            $phone_code_arr = session('phone_code_arr','','admin');
            if (is_array($phone_code_arr)&&$phone_code_arr['scenario']=='update_trading_pass') {
                $old_time = $phone_code_arr['time'];
                $time_poor = time()-$old_time;
                if ($time_poor<60) {
                    $ret['success'] = 2;
                    $ret['hint'] = 60-$time_poor.'秒后可再次发送';
                    return $ret;
                }
            }
            $phone_num = $this->login_user['phone_num'];
            $bool = get_phone_code($phone_num,'update_trading_pass','admin');
            if ($bool) {
                $ret['success'] = 1;
                $ret['hint'] = '已发送验证码至您的手机';
                return $ret;
            }else{
                $ret['success'] = 3;
                $ret['hint'] = '短信验证码发送失败，请稍后重试';
                return $ret;
            }
        }

        if (!request()->isPost()) {
            return $this->index();
        }

        $data = input('post.');
        $validate = new Validate([
            'code' => 'require|number|length:6',
            'pass' => 'require|length:6,16',
            'repeat_pass'=>'require|confirm:pass'

        ],[
            'code' => '验证码错误',
            'pass'     => '密码长度需为6到16位',
            'repeat_pass'        => '两次输入不一致'
        ]);
        if (!$validate->check($data)){
            echo '<script>alert("'.$validate->getError().'");window.history.back(-1);</script>';
            die;
        }
        $data['phone_num'] = $this->login_user['phone_num'];
        $phone_code_arr = session('phone_code_arr','','admin');
        if ($phone_code_arr['phone_num']!=$data['phone_num']||$phone_code_arr['code']!=$data['code']||$phone_code_arr['scenario']!='update_trading_pass') {
            echo '<script>alert("验证码错误");window.history.back(-1);</script>';
            die;
        }
        if (time()-$phone_code_arr['time']>1800) {
            echo '<script>alert("验证码过期，请重新获取");window.history.back(-1);</script>';
            die;
        }

        $pass = encryption($data['pass']);
        
        $bool = Db::table('users')->where('id',$this->login_user['id'])->update(['trading_pass'=>$pass]);
        if ($bool) {
            $this->success('修改成功');
            // echo '<script>alert("修改成功，请重新登陆");window.location.href="/admin/User/exit_login.html";</script>';
            // die;
        }else{
            echo '<script>alert("修改失败，请联系管理员");window.history.back(-1);</script>';
            die;
        }
    }

    public function user_info(){
        $user_data = Db::table('users')
                ->alias('u')
                ->field('a.*,u.email,u.name,u.join_time,u.company,u.merchant_cname,u.state,u.phone_num')
                ->join('assets a','a.uid=u.id')
                ->where('u.id',$this->login_user['id'])
                ->find();


        $user_fee_data = Db::table('user_fee')
            ->field('user_fee.fee,ct.channel_name')
            ->join('channel_type ct','ct.id=user_fee.taid')
            ->where('uid',$this->login_user['id'])
            ->where('user_fee.status',1)
            ->select();

        $user_money = Db::table('user_fee uf')
            ->field('uf.money,ta.channel_name,uf.uid')
            ->join('channel_type ta','ta.id=uf.taid')
            ->where('uf.money','>',0)
            ->where('uf.uid',$this->login_user['id'])
            ->select();
        $user_money_arr = '';
        foreach ($user_money as $value) {

            if ($this->login_user['id'] == $value['uid']) {
                $user_money_arr[$this->login_user['id']][] = $value['channel_name'].':'.($value['money']/100).'元';
            }
        }
        
        $assets = Db::table('assets')->where('uid',$this->login_user['id'])->find();


        $html_data = [
            'assets'=>$assets,
            'user_data'=>$user_data,
            'user_fee_data'=>$user_fee_data,
            'user_money_arr'=>$user_money_arr,
        ];
  
        $this->assign($html_data);
 
        return $this->fetch();
    }

    public function freeze_amount($user_fee_data){
        $sum_freeze = 0;
        foreach ($user_fee_data as $key => $value) {
            $str = '';
            if ($value['settlement_way']=='T1') {
                $this_time = time();
                //当前时间

                $uid = $value['uid'];

                if (date('H',$this_time)>T1HOURS) {
                    // 12点前需要往前推一天
                    $open_time = Recently_working_day($this_time);
                    //最近工作日凌晨0点时间戳
                }else{
                    $open_time = Recently_working_day($this_time-60*60*24);
                    //最近工作日凌晨0点时间戳
                }

                $freeze_amount = Db::table('mch_order')
                    ->where('pay_time','>',$open_time)
                    ->where('uid',$uid)
                    ->where('pay_type',$value['taid'])
                    ->where('pay_status',1)
                    ->where('order_type',1)
                    ->sum('pay_amount');
                //该用户当前通道冻结金额

                $user_freeze_amount = Db::table('user_fee')
                    ->where('taid',$value['taid'])
                    ->where('uid',$uid)
                    ->value('freeze_amount');
                //该用户当前通道上游冻结金额
				
              	$freeze_amount = ceil($freeze_amount*0.3);
              	//该通道目前是0.7D0 0.3T1;
              	
                $freeze_amount += $user_freeze_amount;

                $freeze_fee = Db::table('mch_order')
                    ->where('pay_time','>',$open_time)
                    ->where('uid',$uid)
                    ->where('pay_type',$value['taid'])
                    ->where('pay_status',1)
                    ->where('order_type',1)
                    ->sum('this_fee');
                //该用户当前通道冻结订单手续费
                
                $str .= '不可结算余额：'.(($freeze_amount-$freeze_fee)/100).'元；可结算余额：'.(($value['money']-$freeze_amount+$freeze_fee)/100).'元';

                $sum_freeze += $freeze_amount-$freeze_fee;

                $user_fee_data[$key]['ext'] = $str;
            }else{
                $user_fee_data[$key]['ext'] = $str;
            }
        }

        $this->assign('sum_freeze',$sum_freeze);

        return $user_fee_data;

    }

    public function weeks_order($uid){
        $this_time_d = strtotime(date('Y-m-d 00:00:00'));
        //今日0点时间戳 开始时间

        for ($i=0; $i < 10; $i++) { 
            $where = [
                'uid'=>$uid,
                'order_type'=>1,
                'pay_status'=>1,
                'accept_time'=>['between',[$this_time_d,$this_time_d+60*60*24]],
            ];

            $sum_amount = Db::table('mch_order')
                ->where($where)
                ->sum('pay_amount');

            $sum_fee = Db::table('mch_order')
                ->where($where)
                ->sum('this_fee');
            
            $sum_order_num = Db::table('mch_order')
                ->where($where)
                ->count('id');

            $arr = array();

            $arr = [
                'sum_amount'=>($sum_amount-$sum_fee)/100
            ];
            $weeks_order[date('m-d',$this_time_d).' '.$sum_order_num] = $arr;
            $this_time_d -= 60*60*24;
        }

        return array_reverse($weeks_order);
    }
  
 
//获取密钥以及设备号
  	public function token_data(){
    	
      	if(request()->isAjax()){
        	
          	$pass = input('pass');
          	if(empty($pass)){
            	return ['code'=>'1111','msg'=>'密码不能为空'];
            }
          	
          	$uid = $this->login_user['id'];
          	$user_pass = Db::table('users')->where('id',$uid)->find();
          	
          	if(!empty($pass) && $user_pass['pass'] == encryption($pass)){
   
                 $key = encryption($user_pass['key'].$user_pass['merchant_cname'].$user_pass['id']);

                 $key = substr($key,(strlen($key)-8));
              
            	return ['code'=>'0000','msg'=>'获取成功','data'=>'TOKEN:'.$key.PHP_EOL.'设备编号:'.substr(md5($user_pass['key']), 0,8)];
            }else{
            	return ['code'=>'1111','msg'=>'密码错误'];
            
            }
          
          
        	
        }
    
    
    }
  
//获取谷歌验证密钥
    	public function google_apikey(){
    	
      	if(request()->isAjax()){
        	
          	$uid = $this->login_user['id'];
          	$authenticator = Db::table('users')->where('id',$uid)->value('authenticator');
          	
          	if($authenticator){
 
            	return ['code'=>'0000','msg'=>'获取成功','data'=>$authenticator];
            }else{
            	return ['code'=>'1111','msg'=>'获取失败，请稍后再试'];
            
            }
        	
        }
    
    
    }
  
  
  
  
  
  
  
}