<?php
namespace app\magent\controller;
use think\Controller;
use think\Db;
class Common extends Controller{
    protected $login_user;
    protected function _initialize(){
        //echo 'ά����';die;
        $this->login_user();
    }
    private function login_user(){
        $login_user = session('login_user','','magent');
        if (!$login_user) {
            return $this->redirect('magent/login/login');
        }
        $this_login_time = Db::table('users')->where('id',$login_user['id'])->value('login_time');
        //if (isset($login_user['this_login_time'])&&$login_user['this_login_time']!=$this_login_time) {
        //	echo '<script>alert("�����˺����ڱ𴦵�½�����˻����˲�֪�飬���鼰ʱ�޸�����");</script>';
        //	return $this->exit_login();
        //	exit;
        //}

        $this->login_user = $login_user;
        $this->assign('login_user',$login_user);
    }
    public function exit_login(){
        session(null,'magent');
        echo '<script>window.location.href="/magent/login/login.html";</script>';
    }
}