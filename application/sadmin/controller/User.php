<?php
namespace app\sadmin\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Validate;
class User extends UserCommon{
    public function index(){
  
        return $this->fetch('index');
    }

    public function account(){

         $uid = input('get.uid');
          if ($uid) {
            $uid_arr = explode('_',$uid);
            if (!isset($uid_arr[1])||!is_numeric($uid_arr[1])) {
                echo '<script>alert("商户号错误");window.history.back(-1);</script>';
                die;
            }
            $where1['uid'] = $uid_arr[1];
          }else{
              $where1 = 1;
          }
    
        $admin_status = input('get.admin_status');

        if ($admin_status) {
            if ($admin_status == 4) {
                $where2['u.state'] = ['in',[2,3,4]];
            }else{
                $where2['u.state'] = $admin_status;


            }
        }else{
            $where2 = 1;
        }


        if($this->login_user['state'] == 88 || $this->login_user['state'] == 89){
            $uid_str = $this->login_user['subordinate'].','.$this->login_user['id'];

            $where3['u.id'] = ['in',$uid_str];
            $where4['uid'] = ['in',$uid_str];
            $where5['u.uid'] = ['in',$uid_str];


        }else{
            $where3 = '';
            $where4 = '';
            $where5 = '';
        }

        
        $user_data = Db::table('users')
            ->alias('u')
            ->field('a.*,u.id,u.email,u.name,u.join_time,u.login_time,u.company,u.merchant_cname,u.state,u.bans_num,l.name as level_name,l.operat,l.text,u.superior')
            ->join('assets a','a.uid=u.id','left')
            ->join('level l','l.code=u.state')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            //->where('u.id','<>',$this->login_user['id'])
            ->paginate(10,false,[
                'query' => request()->param()
            ])->each(function($item, $key){
          		$msg = $item['operat'];
          		$item['wechat'] = 0;
                $item['alipay'] = 0;
                $item['solid_code'] = 0;
          		if(in_array($item['state'],[77,90])){
                	if($item['is_open'] == 1){
                    	$msg = '关闭收款';
                    }else{
                    	$msg = '开启收款';
                    }
                   $top_account_assetsModel = Db::name('top_account_assets');
                   $subordinate = $item['id'].','.$this->getBottomUsers($item['id']);
                   $item['wechat'] = $top_account_assetsModel
                        ->where('uid','in',$subordinate)
                        ->where('type','in',[5,1023])
                        ->count();
                   $item['alipay'] = $top_account_assetsModel
                        ->where('uid','in',$subordinate)
                        ->where('type','in',[2,1022])
                        ->count();
                   $item['solid_code'] = $top_account_assetsModel
                        ->where('uid','in',$subordinate)
                        ->where('type','in',[3,4])
                        ->count();
                  
                  
                }
              $item['operat'] = $msg;
          	  
              $item['top_agent'] = Db::table('users')->field('id,merchant_cname')->where('id',$item['superior'])->limit(1)->find();
          	
    		  return $item;
          });

        $sum_money = Db::table('assets')->where($where4)->sum('money');
        
        $user_fee_data = Db::table('user_fee u')
            ->field('u.money,ta.name,u.uid')
            ->join('top_account_assets ta','ta.id=u.taid')
            ->where('u.money','>',0)
            ->where($where5)
            //->where($where)
            ->select();

        $user_fee_arr = '';
        foreach ($user_fee_data as $value) {
            foreach ($user_data as $v) {
                if ($v['uid'] == $value['uid']) {
                    $user_fee_arr[$v['uid']][] = $value['name'].':'.($value['money']/100).'元';
                }
            }
        }
        $user_level_list = Db::table('level')->order('order')->select();


        $html_data = [
            'user_data'=>$user_data,
            'user_fee_arr'=>$user_fee_arr,
            'sum_money'=>$sum_money,
            'user_level_list'=>$user_level_list
        ];
        return $this->fetch('account',$html_data);

    }
  	
  	 public function getBottomUsers($id,$uids=''){
        static $i=1;
        $userList = Db::table('users')
        ->field('id,superior')
        ->where('superior',$id)
        ->select();
       foreach ($userList as $key=>$value){
            $uids .= $value['id'].',';
            $user = Db::table('users')
                ->field('id,superior')
                ->where('superior',$id)
                ->select();
            if($user){
                $uids = $this->getBottomUsers($value['id'],$uids);
            }
        }
        return $uids;
    }
  	
  
       //用户管理
    public function user_management($id){
        if (!in_array($this->login_user['state'],[99,100])) {
            echo '权限不足，请联系管理员';
            die;
        }
        
        $user_data = Db::table('users')
            ->alias('u')
            ->field('u.*,l.name as level_name,a.margin,a.freeze')
            ->join('level l','l.code = u.state')
            ->join('assets a','a.uid = u.id')
            ->where('u.id',$id)->limit(1)->find();
        $this->assign('user_data',$user_data);

        return $this->fetch();


    }

        public function get_key(){
        if ($this->login_user['state']!=100) {
            echo '权限不足，请联系管理员';
            die;
        }
        $data = Db::table('users')->where('id',input('id'))->find();
        $key = encryption($data['key'].$data['merchant_cname'].$data['id']);

        $this->assign('mch_id',$data['merchant_cname'].'_'.$data['id']);
        $this->assign('authenticator',$data['authenticator']);
        $this->assign('key',$key);

        return $this->fetch();
    }
    public function update_enable(){
        if (!request()->isAjax()) {
            return $this->index();
        }
        if ($this->login_user['state']!=100) {
            return '权限不足，请联系管理员';
        }
        $id = input('post.id');
        $state = input('post.state');
        if (in_array($state,[1,4])) {
            $bool = Db::table('users')->where('id',$id)->update(['state'=>2,'login_time'=>time()]);

            if ($bool!==false) {
                return '冻结成功，客户已无法登陆';
            }else{
                return '操作失败，请稍后重试';
            }
        }elseif($state==2){
            $bool = Db::table('users')->where('id',$id)->update(['state'=>4]);

            if ($bool!==false) {
                return '已启用，请为其审核支付功能';
            }else{
                return '操作失败，请稍后重试';
            }
        }elseif($state==3){
            $bool = Db::table('users')->where('id',$id)->delete();
            if ($bool) {
                return '已删除该账户，可重新使用该邮箱手机号注册';
            }else{
                return '操作失败，请稍后重试';
            }
        }
    }
    public function update_open_pay(){
        if (!request()->isAjax()) {
            return $this->index();
        }
        if ($this->login_user['state']!=100) {
            return '权限不足，请联系管理员';
        }
        $id = input('post.id');
        $state = input('post.state');
        if ($state==4) {
            $bool = Db::table('users')->where('id',$id)->update(['state'=>1]);
            if ($bool) {
                return '已开通支付功能。';
            }else{
                return '操作失败，请稍后重试';
            }
        }elseif ($state==1) {
            $bool = Db::table('users')->where('id',$id)->update(['state'=>4]);
            if ($bool) {
                return '已关闭支付功能。';
            }else{
                return '操作失败，请稍后重试';
            }
        }elseif ($state==3) {
            
            $key = time().'key'.rand(0,1000);
            $key = encryption($key);
            $results = Db::table('users')->where('id',$id)->update(['state'=>4,'key'=>$key]);
            if ($results) {
                $bool = Db::table('assets')->insert(['uid'=>$id]);
                if ($bool) {
                    return '直接通过，激活成功';
                }else{
                    return '操作失败，请稍后重试';
                }
            }
        }elseif (in_array($state,[77,90])) {
          	
          	$is_open = Db::table('assets')->where('uid',$id)->value('is_open');
          	if($is_open == 1){
            	$status = 2;
              	$msg = '已关闭收款功能';
              	$msg1 = '系统关闭收款功能';
            }else{
            	$status = 1;
              	$msg = '已开启收款功能';
              	$msg1 = '系统开启收款功能';
            }
            $bool = Db::table('assets')->where('uid',$id)->update(['is_open'=>$status]);
            if ($bool) {
              	$bool9 = Db::name('record')->insert([
                  'date'=>date('Y-m-d H:i:s'),
                  'content'=>$msg1,
                  'time'=>time(),
                  'operator'=>1,
                  'child_id'=>$id,
                  'money'=>0,
                  'type'=>6
                  ]);
              
                return $msg;
            }else{
                return '操作失败，请稍后重试';
            }
        }elseif ($state==2) {
            return '请先启用账户后操作';
        }
    }
    public function update_fee(){
        if (!request()->isAjax()) {
            return $this->index();
        }
        $data = input('post.');
        
        if ($this->login_user['state']!=100) {
            return '权限不足，请联系管理员';
        }
        
        $validate = new Validate([
            'id' => 'require|number',
            'fee' => 'require|number'
        ]);
        if (!$validate->check($data)){
            return '参数错误';
        }
        $bool = Db::table('assets')->where('uid',$data['id'])->update(['fee'=>$data['fee']]);
        if ($bool||$bool==0) {
            return '已修改为：'.($data['fee']/10).'%';
        }else{
            return '修改失败，请联系管理员';
        }
    }


//用户费率列表
    public function fee_list(){
        $uid = input('get.id');
        $user_fee_data = Db::table('user_fee')
            ->field('user_fee.fee,t.channel_name,t.code')
            ->join('channel_type t','t.id=user_fee.taid')
            ->where('uid',$uid)
            ->where('user_fee.status',1)
            ->select();
        $users_data = Db::table('users')->where('id',$uid)->limit(1)->find();

        $this->assign('users_data',$users_data);
        $this->assign('fee_list',$user_fee_data);
        return $this->fetch('user_fee_list');
    }

    public function redirect_key(){
        if (!request()->isAjax()) {
            return $this->index();
        }
        if ($this->login_user['state']!=100) {
            return '权限不足，请联系管理员';
        }
        $id = input('post.id');
        $redirect_key = input('post.redirect_key');
        if ($redirect_key=="重置密钥") {
            $key = time().'key'.rand(0,1000);
            $key = encryption($key);
            $results = Db::table('users')->where('id',$id)->update(['key'=>$key]);
            if ($results) {
                $ret['success'] = 1;
                $ret['hint'] = '重置成功';
            }else{
                $ret['success'] = 2;
                $ret['hint'] = '操作失败';
            }
            return $ret;
        }
    }

    public function go_merchants(){
        $uid = input('get.id');

        $sql_data = Db::table('users')
          	->alias('u')
          	->field('u.*,l.model')
          	->join('level l','l.code=u.state')
            ->where('u.id',$uid)
            ->find();

		
        $state = $sql_data['model'];
        $url = '/'.$sql_data['model'].'/User/index';

       // dump($sql_data);die;

        session(null,$state);
        session('login_user',$sql_data,$state);

        $this->redirect($url);
    }
  	
      public function user_fee(){

        if(request()->ispost()){
		if (!in_array($this->login_user['state'],[100,99,89,88])) {
                 return ['code'=>'1111','msg'=>'权限不足'];
                die;
            }
        $data = input();

            $result = $this->validate($data,
        [
            'uid|用户ID'  => 'require',
            'fee|费率'   => 'require|number|<:1',
            'taid|通道' =>'require',
        ]);
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'msg'=>$result];die;
        } 
		
        $channel_id = Db::table('top_account_assets')->field('id')->where('uid',$data['uid'])->where('type',$data['taid'])->select();
          //dump($data['uid']);die;
         if(!empty($channel_id)){
          	$channel_id = array_column($channel_id, 'id');
           	Db::table('top_child_account')->where('pid','in',$channel_id)->update(['fee'=>$data['fee']]);
         }
         
          
          
        Db::name('user_fee')->where('uid',$data['uid'])->where('taid',$data['taid'])->update(['fee'=>$data['fee']]);

        return ['code'=>'0000','msg'=>'修改成功'];





        }else{
          
			if (!in_array($this->login_user['state'],[100,88])) {
                echo '<script>alert("权限不足，请联系管理员");window.history.back(-1);</script>';
                die;
            }
            $id = input('id');
            $user_fee = Db::name('user_fee')
                ->alias('uf')
                ->field('uf.taid,uf.fee,ta.channel_name as show_name')
                ->join('channel_type ta','ta.id = uf.taid')
                ->where('uf.uid',$id)
                ->select();
            
            $this->assign('user_fee',$user_fee);
            $this->assign('uid',$id);
            return $this->fetch();


        }

    }
  
  	  	//添加code
/**
*@param name账户名 展示的真实收款账户名
  show_name 展示给下游的收款账户名
  pay_type 微信二维码还是支付宝二维码
  app_id 设备唯一标识
  amount_array 添加金额的数组 [50 100 200 400 600 800 1500]
  num 每个金额对应几个码
**/
    public function add_code(){

      if(request()->isPost()){
		if (!in_array($this->login_user['state'],[100,90,99])) {
            return ['code'=>'1111','msg'=>'权限不足'];die;
        }
        
        if($this->login_user['state'] == 90){

          $account_type = 3;
      }else{

          $account_type = 2;
      }
        
        
        $data = input();

        if($data['pay_type'] == 1){
              $type = 1;
              $sql_name = 'wechat';
        }elseif($data['pay_type'] == 0){
              $type = 2;
              $sql_name = 'alipay';
                 
        }elseif($data['pay_type'] == 3){
              $type = 3;
              $sql_name = 'solid_code';
                 
        }

        if($data['the_way'] == 1 && $data['add_type'] == 1 && $data['pay_type'] == 1){
            $fee = '';
            if($this->login_user['state'] == 90){

                    $uid = $this->login_user['id'];
                    $account_type = 3;

                    $fee = Db::table('user_fee')->where('uid',$this->login_user['id'])->where('taid',$type)->value('fee');
                    if(empty($fee)){
                        return ['code'=>'1111','msg'=>'费率未配置，请联系客服'];die;
                    }


                }else{
                    $uid = 1;
                    $account_type = 2;
                }

             $add_data = [
                'sql_name'=>$sql_name,
                'name'=>$data['name'],
                'uid'=>$uid,
                'show_name'=>$data['show_name'],
                'status'=>2,
                'type'=>$type,
                'app_id'=>'Pcode_cloudesc_Bind_'.$data['app_id'],
                'is_enterprise'=>0,
                'account_type'=>$account_type,
                'group_id'=>$data['group_id']
            ];


            $top_id = Db::name('top_account_assets')->insertGetId($add_data);
            $child_data = [
                'pid'=>$top_id,
                'fee'=>$fee

            ];

            $bool = Db::name('top_child_account')->insert($child_data);

            $data = ['code'=>'0000','msg'=>'添加成功','info'=>''];

        }elseif($data['the_way'] == 1 && ($data['add_type'] == 1 || $data['pay_type'] == 3)){

            $img = input('img');
            if(empty($img)){
                return  ['code'=>'1111','msg'=>'聚合码图片必须上传','info'=>''];exit;
            }
			$type = 4;
            $sql_name = 'bank_code';
            $fee = '';
            if($this->login_user['state'] == 90){

                    $uid = $this->login_user['id'];
                    $account_type = 3;

                    $fee = Db::table('user_fee')->where('uid',$this->login_user['id'])->where('taid',$type)->value('fee');
                    if(empty($fee)){
                        return ['code'=>'1111','msg'=>'费率未配置，请联系客服'];die;
                    }


                }else{
                    $uid = 1;
                    $account_type = 2;
                }

             $add_data = [
                'sql_name'=>$sql_name,
                'name'=>$data['name'],
                'uid'=>$uid,
                'show_name'=>$data['show_name'],
                'status'=>2,
                'type'=>$type,
                'app_id'=>'Pcode_cloudesc_Bind_'.$data['app_id'],
                'is_enterprise'=>0,
                'account_type'=>$account_type,
                'group_id'=>$data['group_id']
            ];


            $top_id = Db::name('top_account_assets')->insertGetId($add_data);
            $child_data = [
                'pid'=>$top_id,
                'fee'=>$fee,
                'public_key'=>$img

            ];

            $bool = Db::name('top_child_account')->insert($child_data);

            $data = ['code'=>'0000','msg'=>'添加成功','info'=>''];

        }else{

                if($data['add_type'] == 1){

                  	$key = 'public_key';
                    $add_data = [
                        'sql_name'=>$sql_name,
                        'name'=>$data['name'],
                        'show_name'=>$data['show_name'],
                        'status'=>2,
                        'type'=>$type,
                        'app_id'=>'Pcode_cloudesc_Bind_'.$data['app_id'],
                      	'account_type'=>$account_type,
                        'is_enterprise'=>0,
                      	'quotient_id'=>$data['quotient_id']
                    ];
                    if(isset($data['is_enterprise'])){

                        if($data['is_enterprise'] == 1){

                            $add_data = [

                                'mch_id'=>$data['mch_id'],
                                'alipay_private_key'=>base64_encode(base64_encode(trim_all($data['private_key']))),
                                'alipay_public_key'=>base64_encode(base64_encode(trim_all($data['public_key']))),
                                'receive_account'=>$data['receive_name'].'|'.$data['receive_amount'],
                                'is_enterprise'=>1,
                                'sql_name'=>$sql_name,
                                'name'=>$data['name'],
                                'show_name'=>$data['show_name'],
                                'status'=>2,
                                'type'=>$type,
                                'app_id'=>'Pcode_cloudesc_Bind_'.$data['app_id']


                            ];

                        }

                    }

                    $top_id = Db::name('top_account_assets')->insertGetId($add_data);

                    $amount_array = explode(',', trim($data['amount_array'],','));
                    $child_data = []; 

                    foreach ($amount_array as $k => $v) {
                              $money = $v*100;
                            for ($i=0; $i <$data['num'] ; $i++) { 
                                  
                                
                                $child_data[] = ['pid'=>$top_id,$key=>'/code/'.$sql_name.'/'.date('Ymd').'/'.$top_id.'/'.$money.'.png','amount'=>$money];
                                $money--;
                            }
                      }


                      $bool = Db::name('top_child_account')->insertAll($child_data);
                      $bool2 = Db::name('quata')->insert(['status'=>1,'id'=>$top_id,'type'=>$type]);
                      if($bool){
                        $dir = iconv("UTF-8", "GBK", ROOT_PATH.'/public/code/'.$sql_name.'/'.date('Ymd').'/'.$top_id);
                        if (!file_exists($dir)){
                            mkdir($dir,0777,true);

                        }
                        $data = ['code'=>'0000','msg'=>'添加成功','info'=>'/code/'.$sql_name.'/'.date('Ymd').'/'.$top_id];
                      }else{
                        $data = ['code'=>'1111','msg'=>'添加失败'];
                      }


                }elseif($data['add_type'] == 2){

                    


                        $field = 'pid,amount,public_key';
                        $top_id = $data['pid'];
                    if(isset($data['is_enterprise'])){

                        if($data['is_enterprise'] == 1){

                            $update_data = [

                                'mch_id'=>$data['mch_id'],
                                'alipay_private_key'=>base64_encode(base64_encode(trim_all($data['private_key']))),
                                'alipay_public_key'=>base64_encode(base64_encode(trim_all($data['public_key']))),
                                'receive_account'=>$data['receive_name'].'|'.$data['receive_amount'],
                                'is_enterprise'=>1,


                            ];

                            Db::table('top_account_assets')->where('id',$top_id)->update($update_data);

                        }

                    }


                    $top_child_accountModel = Db::name('top_child_account');
                    $amount_array = explode(',', trim($data['amount_array'],','));
                    $child_data = []; 

                      foreach ($amount_array as $k => $v) {
                              $money = $v*100;
                            for ($i=0; $i <$data['num'] ; $i++) { 
                                  
                                $child_data[] = ['pid'=>$top_id,'public_key'=>'/code/'.$sql_name.'/'.date('Ymd').'/'.$top_id.'/'.$money.'.png','amount'=>$money];
                                $money--;
                        }
                    }



                    $top_data = $top_child_accountModel->field($field)->where('pid',$top_id)->select();

                    $amount = '';

                    foreach ($top_data as $k => $v) {
                        $amount[] = $v['amount'];
                    }


                    $add_data = '';
                    foreach ($child_data as $k => $v) {

                        if(in_array($v,$top_data)){

                            unset($child_data[$k]);
                        }else{

                            $add_data[] = $v; 

                        }
                    }
                    // dump($add_data);die;
                    if(!empty($add_data)){
                        $add_data = array_merge($add_data);
                        $bool = $top_child_accountModel->insertAll($add_data);

                    }


                    $data = ['code'=>'0000','msg'=>'添加成功','info'=>'/code/'.$sql_name.'/'.date('Ymd').'/'.$top_id];

                }
            }

      return $data;


      }else{
        if($this->login_user['state'] == 90){
            
            $uid = $this->login_user['id'];
        }else{

            $uid = 1;
        }
        $group_name = Db::table('group')->where('uid',$uid)->order('id desc')->select();
        $this->assign('group_name',$group_name);
        $users = Db::table('users')->field('id,name')->where('state','66')->select();
        $this->assign('users',$users);
        return $this->fetch();

      }
  
  
    }
  
  //已有收款账号
  public function pay_account_type(){

    $assets_type = input('type');
    // $assets_type = 1;
    // if($type==1){
    //     $assets_type = 2;
    // }


    $accout = Db::name('top_account_assets')->where('type',$assets_type)->field('id,name')->select();
    return $accout;



  } 



//设置全名费率

public function set_fee(){

    if (!in_array($this->login_user['state'],[99,100])) {
           echo '权限不足，请联系管理员';die;
        }
    
    $fee = Db::table('channel_type')->select();
    $this->assign('fee',$fee);
    return $this->fetch();


}

public function ajax_set_fee(){

    if (!in_array($this->login_user['state'],[99,100])) {
            return ['code'=>'1111','msg'=>'权限不足'];
        }
    $data = input();
    $channel_typeModel = Db::name('channel_type');
    $validate = '';
    $fee = $channel_typeModel->select();
    if(empty($fee)){
        return ['code'=>'1111','msg'=>'暂无通道'];die;
    }
    foreach ($fee as $k => $v) {
        $validate[$v['code'].'|'.$v['channel_name']] = 'require|<:1|>:0|number';
    }

    $result = $this->validate($data,$validate);
    if(true !== $result){
        // 验证失败 输出错误信息
        return ['code'=>20001,'msg'=>$result];die;
    } 

    $usersModel = Db::name('users');
    $top_account_assets = Db::name('top_account_assets');
    $users_id = $usersModel->field('id')->where('state',77)->select();
    if(!empty($users_id)){
        $users_id = array_column($users_id, 'id');
        

    }


    foreach ($fee as $k => $v) {

        $code = $v['code'];
        if(isset($data[$code.'_status'])){
            $status = 1;
        }else{
            $status = 0;
        }

       $bool = $channel_typeModel->where('code',$code)->update(['fee'=>$data[$code],'status'=>$status]);
       if($bool){

            if(!empty($users_id)){
                Db::table('user_fee')->where('taid',$v['id'])->where('uid','in',$users_id)->update(['fee'=>$data[$code]]);
            }
            $taid = $top_account_assets
            ->alias('ta')
            ->field('tca.id')
            ->join('top_child_account tca','tca.pid=ta.id')
            ->where('uid','<>',1)
            ->where('ta.type',$v['id'])
            ->update(['tca.fee'=>$data[$code]]);

        }
        
    }


 
    return ['code'=>'0000','msg'=>'修改成功'];




}


public function debit_money()
    {

        if(!request()->isAjax()){
            echo '非法请求';die;
        }

        if (!in_array($this->login_user['state'],[99,100])) {
            return ['code'=>20001,'msg'=>'权限不足'];exit;
        }

        $data = input();
        
        $result = $this->validate($data,
        [
            'pass|口令'  => 'require',
            'money|金额'    => 'require|number',
            'id|必要参数'=>'require',
            'text1|说明'=>'require',

        ]);
    
      
    //验证判断必填项
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'msg'=>$result];exit;
        }
        if($data['pass'] != 'bzj'){

            return ['code'=>20001,'msg'=>'口令错误'];exit;

        }
        $uid = $this->login_user['id'];
        $data['money'] = $data['money']*100;
        $money = $data['money'];
   
        Db::startTrans();
        $bool1 = Db::name('assets')->where('uid',$data['id'])->setInc('margin',$data['money']);
        

         $bool9 = Db::name('record')->insert([
            'date'=>date('Y-m-d H:i:s'),
            'content'=>$data['text1'],
            'time'=>time(),
            'operator'=>1,
            'child_id'=>$data['id'],
            'money'=>$money,
           	'type'=>2
            ]);

        if($bool1&&$bool9){
            $data = ['code'=>'0000','msg'=>'增加成功'];
            Db::commit();
        }else{
            $data = ['code'=>'0000','msg'=>'增加失败'];
            Db::rollback();
        }

        return $data;




    }


//解冻金额
    public function del_freeze(){

         if(!request()->isAjax()){
            echo '非法请求';die;
        }

        if (!in_array($this->login_user['state'],[99,100])) {
            return ['code'=>20001,'msg'=>'权限不足'];exit;
        }

        $data = input();
        
        $result = $this->validate($data,
        [
            'pass|口令'  => 'require',
            'money|金额'    => 'require|number|>:0',
            'id|必要参数'=>'require',

        ]);
    
      
    //验证判断必填项
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'msg'=>$result];exit;
        }
        if($data['pass'] != 'jd'){

            return ['code'=>20001,'msg'=>'口令错误'];exit;

        }
        $uid = $this->login_user['id'];
        $money = $data['money'];
        $data['money'] = $data['money']*100;
        
        //先查询解冻的金额是否够提交的金额

        $freeze = Db::table('assets')->where('uid',$data['id'])->value('freeze');

        if($freeze < $data['money']){
            return ['code'=>20001,'msg'=>'解冻金额不足'.$money.'元'];exit;
        }
   
        Db::startTrans();

        $bool2 = Db::name('assets')->where('uid',$data['id'])->setInc('margin',$data['money']);

        $bool1 = Db::name('assets')->where('uid',$data['id'])->setDec('freeze',$data['money']);
        
        $bool9 = Db::name('record')->insert([
            'date'=>date('Y-m-d H:i:s'),
            'content'=>'系统解冻金额',
            'time'=>time(),
            'operator'=>1,
            'child_id'=>$data['id'],
            'money'=>$data['money'],
          	'type'=>5,
          	'freeze_money'=>-$data['money'],
            ]);

        if($bool1&&$bool9){
            $data = ['code'=>'0000','msg'=>'操作成功'];
            Db::commit();
        }else{
            $data = ['code'=>'0000','msg'=>'操作失败'];
            Db::rollback();
        }

        return $data;



    }








	   public function add_bans($id){

        if(request()->isAjax()){

            $assetsModel = Db::name('users');

            $now_num = $assetsModel->where('id',$id)->value('bans_num');
            $now_num += 1;

            switch ($now_num) {
                case '1':
                    $add_time = time()+30*60;
                    break;
                case '2':
                    $add_time = time()+60*60*24;
                    break;
                case '3':
                    $add_time = time()+60*60*24*15;
                    break;
                case '4':
                    $add_time = strtotime('2030-1-1');
                    break;

                
                default:
                    $add_time = strtotime('2030-1-1');
                    break;
            }
            Db::startTrans();
            $bool1 = $assetsModel->where('id',$id)->setInc('bans_num');
            $bool = $assetsModel->where('id',$id)
                  ->update([
                    'bans_time' => $add_time,
                ]);
            if($bool&&$bool1){
                Db::commit(); 
                $return_data = ['code'=>'0000','msg'=>'增加成功'];
            }else{
                Db::rollback();
                $return_data = ['code'=>'1111','msg'=>'增加失败，请稍后再试'];
            }
            return $return_data;


        }


    }
	//获取自己的邀请二维码
    public function get_code(){


        $id = $this->login_user['id'];

        $url = THIS_URL.'/partner/login/login?id='.$id;
        $logo = 'static/partner.png';

        $url = create_code($url,$logo);

        if($url){

            return ['code'=>'0000','url'=>$url];
        }else{
            return ['code'=>'1111','url'=>'请稍后再试'];
        }
    }

    //编辑分组

    public function edit_group($id){

        if(request()->isAjax()){
           
            $data = input();

            $bool = Db::name('group')->where('id',$data['id'])->update([

                'group_name'=>$data['group_name'],
                'remark'=>$data['remark'],

           ]);

            return ['code'=>'0000','msg'=>'修改成功'];
           



        }else{
            $group_data = Db::table('group')->where('id',$id)->limit(1)->find();
            $this->assign('group_data',$group_data);
            return $this->fetch();
        }



        

    }

//添加分组
    public function add_group(){

        if(request()->isAjax()){

           $group_name = input('group_name');
           $remark = input('remark');

           if($this->login_user['state'] == 90){

            $uid = $this->login_user['id'];
           }else{
            $uid = 1;
           }

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
   //增加通道
    public function add_user_fee(){

        if(request()->isAjax()){
            if (!in_array($this->login_user['state'],[100,99])) {
                return ['code'=>'1111','msg'=>'权限不足'];die;
            }
            $data = input();
            
            $desc = $data['data']['desc'];
            unset($data['data']['desc']);
            if(empty($data['data'])){

                return ['code'=>'1111','msg'=>'至少选择增加一条通道'];die;

            }
             
            $desc_arr = explode('|', $desc);

            $user_fee = array_values($data['data']);

            if(count($desc_arr) != count($user_fee)){
                return ['code'=>'1111','msg'=>'费率设置错误'];die;
            }
           
            $user_feeModel = Db::name('user_fee');
          	Db::startTrans();
            foreach ($user_fee as $k => $v) {
				if($desc_arr[$k]>1 || $desc_arr[$k]==1 ){
                  	Db::rollback();
                	return ['code'=>'1111','msg'=>'费率设置错误'];die;
                }
                $user_feeModel->insert(['uid'=>$data['uid'],'taid'=>$v,'fee'=>$desc_arr[$k]]);
            }
			Db::commit();
            return ['code'=>'0000','msg'=>'添加成功'];die;


        }

        if (!in_array($this->login_user['state'],[100,99])) {
                echo '权限不足，请联系管理员';
                die;
            }
            $id = input('id');
            $user_fee = Db::name('user_fee')->where('uid',$id)->select();

            $taid = '';

            if(!empty($user_fee)){
                foreach ($user_fee as $k => $v) {
                    $taid .= $v['taid'].',';
                }

            }

            

            $channel_type = Db::name('channel_type')
                ->where('id','not in',$taid)
                ->select();


            $this->assign('channel_type',$channel_type);
            $this->assign('uid',$id);
            return $this->fetch();

    }
  //修改用户等级
    public function user_level(){
        if (!in_array($this->login_user['state'],[100,99])) {
                return ['code'=>1111,'msg'=>'权限不足，请联系管理员'];
                die;
            }
        $state = input();
        $bool = Db::table('users')->where('id',$state['id'])->update(['state'=>$state['state']]);
        if($bool){
            return ['code'=>'0000','msg'=>'修改成功'];
        }else{
            return ['code'=>1111,'msg'=>'修改失败，请稍后再试'];
        }

    }
	
 
//主页
    public function main(){
		
      	$state = $this->login_user['state'];
      
      	if($state != 100){
        	return $this->redirect('sadmin/user/account');die;
        }

        $mch_orderModel = Db::name('mch_order');

        //平台总入金(元)
        $order_data['sum_money'] = $mch_orderModel
          	->where('uid','<>',2)
            ->where('order_type',1)
            ->where('pay_status',1)
            ->sum('pay_amount');
        //平台总成交订单(笔)
        $order_data['succ_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count('id');
        //平台总成功率(%)
        $order_data['count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->where('order_type',1)
            ->count('id');
        //今日平台总成交订单(笔)

        $order_data['today_sum_money'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','today')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->sum('pay_amount');
        //今日平台总成交订单(笔)
        $order_data['today_succ_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','today')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count('id');
        //今日平台总成功率(%)
        $order_data['today_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','today')
            ->where('order_type',1)
            ->count('id');

        //昨日平台总成交订单(笔)

        $order_data['yesterday_sum_money'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','yesterday')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->sum('pay_amount');
      
      	
      
        //昨日平台总成交订单(笔)
        $order_data['yesterday_succ_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','yesterday')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count('id');
        //昨日平台总成功率(%)
        $order_data['yesterday_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','yesterday')
            ->where('order_type',1)
            ->count('id');
		
      //本周
        $order_data['week_sum_money'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','week')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->sum('pay_amount');
        //昨日平台总成交订单(笔)
        $order_data['week_succ_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','week')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count('id');
        //昨日平台总成功率(%)
        $order_data['week_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','week')
            ->where('order_type',1)
            ->count('id');

      //前日平台总成交订单(笔)
        
        // $before = date('d')-2;
        // $before = date('Y-m-').$before;

        // $order_data['before_sum_money'] = $mch_orderModel
        //     ->where('accept_time','between time',[$before.' 00:00:00',$before.' 23:59:59'])
        //     ->where('order_type',1)
        //     ->where('pay_status',1)
        //     ->sum('pay_amount');
        // //昨日平台总成交订单(笔)
        // $order_data['before_succ_count'] = $mch_orderModel
        //     ->where('accept_time','between time',[$before.' 00:00:00',$before.' 23:59:59'])
        //     ->where('order_type',1)
        //     ->where('pay_status',1)
        //     ->count('id');
        // //昨日平台总成功率(%)
        // $order_data['before_count'] = $mch_orderModel
        //     ->where('accept_time','between time',[$before.' 00:00:00',$before.' 23:59:59'])
        //     ->where('order_type',1)
        //     ->count('id');

    //dump($order_data);
        $this->assign('order_data',$order_data);




        return $this->fetch();
    }
    public function vmain(){
		
      	$state = $this->login_user['state'];
      
      	if($state != 100){
        	return $this->redirect('sadmin/user/account');die;
        }

        $mch_orderModel = Db::name('mch_order');

        //平台总入金(元)
        $order_data['sum_money'] = $mch_orderModel
          	->where('uid','<>',2)
            ->where('order_type',1)
            ->where('pay_status',1)
            ->sum('pay_amount');
        //平台总成交订单(笔)
        $order_data['succ_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count('id');
        //平台总成功率(%)
        $order_data['count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->where('order_type',1)
            ->count('id');
        //今日平台总成交订单(笔)

        $order_data['today_sum_money'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','today')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->sum('pay_amount');
        //今日平台总成交订单(笔)
        $order_data['today_succ_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','today')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count('id');
        //今日平台总成功率(%)
        $order_data['today_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','today')
            ->where('order_type',1)
            ->count('id');

        //昨日平台总成交订单(笔)

        $order_data['yesterday_sum_money'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','yesterday')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->sum('pay_amount');
      
      	//扣除昨日今日的金额，特殊操作
      	//$order_data['today_sum_money'] -=cache('exp_deduction'.date('Ymd'));
      	
      	$order_data['yesterday_sum_money'] -= cache('exp_deduction'.date('Ymd',strtotime('-1 day')));
      
        //昨日平台总成交订单(笔)
        $order_data['yesterday_succ_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','yesterday')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count('id');
        //昨日平台总成功率(%)
        $order_data['yesterday_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','yesterday')
            ->where('order_type',1)
            ->count('id');
		
      //本周
        $order_data['week_sum_money'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','week')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->sum('pay_amount');
        //昨日平台总成交订单(笔)
        $order_data['week_succ_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','week')
            ->where('order_type',1)
            ->where('pay_status',1)
            ->count('id');
        //昨日平台总成功率(%)
        $order_data['week_count'] = $mch_orderModel
          	->where('uid','<>',2)
            ->whereTime('accept_time','week')
            ->where('order_type',1)
            ->count('id');

      //前日平台总成交订单(笔)
        
        // $before = date('d')-2;
        // $before = date('Y-m-').$before;

        // $order_data['before_sum_money'] = $mch_orderModel
        //     ->where('accept_time','between time',[$before.' 00:00:00',$before.' 23:59:59'])
        //     ->where('order_type',1)
        //     ->where('pay_status',1)
        //     ->sum('pay_amount');
        // //昨日平台总成交订单(笔)
        // $order_data['before_succ_count'] = $mch_orderModel
        //     ->where('accept_time','between time',[$before.' 00:00:00',$before.' 23:59:59'])
        //     ->where('order_type',1)
        //     ->where('pay_status',1)
        //     ->count('id');
        // //昨日平台总成功率(%)
        // $order_data['before_count'] = $mch_orderModel
        //     ->where('accept_time','between time',[$before.' 00:00:00',$before.' 23:59:59'])
        //     ->where('order_type',1)
        //     ->count('id');

    //dump($order_data);
        $this->assign('order_data',$order_data);




        return $this->fetch();
    }
  
  //后台添加商户
    public function add_users(){

        if(request()->isAjax()){

            $data = input();

            $result = $this->validate($data,
                [
                'name|商户名称'  => 'require',
                'company|公司名称'=>'require',
                'email|邮箱地址'    => 'email',
                'phone_num|手机号码'=>'require',
                'pass|密码'=>'require',
                'trading_pass|交易密码'=>'require',

            ]);
        //验证判断必填项
            if(true !== $result){
                // 验证失败 输出错误信息
                return ['code'=>20001,'msg'=>$result];exit;
            }elseif(!is_mobile_phone($data['phone_num'])){

                return ['code'=>20001,'msg'=>'手机号码格式错误'];exit;

            }

            //验证手机号和邮箱有没有被注册
            $sql_phone = Db::table('users')->where('phone_num',$data['phone_num'])->value('phone_num');

            if ($sql_phone!=null) {
                return ['code'=>20001,'msg'=>'手机号码已被注册'];exit;
            }


            $email_phone = Db::table('users')->where('email',$data['email'])->value('email');

            if ($email_phone!=null) {
                return ['code'=>20001,'msg'=>'该邮箱已绑定账户，请更改邮箱'];exit;
            }

            $Getfirstchar = new \app\index\model\Getfirstchar(); 
         
            $data['merchant_cname'] = strtoupper($Getfirstchar->pinyin($data['name']));

            $j = 1;
            $old_merchant_cname = $data['merchant_cname'];
            for ($i=0; $i < $j; $i++) {
                $sql_data = Db::table('users')->where('merchant_cname',$data['name'])->value('merchant_cname');
                if ($sql_data!=null) {
                    $data['merchant_cname'] = $old_merchant_cname.($j+1);
                    $j++;
                }
            }

            $data['pass'] = encryption($data['pass']);
            $data['trading_pass'] = encryption($data['trading_pass']);
            
            //增加谷歌验证码
            $gangsta = new \Google\Authenticator\Authenticator();
            $data['authenticator'] = $gangsta->generateSecret();
            $key = time().'key'.rand(0,1000);
            $data['key'] = encryption($key);
            $data['superior'] = $data['superior'];
            $data['join_time'] = time();
            $data['state'] = 4;
            Db::startTrans();

            $id = Db::table('users')->insertGetId($data);
          	
			$bool3 = Db::table('assets')->insert(['uid'=>$id]);
            if(!empty($data['superior'])){

                $bool2 = Db::execute("update users set subordinate=CONCAT(subordinate,',','".$id."') where id = ".$data['superior']);
                if($bool2&&$bool3){
                    Db::commit(); 
                    return ['code'=>'0000','msg'=>'添加成功'];
                }
                

            }else{
              
                if($id&$bool3){
                    Db::commit(); 
                    return ['code'=>'0000','msg'=>'添加成功'];
                }
            }

            Db::rollback();
            return ['code'=>'1111','msg'=>'添加失败，请稍后再试'];




        }

        //代理信息
        $superior = Db::table('users')->where('state',88)->select();

        $this->assign('superior',$superior);
        return $this->fetch();



    }
  
  	//编辑用户信息
    public function edit_users($id){

        if(request()->isAjax()){

            $data = input();
            $result = $this->validate($data,
                [
                'name|商户名称'  => 'require',
                'company|公司名称'=>'require',
                'email|邮箱地址'    => 'email',
                'phone_num|手机号码'=>'require',

            ]);
        //验证判断必填项
            if(true !== $result){
                // 验证失败 输出错误信息
                return ['code'=>20001,'msg'=>$result];exit;
            }elseif(!is_mobile_phone($data['phone_num'])){

                return ['code'=>20001,'msg'=>'手机号码格式错误'];exit;

            }

            $phone_num = Db::table('users')->where('id','<>',$id)->where('phone_num',$data['phone_num'])->value('phone_num');

            if(!empty($phone_num)){
                return ['code'=>20001,'msg'=>'手机号'.$phone_num.'已被其他商户注册'];exit;
            }
            $email = Db::table('users')->where('id','<>',$id)->where('email',$data['email'])->value('email');
            if(!empty($email)){
                return ['code'=>20001,'msg'=>'手机号'.$email.'已被其他商户注册'];exit;
            }

        //判断是否需要修改密码
            if(!empty($data['pass'])){
                $data['pass'] = encryption($data['pass']);
              	$bool9 = Db::name('record')->insert([
                'date'=>date('Y-m-d H:i:s'),
                'content'=>'修改登录密码',
                'time'=>time(),
                'operator'=>1,
                'child_id'=>$id,
                'money'=>0,
                'type'=>8
                ]);
              
            }else{
                unset($data['pass']);
            }
            if(!empty($data['trading_pass'])){
                $data['trading_pass'] = encryption($data['trading_pass']);
              	$bool9 = Db::name('record')->insert([
                'date'=>date('Y-m-d H:i:s'),
                'content'=>'修改交易密码',
                'time'=>time(),
                'operator'=>1,
                'child_id'=>$id,
                'money'=>0,
                'type'=>8
                ]);
            }else{
                unset($data['trading_pass']);
            }
            $old_superior = $data['old_superior'];
            unset($data['old_superior']);

            $bool = Db::table('users')->update($data);
            if($bool){
                if($old_superior != $data['superior']){

                    $subordinate = Db::table('users')->where('id',$old_superior)->value('subordinate');
                    $subordinate = explode(',', $subordinate);
                    foreach ($subordinate as $k => $v) {
                        if($v == $id){
                            unset($subordinate[$k]);
                        }
                    }
                    $subordinate = implode(',',$subordinate);
                    Db::table('users')->where('id',$old_superior)->update(['subordinate'=>$subordinate]);
                    if(!empty($data['superior'])){

                        Db::execute("update users set subordinate=CONCAT(subordinate,',','".$id."') where id = ".$data['superior']);


                    }

                }

            }

           return ['code'=>'0000','msg'=>'修改成功'];exit;






        }else{

            $user_data = Db::table('users')->where('id',$id)->find();
            $superior = Db::table('users')->where('state',88)->select();

            $this->assign('user_data',$user_data);
            $this->assign('superior',$superior);
			

            return $this->fetch();

        }
 
    }
  
  //清除个吗代理商数据
    public function del_money_info(){
		
      	if (!in_array($this->login_user['state'],[100])) {
            return ['code'=>'1111','msg'=>'权限不足'];
        }
        $data = input();
        if($data['pass'] != 'qcsj'){

            return ['code'=>'1111','msg'=>'口令错误，三次错误锁定当前帐户'];
        }
        $uid = $data['id'];
        Db::startTrans();

        $order_data = Db::table('users')
            ->alias('u')
            ->field('u.id,u.merchant_cname,ta.money,ta.fee_sum,ta.recharge_sum,ta.withdrawal_sum,ta.name')
            ->join('top_account_assets ta','ta.uid=u.id')
            ->where('u.id',$uid)
            ->select();
        


        $bool1 = Db::table('assets')
            ->where('uid',$data['id'])
            ->update([
                'money'=>0,
                'recharge_sum'=>0,
                'fee_sum'=>0,
                'margin'=>0,
            ]);

        $top_id = Db::table('top_account_assets')->field('id')->where('uid',$uid)->select();
        $top_id = array_column($top_id, 'id');

        $bool2 = Db::table('top_account_assets')
        ->where('id','in',$top_id)
        ->update([
                'money'=>0,
                'fee_sum'=>0,
                'recharge_sum'=>0,
                'withdrawal_sum'=>0
            ]);

        $bool3 = Db::table('top_child_account')
            ->where('pid','in',$top_id)
            ->update([
                'money'=>0,
                'fee_sum'=>0,
                'recharge_sum'=>0,
                'withdrawal_sum'=>0
            ]);



        if($bool1&&$bool2&&$bool3){
		
            $bool4 = $this->jl_excel($order_data);
            if($bool4){
                Db::commit(); 
               return ['code'=>'0000','msg'=>'清除成功'];exit;
            }
        }
             Db::rollback();
             return ['code'=>'1111','msg'=>'清除失败，请稍后重试'];
        
    }


//生成xls到服务器
        public function jl_excel($order_data){
        $excel_order_data = array();
        foreach ($order_data as $key => $value) {

            $mch_name = $value['merchant_cname'].'_'.$value['id'];
            $arr = array();
            $arr['name'] = $value['name'];

            // $arr['mch_id'] = $value['mch_id'];
            $arr['recharge_sum'] = $value['recharge_sum']/100;

            $arr['money'] = $value['fee_sum']/100;

            $arr['fee_sum'] = $value['fee_sum']/100;

            $arr['withdrawal_sum'] = $value['withdrawal_sum']/100;

            $excel_order_data[] = $arr;

        }
          
        if(!isset($mch_name)){
        	return false;
        }

        $filename = $mch_name.'__'.date('YmdHis');
        $header = array('收款账户','总充值','可用余额','累计手续费','提现总和');
        $index = array('name','recharge_sum','money','fee_sum','withdrawal_sum');
        $excel_order_data = array_reverse($excel_order_data);
        return $this->createtable_list($excel_order_data,$filename,$header,$index);

    }

    public function createtable_list($list,$filename,$header=array(),$index = array()){
    // header("Content-type:application/vnd.ms-excel");  
    // header("Content-Disposition:filename=".$filename.".xls");
    $teble_header = implode("\t",$header);
    $strexport = $teble_header."\r";
    foreach ($list as $row){  
        foreach($index as $val){
            $strexport.=$row[$val]."\t";   
        }
        $strexport.="\r"; 
    }
    $strexport=iconv('UTF-8',"GB2312//IGNORE",$strexport);  
    $file_full_path = ROOT_PATH.'/public/xls/'.$filename.'.xls';
    if(!file_exists($file_full_path)){
        if($fp=fopen($file_full_path,'w')){
            $excel_order_data = $strexport;
            $conn = $excel_order_data."\r\n";

            fwrite($fp,$conn);
            fclose($fp);
            return true; 
        }else{
            return false;
        }
    }

}
  
  
  
}