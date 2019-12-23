<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Cache;
class Timer extends ApiDemo{
   
//定时任务控制器
  
  //每十秒检测一次是否有收款三笔都没有付款的账户
  public function exceed_three(){
  	
    $uid = Db::table('users')->field('id')->where('state',77)->select();
    $uid = array_column($uid,'id');
    $uid = Db::table('mch_order')
      	->field('proxy_id')
      	->where('accept_time','between time',[time()-60*60,time()])
      	->where('proxy_id','in',$uid)
      	->group('proxy_id')
    	->having('count(proxy_id)>=3')
      	->select();
    $uid = array_column($uid,'proxy_id');
   	if(!empty($uid)){

      	$bool = Db::table('assets')->where('uid','in',$uid)->update(['is_open'=>2]);
      	
      	if($bool){
        	echo 'success';
        }else{
        	echo 'error';
        }
    
    } 
   
  
  
  }



}