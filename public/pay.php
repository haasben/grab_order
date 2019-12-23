<?php
header("Content-type: text/html; charset=utf-8"); 

//echo '<script>alert("小伙子，不要搞事情哦");</script>';die;

function bank_pay(){
	//报备参数
	
	$key = '608bb0b319d1844e1332688c6978c665';
	//商户key，API平台提供
	
	$data = array(
		'mch_id'=>'ZS_2',
		// Y,商户号，API平台提供
		
		'order_num'=>"ceshi".time(),
		// Y,订单号，全局唯一
		
		'pay_amount'=>'50000',
		// Y,订单金额 分为单位,最低一元																		
		
		'notify_url'=>'http://all.jvapi.com/index/index/callback_url',
		// Y,填写你自己的服务器异步回调地址（通知地址,post访问携带参数），不可以为本地地址。

		'return_url'=>'http://www.baidu.com',
		//目前暂时可以不配，但是不能为空
		
		'ext'=>'',
		// Y备注信息，可以为空，但不可删除
		
		//支付类型 alipay_transf 支付宝支付 wechat 微信扫码支付
		'pay_type'=>'solid_code',
       	'pay_type'=>'1033',
      	//'pay_type'=>'wechat_native',

      	//支付方式 pc 二维码支付 mobile H5支付 目前暂不支持微信H5
      	'pay_method'=>'cash',
      
      	//'order_ip'=>getRealIp(),



	);
	
	$data['sign'] = get_sign_str($data,$key);
	//生成sign
	
	$url_str = arrayToKeyValueString($data);
	//拼接url参数



	$location_href = 'http://all.jvapi.com/api/Recharge/pay.html';
	//请求地址，API平台提供

	$location_href .= '?'.$url_str;
	//echo file_get_contents($location_href);die;
	header("Location: $location_href");
}


 function doGet($url)
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


function get_sign_str($data, $key){
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

function arrayToKeyValueString($data){
	$url_str = '';
	foreach($data as $key => $value) {
		$url_str .= $key .'=' . $value . '&';
	}
	$url_str = substr($url_str,0,strlen($url_str)-1);
	return $url_str;
}
function getRealIp(){
    //获取ip
    $ip=false;
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) { 
            array_unshift($ips, $ip); $ip = FALSE; 
        }
        for ($i = 0; $i < count($ips); $i++) {
            if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}
bank_pay();
?>