<?php
namespace app\index\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
class Registered extends Controller{
    public function register(){
    	// echo 1;die;
        return $this->fetch('register');
    }
    public function register_code_ajax(){
    	//注册手机验证码
    	if (!request()->isAjax()) {
    		return $this->redirect('index/Index/index');
    	}

    	$phone_code_arr = session('phone_code_arr','','index');

    	if (is_array($phone_code_arr)&&$phone_code_arr['scenario']=='register_code_ajax') {
    		$old_time = $phone_code_arr['time'];
    		$time_poor = time()-$old_time;
    		if ($time_poor<60) {
    			$ret['success'] = 2;
				$ret['hint'] = 60-$time_poor.'秒后可再次发送';
				return $ret;
    		}
    	}

    	$data = input('get.');
    	$validate = new Validate([
			'phone_num'=>'regex:/^1[34578]\d{9}$/'
		]);

		if ($validate->check($data)){
			//已通过验证规则
			$phone_data = Db::table('users')->where('phone_num',$data['phone_num'])->find();

			if (count($phone_data)!=0) {
				$ret['success'] = 4;
				$ret['hint'] = '该手机号已经注册，请直接登录';
				return $ret;
			}

			$bool = get_phone_code($data['phone_num'],'register_code_ajax');

			if (!$bool) {
				$ret['success'] = 5;
				$ret['hint'] = '短信验证码发送失败，请稍后重试';
				return $ret;
			}

			
			$ret['success'] = 1;
			$ret['hint'] = '已发送验证码至您的手机';
			return $ret;
		}
    }
    public function register_2(){
    	if (!request()->isPost()) {
    		return $this->redirect('/index/Registered/register');
    	}
    	$data = input('post.');
    	$validate = new Validate([
			'email' => 'require|email',
			'phone_num'=>'require|regex:/^1[34578]\d{9}$/',
			'code' => 'require|number|length:6'
		],[
		    'phone_num' => '手机号错误',
		    'code'     => '验证码错误',
		    'email'        => '邮箱格式错误'
		]);
		if (!$validate->check($data)){
			echo '<script>alert("'.$validate->getError().'");window.history.back(-1);</script>';
			die;
		}
		$phone_code_arr = session('phone_code_arr','','index');
		if ($phone_code_arr['phone_num']!=$data['phone_num']||$phone_code_arr['code']!=$data['code']||$phone_code_arr['scenario']!='register_code_ajax') {
			echo '<script>alert("验证码错误");window.history.back(-1);</script>';
			die;
		}

		if (time()-$phone_code_arr['time']>1800) {
			echo '<script>alert("验证码过期，请重新获取");window.history.back(-1);</script>';
			die;
		}

		$email_data = Db::table('users')->where('email',$data['email'])->value('email');
		if ($email_data!=null) {
			echo '<script>alert("该邮箱已绑定账户，请更改邮箱");window.history.back(-1);</script>';
			die;
		}
		//验证码验证成功,设置密码
		$phone_code_arr['email'] = $data['email'];
		session('phone_code_arr',$phone_code_arr,'index');
        return $this->fetch();
    }
    public function register_3(){
    	if (request()->isPost()) {
    		$data = input('post.');
    		$validate = new Validate([
				'pass' => 'require|length:6,16',
				'repeat_pass'=>'require|confirm:pass',
				'trading_pass' => 'require|length:6,16',
				'name' => 'require|length:1,255',
				'web_url' => 'require|length:1,255',
				'company' => 'require|length:1,255',
			]);
			if (!$validate->check($data)){
				$ret['success'] = 2;
				$ret['hint'] = $validate->getError();
				return $ret;
			}

			$phone_code_arr = session('phone_code_arr','','index');

			if (time()-$phone_code_arr['time']>1800) {
				$ret['success'] = 3;
				$ret['hint'] = '页面过期，请验证提交';
				return $ret;
			}


			$data['email'] = $phone_code_arr['email'];
			$data['phone_num'] = $phone_code_arr['phone_num'];
			$data['join_time'] = time();
			unset($data['repeat_pass']);

			$sql_phone = Db::table('users')->where('phone_num',$data['phone_num'])->value('phone_num');

			if ($sql_phone!=null) {
				$ret['success'] = 3;
				$ret['hint'] = '该手机号已注册。';
				return $ret;
			}


			$email_phone = Db::table('users')->where('email',$data['email'])->value('email');

			if ($email_phone!=null) {
				$ret['success'] = 3;
				$ret['hint'] = '该邮箱已绑定账户，请更改邮箱';
				return $ret;
			}



			$Getfirstchar = Model('Getfirstchar'); 



			$data['merchant_cname'] = strtoupper($Getfirstchar->pinyin($data['company']));

			$j = 1;
			$old_merchant_cname = $data['merchant_cname'];
			for ($i=0; $i < $j; $i++) {
				$sql_data = Db::table('users')->where('merchant_cname',$data['merchant_cname'])->value('merchant_cname');
				if ($sql_data!=null) {
					$data['merchant_cname'] = $old_merchant_cname.($j+1);
					$j++;
				}
			}
			$data['pass'] = encryption($data['pass']);
			$data['trading_pass'] = encryption($data['trading_pass']);
          	
          	//增加谷歌验证码
          	$gangsta = new \Google\Authenticator\Authenticator();

          	$data['authenticator'] = $gangsta->generateSecret();
          
			$insert_id = Db::table('users')->insertGetId($data);
			if ($insert_id) {
				$email_code = $data['join_time'].$data['name'].$data['email'].$insert_id;
				$email_code = encryption($email_code);
				$email_str = '<strong>尊敬的商户，您好！ </strong> </br>&nbsp;&nbsp;&nbsp;&nbsp;请您立刻使用下面的链接来激活您的账号。<a href="http://'.$_SERVER['HTTP_HOST'].'/index/Registered/email_code?email='.$data['email'].'&email_code='.$email_code.'">点击验证</a>。</br>如果因为您的邮件阅读程序不支持点击跳转验证，建议您把以下链接复制到网页浏览器（如：IE）的地址栏里打开：</br>http://'.$_SERVER['HTTP_HOST'].'/index/Registered/email_code?email='.$data['email'].'&email_code='.$email_code.'</br>注：这是一封系统自动产生的邮件，请勿回复。</br><div style="COLOR: #000; text-align:right;"></div><div style="text-align:right;">'.date('Y-m-d').'</div>';
				$get_email = sendEmail($data['email'],$email_str);
				if ($get_email) {
					$ret['success'] = 1;
					$ret['hint'] = '发送邮件成功';
					session('phone_code_arr',null,'index');
					$success_data['email'] = $data['email'];
					$success_data['time'] = $data['join_time'];
					$success_data['email_str'] = $email_str;
					session('success_data',$success_data,'index');
					return $ret;
				}else{
					$ret['success'] = 2;
					$ret['hint'] = '验证邮件发送失败，请稍后重试';
					return $ret;
				}
			}
			exit;
    	}
    	$success_data = session('success_data','','index');
    	if (time()-$success_data['time']<1800) {
    		return $this->fetch('register_3',['success_data'=>$success_data]);
    	}else{
    		return $this->register();
    	}
        
    }
    public function repeat_email(){
    	//重新发送邮箱验证
    	$success_data = session('success_data','','index');
    	$this_time = time();
    	$get_email_time = $this_time-$success_data['time'];
    	if ($get_email_time<60) {
    		echo '<script>alert("请稍等，'.(60-$get_email_time).'秒后可再次发送");window.history.back(-1);</script>';
			die;
    	}elseif ($get_email_time>1800) {
    		echo '<script>alert("页面已过期");window.location.href="/index/Registered/register.html";</script>';
			die;
    	}
    	$get_email = sendEmail($success_data['email'],$success_data['email_str']);
    	if ($get_email) {
    		$success_data['time'] = $this_time;
    		session('success_data',$success_data,'index');
    		echo '<script>alert("邮件发送成功，请注意查收");window.history.back(-1);</script>';
    	}else{
    		echo '<script>alert("验证邮件发送失败，请稍后重试");window.history.back(-1);</script>';
    	}
    }
    public function email_code(){
    	//验证激活邮箱
    	$data = input('get.');
    	$validate = new Validate([
			'email' => 'require|email',
			'email_code' => 'require|length:32'
		]);
		if (!$validate->check($data)){
			echo '<script>alert("参数有误");</script>';
			return $this->register();
		}
		$sql_data = Db::table('users')->field('join_time,name,id,state')->where('email',$data['email'])->find();
		if ($sql_data==null) {
			echo '<script>alert("参数有误");</script>';
			return $this->register();
		}
		$email_code = $sql_data['join_time'].$sql_data['name'].$data['email'].$sql_data['id'];
		$email_code = encryption($email_code);
		if ($email_code==$data['email_code']) {
			if (time()-$sql_data['join_time']>1800) {
				echo '<script>alert("页面已过期");</script>';
				return $this->register();
				exit;
			}
			if ($sql_data['state']==3) {
				$key = time().'key'.rand(0,1000);
    			$key = encryption($key);
				$results = Db::table('users')->where('id',$sql_data['id'])->update(['state'=>4,'key'=>$key]);
				if ($results) {
					$bool = Db::table('assets')->insert(['uid'=>$sql_data['id']]);
					if ($bool) {
                      	$this->redirect('/index/login/login');die;
						return $this->fetch('register_4');
					}else{
						echo '<script>alert("数据插入失败，请截图联系我们客服");</script>';
						return $this->register();
					}
				}else{
					echo '<script>alert("激活失败，请稍后重试");</script>';
					return $this->register();
				}
			}elseif ($sql_data['state']==2) {
				echo '<script>alert("参数异常");</script>';
				return $this->register();
				exit;
			}elseif ($sql_data['state']==1) {
              	$this->redirect('/index/login/login');die;
				return $this->fetch('register_4');
			}
		}else{
			echo '<script>alert("参数错误");</script>';
			return $this->register();
		}
    }
}