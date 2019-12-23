<?php

namespace app\magent\controller;

use think\Controller;
use think\Db;
use think\Cache;

class Login extends Controller
{
    public function Login()
    {
        if (request()->isAjax()) {

            $login_ip = request()->ip();
            $cache_login_time = Cache::get($login_ip . 'cache_login_time');
            //限制ip两秒内只能尝试登陆一次。
            if ($cache_login_time && time() - $cache_login_time < 3) {
                $ret['success'] = 5;
                $ret['hint'] = '登录繁忙!';
                return $ret;
            }
            Cache::set($login_ip . 'cache_login_time', time(), 2);


            $data = input();

//            dump(captcha_check($data['vercode']));die;
            if (!captcha_check($data['vercode'])) {
                $ret['success'] = 3;
                $ret['hint'] = '验证码错误!';
                return $ret;
            }
            $pass = encryption($data['pass']);
            $sql_data = Db::table('users')->field('id,email,pass,login_time,phone_num,merchant_cname,company,state,name,login_ip,subordinate')->where('pass', $pass)->where('email|phone_num', $data['email'])->find();
            if ($sql_data['pass'] === $pass) {
              
				if($sql_data['state'] == 90){
                  $ret['success'] = 1;
                  $ret['hint'] = '登录成功!';

                  $sql_data['this_login_time'] = time();

                  Db::table('users')->where('id', $sql_data['id'])
                      ->update([
                          'login_time' => $sql_data['this_login_time'],
                          'login_ip' => $login_ip,
                      ]);
                  session(null, 'magent');
                  session('login_user', $sql_data, 'magent');
                  $ret['url'] = '/magent/index/index';
                }else{
                  $ret['success'] = 3;
                  $ret['hint'] = '无登录权限!';
                }
            } else {

                $ret['success'] = 4;
                $ret['hint'] = '账号或密码错误!';


            }
            return $ret;

            exit;
        }
        return $this->fetch();
    }
  
  //后台添加商户
    public function reg(){

        if(request()->isAjax()){

            $data = input();

            $result = $this->validate($data,
                [
                'name|商户名称'  => 'require',
               	'email|邮箱地址'    => 'email',
                'phone_num|手机号码'=>'require',
                'pass|密码'=>'require',
                'trading_pass|交易密码'=>'require',

            ]);
        //验证判断必填项
            if(true !== $result){
                // 验证失败 输出错误信息
                return ['success'=>20001,'hint'=>$result];exit;
            }elseif(!is_mobile_phone($data['phone_num'])){

                return ['success'=>20001,'hint'=>'手机号码格式错误'];exit;

            }
          
            //验证手机号和邮箱有没有被注册
            $sql_phone = Db::table('users')->where('phone_num',$data['phone_num'])->value('phone_num');

            if ($sql_phone!=null) {
                return ['success'=>20001,'hint'=>'手机号码已被注册'];exit;
            }


            $email_phone = Db::table('users')->where('email',$data['email'])->value('email');

            if ($email_phone!=null) {
                return ['success'=>20001,'hint'=>'该邮箱已绑定账户，请更改邮箱'];exit;
            }
			
          	if (!captcha_check($data['captcha'])) {
                $ret['success'] = 3;
                $ret['hint'] = '验证码错误!';
                return $ret;
            }	
          
          
            $Getfirstchar = new \app\index\model\Getfirstchar(); 
         
            $data['merchant_cname'] = strtoupper($Getfirstchar->pinyin($data['name']));

            $j = 1;
            $old_merchant_cname = $data['merchant_cname'];
            for ($i=0; $i < $j; $i++) {
                $sql_data = Db::table('users')->where('merchant_cname',$data['name'])->value('merchant_cname');
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
            $key = time().'key'.rand(0,1000);
            $data['key'] = encryption($key);
            $data['company'] = $data['name'];
            $data['join_time'] = time();
            $data['state'] = 90; //90码商代理
          	unset($data['vercode']);
            Db::startTrans();

            $id = Db::table('users')->insertGetId($data);
          	
			$bool3 = Db::table('assets')->insert(['uid'=>$id]);

              
            if($id&$bool3){
                Db::commit(); 
                return ['success'=>1,'hint'=>'添加成功'];
            }


            Db::rollback();
            return ['success'=>'1111','hint'=>'添加失败，请稍后再试'];

        }

        //代理信息
       // $superior = Db::table('users')->where('state',88)->select();

       // $this->assign('superior',$superior);
        return $this->fetch();



    }
}