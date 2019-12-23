<?php
namespace app\nagent\controller;
use think\Controller;
use think\Db;
class UserCommon extends Controller{
    protected $login_user;
    protected function _initialize(){
    	$this->login_user();
    }
    private function login_user(){
    	$login_user = session('login_user','','nagent');
    	if ($login_user==null) {

            if(isMobilePhone()){
                
               return $this->redirect('nagent/login/login'); 
            }else{
               return $this->redirect('index/login/login'); 
            }


    		
    	}else{
            
            if(isMobilePhone()){
                    
                return $this->redirect('nagent/mobile/index'); 
            }
        }
        
        $this->login_user = $login_user;
    	$this->assign('login_user',$login_user);
    }
    public function exit_login(){
    	session(null,'nagent');
    	echo '<script>window.location.href="index/login/login.html";</script>';
    }
}