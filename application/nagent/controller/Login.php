<?php
namespace app\nagent\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
use think\Cache;
class Login extends Controller{
    public function Login(){
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

    		
    		$data = input();

          	//dump(captcha_check($data['vercode']));die;
	    	if(!captcha_check($data['vercode'])){
				$ret['success'] = 3;
				$ret['hint'] = '验证码输入有误';
				return $ret;
			}
			$pass = encryption($data['pass']);
			$sql_data = Db::table('users')->field('id,email,pass,login_time,phone_num,merchant_cname,company,state,name,login_ip,subordinate')->where('pass',$pass)->where('email|phone_num',$data['email'])->find();
			if ($sql_data['pass']===$pass) {

					$ret['success'] = 1;
					$ret['hint'] = '登录成功';
				if($sql_data['state']==1||$sql_data['state']==4){
					$login_user = 'admin';

				}elseif($sql_data['state']==100||$sql_data['state']==99 ||$sql_data['state']==89){
					$login_user = 'sadmin';

				}elseif($sql_data['state']==88){
					$login_user = 'madmin';

				}elseif($sql_data['state']==66){
					$login_user = 'quotient';
					
				}elseif($sql_data['state']==90 || $sql_data['state']==77){
					$login_user = 'nagent';
					
				}elseif($sql_data['state']==3){
					$login_user = '';
					$ret['success'] = 2;
					$ret['hint'] = '账户未激活，请重新注册';
					return $ret;
				}else{
					$ret['success'] = 2;
					$ret['hint'] = '登陆失败，请稍后重试';
					return $ret;
				}

				$sql_data['this_login_time'] = time();
					
					Db::table('users')->where('id',$sql_data['id'])
						->update([
							'login_time'=>$sql_data['this_login_time'],
							'login_ip'=>$login_ip,
							]);
				session(null,$login_user);
				session('login_user',$sql_data,$login_user);
				$ret['url'] = '/'.$login_user.'/user/index';

			}else{

				$ret['success'] = 4;
				$ret['hint'] = '用户名或密码错误';
				
			


			}

			return $ret;

    		exit;
    	}
        return $this->fetch();
    }
}