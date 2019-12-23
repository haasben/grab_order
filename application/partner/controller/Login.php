<?php
namespace app\partner\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
use think\Cache;
class Login extends Controller{
    public function login(){
    	if (request()->isAjax()) {
    		$login_ip = getRealIp();
    		$cache_login_time = Cache::get($login_ip.'cache_login_time');
    		//限制ip两秒内只能尝试登陆一次。
    		if ($cache_login_time&&time()-$cache_login_time<3) {
    			$ret['success'] = 5;
				$ret['hint'] = '操作太频繁,请稍后';
				return $ret;
    		}
    		Cache::set($login_ip.'cache_login_time',time(),2);

    		
    		$data = input('post.');
	    	if(!captcha_check($data['vercode'])){
				$ret['success'] = 3;
				$ret['hint'] = '验证码输入有误';
				return $ret;
			}

			$pass = encryption($data['pass']);

			$sql_data = Db::table('users')->field('id,email,pass,login_time,phone_num,merchant_cname,company,state,name,login_ip,subordinate')->where('pass',$pass)->where('phone_num',$data['email'])->find();
			//dump($sql_data);die;
			if ($sql_data['pass']===$pass&&$sql_data['phone_num']===$data['email']) {

				if($sql_data['state']== 77){
					unset($sql_data['pass']);
					unset($sql_data['state']);
					$sql_data['this_login_time'] = time();
					

					Db::table('users')->where('id',$sql_data['id'])
						->update([
							'login_time'=>$sql_data['this_login_time'],
							'login_ip'=>$login_ip,
							]);
					session(null,'partner');
					session('login_user',$sql_data,'partner');
					$ret['success'] = '0000';
					$ret['hint'] = '登陆成功，跳转中...';
					return $ret;

				}else{
                	
                  	$ret['success'] = '1111';
					$ret['hint'] = '走错地方了哦';
					return $ret;
                
                }
			}else{
				$ret['success'] = '1111';
				$ret['hint'] = '用户名或密码错误';
				return $ret;
			}
    		exit;
    	}
      	
      	$id = input('id');
     
      	if(empty($id)){
         	$id = 1;
        }
      	
      	$this->assign('id',$id);
      
        return $this->fetch('login');
    }
}