<?php
use think\Db;
use think\Cache;

if (isset($data['this_secret_key'])) {
	//付款时调用
	$secret_key_data = $data['this_secret_key'];

}elseif (isset($data['app_id'])) {
	//回调时执行
  	
	cache('secret_key_arr',null);
	$secret_key_arr=Cache::get('secret_key_arr');
    //公钥秘钥的数组

	if (isset($secret_key_arr[$data['app_id']])) {
		//存在相关密钥数据时直接使用
        $secret_key_data = $secret_key_arr[$data['app_id']];
	}else{
		//不存在时查询数据库
		$secret_key_data = Db::table('top_account_assets ta')
		            ->field('tc.private_key,tc.public_key,tc.mch_id')
		            ->join('top_child_account tc','ta.id=tc.pid')
		            ->where('tc.mch_id',$data['app_id'])
		            ->find();
      	$secret_key_data['private_key'] = base64_decode(base64_decode($secret_key_data['private_key']));

        $secret_key_data['public_key'] = base64_decode(base64_decode($secret_key_data['public_key']));
	}
}

$config = array (
		//应用ID,您的APPID。
		'app_id' => $secret_key_data['mch_id'],

		//商户私钥
		'merchant_private_key' => $secret_key_data['private_key'],
		
		//异步通知地址
		'notify_url' => THIS_URL."/api/Notify/alipay.html",
		
		//同步跳转
		'return_url' => THIS_URL."/api/Notify/return_url.html",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => $secret_key_data['public_key']
);
