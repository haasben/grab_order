<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
class Demo extends Controller{
   

    public function index(){

      	$channel = Db::table('channel_type')->where('is_show',1)->order('id')->select();
      	$this->assign('channel',$channel);
        return $this->fetch();

    }
	
  	 public function submit_demo_post(){


    	if(request()->isPost()){

            $data = input();
            $result = $this->validate($data,
            [
                'pay_amount|订单金额'  => 'require|number',
                'order_num|订单号'=>'require',
                'pay_type|支付方式'    => 'require',
                //'pay_method|返回方式'=>'require',
            ]);


        //验证判断必填项
            if(true !== $result){
                // 验证失败 输出错误信息
                return ['code'=>20001,'msg'=>$result];exit;
            }


            $bank_data = $this->bank_pay($data,2);

    	//return json_decode($bank_data,true);


    	}else{


    		return ['code'=>'1111','info'=>'非法请求'];
    	}


    }
  
  
  

    public function submit_demo(){


    	if(request()->isAjax()){


    	$data = input();
		
        $result = $this->validate($data,
        [
            'pay_amount|订单金额'  => 'require|number',
            'order_num|订单号'=>'require',
            'pay_type|支付方式'    => 'require',
          	//'pay_method|返回方式'=>'require',
        ]);
    
      
    //验证判断必填项
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'info'=>$result];exit;
        }
		
        if($data['pay_type'] == 1){
        	$data['pay_type'] = 'wechat';
        }elseif($data['pay_type'] == 2){
        	
          	$data['pay_type'] = 'alipay_scan';
        
        }elseif($data['pay_type'] == 4){
        	
          	$data['pay_type'] = 'bank_code';
        
        }elseif($data['pay_type'] == 3){
        	
          	$data['pay_type'] = 'solid_code';
        
        }elseif($data['pay_type'] == '5'){
            $taid = 'wechat_native';
        }
          
           //dump($data);

    	$bank_data = $this->bank_pay($data,1);

    	return json_decode($bank_data,true);


    	}else{


    		return ['code'=>'1111','info'=>'非法请求'];
    	}


    }






 public function bank_pay($data,$type){
  
	//报备参数
	header("Content-type: text/html; charset=utf-8"); 
	$key = '608bb0b319d1844e1332688c6978c665';
	//商户key，API平台提供
	
	$data = array(
		'mch_id'=>'ZS_2',
		// Y,商户号，API平台提供
		
		'order_num'=>$data['order_num'],
		// Y,订单号，全局唯一
		
		'pay_amount'=>$data['pay_amount']*100,
		// Y,订单金额 分为单位,最低一元																		
		
		'notify_url'=>$data['notify_url'],
		// Y,填写你自己的服务器异步回调地址（通知地址,post访问携带参数），不可以为本地地址。

		'return_url'=>$data['return_url'],
		//目前暂时可以不配，但是不能为空
		
		'ext'=>$data['ext'],
		// Y备注信息，可以为空，但不可删除
		
		//支付类型 alipay 支付宝支付 wechat 微信扫码支付
		'pay_type'=>$data['pay_type'],

      	//支付方式 pc 二维码支付 mobile H5支付 目前暂不支持微信H5
      	'pay_method'=>$data['pay_method'],



	);

	$data['sign'] = $this->get_sign_str($data,$key);
	//生成sign
	
	$url_str = $this->arrayToKeyValueString($data);
	//拼接url参数

	$location_href = THIS_URL.'/api/Recharge/pay.html';
	//请求地址，API平台提供

	$location_href .= '?'.$url_str;
   if($type == 1){
   		return file_get_contents($location_href);
   }else{
     
     	//dump($location_href);die;
   		header("Location: $location_href");
   }
	
	//
}

   public function doGet($url)
    {
        //初始化
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //执行并获取HTML文档内容
        $output = curl_exec($ch);

        //释放curl句柄
        curl_close($ch);
        
        return $output;
    }
  
  
public function get_sign_str($data, $key){
	if(isset($data['sign'])) {
		unset($data['sign']);
	}
    ksort($data);
    $sign_str = '';
    foreach($data as $k => $v) {
        $sign_str .= $k . '='.$v.'&';
    }
    $sign_str = substr($sign_str,0,strlen($sign_str)-1);
    $sign_str = strtoupper(md5( $sign_str."&key=".$key));
    return $sign_str;
}

public function arrayToKeyValueString($data){
	$url_str = '';
	foreach($data as $key => $value) {
		$url_str .= $key .'=' . $value . '&';
	}
	$url_str = substr($url_str,0,strlen($url_str)-1);
	return $url_str;
}




}