<?php
namespace app\mobile\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
use think\Cache;
class Login extends Controller{
    public function login(){
    	if (request()->isAjax()) {
    		$data = input('');
    		$validate = new Validate([
                'email|登陆账号' => 'require|gt:0',
                'pass|密码'=>'require|gt:0',

                'vercode|验证码'=>'require',
            ]);

            if (!$validate->check($data)){

                return ['success'=>'1111','hint'=>$validate->getError()];
                die;
            }


    		$login_ip = getRealIp();
    		$cache_login_time = Cache::get($login_ip.'cache_login_time');
    		//限制ip两秒内只能尝试登陆一次。
    		if ($cache_login_time&&time()-$cache_login_time<3) {
    			$ret['success'] = 5;
				$ret['hint'] = '操作太频繁,请稍后';
				return $ret;
    		}
    		Cache::set($login_ip.'cache_login_time',time(),2);

    		
	    	if(!captcha_check($data['vercode'])){
				$ret['success'] = 3;
				$ret['hint'] = '验证码输入有误';
				return $ret;
			}
			$pass = encryption($data['pass']);
			$sql_data = Db::table('users')->field('id,email,pass,login_time,phone_num,merchant_cname,company,state,name,login_ip,subordinate')->where('pass',$pass)->where('email|phone_num',$data['email'])->where('state','>',98)->find();
			if ($sql_data['pass']===$pass&&$sql_data['email']===$data['email']) {

					unset($sql_data['pass']);
					// unset($sql_data['state']);
					$sql_data['this_login_time'] = time();
					
					//Db::table('users')->where('id',$sql_data['id'])
						//->update([
							//'login_time'=>$sql_data['this_login_time'],
							//'login_ip'=>$login_ip,
							//]);
					session(null,'mobile');
					session('login_user',$sql_data,'mobile');
					$ret['success'] = 100;
					$ret['hint'] = '登陆成功';
					return $ret;
				
			}else{
				$ret['success'] = 4;
				$ret['hint'] = '用户名或密码错误';
				return $ret;
			}
    		exit;
    	}
        return $this->fetch('login');
    }
}