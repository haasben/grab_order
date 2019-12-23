<?php
class Config{
    private $cfg = array(
        'url'=>'https://pay.swiftpass.cn/pay/gateway',
        'mchId'=>'7551000001',
        'key'=>'9d101c97133837e13dde2d32a5054abb',  /* MD5密钥 */
        'version'=>'1.0',
        'sign_type'=>'MD5'
       );
    
    public function C($cfgName){
        return $this->cfg[$cfgName];
    }
}
?>