<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class CommonPay extends Model{
    
    public $get_data;
    public $post_data;
    public $private_key;
    public $public_key;
    public $mch_id;

    public function __construct(){
        $this->get_data = input();
        $this->post_data = input();
    }

    public function isset_pay_channel(){
        return true;
    }
  
  //汇财旺通道轮循添加订单
  protected function add_hcw_public_data($type){
        $get_data = $this->get_data;

        $mch_id_arr = explode('_',$get_data['mch_id']);
        $uid = $mch_id_arr[1];
        
        $merchant_cname = $mch_id_arr[0];


        $sql_data = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->find();

        if ($sql_data!=null) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        

        $pid_data = $this->get_hwc_child_account($type);
 
        $add_data = [
            'uid'=>$uid,
            //用户id
            
            'order_num'=>$get_data['order_num'],
            //下级商户订单号
            
            'pay_amount'=>$get_data['pay_amount'],
            //订单金额

            'notify_url'=>$get_data['notify_url'],
            //异步回调地址

            'return_url'=>$get_data['return_url'],
            //同步跳转地址

            'ext'=>$get_data['ext'],
            //扩展信息，备注

            'pay_type'=>$pid_data['pid'],
            //支付方式，上级接口

            'tcid'=>$pid_data['id'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值
            'type'=>$type,

            'ip'=>getRealIp(),
            //用户真实IP
        ];
        // $this->daily_limit($uid,$taid,$get_data['pay_amount']);
        
        $result_id = Db::table('mch_order')->insertGetId($add_data);
        if ($result_id) {
            $pay_data = [
                'out_trade_no'=>$this->set_order_id($add_data['accept_time'],$result_id),
                'total_fee'=>$add_data['pay_amount'],
                'body'=>'商品订单',
                'hwc_this_secret_key'=>$pid_data,
                'mch_create_ip'=>$add_data['ip'],
            ];
            return $pay_data;
        }else{
            $ret['code'] = '10009';
            $ret['info'] = '读取数据失败，请重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
    }   
  
//固码添加订单  
   protected function add_solid_bank_public_data($taid,$get_data,$type,$app_id){
        // $get_data = $this->get_data;

    
        $uid = explode('_',$get_data['mch_id'])[1];
        //下级商户真实id;

        $sql_data = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->find();
        
        if ($sql_data!=null) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pid_data = $this->get_solid_bank_child_account($taid);


        $add_data = [
            'uid'=>$uid,
            //用户id
            
            'order_num'=>$get_data['order_num'],
            //下级商户订单号
            
            'pay_amount'=>$get_data['pay_amount'],
            //订单金额

            'notify_url'=>$get_data['notify_url'],
            //异步回调地址

            'return_url'=>$get_data['return_url'],
            //同步跳转地址

            'ext'=>$get_data['ext'],
            //扩展信息，备注

            'pay_type'=>$pid_data['pid'],
            //支付方式，上级接口

            'tcid'=>$pid_data['id'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值
            
            'type'=>$type,
            //支付类型 1支付宝 2微信

            'app_id'=>$app_id,
          
          	'ip'=>getRealIp(),

            
        ];
        
        // $this->daily_limit($uid,$type,$get_data['pay_amount']);
        
        $result_data['id'] = Db::table('mch_order')->insertGetId($add_data);
        if ($result_data['id']) {
            $result_data['accept_time'] = $add_data['accept_time'];
            return $result_data;
        }else{
            $ret['code'] = 10009;
            $ret['info'] = '读取数据失败，请重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
  
  
  
  
  //原生支付宝添加订单
  protected function add_official_public_data($type){
		$get_data = $this->get_data;

		$mch_id_arr = explode('_',$get_data['mch_id']);
        $uid = $mch_id_arr[1];
		
        $merchant_cname = $mch_id_arr[0];


		$sql_data = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->find();

        if ($sql_data!=null) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
		

        $pid_data = $this->get_official_child_account($type);

		$add_data = [
            'uid'=>$uid,
            //用户id
            
            'order_num'=>$get_data['order_num'],
            //下级商户订单号
            
            'pay_amount'=>$get_data['pay_amount'],
            //订单金额

            'notify_url'=>$get_data['notify_url'],
            //异步回调地址

            'return_url'=>$get_data['return_url'],
            //同步跳转地址

            'ext'=>$get_data['ext'],
            //扩展信息，备注

            'pay_type'=>$pid_data['pid'],
            //支付方式，上级接口

            'tcid'=>$pid_data['id'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值

            //'ext'=>'商品订单',
            //订单名称、说明
          	//'ip'=>getRealIp(),
          	//用户真实IP
          	
          	 'type'=>$type,
        ];
		
      	// $this->daily_limit($uid,$taid,$get_data['pay_amount']);
      	
        $result_id = Db::table('mch_order')->insertGetId($add_data);
        if ($result_id) {
            $pay_data = [
                'out_trade_no'=>$merchant_cname.'_'.$result_id,
                'subject'=>'商品订单',
                'total_amount'=>$add_data['pay_amount']/100,
                'body'=>'商品订单',
                'this_secret_key'=>$pid_data,
              	'accept_time'=>time(),
              	'id'=>$result_id,
              	'pid'=>$pid_data['id'],
              	
            ];
            return $pay_data;
        }else{
            $ret['code'] = '10009';
            $ret['info'] = '读取数据失败，请重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
	}	
  
  
  
  
  
  
  
  
//其他通道添加订单
  protected function add_public_data_other_asise($type){

        $get_data = $this->get_data;

        $mch_id_arr = explode('_',$get_data['mch_id']);
        $uid = $mch_id_arr[1];
        
        $merchant_cname = $mch_id_arr[0];


        //下级商户真实id;

        $sql_data = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->find();

        if ($sql_data!=null) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        

        $pid_data = $this->get_child_other_account($type);
        


        $add_data = [
            'uid'=>$uid,
            //用户id
            
            'order_num'=>$get_data['order_num'],
            //下级商户订单号
            
            'pay_amount'=>$get_data['pay_amount'],
            //订单金额

            'notify_url'=>$get_data['notify_url'],
            //异步回调地址

            'return_url'=>$get_data['return_url'],
            //同步跳转地址

            'ext'=>$get_data['ext'],
            //扩展信息，备注

            'pay_type'=>$pid_data['pid'],
            //支付方式，上级接口

            'tcid'=>$pid_data['id'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值

            'type'=>$type,
            //支付类型 1支付宝 2微信

            // 'body'=>$get_data['body'],
            //订单名称、说明
            'ip'=>getRealIp(),
            //用户真实IP
        ];
        
        // $this->daily_limit($uid,$taid,$get_data['pay_amount']);
        
        $result_id = Db::table('mch_order')->insertGetId($add_data);
        if ($result_id) {
            $pay_data = [
                // 'out_trade_no'=>$merchant_cname.'_'.$result_id,
                // 'subject'=>$add_data['body'],
                // 'total_amount'=>$add_data['pay_amount']/100,
                // // 'body'=>$add_data['body'],
                // 'this_secret_key'=>$pid_data,
                'accept_time'=>time(),
                'id'=>$result_id,
                // 'pid'=>$pid_data['id'],
                
            ];
            return $pay_data;
        }else{
            $ret['code'] = '10009';
            $ret['info'] = '读取数据失败，请重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
  
  protected function add_public_data($taid,$get_data,$type,$app_id,$pay_amount){
        // $get_data = $this->get_data;

    
        $uid = explode('_',$get_data['mch_id'])[1];
        //下级商户真实id;

        $sql_data = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->find();
        
        if ($sql_data!=null) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pid_data = $this->get_child_account($taid,$pay_amount);


        $add_data = [
            'uid'=>$uid,
            //用户id
            
            'order_num'=>$get_data['order_num'],
            //下级商户订单号
            
            'pay_amount'=>$pay_amount,
            //订单金额

            'notify_url'=>$get_data['notify_url'],
            //异步回调地址

            'return_url'=>$get_data['return_url'],
            //同步跳转地址

            'ext'=>$get_data['ext'],
            //扩展信息，备注

            'pay_type'=>$pid_data['pid'],
            //支付方式，上级接口

            'tcid'=>$pid_data['id'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值
            
            'type'=>$type,
            //支付类型 1支付宝 2微信

            'app_id'=>$app_id,

            //是否是企业号收款
            // 'is_enterprise'=>$pid_data['is_enterprise'],
            //收款银行
            // 'bank_name'=>explode('_',$pid_data['private_key'])[0],
            
             'ip'=>getRealIp(),
            
        ];
        
        // $this->daily_limit($uid,$type,$get_data['pay_amount']);
        
        $result_data['id'] = Db::table('mch_order')->insertGetId($add_data);
        if ($result_data['id']) {
            $result_data['accept_time'] = $add_data['accept_time'];
            return $result_data;
        }else{
            $ret['code'] = '10009';
            $ret['info'] = '读取数据失败，请重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
  
  
 //个吗支付宝微信添加订单 
    protected function add_public_data_alipay($type){


        $get_data = $this->get_data;

    
        $uid = explode('_',$get_data['mch_id'])[1];
        //下级商户真实id;

        $sql_data = Db::table('mch_order')->where(['uid'=>$uid,'order_num'=>$get_data['order_num']])->find();
        
        if ($sql_data!=null) {
            $ret['code'] = '10008';
            $ret['info'] = '订单号重复';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $pid_data = $this->get_child_account_min($type);
      

        $add_data = [
            'uid'=>$uid,
            //用户id
            
            'order_num'=>$get_data['order_num'],
            //下级商户订单号
            
            'pay_amount'=>$get_data['pay_amount'],
            //订单金额

            'notify_url'=>$get_data['notify_url'],
            //异步回调地址

            'return_url'=>$get_data['return_url'],
            //同步跳转地址

            'ext'=>$get_data['ext'],
            //扩展信息，备注

            'pay_type'=>$pid_data['pid'],
            //支付方式，上级接口

            'tcid'=>$pid_data['id'],
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值
            
            'type'=>$type,
            //支付类型 1支付宝 2微信

            'app_id'=>$pid_data['app_id'],

            //是否是企业号收款
            'is_enterprise'=>$pid_data['is_enterprise'],

            //是否是合伙人账号
            'is_proxy'=>$pid_data['account_type'],

            'proxy_id'=>$pid_data['uid'],

            //通道代理ID
            'top_id'=>$pid_data['top_id'],
            'ip'=>getIp(),
            
        ];

        //$this->daily_limit($uid,$type,$get_data['pay_amount']);
        
        $result_data['id'] = Db::table('mch_order')->insertGetId($add_data);
        if ($result_data['id']) {
            $result_data['accept_time'] = $add_data['accept_time'];
            return $result_data;
        }else{
            $ret['code'] = '10009';
            $ret['info'] = '读取数据失败，请重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    protected function daily_limit($uid,$taid,$pay_amount){

        $daily_limit = Db::table('user_fee')
            ->where('uid',$uid)
            ->where('taid',$taid)
            ->value('daily_limit');
        //该用户当前通道每日额度

        $associated_account = Db::table("top_account_assets")
            ->where('id',$taid)
            ->value('associated_account');
        //该通道关联账户

        $this_time_d = strtotime(date('Y-m-d 00:00:00'));
        //今日0点时间戳 开始时间

        $where = [
            'uid'=>$uid,
            'order_type'=>1,
            'pay_status'=>1,
            'accept_time'=>['between',[$this_time_d,$this_time_d+60*60*24]],
            'pay_type'=>['in',$associated_account]
        ];

        $sum_amount = Db::table('mch_order')
            ->where($where)
            ->sum('pay_amount');
        //今日已入金额度

        $remaining_limit = $daily_limit-$sum_amount;
        //剩余额度

        if ($pay_amount>$remaining_limit) {
            $ret['code'] = '10001';
            $ret['info'] = '支付通道今日额度剩余'.($remaining_limit/100).'元';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
//当前收款通道是否已经超过限额，超过则重新找下一个收款账号
    protected function is_limit($id,$pay_amount){

        $limit_data = Db::table('top_account_assets')->where('id',$id)->limit(1)->find();
        $daily_limit = $limit_data['daily_limit']+$pay_amount;
        if($limit_data <= $daily_limit){
            cache("secret_key_arr",null);
            Db::table('top_account_assets')->where('id',$id)->update(['status',2]);
            return true;
        }else{
            return false;
        }

    }
  
  //汇旺财获取账户子子信息
    protected function get_hwc_child_account($type){
        //查询具体收款子账户商户id、key
      	//cache('hwc_secret_key_arr',null);
        $secret_key_arr=Cache::get('hwc_secret_key_arr');

        //公钥秘钥的数组        
        $secret_key_arr_key=Cache::get('hwc_secret_key_arr_key');
        //存放公钥秘钥索引的数组

        $secret_key_arr_key_i = Cache::get('hwc_secret_key_arr_key_i');
        //当前数组的指标


        if (!$secret_key_arr||!$secret_key_arr_key||$secret_key_arr_key_i===false) {
            //不存在相关密钥数据时从数据库获取
            unset($secret_key_arr);
            unset($secret_key_arr_key);
            unset($secret_key_arr_key_i);
            
            $secret_key_data = Db::table('top_account_assets ta')
            ->field('tc.id,tc.pid,tc.private_key,tc.public_key,tc.mch_id,tc.pay_url')
            ->join('top_child_account tc','ta.id=tc.pid')
            ->where('tc.status',1)
            ->where('ta.type',$type)
            ->select();
            //查询状态为1的密钥数据
 
            foreach ($secret_key_data as $key => $value) {
                $secret_key_arr[$value['mch_id']] = $value;
                $secret_key_arr_key[] = $value['mch_id'];
            }

            //使用appid作为key存入数组、缓存
            if (!isset($secret_key_arr)) {
                $secret_key_arr = null;
                $secret_key_arr_key = null;
            }
            Cache::set('hwc_secret_key_arr',$secret_key_arr,36000);
            
            Cache::set('hwc_secret_key_arr_key',$secret_key_arr_key,36000);
            //使用i++方式获取当前appid而设置的数组、缓存

            $secret_key_arr_key_i = 0;
            //当前指针
            Cache::set('hwc_secret_key_arr_key_i',$secret_key_arr_key_i,36000);
        }
       
        if ($secret_key_arr==null) {
            $ret['code'] = '10007';
            $ret['info'] = '商户额度已满，请稍后重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $this_secret_key = $secret_key_arr[$secret_key_arr_key[$secret_key_arr_key_i]];
        $secret_key_arr_key_i++;
        //指针加一

        if ($secret_key_arr_key_i>count($secret_key_arr_key)-1) {
            //指针大于数组长度时归零
            $secret_key_arr_key_i = 0;
        }

        Cache::set('hwc_secret_key_arr_key_i',$secret_key_arr_key_i,36000);

        $this_secret_key['private_key'] = base64_decode(base64_decode($this_secret_key['private_key']));

        $this->private_key = $this_secret_key['private_key'];
        $this->mch_id = $this_secret_key['mch_id'];
        $this->pay_url = $this_secret_key['pay_url'];

        return $this_secret_key;
    } 
  
  
  
  
  
  
//获取当前收款金额最少的开启的收款账号
  
    protected function get_child_account_min($type){
        
        //cache('have_order_28',NULL);
        //查找有没有合伙人的在线收款账号
        if($type == 1){
                $channel = 'wechat';
            }else{
                $channel = 'alipay';
            }
        
        $channel_id = cache($channel.'_channel_id_'.date('s'));

        if($channel_id){
          
			$pay_amount = $this->get_data['pay_amount'];
          	foreach ($channel_id as $k => $v) {
              	
              	$channel = explode('_',$v);
              		if(cache('have_order_'.$k)){
                    	
                      	$where1 = '';
                      	coutinue;
                    
                    }else{
                    	if($channel[1]*0.7 > $pay_amount){
                    	
                        cache('have_order_'.$k,1,180);
                        $where1['ta.id'] = $channel[0]; 
                        $where1['ta.group'] = 0;

                        break;

                        }else{
                            $where1 = '';
                        }
                    
                    }
              
             }
          	if(empty($where1)){

                $where1['ta.group_id'] = Db::table('group')->where('status',1)->order('rand()')->limit(1)->value('id');

            	$where1['ta.account_type'] = ['>',1];
                $where1['a.margin'] = ['>',$this->get_data['pay_amount']+1000000];

                
            }


        }else{
            $where1['ta.account_type'] = ['>',1];
            $where1['a.margin'] = ['>',$this->get_data['pay_amount']+1000000];
            $where1['ta.group_id'] = Db::table('group')->where('status',1)->order('rand()')->limit(1)->value('id');

        }
		
        $this_secret_key  = Db::table('top_account_assets ta')
            ->field('tc.id,tc.pid,tc.mch_id,ta.uid,ta.is_enterprise,ta.app_id,account_type')
            ->join('top_child_account tc','ta.id=tc.pid')
            ->join('assets a','a.uid = ta.uid')
            ->where('ta.status',1)
            ->where('ta.type',$type)
            ->where('a.is_open',1)
            
            ->where($where1)
          	->order('rand()')
            //->order('ta.money')
            ->limit(1)
            ->find();
	
        if (empty($this_secret_key)) {
            $ret['code'] = '10007';
            $ret['info'] = '商户额度已满，请稍后重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        if($this_secret_key['uid'] != 1){
            $this_secret_key['top_id'] = Db::table('users')->where('id',$this_secret_key['uid'])->limit(1)->value('superior');
        }else{
            $this_secret_key['top_id'] = 1;
        }

        //dump($this_secret_key);


        $this->mch_id = $this_secret_key['mch_id'];
        $this->app_id = $this_secret_key['app_id']; 
        return $this_secret_key;
    }

  	 protected function get_official_child_account($type){
        //查询具体收款子账户商户id、key

        $secret_key_arr=Cache::get('secret_key_arr'.$type);

        //公钥秘钥的数组        
        $secret_key_arr_key=Cache::get('secret_key_arr_key'.$type);
        //存放公钥秘钥索引的数组

        $secret_key_arr_key_i = Cache::get('secret_key_arr_key_i'.$type);
        //当前数组的指标


        if (!$secret_key_arr||!$secret_key_arr_key||$secret_key_arr_key_i===false) {
            //不存在相关密钥数据时从数据库获取
            unset($secret_key_arr);
            unset($secret_key_arr_key);
            unset($secret_key_arr_key_i);
            
            $secret_key_data = Db::table('top_account_assets ta')
            ->field('tc.id,tc.pid,tc.private_key,tc.public_key,tc.mch_id')
            ->join('top_child_account tc','ta.id=tc.pid')
            ->where('tc.status',1)
            ->where('ta.type',$type)
            ->select();
            //查询状态为1的密钥数据
	
            foreach ($secret_key_data as $key => $value) {
                $secret_key_arr[$value['mch_id']] = $value;
                $secret_key_arr_key[] = $value['mch_id'];
            }

            //使用appid作为key存入数组、缓存
            if (!isset($secret_key_arr)) {
                $secret_key_arr = null;
                $secret_key_arr_key = null;
            }
            Cache::set('secret_key_arr'.$type,$secret_key_arr,36000);
            
            Cache::set('secret_key_arr_key'.$type,$secret_key_arr_key,36000);
            //使用i++方式获取当前appid而设置的数组、缓存

            $secret_key_arr_key_i = 0;
            //当前指针
            Cache::set('secret_key_arr_key_i'.$type,$secret_key_arr_key_i,36000);
        }
       
        if ($secret_key_arr==null) {
            $ret['code'] = '10007';
            $ret['info'] = '商户额度已满，请稍后重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $this_secret_key = $secret_key_arr[$secret_key_arr_key[$secret_key_arr_key_i]];
        $secret_key_arr_key_i++;
        //指针加一

        if ($secret_key_arr_key_i>count($secret_key_arr_key)-1) {
            //指针大于数组长度时归零
            $secret_key_arr_key_i = 0;
        }

        Cache::set('secret_key_arr_key_i'.$type,$secret_key_arr_key_i,36000);

        $this_secret_key['private_key'] = base64_decode(base64_decode($this_secret_key['private_key']));

        $this_secret_key['public_key'] = base64_decode(base64_decode($this_secret_key['public_key']));

        $this->private_key = $this_secret_key['private_key'];
        $this->public_key = $this_secret_key['public_key'];
        $this->mch_id = $this_secret_key['mch_id'];

        return $this_secret_key;
    }  

  

    protected function get_child_account_alipay($type){

        //查询具体收款子账户商户id、key
        cache("secret_key_arr",null);
        $secret_key_arr=Cache::get('secret_key_arr');

        //公钥秘钥的数组        
        $secret_key_arr_key=Cache::get('secret_key_arr_key');
        //存放公钥秘钥索引的数组

        $secret_key_arr_key_i = Cache::get('secret_key_arr_key_i');
        //当前数组的指标

        if (!$secret_key_arr||!$secret_key_arr_key||$secret_key_arr_key_i===false) {
            //不存在相关密钥数据时从数据库获取
            unset($secret_key_arr);
            unset($secret_key_arr_key);
            unset($secret_key_arr_key_i);
            
            $secret_key_data = Db::table('top_account_assets ta')
            ->field('tc.id,tc.pid,tc.private_key,tc.public_key,tc.mch_id,ta.uid,ta.is_enterprise,ta.app_id')
            ->join('top_child_account tc','ta.id=tc.pid')
            ->where('tc.status',1)
            ->where('ta.type',$type)
            ->select();
            //查询状态为1的密钥数据

            foreach ($secret_key_data as $key => $value) {
                $secret_key_arr[$value['mch_id']] = $value;
                $secret_key_arr_key[] = $value['mch_id'];
            }

            //使用appid作为key存入数组、缓存
            if (!isset($secret_key_arr)) {
                $secret_key_arr = null;
                $secret_key_arr_key = null;
            }
            Cache::set('secret_key_arr',$secret_key_arr,36000);
            
            Cache::set('secret_key_arr_key',$secret_key_arr_key,36000);
            //使用i++方式获取当前appid而设置的数组、缓存

            $secret_key_arr_key_i = 0;
            //当前指针
            Cache::set('secret_key_arr_key_i',$secret_key_arr_key_i,36000);
        }
       
        if ($secret_key_arr==null) {
            $ret['code'] = '10007';
            $ret['info'] = '商户额度已满，请稍后重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $this_secret_key = $secret_key_arr[$secret_key_arr_key[$secret_key_arr_key_i]];
        $secret_key_arr_key_i++;
        //指针加一

        if ($secret_key_arr_key_i>count($secret_key_arr_key)-1) {
            //指针大于数组长度时归零
            $secret_key_arr_key_i = 0;
        }

        Cache::set('secret_key_arr_key_i',$secret_key_arr_key_i,36000);
        
        $this->mch_id = $this_secret_key['mch_id'];
        $this->app_id = $this_secret_key['app_id'];

        return $this_secret_key;
    }
    
        protected function get_child_account_wechat($type){
        //查询具体收款子账户商户id、key
         //cache("secret_key_arr_wechat",null);
        $secret_key_arr=Cache::get('secret_key_arr_wechat');

        //公钥秘钥的数组        
        $secret_key_arr_key=Cache::get('secret_key_arr_key_wechat');
        //存放公钥秘钥索引的数组

        $secret_key_arr_key_i = Cache::get('secret_key_arr_key_i_wechat');
        //当前数组的指标


        if (!$secret_key_arr||!$secret_key_arr_key||$secret_key_arr_key_i===false) {
            //不存在相关密钥数据时从数据库获取
            unset($secret_key_arr);
            unset($secret_key_arr_key);
            unset($secret_key_arr_key_i);
            
            $secret_key_data = Db::table('top_account_assets ta')
            ->field('tc.id,tc.pid,ta.is_enterprise,ta.app_id,ta.uid')
            ->join('top_child_account tc','ta.id=tc.pid')
            ->where('tc.status',1)
            ->where('ta.type',$type)
            ->select();
            //查询状态为1的密钥数据
            
            foreach ($secret_key_data as $key => $value) {
                $secret_key_arr[$value['app_id']] = $value;
                $secret_key_arr_key[] = $value['app_id'];
            }

            //使用appid作为key存入数组、缓存
            if (!isset($secret_key_arr)) {
                $secret_key_arr = null;
                $secret_key_arr_key = null;
            }
            Cache::set('secret_key_arr_wechat',$secret_key_arr,36000);
            
            Cache::set('secret_key_arr_key_wechat',$secret_key_arr_key,36000);
            //使用i++方式获取当前appid而设置的数组、缓存

            $secret_key_arr_key_i = 0;
            //当前指针
            Cache::set('secret_key_arr_key_i_wechat',$secret_key_arr_key_i,36000);
        }
       
        if ($secret_key_arr==null) {
            $ret['code'] = '10007';
            $ret['info'] = '商户额度已满，请稍后重试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $this_secret_key = $secret_key_arr[$secret_key_arr_key[$secret_key_arr_key_i]];
        $secret_key_arr_key_i++;
        //指针加一

        if ($secret_key_arr_key_i>count($secret_key_arr_key)-1) {
            //指针大于数组长度时归零
            $secret_key_arr_key_i = 0;
        }

        Cache::set('secret_key_arr_key_i_wechat',$secret_key_arr_key_i,36000);

        $this->app_id = $this_secret_key['app_id'];

        return $this_secret_key;
    }
  
  //银行固码查询账户链接
    
    protected function get_solid_bank_child_account($taid){
      
        //查询具体收款子账户商户id、key
        $pid_data = Db::table('top_account_assets ta')
            ->field('tc.id,tc.pid,tc.private_key,tc.public_key,tc.mch_id,tc.amount,ta.is_enterprise,tc.receive_account')
            ->join('top_child_account tc','ta.id=tc.pid')
            ->where('ta.id',$taid)
            ->where('tc.status',1)
            ->find();

        if (!$pid_data) {
            $ret['code'] = '10010';
            $ret['info'] = '当前订单量过大,请稍后再试';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $this->private_key = $pid_data['private_key'];
        $this->public_key = $pid_data['public_key'];
        $this->mch_id = $pid_data['mch_id'];
        return $pid_data;
    }
  


    protected function get_child_account($taid,$pay_amount){
      
        //dump($pay_amount);die;
        //查询具体收款子账户商户id、key
        $pid_data = Db::table('top_account_assets ta')
            ->field('tc.id,tc.pid,tc.private_key,tc.public_key,tc.mch_id,tc.amount,ta.is_enterprise,tc.receive_account')
            ->join('top_child_account tc','ta.id=tc.pid')
            ->where('ta.id',$taid)
            ->where('tc.status',1)
            ->where('tc.amount',$pay_amount)
            ->find();

        if (!$pid_data) {
            $ret['code'] = '10010';
            $ret['info'] = '付款整百，成功率更高哦';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }
        // dump($pid_data);die;
        // $pid_data = $pid_data[array_rand($pid_data)];

        // $pid_data['private_key'] = $pid_data['private_key'];

        // $pid_data['public_key'] = $pid_data['public_key'];

        $this->private_key = $pid_data['private_key'];
        $this->public_key = $pid_data['public_key'];
        $this->mch_id = $pid_data['mch_id'];
        //$this->name = $pid_data['receive_account'];


        return $pid_data;
    }

    protected function get_child_other_account($taid){
        //查询具体收款子账户商户id、key
        $pid_data = Db::table('top_account_assets ta')
            ->field('tc.id,tc.pid,tc.private_key,tc.public_key,tc.mch_id,tc.pay_url')
            ->join('top_child_account tc','ta.id=tc.pid')
            ->where('ta.type',$taid)
            ->where('tc.status',1)
            ->select();

        if (!$pid_data) {
            $ret['code'] = '10010';
            $ret['info'] = '通道临时维护中';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pid_data = $pid_data[array_rand($pid_data)];

        $pid_data['private_key'] = base64_decode(base64_decode($pid_data['private_key']));

        $pid_data['public_key'] = base64_decode(base64_decode($pid_data['public_key']));

        $this->private_key = $pid_data['private_key'];
        $this->public_key = $pid_data['public_key'];
        $this->mch_id = $pid_data['mch_id'];
        $this->pay_url = $pid_data['pay_url'];

        return $pid_data;
    }




    protected function notify_child_account($order_id,$taid=null){
        //mch_order.id查询该订单商户号、key
        //使用taid获取秘钥的 只能单个子商户

        if ($taid==null) {
            $config_data = Db::table('mch_order m')
                ->field('tc.private_key,tc.public_key,tc.mch_id,tc.pay_url')
                ->join('top_child_account tc','tc.id=m.tcid')
                ->where('m.id',$order_id)
                ->find();
        }else{
            $config_data = Db::table('top_child_account')
                ->field('private_key,public_key,mch_id')
                ->where('pid',$taid)
                ->find();
        }
        

        if ($config_data==null) {
            return false;
        }

        $config_data['private_key'] = base64_decode(base64_decode($config_data['private_key']));

        $config_data['public_key'] = base64_decode(base64_decode($config_data['public_key']));
        
        $this->private_key = $config_data['private_key'];
        $this->public_key = $config_data['public_key'];
        $this->mch_id = $config_data['mch_id'];
        $this->pay_url = $config_data['pay_url'];
    }
	
  	protected function notify_update_reissue($order_data,$received,$trade_no,$pay_time){
        //order_data:订单表数据，
        // received：收款账户实际收到的金额（减去上级收取的手续费后）
        //trade_no：上级流水
        //pay_time：付款时间戳

        if($order_data['pay_status']==1){
            $this->linshi_spl('拦截通知 '.$order_data['id'],date('Y-m-d H:i:s'));
            return ['code'=>1,'data'=>0];
        }elseif($order_data['accept_time'] < (time()-60*60*36)){
        	$this->linshi_spl('超时回调拦截 '.$order_data['id'],date('Y-m-d H:i:s'));
            return ['code'=>0,'data'=>0];
        }

        Db::startTrans();
        //启动事务

        $user_money = Db::table('assets a')
            ->field('a.money,uf.fee')
            ->join('user_fee uf','a.uid=uf.uid')
             ->where('a.uid',$order_data['uid'])
             ->where('uf.taid',$order_data['type'])
            ->find();
        //用户资金数据


      	$account_this_fee = $order_data['pay_amount']*$user_money['fee'];
        //进一取
      
      	$account_this_fee = ceil((int)$account_this_fee);
        //进一取

        $account_this_fee = $account_this_fee<10?10:$account_this_fee;
        //$account_this_fee = $account_this_fee<10?1:$account_this_fee;
        //最少收一毛

        $account_this_fee = $account_this_fee<$order_data['pay_amount']-$received?$order_data['pay_amount']-$received:$account_this_fee;
        //如果下级手续费小于上级收费，则收取下级相同手续费

        $real_money = $order_data['pay_amount']-$account_this_fee;
        //客户所得金额,手续费：精确到分，进一取位
        
        $profit = $received-$real_money;
        //利润=收款账户收到的资金-客户得到的资金
        
        $tc_money = Db::table('top_account_assets')->where('id',$order_data['pay_type'])->value('money');
        //收款账户当时余额

        $profits_money = Db::table('assets')->where('uid',1)->value('money');
        //利润余额

        $update_order = array();

        $update_order = [
            'pay_status'=>1,
            //修改订单状态

            'pay_time'=>$pay_time,
            //订单支付时间

            'trade_no'=>$trade_no,
            //第三方交易流水


            'this_money'=>$user_money['money']+$real_money,
            //用户充值后余额

            'this_received_money'=>$tc_money+$received,
            //收款账户充值后余额

            'this_profits_money'=>$profits_money+$profit,
            //利润充值后余额


            'this_fee'=>$order_data['pay_amount']-$real_money,
            //下级商户本次手续费

            'this_channel_fee'=>$order_data['pay_amount']-$received,
            //上级商户本次手续费

            'fee'=>$profit,
            //本次利润
            'pay_amount' =>$order_data['pay_amount'],
          	
          	'receipt_amount'=>$order_data['receipt_amount'],
        ];

        $top_account_assetsModel = Db::name('top_account_assets');
        $top_child_accountModel = Db::name('top_child_account');
        $bool1 = Db::table('mch_order')->where('id',$order_data['id'])->where('pay_status',2)->update($update_order);
        //更新订单


        //增加下级商户余额、累计收款、累计手续费
        $bool2 = Db::table('assets')->where('uid',$order_data['uid'])->setInc('money',$real_money);

        $bool3 = Db::table('assets')->where('uid',$order_data['uid'])->setInc('fee_sum',$order_data['pay_amount']-$real_money);

        $bool4 = Db::table('assets')->where('uid',$order_data['uid'])->setInc('recharge_sum',$order_data['pay_amount']);

        

        //增加上级收款商户余额、累计收款、累计手续费
        $bool5 = $top_account_assetsModel->where('id',$order_data['pay_type'])->setInc('recharge_sum',$order_data['pay_amount']);
        
        $bool6 = $top_account_assetsModel->where('id',$order_data['pay_type'])->setInc('money',$received);

        $top_account_assetsModel->where('id',$order_data['pay_type'])->setInc('fee_sum',$order_data['pay_amount']-$received);

       // $top_account_assetsModel->where('id',$order_data['pay_type'])->setInc('daily_limit',$order_data['pay_amount']);
		
      	//连续收单未回调次数变为0
      	$top_account_assetsModel->where('id',$order_data['pay_type'])->update(['receipt'=>0]);

        //增加收款商户具体子账户余额、累计收款、累计手续费
        $bool7 = $top_child_accountModel->where('id',$order_data['tcid'])->setInc('recharge_sum',$order_data['pay_amount']);
        
        $bool8 = $top_child_accountModel->where('id',$order_data['tcid'])->setInc('money',$received);

        $top_child_accountModel->where('id',$order_data['tcid'])->setInc('fee_sum',$order_data['pay_amount']-$received);
        

        $assetsModel = Db::name('assets');
        $user_feeModel = Db::name('user_fee');
        //有代理商平台与代理商平分利润
        $superior_id = Db::name('users')->where('id',$order_data['uid'])->value('superior');

                //通道合伙人费率计算
        $bool10 = 1;
        $top_money = 0;
        $bool11 = 1;
        if($order_data['proxy_id'] !=1){
			
            $bool11 = $assetsModel->where('uid',$order_data['proxy_id'])->setDec('margin',$order_data['pay_amount']);
            //再次扣除用户保证金
            $assetsModel->where('uid',$order_data['proxy_id'])->setInc('recharge_sum',$order_data['pay_amount']);
        }

      	if($order_data['uid'] ==363 && ($order_data['proxy_id'] == 1 || $order_data['proxy_id'] == 435 || $order_data['proxy_id'] == 437)){
            //扣除平台总览页面收款总金额
            $deduction = cache('exp_deduction'.date('Ymd'));
            cache('exp_deduction'.date('Ymd'),$deduction+$order_data['pay_amount'],10*24*60*60);

            //扣除单独每个在跑的客户的充值总金额
            $user_deduction = cache('exp_deduction_584');

            cache('exp_deduction_584',$user_deduction+$order_data['pay_amount'],10*24*60*60);

            //扣除单独每个在跑的客户的实际到账总金额
            $deduction_receive = cache('exp_deduction_receive_584');
            cache('exp_deduction_receive_584',$user_deduction+$real_money,10*24*60*60);
      }
      

        if(!empty($superior_id)){

            $user_fee = $user_feeModel->where('uid',$superior_id)->where('taid',$order_data['type'])->value('fee');
            
            $user_fee_int = $order_data['pay_amount']*($user_money['fee']-$user_fee);
            //代理商利润
            
            $superior_money = ceil((string)$user_fee_int);
            
            if($superior_money > 0){
             //增加代理商利润余额、累计收款
              $assetsModel->where('uid',$superior_id)->setInc('recharge_sum',$superior_money);
              $assetsModel->where('uid',$superior_id)->setInc('money',$superior_money);
              $user_feeModel->where('uid',$superior_id)->where('taid',$order_data['type'])->setInc('money',$superior_money);
            }
            

            //平台利润
            $platform = $profit-$superior_money-$top_money;
            //增加利润余额、累计收款
            $assetsModel->where('uid',1)->setInc('recharge_sum',$platform);
            $assetsModel->where('uid',1)->setInc('money',$platform);
            $user_feeModel->where('uid',1)->where('taid',$order_data['type'])->setInc('money',$platform);

        }else{

            $profit = $profit-$top_money;
            //增加利润余额、累计收款
            $assetsModel->where('uid',1)->setInc('recharge_sum',$profit);
            
            $assetsModel->where('uid',1)->setInc('money',$profit);

            $user_feeModel->where('uid',1)->where('taid',$order_data['type'])->setInc('money',$profit);

        }



        $bool9 = $user_feeModel->where('uid',$order_data['uid'])->where('taid',$order_data['type'])->setInc('money',$real_money);
        //增加下级商户该通道余额


        if ($bool1&&$bool2&&$bool3&&$bool4&&$bool5&&$bool6&&$bool7&&$bool8&&$bool10&&$bool11) {
            Db::commit();
            //提交事务
             return ['code'=>1,'data'=>1];
        }else{
            Db::rollback();
            //回滚
            Db::table('linshi')->insert([
                'info1'=>'回滚了 id:'.$order_data['id'],
                'info2'=>date('Y-m-d H:i:s'),
            ]);

            //send_code(get_admin_phone(),'huigun');

            return ['code'=>0];
        }
    }
  

    protected function notify_update($order_data,$received,$trade_no,$pay_time){
        //order_data:订单表数据，
        // received：收款账户实际收到的金额（减去上级收取的手续费后）
        //trade_no：上级流水
        //pay_time：付款时间戳

        if($order_data['pay_status']==1){
            $this->linshi_spl('拦截通知 '.$order_data['id'],date('Y-m-d H:i:s'));
            return ['code'=>1,'data'=>0];
        }elseif($order_data['accept_time'] < (time()-60*60*36)){
        	$this->linshi_spl('超时回调拦截 '.$order_data['id'],date('Y-m-d H:i:s'));
            return ['code'=>0,'data'=>0];
        }

        Db::startTrans();
        //启动事务

        $user_money = Db::table('assets a')
            ->field('a.money,uf.fee')
            ->join('user_fee uf','a.uid=uf.uid')
             ->where('a.uid',$order_data['uid'])
             ->where('uf.taid',$order_data['type'])
            ->find();
        //用户资金数据

        $account_this_fee = $order_data['pay_amount']*$user_money['fee'];
        //进一取
      
      	$account_this_fee = ceil((int)$account_this_fee);
        //进一取

        $account_this_fee = $account_this_fee<10?10:$account_this_fee;
        //$account_this_fee = $account_this_fee<10?1:$account_this_fee;
        //最少收一毛

        $account_this_fee = $account_this_fee<$order_data['pay_amount']-$received?$order_data['pay_amount']-$received:$account_this_fee;
        //如果下级手续费小于上级收费，则收取下级相同手续费

        $real_money = $order_data['pay_amount']-$account_this_fee;
        //客户所得金额,手续费：精确到分，进一取位
        
        $profit = $received-$real_money;
        //利润=收款账户收到的资金-客户得到的资金
        
        $tc_money = Db::table('top_account_assets')->where('id',$order_data['pay_type'])->value('money');
        //收款账户当时余额

        $profits_money = Db::table('assets')->where('uid',1)->value('money');
        //利润余额

        $update_order = array();

        $update_order = [
            'pay_status'=>1,
            //修改订单状态

            'pay_time'=>$pay_time,
            //订单支付时间

            'trade_no'=>$trade_no,
            //第三方交易流水


            'this_money'=>$user_money['money']+$real_money,
            //用户充值后余额

            'this_received_money'=>$tc_money+$received,
            //收款账户充值后余额

            'this_profits_money'=>$profits_money+$profit,
            //利润充值后余额


            'this_fee'=>$order_data['pay_amount']-$real_money,
            //下级商户本次手续费

            'this_channel_fee'=>$order_data['pay_amount']-$received,
            //上级商户本次手续费

            'fee'=>$profit,
            //本次利润
          	'pay_amount' =>$order_data['pay_amount'],
          
          	'receipt_amount'=>$order_data['receipt_amount'],
        ];

        $top_account_assetsModel = Db::name('top_account_assets');
        $top_child_accountModel = Db::name('top_child_account');
        $bool1 = Db::table('mch_order')->where('id',$order_data['id'])->where('pay_status',2)->update($update_order);
        //更新订单


        //增加下级商户余额、累计收款、累计手续费
        $bool2 = Db::table('assets')->where('uid',$order_data['uid'])->setInc('money',$real_money);

        $bool3 = Db::table('assets')->where('uid',$order_data['uid'])->setInc('fee_sum',$order_data['pay_amount']-$real_money);

        $bool4 = Db::table('assets')->where('uid',$order_data['uid'])->setInc('recharge_sum',$order_data['pay_amount']);

        

        //增加上级收款商户余额、累计收款、累计手续费
        $bool5 = $top_account_assetsModel->where('id',$order_data['pay_type'])->setInc('recharge_sum',$order_data['pay_amount']);
        
        $bool6 = $top_account_assetsModel->where('id',$order_data['pay_type'])->setInc('money',$received);

        $top_account_assetsModel->where('id',$order_data['pay_type'])->setInc('fee_sum',$order_data['pay_amount']-$received);

        //$top_account_assetsModel->where('id',$order_data['pay_type'])->setInc('daily_limit',$order_data['pay_amount']);

		//连续收单未回调次数变为0
      	$top_account_assetsModel->where('id',$order_data['pay_type'])->update(['receipt'=>0]);
      
        //增加收款商户具体子账户余额、累计收款、累计手续费
        $bool7 = $top_child_accountModel->where('id',$order_data['tcid'])->setInc('recharge_sum',$order_data['pay_amount']);
        
        $bool8 = $top_child_accountModel->where('id',$order_data['tcid'])->setInc('money',$received);

        $top_child_accountModel->where('id',$order_data['tcid'])->setInc('fee_sum',$order_data['pay_amount']-$received);


        $assetsModel = Db::name('assets');
        $user_feeModel = Db::name('user_fee');
        //有代理商平台与代理商平分利润
        $superior_id = Db::name('users')->where('id',$order_data['uid'])->value('superior');

                //通道合伙人费率计算
        $bool10 = 1;
        $top_money = 0;
        $bool11 = 1;
      	$bool12 = 1;
        if($order_data['proxy_id'] !=1){
				
         	$bool11 = $assetsModel->where('uid',$order_data['proxy_id'])->setDec('freeze',$order_data['pay_amount']);
          	 $bool12 = Db::name('record')->insert([
                'date'=>date('Y-m-d H:i:s'),
                'content'=>'正常回调扣除冻结金额',
                'time'=>time(),
                'operator'=>1,
                'child_id'=>$order_data['proxy_id'],
                'money'=>0,
                'type'=>1,
               	'freeze_money'=>-$order_data['pay_amount'],
              ]);
          	$assetsModel->where('uid',$order_data['proxy_id'])->setInc('recharge_sum',$order_data['pay_amount']);
          
            // $bool9 = $user_feeModel->where('uid',$order_data['proxy_id'])->where('taid',$order_data['type'])->setInc('money',$real_money);
            //增加下级商户余额、累计收款、累计手续费
          	
           // $bool11 = $assetsModel->where('uid',$order_data['proxy_id'])->setDec('margin',$received);
			//$fee_sum = $order_data['pay_amount']-$received;
          	//if($fee_sum > 0){
            	//$bool12 = $assetsModel->where('uid',$order_data['proxy_id'])->setInc('fee_sum',$fee_sum);
           // }
            
           // $bool13 = $assetsModel->where('uid',$order_data['proxy_id'])->setInc('recharge_sum',$order_data['pay_amount']);



        }
		if($order_data['uid'] ==363 && ($order_data['proxy_id'] == 1 || $order_data['proxy_id'] == 435 || $order_data['proxy_id'] == 437)){
            //扣除平台总览页面收款总金额
            $deduction = cache('exp_deduction'.date('Ymd'));
            cache('exp_deduction'.date('Ymd'),$deduction+$order_data['pay_amount'],10*24*60*60);

            //扣除单独每个在跑的客户的充值总金额
           $user_deduction = cache('exp_deduction_584');

            cache('exp_deduction_584',$user_deduction+$order_data['pay_amount'],10*24*60*60);

            //扣除单独每个在跑的客户的实际到账总金额
            $deduction_receive = cache('exp_deduction_receive_584');
            cache('exp_deduction_receive_584',$user_deduction+$real_money,10*24*60*60);
       }

        if(!empty($superior_id)){

            $user_fee = $user_feeModel->where('uid',$superior_id)->where('taid',$order_data['type'])->value('fee');
            
            $user_fee_int = $order_data['pay_amount']*($user_money['fee']-$user_fee);
            //代理商利润
            
            $superior_money = ceil((string)$user_fee_int);
            
          	if($superior_money > 0){
             //增加代理商利润余额、累计收款
              $assetsModel->where('uid',$superior_id)->setInc('recharge_sum',$superior_money);
              $assetsModel->where('uid',$superior_id)->setInc('money',$superior_money);
              $user_feeModel->where('uid',$superior_id)->where('taid',$order_data['type'])->setInc('money',$superior_money);
            }
            

            //平台利润
            $platform = $profit-$superior_money-$top_money;
            //增加利润余额、累计收款
            $assetsModel->where('uid',1)->setInc('recharge_sum',$platform);
            $assetsModel->where('uid',1)->setInc('money',$platform);
            $user_feeModel->where('uid',1)->where('taid',$order_data['type'])->setInc('money',$platform);

        }else{

            $profit = $profit-$top_money;
            //增加利润余额、累计收款
            $assetsModel->where('uid',1)->setInc('recharge_sum',$profit);
            
            $assetsModel->where('uid',1)->setInc('money',$profit);

            $user_feeModel->where('uid',1)->where('taid',$order_data['type'])->setInc('money',$profit);

        }



        $bool9 = $user_feeModel->where('uid',$order_data['uid'])->where('taid',$order_data['type'])->setInc('money',$real_money);
        //增加下级商户该通道余额


        if ($bool1&&$bool2&&$bool3&&$bool4&&$bool5&&$bool6&&$bool7&&$bool8&&$bool10&&$bool11&&$bool12) {
            Db::commit();
            //提交事务
            return ['code'=>1,'data'=>1];
        }else{
            Db::rollback();
            //回滚
            Db::table('linshi')->insert([
                'info1'=>'回滚了 id:'.$order_data['id'],
                'info2'=>date('Y-m-d H:i:s'),
            ]);

            //send_code(get_admin_phone(),'huigun');

            return ['code'=>0];
        }
    }


    public function linshi_spl($info1,$info2){
        //调试、做记录时调用
        Db::table('linshi')->insert([
            'info1' => $info1,
            'info2' => $info2,
        ]);
    }

    public function set_order_id($accept_time,$id){
        return date('YmdHis',$accept_time).$id;
    }

    public function get_order_id($id){
        return substr($id,14);
    }
}