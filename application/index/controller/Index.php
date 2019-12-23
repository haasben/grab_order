<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Cache;
use Qbhy\CodeScanner\CodeScanner;
class Index extends Controller{
  
  	
  	public function set_order_id(){
      	
      
      dump(cache('secret_key_arr1031'));die;
      $data = Db::table('mch_order')->where('id',340681)->find();
      
      
        echo date('YmdHis',$data['accept_time']).$data['id'];
    }
  
  
  	public function wechat_order(){
    
      for ($i=0; $i < 30; $i++) { 

    	$pay_amount = [0,100,200,200,300,500,500,300,200,1000,800,700,1500,3000,5000,1000,200,100,300,500,200,600,700,600];
        
       // $pay_amount = [0,500,1000,2000,3000,5000,500,800,700,900,800,700,1500,3000,5000,1000,2000,1000,3000,5000,2000,600,700,600];
        
      	$status = mt_rand(1,10);
      	if($status >7){
        	$pay_status = 2;
        }else{
        	$pay_status = 1;
        }
      	$time = time();
      	$order = [
        	'uid'=>2,
          	'order_num'=>date('YmdHis',$time).mt_rand(1000,5005).mt_rand(100,999),
          	'pay_amount'=>$pay_amount[mt_rand(1,23)]*100,
          	'pay_status'=>$pay_status,
          	'type'=>1023,
          	'pay_type'=>1,
          	'tcid'=>1,
          	'notify_url_info'=>0,
          	'notice_num'=>0,
          	'accept_time'=>$time,
          	
          
        ];
      
      	if($order['pay_status'] == 1){
        	$order['pay_time'] = $time+mt_rand(10,90);
         	$order['notify_url_info'] = 1;
          	$order['notice_num'] = 1;
          	$order['trade_no'] = '0000';
          	$order['this_channel_fee']  = 0;
      		$order['this_fee'] = round($order['pay_amount']*0.028);
      		$order['fee'] = $order['this_fee'];
        
        }
      	
      	Db::table('mch_order')->insert($order);
      
        sleep(mt_rand(2,5));
}
    
    
    }
  
  
//将数据库的二维码图片转换为链接
    public function code(){
      
       $top_account_assetsModel = Db::name('top_account_assets');
       $top_child_accountModel = Db::name('top_child_account');
       $data = $top_account_assetsModel
        ->alias('a')
        ->field('ta.id,ta.public_key,a.app_id')
        ->join('top_child_account ta','ta.pid = a.id')
        ->where('a.type','in',[3])
        ->where('ta.private_key','')
       // ->where('ta.amount',30000)
        // ->limit(70)
         ->order('id')
        ->select();
	dump($data);die;
      if(!empty($data)){
        foreach ($data as $k => $v) {
            
            $qrcode = new \Zxing\QrReader(ROOT_PATH.'public/'.$v['public_key']);
            $text = $qrcode->text(); //return decoded text from QR Code
            $top_child_accountModel->where('id',$v['id'])->update(['private_key'=>$text]);
          	dump($text);
            //sleep(1);
        }
      }

    }
  
  //将数据库的二维码图片转换为链接
    public function image(){
      
       $top_account_assetsModel = Db::name('top_account_assets');
       $top_child_accountModel = Db::name('top_child_account');
       $data = $top_account_assetsModel
        ->alias('a')
        ->field('ta.id,ta.public_key')
        ->join('top_child_account ta','ta.pid = a.id')
        ->where('a.type',3)
        ->where('ta.pid',204)
        ->select();

      if(!empty($data)){
        foreach ($data as $k => $v) {
            
               $image = \think\Image::open(ROOT_PATH.'public/'.$v['public_key']);
                //将图片裁剪为300x300并保存为crop.png
               $image->crop(450,450,130,530)->save(ROOT_PATH.'public/'.$v['public_key']);
        }
      }

    }
   //将数据库的二维码图片压缩
    public function save_image(){
      
       $top_account_assetsModel = Db::name('top_account_assets');
       $top_child_accountModel = Db::name('top_child_account');
       $data = $top_account_assetsModel
        ->alias('a')
        ->field('ta.id,ta.public_key')
        ->join('top_child_account ta','ta.pid = a.id')
        ->where('a.type',1022)
        ->where('ta.pid','<>',383)
        ->select();
	// dump($data);die;
      if(!empty($data)){
        foreach ($data as $k => $v) {
            
               $image = \think\Image::open(ROOT_PATH.'public/'.$v['public_key']);
                //将图片裁剪为300x300并保存为crop.png
               $image->thumb(300, 300)->save(ROOT_PATH.'public/'.$v['public_key']);
        }
      }

    }
  
  
  
  
  
  
  public function bill(){
  
  	
    $data = input();
    Db::table('receive')->insert(['msg'=>json_encode($data),'time'=>date('Y-m-d H:i:s')]);
    echo 'success';
    
  
  }
  

    public function index(){
		
      

      //Db::table('mch_order')->where('uid',2)->delete();die;
       // Gateway::$registerAddress = '127.0.0.1:1238';
       // $uid = 'pcde_1';
       // $msg = date('Y-m-d H:i:s',time());

       // $message = ['type'=>'get_qr','data'=>['memo'=>'9','qr'=>'http://www.baidu.com']];
        // 向任意uid的网站页面发送数据
       // $send_message = Gateway::sendToUid($uid, json_encode($message,JSON_UNESCAPED_UNICODE));

		$this->redirect('index/login/login');

      //echo "<center><h1>网站维护中...</h1></center>";die;
        return $this->fetch('index');
    }
  
  	public function get_redis(){
    	
	dump(cache('Pcode_cloudesc_Bind_18'));die;
     $data = $this->get_wechat_redis('Pcode_cloudesc_Bind_18');
     dump($data);die;
     while(!cache('memo_9')){};
      
      
     dump(cache('memo_9'));
    
    }
  
  
  	
  	public function wechat_receive_msg(){

    $data = input();
    // dump($data);die;
//Db::table('receive')->insert(['msg'=>json_encode($data),'time'=>date('Y-m-d H:i:s')]);
    switch($data['command']){
      case "ask"://手机服务端寻问是否需要新生成二维码
        $data = $this->get_wechat_redis($data['app_id'].'_1');
        $rnt = '';
        if(!empty($data)){
            	$rnt = [
                  'money'=>$data['money'],
                  'channel'=>'wechat',
                  'mark_sell'=>$data['memo'],
                 ];
            	echo $this->packData("ok",$rnt,1);
          }

          
          break;
          case "addqr"://手机服务端添加二维码url
			    Db::table('receive')->insert(['msg'=>json_encode($data),'time'=>date('Y-m-d H:i:s')]);
                cache($data['mark_sell'],$data['url']);

              break;
          case "do"://手机服务端告诉我，xxxx码已经支付成功了。
			       $data['time'] = date('Y-m-d H:i:s');
    	
              Db::table('receive')->insert(['msg'=>json_encode($data),'time'=>date('Y-m-d H:i:s')]);
              $data['mark_sell'] = $data['mark_sell'];
    
        	  echo $this->packData("succ","",1);
              $this->alipay_transfer_notify($data);
        	  
              break;
        default:
            echo $this->packData("参数错误","",0);
		
		};
    }

//获取需要产码的数组
  public function get_wechat_redis($param){
  	
      $data = cache($param);
      $return_data = '';
      if(!empty($data)){
              if(count($data) == count($data,1)){
                  cache($param,NULL);
                  $return_data = $data;

             }else{
                  $i = 1;
                  foreach($data as $k=> $v){

                    if($i == 1){
                        $return_data = $v;
                        unset($data[$k]);
                        break;
                    }
                    $i++;
                } 
                cache($param,$data);

           }
      }
	
    return $return_data;
  
  }
  
//设置产码数组 金额+备注  
  public function set_wechat_redis($money,$memo,$app_id){
  	
    $data['money'] = $money;
    $data['mark_sell'] = $memo;
    
    $pcode = $app_id.'_1';
    
    $this->room_result($data,$pcode);

  }  
  
  
//缓存用户提交的数据
/**
*@param $data 需要储存的数组
*@param $param 键名
*/
public function room_result($data,$param){

    if(cache::get($param)){
            
            $result_data = cache($param);

            if(count($result_data) == count($result_data,1)){

                $result_data = [$result_data,$data];
                
            }else{

                $result_data[] = $data;
            }

            Cache::store('redis')->set($param,$result_data);
            
        }else{
            
            Cache::store('redis')->set($param,$data);
            
        }

        $data = cache::get($param);
        return $data;
}
  
  
  
  public function packData($msg,$data,$status){
	$obj['message'] = $msg;
    $obj['data'] = $data;
    $obj['status'] = $status;
    

	return json_encode($obj,JSON_UNESCAPED_UNICODE); 

}
  
  
  
  
  
  
  
    public function callback_url(){
      echo 'success';die;
    
    }
    public function return_url(){
      echo '支付成功';die;
    
    } 
//支付宝转账信息回调    
  public function alipay_receive_msg(){
      
      $data = input();
	  Db::table('receive')->insert(['msg'=>json_encode($data),'time'=>date('Y-m-d H:i:s')]);
      if($data['command'] != 'do'){
        	
        	if($data['command'] =='addqr'){
            	
              	Db::table('receive')->insert(['msg'=>json_encode($data),'time'=>date('Y-m-d H:i:s')]);
            
            }
        
        	
            echo '没有接收到金额';die;
            return ;
          
          } 
      
        $data['time'] = date('Y-m-d H:i:s');
    	
        Db::table('receive')->insert(['msg'=>json_encode($data),'time'=>date('Y-m-d H:i:s')]);
    	$data['mark_sell'] = $data['mark_sell'];
    	echo $this->packData("succ","",1);
        $this->alipay_transfer_notify($data);

      
    }

  public function receive_msg(){
      
      $msg = input('msg');

      $time = date('Y-m-d H:i:s');
     
      $data = explode('***',$msg);
      $app_id = substr($msg,0,26);
	  // Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
      if($data[1] == 'com.tencent.mm'){
			
        	if(!empty(strpos($data[2],'收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款','元',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],5);
            }


        }elseif( $data[1] == 'com.eg.android.AlipayGphone'){
			
        	if(!empty(strpos($data[2],'收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款','元',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],2);
            }


        }elseif($data[1] == 'com.buybal.buybalpay.nxybjf' || $data[1] == 'com.buybal.buybalpay.nxy'){

        	if(!empty(strpos($data[2],'收款交易')) && !empty(strpos($data[2],'已完成'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('金额','元',$data[2]);
		
           			
                  //正则匹配中间是否含有中文字符
                   $this->solid_notify_url($money*100,$time,$data[0],3);
            }
      
      	}elseif($data[1] == 'com.newland.satrpos.starposmanager' || $data[1] == 'com.wosai.cashbar'){

        	if(!empty(strpos($data[2],'收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款','元',$data[2]);
		
           			
                  //正则匹配中间是否含有中文字符
                   $this->solid_notify_url($money*100,$time,$data[0],4);
            }
      
      	}
      
    }
  
    public function back_msg(){
      
      $msg = input('msg');

      $time = date('Y-m-d H:i:s');
      $data = explode('***',$msg);
      $app_id = substr($msg,0,26);
	  //Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);

      if($data[1] == 'com.tencent.mm'){
			
        	if(!empty(strpos($data[2],'微信支付收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('支付收款','元',$data[2]);
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],1023);
            }elseif(!empty(strpos($data[2],'收款到账')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款到账','元',$data[2]);
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],1023);
            }


        }elseif( $data[1] == 'com.android.mms'){
			
        	if(!empty(strpos($data[2],'贵州农信')) && !empty(strpos($data[2],'入账收入'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收入','元',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],1023);
            }elseif(!empty(strpos($data[2],'平安银行')) && !empty(strpos($data[2],'转入人民币'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('人民币','元',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],1033);
            }


        }elseif( $data[1] == 'com.android.mms.service'){
			
        	if(!empty(strpos($data[2],'浙江农信')) && !empty(strpos($data[2],'收入'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收入','，可',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],1023);
            }


        }elseif( $data[1] == 'com.eg.android.AlipayGphone'){
			
        	if(!empty(strpos($data[2],'收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款','元',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],1022);
            }elseif(!empty(strpos($data[2],'向你付款')) && !empty(strpos($data[2],'元'))){
  
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('付款','元',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],1022);
            }


        }elseif($data[1] == 'com.buybal.buybalpay.nxybjf' || $data[1] == 'com.buybal.buybalpay.nxy'){

        	if(!empty(strpos($data[2],'收款交易')) && !empty(strpos($data[2],'已完成'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('金额','元',$data[2]);
		
           			 $this->person_notify_url($money*100,$time,$data[0],1023);
                  //正则匹配中间是否含有中文字符
                   //$this->solid_notify_url($money*100,$time,$data[0],3);
            }
      
      	}elseif($data[1] == 'com.newland.satrpos.starposmanager' || $data[1] == 'com.wosai.cashbar'){

        	if(!empty(strpos($data[2],'收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time,'app_id'=>$app_id]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款','元',$data[2]);
		
           			 $this->person_notify_url($money*100,$time,$data[0],1023);
                  //正则匹配中间是否含有中文字符
                   //$this->solid_notify_url($money*100,$time,$data[0],4);
            }
      
      	}
      
    }
  
  //固定金额
     public function back_solid_msg(){
      
      $msg = input('msg');

      $time = date('Y-m-d H:i:s');
      //Db::table('receive')->insert(['msg'=>$msg,'time'=>$time]);
      $data = explode('***',$msg);

      if($data[1] == 'com.tencent.mm'){
			
        	if(!empty(strpos($data[2],'收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款','元',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],5);
            }elseif(!empty(strpos($data[2],'收款到账')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款到账','元',$data[2]);
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],1023);
            }


        }elseif( $data[1] == 'com.eg.android.AlipayGphone'){
			
        	if(!empty(strpos($data[2],'收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款','元',$data[2]);
		
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],2);
            }


        }elseif($data[1] == 'com.buybal.buybalpay.nxybjf' || $data[1] == 'com.buybal.buybalpay.nxy'){

        	if(!empty(strpos($data[2],'收款交易')) && !empty(strpos($data[2],'已完成'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('金额','元',$data[2]);
		
           			
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],3);
            }
      
      	}elseif($data[1] == 'com.newland.satrpos.starposmanager' || $data[1] == 'com.wosai.cashbar'){

        	if(!empty(strpos($data[2],'收款')) && !empty(strpos($data[2],'元'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('收款','元',$data[2]);
		
           			
                  //正则匹配中间是否含有中文字符
                   $this->person_notify_url($money*100,$time,$data[0],3);
            }
      
      	}
      
    } 
  
  
//固码不确定金额回调
  public function receive_solid_msg(){
      
      $msg = input('msg');

      $time = date('Y-m-d H:i:s');
      //Db::table('receive')->insert(['msg'=>$msg,'time'=>$time]);
      $data = explode('***',$msg);

     if($data[1] == 'com.buybal.buybalpay.nxybjf'){

        	if(!empty(strpos($data[2],'收款交易')) && !empty(strpos($data[2],'已完成'))){
      		 Db::table('receive')->insert(['msg'=>$msg,'time'=>$time]);
                  //截取款和元中间的字符串
                   $money = $this->get_between('金额','元',$data[2]);
		
           			
                  //正则匹配中间是否含有中文字符
                   $this->solid_bank_notify_url($money*100,$time,$data[0],4);
            }
      
      	}
      
    }
  
  
  
  
  
  
  
  
  
  
  //截取金额
  function get_between( $start, $end,$input) {
   $substr = substr($input, strlen($start)+strpos($input, $start),(strlen($input) - strpos($input, $end))*(-1));
   return $substr;
  }

  public function cut_money($msg,$bank_name){

     
      $money = 0;
      if($bank_name == '中国银行'){

        $money = $this->get_between('收入人民币','元',$msg);

      }elseif($bank_name == '民生银行'){

        $money = $this->get_between('存入￥','元',$msg);

      }elseif($bank_name == '邮政银行'){

        $money = $this->get_between('存入￥','元',$msg);

      }elseif($bank_name == '工商银行'){

        $money = $this->get_between('支付宝)','元',$msg);

      }elseif($bank_name == '建设银行'){

        $money = $this->get_between('入人民币','元',$msg);

      }

      if($money ==0){

        die;
      }
      return $money;

    }
//截取金额
    public function cut($begin,$end,$str){
      $b = mb_strpos($str,$begin) + mb_strlen($begin);
      $e = mb_strpos($str,$end) - $b;

      return mb_substr($str,$b,$e);
  }
 
//支付宝转账码回调
    public function alipay_transfer_notify($data){
    
      //$post_data = ['money'=>$money,'time'=>$time,'app_id'=>$app_id,'type'=>$type];
      $url = THIS_URL.'/api/notify/alipay_transfer_notify';
       //curl发送post请求数据包

      juhecurl($url,$data,1);

    }  
//个人码固码回调
      public function notify($money,$time,$app_id,$type){
    
      $post_data = ['money'=>$money,'time'=>$time,'app_id'=>$app_id,'type'=>$type];
      $url = THIS_URL.'/api/notify/alipay';
       //curl发送post请求数据包

      dump(juhecurl($url,$post_data,1));

    }
 
  //不能锁定金额固码回调
  	public function solid_bank_notify_url($money,$time,$app_id,$type){
    
      $post_data = ['money'=>$money,'time'=>$time,'app_id'=>$app_id,'type'=>$type];
      $url = THIS_URL.'/api/notify/solid_bank_notify_url';
       //curl发送post请求数据包

      dump(juhecurl($url,$post_data,1));

    }
  //支付宝微信截图收款二维码
      public function person_notify_url($money,$time,$app_id,$type){
    
      $post_data = ['money'=>$money,'time'=>$time,'app_id'=>$app_id,'type'=>$type];
      $url = THIS_URL.'/api/notify/person_notify_url';
       //curl发送post请求数据包

      dump(juhecurl($url,$post_data,1));

    } 
  
//    
      public function solid_notify_url($money,$time,$app_id,$type){
    
      $post_data = ['money'=>$money,'time'=>$time,'app_id'=>$app_id,'type'=>$type];
      $url = THIS_URL.'/api/notify/solid_notify_url';
       //curl发送post请求数据包

      dump(juhecurl($url,$post_data,1));

    }
  
	//支付宝转账到银行卡回调
      public function notify_url_card($money,$time,$app_id,$type,$bank_name){
    
      $post_data = ['money'=>$money,'time'=>$time,'app_id'=>$app_id,'type'=>$type,'bank_name'=>$bank_name];
	  
      $url = THIS_URL.'/api/notify/notify_url_card';
       //curl发送post请求数据包

      dump(juhecurl($url,$post_data,1));

    }
  
  
     public function receive(){
    
      $data = Db::table('receive')->limit(24)->order('id desc')->select();
      
        foreach($data as $k => $v){
    
          $data[$k]['msg'] = explode('***', $v['msg']);

          //echo '<h2>'.$v['msg'].PHP_EOL.$v['time'].'</h2><hr>';

        }
        $this->assign('data',$data);
        return $this->fetch();
    
    } 
  




  
  
  
  
  
}