<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;
class Widthdrawal extends CommonWithdrawal{
    public $taid;
    public $top_data;

   
//商户自己的代付通道
    public function mch_withdrawal(){
       
        $get_data = $this->get_data;
        if ($get_data['pay_amount']<1000||$get_data['pay_amount']>5000000) {
             $ret['code'] = '10001';
             $ret['info'] = '金额限制（10-50000）元';
             echo json_encode($ret,JSON_UNESCAPED_UNICODE);
             exit;
         }

       	$type = Db::table('user_fee')->order('money desc')->limit(1)->value('taid');

        $account_this_fee = $get_data['pay_amount']/1000;
        $fee = $account_this_fee<10?10:ceil($account_this_fee);
        $fee = 300;
        //当前代付手续费(分为单位)
        
        $this->available_money($fee);

        $ret_data = $this->mch_add_withdrawal_order($type,$fee);
        
        if($ret_data){
          	    Db::commit();
          	 	$order_data = Db::table('mch_order')->field('uid,order_num,pay_amount,pay_time,notify_url,pay_status,trade_no,this_fee')->where('id',$ret_data['order_id'])->find();
                $key_data  = Db::table('users')->field('id,merchant_cname,key,email')->where('id',$order_data['uid'])->find();

                $key = encryption($key_data['key'].$key_data['merchant_cname'].$key_data['id']).base64_encode($key_data['email']);
              
              	//收款账户当时余额
              	$this_money = Db::name('assets')->where('uid',$order_data['uid'])->value('money');
              
                $return = [
                  	
                  	'code'=>'0000',

                    'info'=>'提交成功',
                  	
                    'mch_id'=>$get_data['mch_id'],

                    'order_num'=>$order_data['order_num'],
                    //下级商户订单号

                    'pay_status'=>'success',
                  
                  	'this_money'=>$this_money,
                  	
                  	//代付金额
                     'pay_amount'=>$order_data['pay_amount'],
                  	
                  	//手续费
                     'fee'=>$order_data['this_fee'],
                ];
                $return['sign'] = $this->get_sign_mch($return,$key);

                echo json_encode($return,JSON_UNESCAPED_UNICODE);die;
          	
            
        }else{
              Db::rollback();
              $ret['code'] = '1111';
              $ret['info'] = $data['msg'];

          }

          echo json_encode($ret,JSON_UNESCAPED_UNICODE);
          exit;    

    }
  
  
  public function curlPost($url, $postFields){
    $postFields = http_build_query($postFields);
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_POST, 1 );
    curl_setopt ( $ch, CURLOPT_HEADER, 0 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );
    $result = curl_exec ( $ch );
    curl_close ( $ch );
    return $result;
}


    //商户自己的加密方式  
    public function get_sign_mch($data, $key){
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
  
  
  
      public function curl_post($url = '', $post_data = array())
      {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            //以文件流的形式返回，而不直接输出
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            $output = curl_exec($ch);
            curl_close($ch);
            //打印获得的数据
            return $output;

      }

  
  
  
//查询资金是否充足
    public function available_money($fee){
        //判断资金是否充足
        $get_data = $this->get_data;

        //当前时间

        $uid = explode('_', $get_data['mch_id'])[1];

        //该用户当前余额
        $user_amount = Db::name('assets')->where('uid',$uid)->value('money');

        $user_freeze_amount = Db::table('user_fee')
            ->field('freeze_amount')
            ->where('uid',$uid)
            ->select();
        $freeze_amount = 0;
        foreach ($user_freeze_amount as $k => $v) {
            $freeze_amount +=$v['freeze_amount'];
        }
        
        //该用户当前通道上游冻结金额
     
        $sum_deductions = $freeze_amount+$fee;
        //该用户当前通道上游冻结金额
        
        //总扣款
        if ($user_amount-$sum_deductions<0) {
            $ret['code'] = '80008';
            $ret['info'] = '可用余额不足';
            echo json_encode($ret,JSON_UNESCAPED_UNICODE);
            exit;
        }

    }
  
}