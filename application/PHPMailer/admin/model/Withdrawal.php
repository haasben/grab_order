<?php
namespace app\admin\model;
use think\Model;
use think\Db;
use think\Cache;
class Withdrawal extends Model{
    public function index($post_data,$user_account_data,$sql_name){

        $data = Db::table('users')->where('id',$post_data['uid'])->find();

        $key = encryption($data['key'].$data['merchant_cname'].$data['id']);

        $mch_id = $data['merchant_cname'].'_'.$data['id'];

        $login_user = base64_encode($data['email']);
        
        $pay_amount = $post_data['w_amount']*100;

        return $this->yl_withdrawal($key,$login_user,$mch_id,$user_account_data,$pay_amount,$sql_name);
    }



    public function yl_withdrawal($key,$login_user,$mch_id,$user_account_data,$pay_amount,$sql_name){

        $login_user = $login_user;
        //base64编码后的登陆邮箱
        // 如：base64_encode('123456@qq.com')为MTIzNDU2QHFxLmNvbQ==；

        $key = $key;
        //商户key，API平台提供
        
        $key .= $login_user;

        $data = array(
            'mch_id'=>$mch_id,
            // Y,商户号，API平台提供
            
            'order_num'=>$mch_id.'_'.date('YmdHis'),
            //订单号

            'pay_amount'=>$pay_amount,
            // Y,提现金额 分为单位,最低10元
            
            'pay_type'=>$sql_name,
            // Y，网银支付

            'account_name'=>$user_account_data['user_name'],
            //持卡人姓名

           // 'cert_number'=>$user_account_data['name_id'],
            //证件号码

            'account_id'=>$user_account_data['id_num'],
            //银行卡号码

           // 'mobile'=>$user_account_data['phone_num'],
            //绑定手机号

           // 'bank_no'=>$user_account_data['bank_no'],
            //开户行号

           	'bank_name'=>$user_account_data['withdrawal_type'],
              //银行名称

             'kaihuhang'=>$user_account_data['address'],
          	//开户行

            'purpose'=>'提现',
            //付款目的

            'summary'=>'提现',
            //付款人付款摘要

            'notify_url'=>'',
            //异步通知地址
        );
        
        $data['sign'] = $this->get_sign_str($data,$key);
        //生成sign

        $url_str = $this->arrayToKeyValueString($data);
        //拼接url参数
        
        $location_href = THIS_URL.'/api/Withdrawal/index';
        //请求地址，API平台提供

        $location_href .= '?'.$url_str;

        return file_get_contents($location_href);

    }

    public function get_sign_str($data, $key){
        if(isset($data['sign'])) {
            unset($data['sign']);
        }
        ksort($data);
        $sign_str = '';
        foreach($data as $k => $v) {
            $sign_str .= $k . '='.$v.'&';
        }
        $sign_str = substr($sign_str,0,strlen($sign_str)-1);
        $sign_str = strtoupper(md5( $sign_str."&key=".$key));
        return $sign_str;
    }

    public function arrayToKeyValueString($data){
        $url_str = '';
        foreach($data as $key => $value) {
            $url_str .= $key .'=' . $value . '&';
        }
        $url_str = substr($url_str,0,strlen($url_str)-1);
        return $url_str;
    }
}