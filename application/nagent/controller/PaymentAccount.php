<?php
namespace app\nagent\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Validate;
use think\Cache;
class PaymentAccount extends UserCommon{
    public function index(){
      
      	$where10 = '';
		

        $start_time = input('get.start_time');
        $end_time = input('get.end_time');
        $order_str = input('get.order');
        $email = input('get.email');
      	$type = input('get.type');
		$account_type = input('get.account_type');
        $group_id = input('get.group_id');
      	$quotient = input('get.quotient');
      	$name = input('name');
        $app_id = input('clerk_id');
      


        $where12['s.uid'] = $this->login_user['id'];
        $channel_id = Db::table('top_account_assets')->field('id')->where('uid',$this->login_user['id'])->select();

        $channel_id = array_column($channel_id, 'id');

        $where10['s.id'] = ['in',$channel_id];

		$where21 = '';
        if ($email) {
            $where1['s.email'] = $email;
        }else{
            $where1 = '';
        }
      
      	if($name){
        	$where1['s.name|s.app_id'] = ['like','%'.$name.'%'];
        }
      	if($app_id){
        	$where1['s.app_id'] = $app_id;
        }
      
        if($group_id){
            $where1['s.group_id'] = $group_id;
        }
      	if($type){
        	$where1['s.type']= $type;
        }
      	if($quotient){
        	$where21['s.quotient_id']= $quotient;
        }
        if (!$order_str) {
            $order_str = 'id';
        }
        if ($order_str!='id'&&$order_str!='status') {
            $order_str = $order_str.' desc';
        }
        if ($start_time==null||$end_time==null) {
            $start_time = date('Y-m-d',time());
            $end_time = date('Y-m-d',time()+86400);
        }
        $join_where = 'm.accept_time>'.strtotime($start_time).' and m.accept_time<'.strtotime($end_time);

        $secret_key_data = Db::table('top_account_assets')
            ->alias('s')
            ->field('s.id,s.fee_sum,s.server_url,s.name,s.withdrawal_sum,s.recharge_sum,s.money,sum(m.pay_amount) as sum_amount,s.status,s.receive_account,s.type,s.app_id,ct.code,ct.channel_name,ct.show_code,ct.show_name,ct.is_platform,s.is_clerk,g.group_name,s.low_mode')
            ->join('mch_order m','m.pay_status=1 and order_type=1 and m.pay_type=s.id and '.$join_where,'left')
          	->join('channel_type ct','ct.id = s.type')
            ->join('group g','g.id = s.group_id')
            ->where($where1)
          	->where($where10)
          	->where($where12)
          	->where($where21)
          	->order('s.id desc,s.app_id desc')
            ->group('s.id')
            ->paginate(10,false,[
                'query' => request()->param()
                ])
            ->each(function($item, $key){
              	//今日收单
              	$item['today_succ_count'] = Db::table('mch_order')->where('pay_type',$item['id'])->where('order_type',1)->where('pay_status',1)->whereTime('accept_time','today')->count();
              	$item['today_count'] = Db::table('mch_order')->where('pay_type',$item['id'])->where('order_type',1)->whereTime('accept_time','today')->count();
                $item['count'] = Db::table('mch_order')->where('pay_type',$item['id'])->where('order_type',1)->count();
                $item['succ_count'] = Db::table('mch_order')->where('pay_type',$item['id'])->where('pay_status',1)->where('order_type',1)->count();
              	$item['count_img'] = Db::table('top_child_account')->where('pid',$item['id'])->count();

                if($item['is_clerk'] == 2){
                    $item['clerk'] = Db::table('top_account_assets')->where('app_id',$item['app_id'])->where('is_clerk',1)->limit(1)->value('name');
                }

                return $item;
            });
      	
        $sum_money = Db::table('top_account_assets')->alias('s')->where($where10)->where($where21)->where($where12)->sum('money');
        $where = '';
      
        $uid = $this->login_user['id'];
        $code = Db::table('user_fee')->field('taid')->where('uid',$uid)->select();
        $code = array_column($code, 'taid');
        $where['id'] = ['in',$code];


        //输入通道名称
      	$channel = Db::table('channel_type')->where($where)->where('status',1)->order('id desc')->select();

        //所属分组
        $group_name = Db::table('group')->where('uid',$uid)->order('id desc')->select();
      
      	$quotient = Db::table('top_account_assets')->where('uid',$uid)->where('is_clerk',1)->select();
      
      	


        $html_data = [
            'secret_key_data'=>$secret_key_data,
            'sum_money'=>$sum_money,
          	'channel'=>$channel,
            'group_name'=>$group_name,
         	'quotient'=>$quotient,
        ];

        
        return $this->fetch('index',$html_data);
    }
    
    public function update_status(){
        //开、关收款账户
        if (!request()->isAjax()) {
            return $this->index();
        }

        $id = input('id');
        $status = input('status');
        if($status == 'true'){
            $status = 2;
        }else{

            $status = 1;

        }

        if ($status==1) {
            $bool = Db::table('top_account_assets')->where('id',$id)->update(['status'=>2]);
          	$bool2 = Db::table('top_child_account')->where('pid',$id)->update(['status'=>2]);
          	//$bool3 = Db::table('quata')->where('id',$id)->update(['status'=>1]);
            if ($bool) {
              	cache("secret_key_arr_wechat",null);
              	//cache("secret_key_arr",null);
                $ret['success'] = 1;
                $ret['hint'] = '关闭成功';
            }else{
                $ret['success'] = 2;
                $ret['hint'] = '关闭失败';
            }
        }elseif($status==2){
            $bool = Db::table('top_account_assets')->where('id',$id)->update(['status'=>1]);
          	$bool2 = Db::table('top_child_account')->where('pid',$id)->update(['status'=>1]);
          	//$bool3 = Db::table('quata')->where('id',$id)->update(['status'=>0]);
          	cache("secret_key_arr_wechat",null);
          	//cache("secret_key_arr",null);
            $ret['success'] = 1;
            $ret['hint'] = '开启成功';
        }

        return $ret;
    }
  
  //开启关闭低收款模式
      public function low_mode(){
        //开、关收款账户
        if (!request()->isAjax()) {
            return $this->index();
        }

        $id = input('id');
        $status = input('status');
        if($status == 'true'){
            $status = 1;
          	$msg = '开启';
        }else{
          	$msg = '关闭';
            $status = 2;
        }

        $bool = Db::table('top_account_assets')->where('id',$id)->update(['low_mode'=>$status]);
        if ($bool) {

            $ret['success'] = 1;
            $ret['hint'] = $msg.'成功';
        }else{
            $ret['success'] = 2;
            $ret['hint'] = '关闭失败';
        }


        return $ret;
    }

    //设置为店员
    public function update_clerk(){

        $id = input('id');
        $status = input('status');

        $top_account_assetsModel = Db::name('top_account_assets');
        //查询当前账号下是否已经有店员号
        $app_id = $top_account_assetsModel
            ->where('id',$id)
            ->limit(1)
            ->find();
        $is_hava_app_id = $top_account_assetsModel
            ->where('app_id',$app_id['app_id'])
            ->where('id','<>',$id)
            ->limit(1)
            ->value('app_id');
        if($is_hava_app_id){

            if($app_id['is_clerk'] == 1){
                return ['code'=>'1111','msg'=>'当前账号下有绑定的店长号，请取消绑定后在进行修改'];
            }elseif($app_id['is_clerk'] == 2){

                return ['code'=>'1111','msg'=>'当前账号有绑定的店员号，请取消绑定后在进行修改'];
            }
            
        }

        if($status == 'true'){
            $status = 1;

        }else{

            $status = 0;
            
        }
        $bool = $top_account_assetsModel->where('id',$id)
                ->update([
                    'is_clerk'=>$status
                ]);
        if($bool){
            return ['code'=>'0000','msg'=>'修改成功'];
        }else{
            return ['code'=>'1111','msg'=>'修改失败'];
        }

    }

    public function bind_clerk($id){
        

        if(request()->isAjax()){

            $id = input('id');
            $clerk_id = input('clerk_id');
            $app_id = Db::table('top_account_assets')
                ->where('id',$clerk_id)
                ->limit(1)
                ->value('app_id');
            $bool = Db::table('top_account_assets')
                ->where('id',$id)
                ->update([
                    'app_id'=>$app_id,
                    'is_clerk'=>2
                ]);
            

            if($bool){


                $bool1 = Db::table('top_account_assets')
                ->where('id',$clerk_id)
                ->setInc('clerk_sum');
                if($bool1){
                    return ['code'=>'0000','msg'=>'绑定成功'];
                }
            }
            

              
            return ['code'=>'1111','msg'=>'已经绑定成功该店员'];
            




        }else{
            $type = Db::table('top_account_assets')
                ->where('id',$id)
                ->limit(1)
                ->value('type');

            $uid = $this->login_user['id'];
            $clerk_data = Db::table('top_account_assets')
                ->where('uid',$uid)
                ->where('type',$type)
                ->where('is_clerk',1)
                ->where('clerk_sum','<',5)
                ->select();
            $this->assign('clerk_data',$clerk_data);
            $this->assign('id',$id);
            return $this->fetch(); 
        }

    }

//解除店员
    public function lift_clerk($id){

        $app_id = Db::table('top_account_assets')
            ->where('id',$id)
            ->limit(1)
            ->value('app_id');

        $bool = Db::table('top_account_assets')
            ->where('id',$id)
            ->update([
                'app_id'=>'Pcode_cloudesc_Bind_'.strtoupper(substr(md5($id),0,6)),
                'is_clerk'=>0

            ]);
        if($bool){
            $bool1 = Db::table('top_account_assets')
            ->where('app_id',$app_id)
            ->where('is_clerk',1)
            ->setDec('clerk_sum');
            if($bool1){
                return ['code'=>'0000','msg'=>'解除成功'];
            }
        }
        

        return ['code'=>'1111','msg'=>'解除失败，请稍后再试'];
        

    }




    public function child_account(){
        $id = input('get.id');
        $data = Db::table('top_child_account')->field('money,login_user,fee')->where('pid',$id)->select();
        $str = '';
        foreach ($data as $key => $value) {
            $str .= '<p>'.$value['login_user'].'&nbsp;;余额：'.($value['money']/100).'元;费率'.($value['fee']*100).'%</p>';
        }
        // dump($data[0]['fee']*100);die;
        echo  $str;
    }
  	
  	public function del_account(){
    
      	if (!request()->isAjax()) {
            return $this->index();
        }

        $id = input('get.id');

        $time = Db::table('mch_order')->where('pay_type',$id)->limit(1)->order('accept_time desc')->value('accept_time');

        if(!empty($time)){
           $now_time = time()-$time;
            $seven = 24*60*60*7;
            if($now_time < $seven){
                $ret['success'] = 2;
                $ret['hint'] = '不能删除七天内有收款的账户';
                return $ret;
            }

        }
       //如果是店员账号，在查询是否有店长账号
        $is_clerk = Db::table('top_account_assets')
            ->where('id',$id)
            ->limit(1)
            ->find();
        if($is_clerk['is_clerk'] == 1){
            $app_id = Db::table('top_account_assets')
                ->where('app_id',$is_clerk['app_id'])
                ->limit(1)
                ->find();
            if($app_id){
                return ['success'=>2,'hint'=>'有绑定的店长账号，暂不能删除'];
            }
        }else{
        	
          $bool = Db::table('top_account_assets')->where('app_id',$is_clerk['app_id'])->where('is_clerk',1)->setDec('clerk_sum');
        
        }


        $bool = Db::table('top_account_assets')->where('id',$id)->delete();
        $bool2=Db::table('top_child_account')->where('pid',$id)->delete();
            if ($bool) {
              	
              
              
                $ret['success'] = 1;
                $ret['hint'] = '操作成功';
            }else{
                $ret['success'] = 2;
                $ret['hint'] = '操作失败';
            }


        return $ret;
      
    
    }
  public function delDirAndFile($path, $delDir = FALSE) { 
            $handle = opendir($path); 
            if ($handle) { 
                while (false !== ( $item = readdir($handle) )) { 
                    if ($item != "." && $item != "..") 
                        is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item"); 
                } 
                closedir($handle); 
                if ($delDir) 
                    return rmdir($path); 
            }else { 
                if (file_exists($path)) { 
                    return unlink($path); 
                } else { 
                    return FALSE; 
                } 
            } 
        }
        public function debit_money()
    {

        if(!request()->isAjax()){
            echo '非法请求';die;
        }


        $data = input();
		
        $result = $this->validate($data,
        [
            'pass|口令'  => 'require',
            'money|金额'    => 'require|number',
          	'id|必要参数'=>'require',
        ]);
    
      
    //验证判断必填项
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'msg'=>$result];exit;
        }
        if($data['pass'] != 'xz'){

            return ['code'=>20001,'msg'=>'下账口令错误'];exit;

        }

        $data['money'] = $data['money']*100;
        $money = $data['money']*100;
      	$top_child_accountModel = Db::name('top_child_account');
      	$top_account_assetsModel = Db::name('top_account_assets');
        Db::startTrans();
      

        $bool1 = $top_account_assetsModel->where('id',$data['id'])->setInc('withdrawal_sum',$data['money']);
        $bool2 = $top_account_assetsModel->where('id',$data['id'])->setDec('money',$data['money']);
      	
        while ($data['money'] > 0) {
         
            $user_fee_data = $top_child_accountModel->where('pid',$data['id'])->where('money','>',0)->limit(1)->find();
          	if(empty($user_fee_data)){
            
              	return $data = ['code'=>'1111','msg'=>'可下账余额不足'];die;
            
            }
            if($user_fee_data['money'] < $data['money']){
               
                $bool8 = $top_child_accountModel->where('id',$user_fee_data['id'])->update(['money'=>0]);
              	$bool8 = $top_child_accountModel->where('id',$user_fee_data['id'])->setInc('withdrawal_sum',$user_fee_data['money']);
                $data['money'] -= $user_fee_data['money'];

            }else{

                $bool8 = $top_child_accountModel->where('id',$user_fee_data['id'])->setDec('money',$data['money']);
              	$bool8 = $top_child_accountModel->where('id',$user_fee_data['id'])->setInc('withdrawal_sum',$data['money']);
                $data['money'] = 0;

            }

        }

		$bool9 = Db::name('record')->insert(['date'=>date('Y-m-d H:i:s'),
            'content'=>'下账金额(元)：'.$money/100,
            'time'=>time(),
            'operator'=>session('login_user','','sadmin')['merchant_cname'].'_'.session('login_user','','sadmin')['id'].'_'.session('login_user','','sadmin')['name'].'_账户ID：'.$data['id']
          	]);

        if($bool1&&$bool2&&$bool8&&$bool9){
            $data = ['code'=>'0000','msg'=>'下账成功'];
            Db::commit();
        }else{
            $data = ['code'=>'0000','msg'=>'下账失败'];
            Db::rollback();
        }

        return $data;




    }


//查询是否有商户ID

    public function is_mch_id(){


        $return = Db::name('top_account_assets')->where('type',1)->limit(1)->value('mch_id');
        if(empty($return)){

            $return = ['code'=>'0000'];


        }else{
            $return = ['code'=>'1111'];

        }
        return $return;

    }

 //添加支付宝通道
    public function add_aisle(){


    if(request()->isAjax()){

         $data = input();
         if($data['add_type'] == 1){

            if($data['is_enterprise'] == 1){

                $result = $this->validate($data,
                [
                    'name|通道名称'  => 'require',
                    'sql_name|通道类型'    => 'require',
                    'show_name|展示给下级名称'=>'require',
                    'app_id|设备app_id'=>'require',
                    'mch_id|商户APPID'=>'require',
                    'private_key|支付私钥'=>'require',
                    'public_key|支付公钥'=>'require',
                    'fee|通道手续费率'=>'require|number',
                    'receive_name|收款支付宝姓名'=>'require',
                    'alipay_name|支付宝实名收款姓名'=>'require',
                    'alipay_amount|支付宝收款账号'=>'require',
                    'receive_amount|账户userId'=>'require|number',
                ]);
            //验证判断必填项
                if(true !== $result){
                    // 验证失败 输出错误信息
                    return ['code'=>20001,'msg'=>$result];exit;
                }
                $private_key = base64_encode(base64_encode(trim_all($data['private_key'])));
                $public_key = base64_encode(base64_encode(trim_all($data['public_key'])));
                $fee = '';
                if($this->login_user['state'] == 90){

                    $uid = $this->login_user['id'];
                    $account_type = 3;
                    $fee = Db::table('user_fee')->where('uid',$this->login_user['id'])->where('taid',2)->value('fee');
                    if(empty($fee)){
                        return ['code'=>'1111','msg'=>'费率未配置，请联系客服'];die;
                    }
                }else{
                    $uid = 1;
                    $account_type = 2;
                }



                $pid = Db::name('top_account_assets')->insertGetId([
                    'sql_name'=>$data['sql_name'],
                    'uid'=>$uid,
                    'name'=>$data['name'],
                    'server_url'=>'',
                    'status'=>2,
                    'type'=>2,
                    'app_id'=>'Pcode_cloudesc_Bind_'.$data['app_id'],
                    'show_name'=>$data['show_name'],
                    'settlement_way'=>'D0',
                    'associated_account'=>'',
                    'is_enterprise'=>$data['is_enterprise'],
                    'alipay_public_key'=>$public_key,
                    'alipay_private_key'=>$private_key,
                    'mch_id'=>$data['mch_id'],
                    'receive_account'=>$data['alipay_name'].'|'.$data['alipay_amount'],
                    'account_type'=>$account_type
                ]);

       
            }elseif($data['is_enterprise'] == 0){

                $result = $this->validate($data,
                [
                    'name|通道名称'  => 'require',
                    'sql_name|通道类型'    => 'require',
                    'show_name|展示给下级名称'=>'require',
                    'app_id|设备app_id'=>'require',
                    'fee|通道手续费率'=>'require|number',
                    'receive_name|收款支付宝姓名'=>'require',
                    'receive_amount|账户userId'=>'require|number',
                ]);
            //验证判断必填项
                if(true !== $result){
                    // 验证失败 输出错误信息
                    return ['code'=>20001,'msg'=>$result];exit;
                }
                $fee = '';
                if($this->login_user['state'] == 90){

                    $uid = $this->login_user['id'];
                    $account_type = 3;
                    $fee = Db::table('user_fee')->where('uid',$this->login_user['id'])->where('taid',2)->value('fee');
                    if(empty($fee)){
                        return ['code'=>'1111','msg'=>'费率未配置，请联系客服'];die;
                    }

                }else{
                    $uid = 1;
                    $account_type = 2;
                }
                $pid = Db::name('top_account_assets')->insertGetId([
                    'sql_name'=>$data['sql_name'],
                    'name'=>$data['name'],
                    'uid'=>$uid,
                    'server_url'=>'',
                    'status'=>2,
                    'type'=>2,
                    'app_id'=>'Pcode_cloudesc_Bind_'.$data['app_id'],
                    'show_name'=>$data['show_name'],
                    'settlement_way'=>'D0',
                    'associated_account'=>'',
                    'is_enterprise'=>$data['is_enterprise'],
                    'account_type'=>$account_type,
                    'group_id'=>$data['group_id'],
                ]);

            }



            
         }else{

                 $result = $this->validate($data,
                [
                    'name_account|主账户'  => 'require',
                    'mch_id|商户APPID'=>'require',
                    'private_key|支付私钥'=>'require',
                    'public_key|支付公钥'=>'require',
                    'fee|通道手续费率'=>'require|number',
                    'receive_name|收款支付宝姓名'=>'require',
                    'receive_amount|账户userId'=>'require|number',
                ]);
            
            //验证判断必填项
                if(true !== $result){
                    // 验证失败 输出错误信息
                    return ['code'=>20001,'msg'=>$result];exit;
                }

                $pid = $data['name_account'];
         }


       
        $bool2 = Db::name('top_child_account')->insert([
            'pid'=>$pid,
            'private_key'=>'',
            'public_key'=>'',
            'mch_id'=>$data['receive_amount'],
            'fee'=>$data['fee'],
            'status'=>2,
            'login_user'=>$data['name'],
            'pass'=>'',
            'receive_account'=>$data['receive_name'],
            'fee'=>$fee,

        ]);

        return ['code'=>'0000','msg'=>'添加成功'];

    }else{

        $group_name = Db::table('group')->where('uid',$this->login_user['id'])->order('id desc')->select();
        $this->assign('group_name',$group_name);

        return $this->fetch();

    }

  }
  public function top_account(){

        $data = Db::name('top_account_assets')->select();

        return $data;

  }


//批量开启账户
  public function open_account(){


    $data = input()['value'];
    if(empty($data)){
        return ['code'=>'1111','msg'=>'至少选择一个需要开启的账户'];
    }
    // dump($data);die;
    $bool1 = Db::table('top_account_assets')->where('id','in',$data)->update(['status'=>1]);
    $bool2 = Db::table('top_child_account')->where('pid','in',$data)->update(['status'=>1]);
    // dump($bool1);
    // dump($bool2);die;
    if($bool1&&$bool2){

         return ['code'=>'0000','msg'=>'开启成功'];
    }else{
         return ['code'=>'1111','msg'=>'开启失败，请稍后再试'];
    }

  }
//批量关闭账户
  public function close_account(){


    $data = input()['value'];
    if(empty($data)){
        return ['code'=>'1111','msg'=>'至少选择一个需要关闭的账户'];
    }
    // dump($data);die;
    $bool1 = Db::table('top_account_assets')->where('id','in',$data)->update(['status'=>2]);
    $bool2 = Db::table('top_child_account')->where('pid','in',$data)->update(['status'=>2]);
    // dump($bool1);
    // dump($bool2);die;
    if($bool1&&$bool2){

         return ['code'=>'0000','msg'=>'关闭成功'];
    }else{
         return ['code'=>'1111','msg'=>'关闭失败，请稍后再试'];
    }

  }

//分组管理
  public function group_management(){


    $uid = $this->login_user['id'];

    
    $group_data = Db::name('group')
    ->alias('g')
    ->field('g.*,ct.channel_name')
    ->join('channel_type ct','ct.code = g.type')
    ->where('uid',$uid)
    ->order('g.type desc')
    ->paginate(10)
    ->each(function($item, $key){
        $item['sum'] = Db::table('top_account_assets')->where('group_id',$item['id'])->where('uid',$item['uid'])->count();
        return $item;
    });

    $this->assign('group_data',$group_data);

    return $this->fetch();



  }

//修改分组状态
  public function status_update(){


    $data = input();

    if($data['status'] == 1){
        $status = 2;
        $msg = '关闭';
    }else{
        $status = 1;
        $msg = '开启';
    }
    Db::startTrans();
    $bool1 = Db::table('group')->where('id',$data['id'])->update(['status'=>$status]);

    // $bool2 = Db::table('top_account_assets')->where('group_id',$data['id'])->update(['status'=>$status]);
   //dump($bool1);dump($bool2);die;
    if($bool1){
        Db::commit();
        return ['code'=>'0000','msg'=>$msg.'成功'];

    }else{
        Db::rollback();
        return ['code'=>'1111','msg'=>$msg.'失败,请稍后再试'];
    }

  }
//今日收款
  public function get_money(){

    $state = $this->login_user['state'];
    $uid = $this->login_user['id'];
    $where = '';

    $where['proxy_id'] = $uid;

    $dayType = input('dayType');

    switch ($dayType) {
        case '1':
            $day = 'today';
            break;
        case '2':
            $day = 'yesterday';
            break;
        
        default:
            $day = 'today';
            break;
    }

    $money = Db::table('mch_order')
        ->where('pay_status',1)
        ->where('order_type',1)
        ->whereTime('accept_time',$day)
        ->where($where)
        ->sum('pay_amount');

    return ['code'=>'0000','msg'=>'获取成功','data'=>$money/100];

  }

  public function add_code(){


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

   public function edit_account($id){

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

            $update = [
                'name'=>$data['name'].'_'.$data['show_name'],
                'receive_account'=>$data['receive_account']

            ];

            $bool = Db::table('top_account_assets')
                ->where('id',$data['id'])
                ->where('uid',$this->login_user['id'])
                ->update($update);
            if($bool){
                return ['code'=>'0000','msg'=>'修改成功'];exit;

            }else{
                return ['code'=>20001,'msg'=>'没有修改数据哦'];exit;
            }

    }else{
       $account_data = Db::table('top_account_assets')
        ->alias('ta')
        ->field('ct.channel_name,g.group_name,ta.*')
        ->join('channel_type ct','ct.code=ta.type')
        ->join('group g','g.id=ta.group_id')
        ->where('ta.id',$id)
        ->where('ta.uid',$this->login_user['id'])
        ->find();


        if(empty($account_data)){
            echo '非法请求';die;
        }

        $name = explode('_', $account_data['name']);
        $account_data['show_name'] = $name[1];
        $account_data['name'] = $name[0];

        $this->assign('account_data',$account_data);
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

  //获取设备最后在线时间
    public function get_cache(){
  	
    	$data = input()['data'];
    	$now_time = time();
    	$retiurn_data = [];
    	foreach ($data as $k => $v) {
            $time = $now_time - cache('DeviceNo'.$v);

            if($time < 6 ){
                $msg = '<sapn style="color:green;">'.$time.' 秒前在线 ●';
            }elseif($time>6 && $time<180){
                $msg = '设备掉线<b style="color:orange;">'.($time-5).'</b>秒';

            }else{
                 $msg = '<sapn style="color:#999;">设备离线</span>';
            }
			$retiurn_data[$v] = $msg;
			
       }
    $retiurn_data = ['code'=>'0000','data'=>$retiurn_data];
    return $retiurn_data;
  
  }
 //获取二维码
  public function get_qrcode($id){

    $img = Db::table('top_child_account')
        ->where('pid',$id)
        ->limit(1)
        ->value('public_key');

    if(empty($img)){

        return ['code'=>'1111','msg'=>'请先添加收款二维码'];
    }else{

        $json = ["title"=>"","id"=>1,"start"=>0,"data"=>[["alt"=>"",'pid'=>"","src"=>$img,"thumb"=>""]]];
        return ['code'=>'0000','json'=>$json];
    }


  } 

  
}   