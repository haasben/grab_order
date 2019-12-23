<?php
namespace app\admin\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
class User extends UserCommon{
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
            ->field('user_fee.fee,uf.uid,uf.status,ta.show_name,uf.money,ta.settlement_way,uf.taid')
            ->join('top_account_assets ta','ta.id=uf.taid')
            ->where('uid',$this->login_user['id'])
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
            ->limit('5')
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


    public function linshi_key(){
      echo '<script>alert("如需获取、修改 商户秘钥，请联系管理员处理！");window.history.back(-1);</script>';
			die;
    	$data = Db::table('users')->where('id',$this->login_user['id'])->find();
    	echo encryption($data['key'].$data['merchant_cname'].$data['id']);
    }

    public function update_login_pass(){
        return $this->fetch('update_login_pass');
    }

    public function update_pass(){
      	
        if (request()->isAjax()) {
            $phone_code_arr = session('phone_code_arr','','admin');
            if (is_array($phone_code_arr)&&$phone_code_arr['scenario']=='update_pass') {
                $old_time = $phone_code_arr['time'];
                $time_poor = time()-$old_time;
                if ($time_poor<60) {
                    $ret['success'] = 2;
                    $ret['hint'] = 60-$time_poor.'秒后可再次发送';
                    return $ret;
                }
            }
            $phone_num = $this->login_user['phone_num'];
            $bool = get_phone_code($phone_num,'update_pass','admin');
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
        if ($phone_code_arr['phone_num']!=$data['phone_num']||$phone_code_arr['code']!=$data['code']||$phone_code_arr['scenario']!='update_pass') {
            echo '<script>alert("验证码错误");window.history.back(-1);</script>';
            die;
        }
        if (time()-$phone_code_arr['time']>1800) {
            echo '<script>alert("验证码过期，请重新获取");window.history.back(-1);</script>';
            die;
        }

        $pass = encryption($data['pass']);
        
        $bool = Db::table('users')->where('id',$this->login_user['id'])->update(['pass'=>$pass]);
        if ($bool) {
            $this->success('修改成功');
            // echo '<script>alert("修改成功，请重新登陆");window.location.href="/admin/User/exit_login.html";</script>';
            // die;
        }else{
            echo '<script>alert("修改失败，请联系管理员");window.history.back(-1);</script>';
            die;
        }
    }



    public function update_trading_pass_page(){
        return $this->fetch('update_trading_pass_page');
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

    public function personinfo(){
        $user_data = Db::table('users')
                ->alias('u')
                ->field('a.*,u.email,u.name,u.join_time,u.company,u.merchant_cname,u.state,u.phone_num')
                ->join('assets a','a.uid=u.id')
                ->where('u.id',$this->login_user['id'])
                ->find();


        $user_fee_data = Db::table('user_fee')
            ->field('user_fee.fee,show_name')
            ->join('top_account_assets','top_account_assets.id=user_fee.taid')
            ->where('uid',$this->login_user['id'])
            ->where('user_fee.status',1)
            ->select();

        $user_money = Db::table('user_fee uf')
            ->field('uf.money,ta.show_name,uf.uid')
            ->join('top_account_assets ta','ta.id=uf.taid')
            ->where('uf.money','>',0)
            ->where('uf.uid',$this->login_user['id'])
            ->select();
        $user_money_arr = '';
        foreach ($user_money as $value) {
            if ($this->login_user['id'] == $value['uid']) {
                $user_money_arr[$this->login_user['id']][] = $value['show_name'].':'.($value['money']/100).'元';
            }
        }
        

        $html_data = [
            'user_data'=>$user_data,
            'user_fee_data'=>$user_fee_data,
            'user_money_arr'=>$user_money_arr,
        ];
        return $this->fetch('personinfo',$html_data);
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
}