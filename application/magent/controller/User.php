<?php


namespace app\magent\controller;

use think\cache\driver\Redis;
use think\Db;

class User extends Common
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    public function mine()
    {
//        $user['name'] = $this->login_user['merchant_cname'] . '_' . $this->login_user['id'];
        $user = $this->login_user;
        $this->assign('info', $user);
        return $this->fetch();
    }

    public function info()
    {
        $id = input('param.id/d');
        $id ? $id : $id = $this->login_user['id'];
        $info = Db::name('users')->find($id);
        if (request()->isPost()) {
            $captcha = input('post.captcha/s');
            if (!captcha_check($captcha)) {
                $ret['success'] = 3;
                $ret['hint'] = "error captcha";
                return $ret;
            }
            $receive_data = input();
            
            $result = $this->validate($receive_data,
                [
                'name|代理名称'  => 'require',
                'email|邮箱地址'    => 'email',
                'phone_num|手机号码'=>'require',

            ]);
        //验证判断必填项
            if(true !== $result){
                // 验证失败 输出错误信息
                return ['success'=>2,'hint'=>$result];exit;
            }elseif(!is_mobile_phone($receive_data['phone_num'])){

                return ['success'=>2,'hint'=>'手机号码格式错误'];exit;

            }
          
            $data['name'] = input('post.name/s');
            $data['email'] = input('post.email/s');
            $data['phone_num'] = input('post.phone_num/s');

            $res = Db::name('users')->where('id', $id)->update($data);
            if ($res) {
                $ret['success'] = 1;
                $ret['hint'] = '修改成功!';
                $ret['url'] = '/magent/user/info/id/' . $id;
            } else {
                $ret['success'] = 2;
                $ret['hint'] = '修改失败!';
                $ret['url'] = '/magent/user/info/id/' . $id;
            }
            return $ret;
        }
        $info['phone_num'] = mb_substr($info['phone_num'], 0, 3) . '****' . mb_substr($info['phone_num'], -4);
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function edit_pwd()
    {
        if (request()->isPost()) {
            $data = input();
            $new = encryption($data['new_pass']);
            $old = encryption($data['old_pass']);
            $ok = encryption($data['ok_pass']);
            if ($old != $this->login_user['pass']) {
                $ret['success'] = 2;
                $ret['hint'] = '原密码错误!';
            } elseif ($new == $old || $new == $this->login_user['pass']) {
                $ret['success'] = 2;
                $ret['hint'] = '新密码不能和原密码相同!';

            } elseif ($new != $ok) {
                $ret['success'] = 2;
                $ret['hint'] = '两次输入密码不一致!';
            } else {
                $res = Db::name('users')->where('id', $this->login_user['id'])->setField('pass', $new);
                if ($res) {
                    $ret['success'] = 1;
                    $ret['hint'] = '修改成功!';
                    $ret['url'] = '/magent/user/login';
                } else {
                    $ret['success'] = 2;
                    $ret['hint'] = '修改失败!';
                }
            }
            return $ret;
        }
        return $this->fetch();
    }
    //添加分组
     public function add_group(){
     
        if(request()->isAjax()){

           $group_name = input('group_name');
           $remark = input('remark');
            
           $uid = $this->login_user['id'];
           $status = input('status');
           if($status == 'true'){
             $status = 1;
           }else{
             $status = 2;
           }
          
           $bool = Db::name('group')->insert([

                'uid'=>$uid,
                'group_name'=>$group_name,
                'remark'=>$remark,
                'type'=>input('type'),
                'status'=>$status

           ]);
           if($bool){
                return ['code'=>'0000','msg'=>'添加成功'];
           }else{
                return ['code'=>'1111','msg'=>'添加失败，请稍后再试'];
           }

        }else{
            
            $uid = $this->login_user['id'];
            $code = Db::table('user_fee')->field('taid')->where('uid',$uid)->select();
            $code = array_column($code, 'taid');
 
            $pay_type = Db::table('channel_type')->where('code','in',$code)->select();
            $this->assign('pay_type',$pay_type);
            
            return $this->fetch();

        } 
     
     }
  
    // 编辑分组
    public function edit_group($id)
    {
        if (request()->isPost()) {
            $id = input('post.id/d');
            $data['group_name'] = input('post.group_name/s');
            $data['remark'] = input('post.remark/s');
            $status = input('status');
             if($status == 'true'){
               $data['status'] = 1;
             }else{
               $data['status'] = 2;
             }
            $res = Db::name('group')->where('id', $id)->update($data);
            if ($res) {
                return ['code' => '0000', 'msg' => '修改成功'];
            }
            return ['code' => '1111', 'msg' => '修改失败'];
        }
        $group = Db::name('group')
            ->alias('g')
            ->field('g.*,ct.channel_name')
            ->join('channel_type ct', 'ct.code = g.type')
            ->find($id);
        $this->assign('info', $group);
        return $this->fetch();
    }

    // 变更状态
    public function edit_status()
    {
        if (request()->isPost()) {
            $id = input('post.id/d');
            $status = input('post.status/d');
            $type = input('post.type/d');
            $res = [];
            if ($type == 1)
                $res = Db::name('top_account_assets')->where('id', $id)->setField('status', $status);
            elseif ($type == 2)
                $res = Db::name('group')->where('id', $id)->setField('status', $status);
            if ($res) {
                if ($status == 1)
                    return ['code' => '0000', 'msg' => '开启成功'];
                elseif ($status == 2)
                    return ['code' => '0000', 'msg' => '关闭成功'];
            }
            if ($status == 1)
                return ['code' => '1111', 'msg' => '开启失败'];
            elseif ($status == 2)
                return ['code' => '1111', 'msg' => '关闭失败'];
            return ['code' => '1111', 'msg' => '操作失败'];
        }
    }
    
    // 店员列表
    public function clerk_list()
    {
        $page = input('post.page/d', 1);
        $type = input('post.type/d');
        $uid = $this->login_user['id'];
        $list = Db::name('top_account_assets')
            ->alias('ta')
            ->join('channel_type ct', 'ct.code = ta.type')
            ->where('ta.uid', $uid)
            ->field('ta.id,ta.name,ct.channel_name,ta.type,ta.is_clerk,ta.status,ta.app_id,ta.low_mode')
            ->page($page)
            ->order('ta.id desc')
            ->limit(13)
            ->select();
        foreach ($list as &$item) {
            // 今日收款
            $price = Db::table('mch_order')->where('pay_type', $item['id'])->whereTime('pay_time', 'today')->sum('pay_amount');
            $item['today'] =$price / 100;
            $item['name'] = mb_substr($item['name'],0,11);
            $item['sum_succ_order'] = Db::table('mch_order')->where('pay_type', $item['id'])->where('order_type',1)->whereTime('pay_time', 'today')->count();
            $item['sum_order'] = Db::table('mch_order')->where('pay_type', $item['id'])->whereTime('accept_time', 'today')->count();
            $item['app_id'] = mb_substr($item['app_id'], -6);
        }
        $group = Db::name('group')
            ->alias('g')
            ->field('g.*,ct.channel_name,ct.code')
            ->join('channel_type ct', 'ct.code = g.type')
            ->where('uid', $uid)
            ->order('g.type desc')
            ->limit(5)
            ->page(1)
            ->select();
        $group1 = [];
        $group2 = [];
        foreach ($group as &$datum) {
            if ($datum['code'] == '1023') {
                $datum['type'] = 1;
                $group1[] = $datum;
            } elseif ($datum['code'] == '1022') {
                $datum['type'] = 2;
                $group2[] = $datum;
            }elseif ($datum['code'] == '1032') {
                $datum['type'] = 3;
                $group2[] = $datum;
            }
        }
        if (request()->isPost()) {
            if ($type == 1) {
                $data['list'] = $list;
                $data['page'] = $page;
                return $data;
            } elseif ($type == 2) {
                $data['list'] = $group;
                $data['page'] = $page;
                return $data;
            } elseif ($type == 3) {
                $data['list'] = $group1;
                $data['page'] = $page;
                return $data;
            } elseif ($type == 4) {
                $data['list'] = $group2;
                $data['page'] = $page;
                return $data;
            }
        }
        $this->assign('group', $group);
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 店员详情
    public function clerk_des()
    {
        $id = input('param.id/d');
 
        $start_time = date('Y-m-d',time());
        $end_time = date('Y-m-d',time()+86400);

        $join_where = 'm.accept_time>'.strtotime($start_time).' and m.accept_time<'.strtotime($end_time);
        $des = Db::table('top_account_assets')
            ->alias('s')
            ->field('s.id,s.fee_sum,s.server_url,s.name,s.withdrawal_sum,s.recharge_sum,s.money,sum(m.pay_amount) as sum_amount,s.status,s.receive_account,s.type,s.app_id,ct.code,ct.channel_name,ct.show_code,ct.show_name,ct.is_platform,s.is_clerk,g.group_name')
            ->join('mch_order m', 'm.pay_status=1 and order_type=1 and m.pay_type=s.id and '.$join_where, 'left')
            ->join('channel_type ct', 'ct.id = s.type')
            ->join('group g', 'g.id = s.group_id')
            ->where('s.id', $id)
            ->order('s.id desc')
            ->group('s.id')
            ->find();
        if ($des) {
            $des['count'] = Db::table('mch_order')->where('pay_type', $des['id'])->where('order_type', 1)->count();
            $des['succ_count'] = Db::table('mch_order')->where('pay_type', $des['id'])->where('pay_status', 1)->where('order_type', 1)->count();
            $des['rate'] = $des['count'] ? number_format($des['succ_count'] / $des['count'] * 100, 2) : '0.00';
            $des['count_img'] = Db::table('top_child_account')->where('pid', $des['id'])->count();
            if ($des['is_clerk'] == 2) {
                $des['clerk'] = Db::table('top_account_assets')->where('app_id', $des['app_id'])->where('is_clerk', 1)->limit(1)->value('name');
            } elseif ($des['is_clerk'] == 1) {
                $des['clerk'] = '是';
            }
            $now_time = time();
            $time = $now_time - cache('DeviceNo'.$des['app_id']);

            if($time < 6 ){
                $msg = '<sapn style="color:green;">'.$time.' 秒前在线 ●';
            }elseif($time>6 && $time<180){
                $msg = '设备掉线<b style="color:orange;">'.($time-5).'</b>秒';

            }else{
                 $msg = '<sapn style="color:#999;">设备离线</span>';
            }
            $des['app_time'] = $msg;
            $des['app_id'] = mb_substr($des['app_id'], -6);
          
        }

      //回调监控信息
        $data = Db::table('receive')->where('app_id','Pcode_cloudesc_Bind_'.$des['app_id'])->limit(5)->order('id desc')->select();
      
        foreach($data as $k => $v){
    
          $data[$k]['msg'] = explode('***', $v['msg']);

          //echo '<h2>'.$v['msg'].PHP_EOL.$v['time'].'</h2><hr>';

        }
        $this->assign('data',$data);
        
      
        $this->assign('des', $des);
        return $this->fetch();
    }

    // 绑定店员
    public function bind_clerk($id)
    {
        if (request()->isPost()) {
            $id = input('id');
            // 店员id
            $clerk_id = input('clerk_id');
            if (!$id || !$clerk_id) {
                return ['code' => '1111', 'msg' => '暂无可绑店员'];
            }
            $app_id = Db::table('top_account_assets')
                ->where('id', $clerk_id)
                ->limit(1)
                ->value('app_id');
            $bool = Db::table('top_account_assets')
                ->where('id', $id)
                ->update([
                    'app_id' => $app_id,
                    'is_clerk' => 2
                ]);
            if ($bool) {
                $bool1 = Db::table('top_account_assets')
                    ->where('id', $clerk_id)
                    ->setInc('clerk_sum');
                if ($bool1) {
                    return ['code' => '0000', 'msg' => '绑定成功'];
                }
            }
            return ['code' => '1111', 'msg' => '已经绑定成功该店员'];
        } else {
            $type = Db::table('top_account_assets')
                ->where('id', $id)
                ->limit(1)
                ->value('type');

            $uid = $this->login_user['id'];
            $clerk_data = Db::table('top_account_assets')
                ->where('uid', $uid)
                ->where('type', $type)
                ->where('is_clerk', 1)
                ->where('clerk_sum', '<', 5)
                ->select();
            foreach ($clerk_data as &$clerk_datum) {
                $clerk_datum['app_id'] = mb_substr($clerk_datum['app_id'], -6);
            }
            return $clerk_data;
        }

    }

    // 编辑店员
    public function edit_info($id)
    {
        if (request()->isAjax()) {

            $data = input();

            $result = $this->validate($data,
                [
                    'pay_type|收款码类型' => 'require',
                    'group_id|所属分组' => 'require',
                    'name|真实账户名' => 'require',
                    'show_name|账户昵称' => 'require',
                ]);


            //验证判断必填项
            if (true !== $result) {
                // 验证失败 输出错误信息
                return ['code' => 20001, 'msg' => $result];
                exit;
            } elseif ($data['pay_type'] == 1022 && empty($data['receive_account'])) {
                return ['code' => 20001, 'msg' => '支付宝账户不能为空'];
                exit;

            }
            if($data['low_mode'] == 'true'){
                $low_mode = 1;
            }else{
                $low_mode = 2;
            }
            $update = [
                'name' => $data['name'] . '_' . $data['show_name'],
                'receive_account' => $data['receive_account'],
                'low_mode'=>$low_mode

            ];
            
            
          

            $bool = Db::table('top_account_assets')
                ->where('id', $data['id'])
                ->where('uid', $this->login_user['id'])
                ->update($update);
            if ($bool) {
                return ['code' => '0000', 'msg' => '修改成功'];
                exit;

            } else {
                return ['code' => 20001, 'msg' => '没有修改数据哦'];
                exit;
            }

        } else {
            $account_data = Db::table('top_account_assets')
                ->alias('ta')
                ->field('ct.channel_name,g.group_name,ta.*')
                ->join('channel_type ct', 'ct.code=ta.type')
                ->join('group g', 'g.id=ta.group_id')
                ->where('ta.id', $id)
                ->where('ta.uid', $this->login_user['id'])
                ->find();


            if (empty($account_data)) {
                echo '非法请求';
                die;
            }

            $name = explode('_', $account_data['name']);
            $account_data['show_name'] = $name[1];
            $account_data['name'] = $name[0];

            $this->assign('info', $account_data);
            return $this->fetch();
        }
    }

    // 上下分记录
    public function data()
    {
        $page = input('post.page/d', 1);
        $type = input('post.dtype/d', 1);
        $type_id = input('type_id');
        $where1 = '';
        if($type_id){
            $where1['type'] = $type_id;
        }

        $list = Db::name('record')
            ->field('id,time', true)
            ->where("operator|child_id",$this->login_user['id'])
            ->where($where1)
            ->limit(6)
            ->page($page)
            ->order('date', 'desc')
            ->select();
        foreach ($list as &$item) {
            $item['operator'] = Db::name('users')->where('id', $item['operator'])->value("concat(merchant_cname,'_',id,' ',name)");
            $item['child_id'] = Db::name('users')->where('id', $item['child_id'])->value("concat(merchant_cname,'_',id,' ',name)");
            $item['type'] = Db::name('record_type')->where('id', $item['type'])->value('name');
            $item['money'] = number_format($item['money'] / 100, 2);
            $item['freeze_money'] = number_format($item['freeze_money'] / 100, 2);

        }
        $uid = $this->login_user['id'];
        //日切数据
        $daily_data = Db::table('daily_data')
            ->alias('d')
            ->field('d.*,c.channel_name')
            ->join('channel_type c', 'c.code=d.type')
            ->where('uid', $uid)
            ->order('date desc')
            ->limit(6)
            ->page($page)
            ->select();
        foreach ($daily_data as &$item) {
            $item['uid'] = Db::name('users')->where('id', $item['uid'])->value('name');
            $item['succ_money_sum'] = number_format($item['succ_money_sum'] / 100, 2);
            $item['money_sum'] = number_format($item['money_sum'] / 100, 2);
        }
        if (request()->isPost()) {
            // 上下分
            if ($type == 1) {
                $data['list'] = $list;
                $data['page'] = $page;
                return $data;
            }
            // 日切数据
            if ($type == 2) {
                $data['list'] = $daily_data;
                $data['page'] = $page;
                return $data;
            }
        }
        //日志类型
        $record_type = Db::table('record_type')->select();
        $this->assign('record_type',$record_type);


        $this->assign('daily_data', $daily_data);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function login()
    {
        $this->exit_login();
    }

    //解除店员
    public function lift_clerk()
    {
        $bool = false;
        Db::startTrans();
        try {
            $id = input('id');
            $app_id = Db::table('top_account_assets')
                ->where('id', $id)
                ->limit(1)
                ->value('app_id');
            Db::table('top_account_assets')
                ->where('app_id', $app_id)
                ->where('is_clerk', 1)
                ->setDec('clerk_sum');
            Db::table('top_account_assets')
                ->where('id', $id)
                ->update([
                    'app_id' => 'Pcode_cloudesc_Bind_' . strtoupper(substr(md5($id), 0, 6)),
                    'is_clerk' => 0
                ]);
            $bool = true;
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $bool = false;
        }
        if ($bool) {
            return ['code' => '0000', 'msg' => '解除成功'];
        }
        return ['code' => '1111', 'msg' => '解除失败，请稍后再试'];
    }

    //添加二维码图片
    public function add_qrcode()
    {
        if (request()->isAjax()) {

            $data = input();
            $uid = $this->login_user['id'];
            if (!$data['amount']) {
                $return_data = ['code' => '1111', 'msg' => '金额不能为空'];
                return $return_data;
            }
            $bool = Db::table('top_child_account')->where('pid', $data['id'])->where('amount', $data['amount'] * 100)->limit(1)->find();
            if ($bool) {
                $return_data = ['code' => '1111', 'msg' => '该账户已添加过金额为' . $data['amount'] . '元的二维码'];
                return $return_data;
            }


            $user_fee = Db::table('user_fee')->where('uid', $uid)->where('taid', $data['pay_type'])->value('fee');
            if (empty($user_fee)) {
                $return_data = ['code' => '1111', 'msg' => '账户暂未配置费率，请联系管理员'];
                return $return_data;
            }

            $add_data = [
                'pid' => $data['id'],
                'public_key' => $data['img'],
                'fee' => $user_fee,
                'private_key' => '',
                'amount' => $data['amount']

            ];
            if (in_array($data['pay_type'], [2, 5])) {
                $add_data['amount'] = $data['amount'] * 100;

            } else {
                Db::table('top_child_account')->where('pid', $data['id'])->delete();

            }
//            dump($add_data);die;
            $bool = Db::table('top_child_account')->insert($add_data);
            if ($bool) {
                $return_data = ['code' => '0000', 'msg' => '添加成功'];

            } else {
                $return_data = ['code' => '1111', '添加失败，请稍后再试'];
            }

            return $return_data;

        } else {


            $id = input('id');

            $account_assets = Db::table('top_account_assets')->where('id', $id)->limit(1)->find();
            $this->assign('account_assets', $account_assets);
            $channel = Db::table('channel_type')->where('id', 'in', [2, 5, 1022, 1023, 4])->where('status', 1)->select();
            $this->assign('channel', $channel);
            return $this->fetch('code');

        }


    }

    //获取二维码
    public function get_qrcode($id)
    {

        $img = Db::table('top_child_account')
            ->where('pid', $id)
            ->limit(1)
            ->value('public_key');

        if (empty($img)) {
            return ['code' => '1111', 'msg' => '请先添加收款二维码'];
        } else {
            return ['code' => '0000', "msg" => $img];
        }


    }

    // 删除账号
    public function del_account()
    {
        if (!request()->isAjax()) {
            return $this->index();
        }

        $id = input('post.id');

        $time = Db::table('mch_order')->where('pay_type', $id)->limit(1)->order('accept_time desc')->value('accept_time');

        if (!empty($time)) {
            $now_time = time() - $time;
            $seven = 24 * 60 * 60 * 7;
            if ($now_time < $seven) {
                $ret['success'] = 2;
                $ret['hint'] = '不能删除七天内有收款的账户';
                return $ret;
            }

        }
        //如果是店员账号，在查询是否有店长账号
        $is_clerk = Db::table('top_account_assets')
            ->where('id', $id)
            ->limit(1)
            ->find();
        if ($is_clerk['is_clerk'] == 1) {
            $app_id = Db::table('top_account_assets')
                ->where('app_id', $is_clerk['app_id'])
                ->limit(1)
                ->find();
            if ($app_id) {
                return ['success' => 2, 'hint' => '有绑定的店长账号，暂不能删除'];
            }
        } else {

            $bool = Db::table('top_account_assets')->where('app_id', $is_clerk['app_id'])->where('is_clerk', 1)->setDec('clerk_sum');

        }


        $bool = Db::table('top_account_assets')->where('id', $id)->delete();
        $bool2 = Db::table('top_child_account')->where('pid', $id)->delete();
        if ($bool) {


            $ret['success'] = 1;
            $ret['hint'] = '操作成功';
        } else {
            $ret['success'] = 2;
            $ret['hint'] = '操作失败';
        }


        return $ret;


    }
  //添加账号
    public function add_account(){


        $uid = $this->login_user['id'];
        if(request()->isAjax()){
            
            $data = input();
            
            $result = $this->validate($data,
            [
                'pay_type|收款码类型'  => 'require',
                'group_id|所属分组'    => 'require',
                'name|真实账户名'=>'require',
                'show_name|账户昵称'=>'require',
            ]);
        
        //验证判断必填项
            if(true !== $result){
                // 验证失败 输出错误信息
                return ['code'=>20001,'msg'=>$result];exit;
            }elseif($data['pay_type'] == 1022 && empty($data['receive_account'])){
                return ['code'=>20001,'msg'=>'支付宝账户不能为空'];exit;

            }
                
          
            $channel_data = Db::table('channel_type')->where('code',$data['pay_type'])->limit(1)->find();

            $add_data = [
                'uid'=>$uid,
                'sql_name'=>$channel_data['show_code'],
                'name'=>$data['name'].'_'.$data['show_name'],
                'show_name'=>$channel_data['show_code'],
                'type'=>$data['pay_type'],
                'group_id'=>$data['group_id'],
               // 'app_id'=>'Pcode_cloudesc_Bind_'.substr(md5(time().mt_rand(100,10000)),0,6),

            ];
            if($data['status'] == 'true'){
                $add_data['is_clerk'] = 1;
            }
            if($data['low_mode'] == 'true'){
                $add_data['low_mode'] = 1;
            }
          
            
            if(!empty($data['receive_account'])){
                $add_data['receive_account'] = $data['receive_account'];

            }
             $bool = Db::table('top_account_assets')->insertGetId($add_data);
            if($bool){
                Db::table('top_account_assets')->where('id',$bool)->update(['app_id'=>'Pcode_cloudesc_Bind_'.strtoupper(substr(md5($bool),0,6))]);
                if($data['pay_type'] == '1032'){
                    Db::table('top_child_account')->insert(['pid'=>$bool]);
                }
                $return_data = ['code'=>'0000','msg'=>'添加成功'];

            }else{
                $return_data = ['code'=>'1111','添加失败，请稍后再试'];
            }

            return $return_data;

        }else{
            $uid = $this->login_user['id'];
            $code = Db::table('user_fee')->field('taid')->where('uid',$uid)->select();
            $code = array_column($code, 'taid');
          
            $channel = Db::table('channel_type')
                ->where('id','in',$code)
                ->where('status',1)
                ->order('order')
                ->select();
            $this->assign('channel',$channel);

            $group_name = '';
            if(!empty($channel)){
               $group_name = Db::table('group')
                   ->where('type',$channel[0]['code'])
                   ->where('uid',$uid)
                   ->order('id desc')
                   ->select();
            }

            $this->assign('group_name',$group_name);
            $users = Db::table('users')->field('id,name')->where('state','66')->select();
            $this->assign('users',$users);
            return $this->fetch();


        }
 
  }
  //获取分组列表
  public function group_list($type){

        $group_list = Db::table('group')
        ->where('type',$type)
        ->where('uid',$this->login_user['id'])
        ->select();
        return $group_list;


  }
     //添加下级代理
    public function add_users_group(){

        if(request()->isAjax()){

            $data = input();
            
            $result = $this->validate($data,
                [
                'name|代理名称'  => 'require',
                'email|邮箱地址'    => 'email',
                'phone_num|手机号码'=>'require',
                'pass|密码'=>'require|length:6,12',
                'trading_pass|交易密码'=>'require|length:6,12',

            ]);
        //验证判断必填项
            if(true !== $result){
                // 验证失败 输出错误信息
                return ['code'=>20001,'msg'=>$result];exit;
            }elseif(!is_mobile_phone($data['phone_num'])){

                return ['code'=>20001,'msg'=>'手机号码格式错误'];exit;

            }
            
            $uid = $this->login_user['id'];
            $usersModel = Db::name('Users');
        
            //验证手机号和邮箱有没有被注册
            $sql_phone = $usersModel->where('phone_num',$data['phone_num'])->value('phone_num');
        
            if ($sql_phone!=null) {
                return ['code'=>20001,'msg'=>'手机号码已被注册'];exit;
            }


            $email_phone = $usersModel->where('email',$data['email'])->value('email');

            if ($email_phone!=null) {
                return ['code'=>20001,'msg'=>'该邮箱已绑定账户，请更改邮箱'];exit;
            }

            $Getfirstchar = new \app\index\model\Getfirstchar(); 
         
            $data['merchant_cname'] = strtoupper($Getfirstchar->pinyin($data['name']));
            


            $data['pass'] = encryption($data['pass']);
            $data['trading_pass'] = encryption($data['pass']);
            
            //增加谷歌验证码
            $gangsta = new \Google\Authenticator\Authenticator();
            $data['authenticator'] = $gangsta->generateSecret();
            $key = time().'key'.rand(0,1000);
            $data['key'] = encryption($key);
            $data['join_time'] = time();
            $data['state'] = 90;
            $data['superior'] = $this->login_user['id'];
            
            Db::startTrans();

            $id = $usersModel->insertGetId($data);
          
            $bool3 = Db::table('assets')->insert(['uid'=>$id]);

            $bool2 = Db::execute("update users set subordinate=CONCAT(subordinate,',','".$id."') where id = ".$uid);
  
            $this_user_fee = Db::table('user_fee')
                ->field('fee,taid')
                ->where('uid',$uid)
                ->select();
            foreach ($this_user_fee as $k => $v) {
                $this_user_fee[$k]['uid'] = $id;
            }
            if(empty($this_user_fee)){

                 return ['code'=>'1111','msg'=>'账户未配置通道，暂时无法添加代理'];
            }

            //$bool4 = Db::table('user_fee')->insertAll($this_user_fee);

            if($bool2&&$bool3){
                $redis = new Redis();
                $redis->rm('magent_data_index_'. $this->login_user['id']);
                $redis->rm('magent_data_daili_'. $this->login_user['id']);
              
              
                Db::commit(); 
                return ['code'=>'0000','msg'=>'添加成功'];
            }
                
            Db::rollback();
            return ['code'=>'1111','msg'=>'添加失败，请稍后再试'];
        }

        return $this->fetch();



    }
  
  //在线充值
    public function top_up(){

        $data = input();
        
        $result = $this->validate($data,
        [
            'amount|充值金额'  => 'require|number|>=:500',
            'name|开户姓名'  => 'require',
            'card_num|卡号后四位'  => 'require',
            'remark|充值附言'  => 'require',
        ]);
    
        $data['uid'] = $this->login_user['id'];
    //验证判断必填项
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'info'=>$result];exit;
        }
        
        //查询开启的收款账户
         $account_data = Db::table('top_account_assets')
            ->alias('t')
            ->field('t.id,t.receive_account,ta.private_key,ta.public_key,ta.id as tcid,t.type')
            ->join('top_child_account ta','ta.pid = t.id')
            ->where('t.type',1033)
            ->where('t.status',1)
            ->limit(1)
            ->find();
        //添加订单
        $add_data = [
            'uid'=>519,
            //用户id
            'note_ext'=>$data['uid'],
          
            'order_num'=>'MSSF_'.date('YmdHis').mt_rand(1000,9999).$data['uid'],
            //下级商户订单号
            
            'pay_amount'=>$data['amount']*100,
            //订单金额

            'notify_url'=>'http://all.jvapi.com/index/index/callback_url',
            //异步回调地址

            'return_url'=>'',
            //同步跳转地址

            'ext'=>'码商编号：  '.$this->login_user['merchant_cname'].'_'.$data['uid'].'<br>客户姓名：  '.$data['name'].'<br>卡号：  '.$data['card_num'].'<br>附言：  '.$data['remark'],
            //扩展信息，备注

            'pay_type'=>$account_data['id'],
            //支付方式，上级接口

            'tcid'=>$account_data['tcid'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值
            'type'=>$account_data['type'],
          
            
        ];
      $bool = Db::table('mch_order')->insert($add_data);
      if($bool){
            return ['code'=>'0000','info'=>'请求成功，请及时完成充值','url'=>THIS_URL.'/magent/user/card_number?id='.$account_data['id']];
      }else{
            return ['code'=>'1111','info'=>'系统繁忙，请稍后再试'];
      }
      

    }
  //账户下分
  public function cash_out(){
    
    if(request()->isAjax()){
        $data = input();
        $result = $this->validate($data,
        [
            'amount|提现金额'  => 'require|number|<=:50000',
            'name|开户姓名'  => 'require',
            'bank_name|开户银行'  => 'require',
            'card_num|银行账户'  => 'require',
            'trading_pass|交易密码'  => 'require',
        ]);
    
        $uid = $this->login_user['id'];
    //验证判断必填项
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'info'=>$result];exit;
        }
        
        //查询码商的可提现余额
        $amount = Db::table('assets')->where('uid',$uid)->limit(1)->value('margin');
        
        $data['amount'] = $data['amount']*100;
        if($amount < $data['amount']){
            return ['code'=>20001,'info'=>'保证金金额不足哦'];die;
        }

        $user_data = Db::table("users")->where('id',$uid)->find();

        if (encryption($data['trading_pass'])!==$user_data['trading_pass']) {
            return ['code'=>20001,'info'=>'交易密码错误，5次错误后账户自动锁定'];die;
        }

        if (!captcha_check($data['captcha'])) {
                $ret['code'] = 3;
                $ret['info'] = '验证码错误!';
                return $ret;
            }   
        $pay_Model = model('Withdrawal');
        $result_data = $pay_Model->add_withdrawal_order($data,$uid);
      	$result_data = json_decode($result_data,true);

     
        return ['code'=>$result_data['code'],'info'=>$result_data['info']];


        die;
      
    
    }else{
        $amount = Db::table('assets')->where('uid', $this->login_user['id'])->value('margin');
        $this->assign('amount',$amount/100);
        return $this->fetch();
    }
    
    
    
    
  }
//账户上分
 public function charge_in(){
    
    return $this->fetch();
    
  }
  
 //展示收款账号
 public function card_number(){
    
    $id = input('id');
    $uid = $this->login_user['id'];
    //收款账号信息
    $account_data = Db::table('top_account_assets')
        ->alias('t')
        ->field('t.id,t.receive_account,ta.private_key,ta.public_key,ta.id as tcid,t.type')
        ->join('top_child_account ta','ta.pid = t.id')
        ->where('t.id',$id)
        ->where('t.status',1)
        ->limit(1)
        ->find();
    
    $this->assign('account_data',$account_data);
   
    return $this->fetch();
    
  }
  
  

}