<?php
namespace app\partner\controller;
use think\Controller;
use think\Db;
use think\Validate;
use think\Model;
class Withdrawal extends Common{


    public function add_withdrawal(){

        if (request()->isPost()) {
            $post_data = input('post.');

            $validate = new Validate([
                 '__token__' => 'require|token',
                'uid' => '>:0',
                // 'pay_type'=>'>:0',
                'user_account_id' =>'>:0',
                'w_amount'=>'between:10,50000',
                'gangsta_code'=>'require',
                'trading_pass'=>'require',
            ],[
                '__token__'=>'页面过期，请重新提交',
                'uid'=> '数据异常，请重试',
                // 'pay_type'=> '请选择支付方式',
                'user_account_id'=> '请选择收款人',
                'w_amount'=> '单笔提现金额在10-50000元',
                'gangsta_code'=>'请输入安全码',
                'trading_pass'=> '请输入登录密码',
            ]);
            if (!$validate->check($post_data)){
                $this->error($validate->getError());
                die;
            }
            if ($this->login_user['id']!=$post_data['uid']) {
                echo '<script>alert("数据异常，请重试");window.history.back(-1);</script>';
                die;
            }

            $user_data = Db::table("users")->where('id',$post_data['uid'])->find();

            $gangsta = new \Google\Authenticator\Authenticator();
            $code = $gangsta->getCode($user_data['authenticator']);
           // if($code != $post_data['gangsta_code']){
                //$this->error('安全码错误');


            //}

            if (encryption($post_data['trading_pass'])!==$user_data['pass']) {
                $this->error('登录密码错误');
            }

            


            $user_account_data = Db::table('user_account')->where('uid',$this->login_user['id'])->where('id',$post_data['user_account_id'])->find();

            // $sql_name = Db::table('top_account_assets')->where('id',$post_data['pay_type'])->value('sql_name');
			
       
            $pay_Model = model('Withdrawal');

            $result_data = $pay_Model->index($post_data,$user_account_data);
            $result_data = json_decode($result_data,true);

            
            if ($result_data['code'] == '0000') {
                $this->success($result_data['info']);
            }else{
                $this->error($result_data['info']);
            }

            die;
        }
        $uid = $this->login_user['id'];
        $assets_data = Db::table('assets')
            ->alias('a')
            ->field('a.*,u.merchant_cname,u.company')
            ->join('users u','u.id=a.uid')
            ->where('a.uid',$uid)
            ->find();

        $user_fee_data = Db::table('user_fee')
            ->alias('uf')
            ->field('uf.money,ct.code as show_name,uf.taid')
            ->join('channel_type ct','ct.id=uf.taid')
            ->where('uf.uid',$uid)
            ->select();

        $user_account_data = Db::table('user_account')->where('uid',$uid)->select();

        $html_data = [
            'assets_data'=>$assets_data,
            'user_fee_data'=>$user_fee_data,
            'user_account_data'=>$user_account_data,
        ];
        return $this->fetch('add_withdrawal',$html_data);

    }

    public function get_user_account(){
        $id = input('get.id');
        return Db::table('user_account')->where('id',$id)->find();
    }

    public function add_user_account(){

        if (request()->isPost()) {
            $data = input('post.');

            $validate = new Validate([

                'name|姓名'=>'require',
                'id_num|银行卡号'=>'require',
                'withdrawal_type|提现银行'=>'require',
            ]);
            if (!$validate->check($data)){
                $this->error($validate->getError());
                die;
            }

            $with_type = ['1001'=>'中国工商银行','1002'=>'中国农业银行','1003'=>'中国银行','1004'=>'中国建设银行','1005'=>'交通银行','1006'=>'中信银行','1004'=>'中国光大银行','1009'=>'中国民生银行','1010'=>'广发银行','1011'=>'平安银行','1012'=>'招商银行'];
            $data['bank_no'] = array_search($data['withdrawal_type'],$with_type);


            $data['user_name'] = $data['name'];
            $data['uid']  =$this->login_user['id'];
            $id = $data['user_account_id'];
            unset($data['name']);
            unset($data['user_account_id']);


            if ($id==0) {
                $bool = Db::table('user_account')->insert($data);
                if ($bool) {
                    $this->success('添加成功');
                }
            }else{
                $bool = Db::table('user_account')
                    ->where('id',$id)->update($data);
                if ($bool) {
                    $this->success('修改成功');
                }
            }



            die;
        }



        $uid = $this->login_user['id'];
        $user_account_data = Db::table('user_account')->where('uid',$uid)->select();
        $html_data = [
            'user_account_data'=>$user_account_data,
        ];
        return $this->fetch('add_user_account',$html_data);
    }

    public function delete_user_acc(){

        $id = input('get.id');

        return Db::table('user_account')->where('uid',$this->login_user['id'])->where('id',$id)->delete();
    }
}