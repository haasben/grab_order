<?php
namespace app\partner\controller;
use think\Controller;
use think\Db;
class Common extends Controller{
    protected $login_user;
    protected function _initialize(){


        // Db::table('mch_order')->where('id','<',500)->update(['notify_url'=>'http://pcode.cloud-esc.com/index/index/callback_url']);die;





      	//echo '维护中';die;
    	$this->login_user();
    }
    private function login_user(){
    	$login_user = session('login_user','','partner');
    	if ($login_user==null) {
    		return $this->redirect('partner/login/login');
    	}
    	$this_login_time = Db::table('users')->where('id',$login_user['id'])->value('login_time');
    	if (isset($login_user['this_login_time'])&&$login_user['this_login_time']!=$this_login_time) {
    		echo '<script>alert("您的账号已在别处登陆，若账户本人不知情，建议及时修改密码");</script>';
    		return $this->exit_login();
			exit;
    	}
        $this->login_user = $login_user;

    	$this->assign('login_user',$login_user);
    }
    public function exit_login(){
    	session(null,'partner');
    	echo '<script>window.location.href="index/login/login.html";</script>';
    }
}