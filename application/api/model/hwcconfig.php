<?php
use think\Db;
use think\Cache;

if (isset($data['hwc_this_secret_key'])) {
    //付款时调用
    $secret_key_data = $data['hwc_this_secret_key'];
}

$config = array(
    'url'=>$secret_key_data['pay_url'],
    'mchId'=>$secret_key_data['mch_id'],
    'key'=>$secret_key_data['private_key'],  /* MD5密钥 */
    'version'=>'1.0',
    'sign_type'=>'MD5'
   );
 //dump($config);die;  


?>