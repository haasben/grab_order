<?php
namespace app\madmin\controller;
use think\Controller;
use think\Db;
class UserCommon extends Controller{
    protected $login_user;
    protected function _initialize(){
    	$this->login_user();
    }
    private function login_user(){


    	$login_user = session('login_user','','madmin');
    	if ($login_user==null) {
    		return $this->redirect('index/login/login');
    	}
    	

        $this->login_user = $login_user;
    	$this->assign('login_user',$login_user);
    }
    public function exit_login(){
    	session(null,'madmin');
    	echo '<script>window.location.href="index/login/login.html";</script>';
    }
}