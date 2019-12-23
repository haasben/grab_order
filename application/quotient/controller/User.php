<?php
namespace app\quotient\controller;
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


        }elseif($this->login_user['state'] == 90){

            $where3['u.id'] = $this->login_user['id'];
            $where4['uid'] = $this->login_user['id'];
            $where5 = '';


        }else{
            $where3 = '';
            $where4 = '';
            $where5 = '';
        }

        
        $user_data = Db::table('users')
            ->alias('u')
            ->field('a.*,u.id,u.email,u.name,u.join_time,u.login_time,u.company,u.merchant_cname,u.state,u.bans_num')
            ->join('assets a','a.uid=u.id','left')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            //->where('u.id','<>',$this->login_user['id'])
            ->paginate(15,false,[
                'query' => request()->param()
            ]);

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
        
        $html_data = [
            'user_data'=>$user_data,
            'user_fee_arr'=>$user_fee_arr,
            'sum_money'=>$sum_money,
        ];
        return $this->fetch('account',$html_data);

    }

    //用户管理
    public function user_management($id){
        if (!in_array($this->login_user['state'],[99,100])) {
            echo '权限不足，请联系管理员';
            die;
        }
        
        $user_data = Db::table('users')->where('id',$id)->limit(1)->find();
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


    public function fee_list(){
        $uid = input('get.uid');
        $user_fee_data = Db::table('user_fee')
            ->field('user_fee.fee,t.channel_name as name')
            ->join('channel_type t','t.id=user_fee.taid')
            ->where('uid',$uid)
            ->where('user_fee.status',1)
            ->select();

        $str = '';

        foreach ($user_fee_data as $value) {
            $str .= $value['name'].':'.($value['fee']*100).'%<br/>';
        }
        echo $str;
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
            ->field('id,email,login_time,phone_num,merchant_cname,company,name,login_ip')
            ->where('id',$uid)
            ->find();

        session(null,'admin');
        session('login_user',$sql_data,'admin');

        $this->redirect('/admin/User/index');
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
                'private_key'=>$img

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
                        'is_enterprise'=>0
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
                                  
                                
                                $child_data[] = ['pid'=>$top_id,$key=>'/code/'.$sql_name.'/'.$top_id.'/'.$money.'.png','amount'=>$money];
                                $money--;
                            }
                      }


                      $bool = Db::name('top_child_account')->insertAll($child_data);
                      $bool2 = Db::name('quata')->insert(['status'=>1,'id'=>$top_id,'type'=>$type]);
                      if($bool){
                        $dir = iconv("UTF-8", "GBK", ROOT_PATH.'/public/code/'.$sql_name.'/'.$top_id);
                        if (!file_exists($dir)){
                            mkdir($dir,0777,true);

                        }
                        $data = ['code'=>'0000','msg'=>'添加成功','info'=>'/code/'.$sql_name.'/'.$top_id];
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
                                  
                                $child_data[] = ['pid'=>$top_id,'public_key'=>'/code/'.$sql_name.'/'.$top_id.'/'.$money.'.png','amount'=>$money];
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


                    $data = ['code'=>'0000','msg'=>'添加成功','info'=>'/code/'.$sql_name.'/'.$top_id];

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
        ]);
    
      
    //验证判断必填项
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'msg'=>$result];exit;
        }
        if($data['pass'] != 'bzj'){

            return ['code'=>20001,'msg'=>'口令错误'];exit;

        }

        $data['money'] = $data['money']*100;
        $money = $data['money']*100;
   
        Db::startTrans();
        $bool1 = Db::name('assets')->where('uid',$data['id'])->setInc('margin',$data['money']);
        

        $bool9 = Db::name('record')->insert(['date'=>date('Y-m-d H:i:s'),
            'content'=>'增加保证金金额(元)：'.$money/100,
            'time'=>time(),
            'operator'=>session('login_user','','sadmin')['merchant_cname'].'_'.session('login_user','','sadmin')['id'].'_'.session('login_user','','sadmin')['name'].'_账户ID：'.$data['id']
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

           ]);
           if($bool){
                return ['code'=>'0000','msg'=>'添加成功'];
           }else{
                return ['code'=>'1111','msg'=>'添加失败，请稍后再试'];
           }

        }else{


            
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
            foreach ($user_fee as $k => $v) {

                $user_feeModel->insert(['uid'=>$data['uid'],'taid'=>$v,'fee'=>$desc_arr[$k]]);
            }

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
  
//修改用户登录
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



        return $this->fetch();
    }




  
}