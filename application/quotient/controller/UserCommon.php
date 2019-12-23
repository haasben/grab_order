<?php
namespace app\quotient\controller;
use think\Controller;
use think\Db;
class UserCommon extends Controller{
    protected $login_user;
    protected function _initialize(){
    	$this->login_user();
    }
    private function login_user(){
    	$login_user = session('login_user','','quotient');
    	if ($login_user==null) {
    		return $this->redirect('index/login/login');
    	}
    	
        // $this_login_time = Db::table('users')->where('id',$login_user['id'])->value('login_time');
        // if ($login_user['this_login_time']!=$this_login_time) {
        //     echo '<script>alert("您的账号已在别处登陆，若账户本人不知情，建议及时修改密码");</script>';
        //     return $this->exit_login();
        //     exit;
        // }
        
        $this->login_user = $login_user;
       
    	$this->assign('login_user',$login_user);
    }
    public function exit_login(){
    	session(null,'quotient');
    	echo '<script>window.location.href="index/login/login.html";</script>';
    }
}