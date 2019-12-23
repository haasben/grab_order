<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
class ApiDemo extends Controller{
    protected function get_sign_str($data, $key){
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
	/*
    * 生成签名，签名转换
    */
	protected function arrayToKeyValueString($data){
		$url_str = '';
		foreach($data as $key => $value) {
			$url_str .= $key .'=' . $value . '&';
		}
		$url_str = substr($url_str,0,strlen($url_str)-1);
		return $url_str;
	}

	public function linshi_spl($info1,$info2){
        //调试、做记录时调用
        Db::table('linshi')->insert([
            'info1' => $info1,
            'info2' => $info2,
        ]);
    }


    public function get_order_id($id){
        return substr($id,14);
    }
}