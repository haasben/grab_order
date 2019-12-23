<?php
namespace app\sadmin\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Valcodeate;
use think\Cache;
class PointOrder extends UserCommon{

//指向派单
    public function index(){

        //查询所有通道列表进行指向派单

        $channel_list = Db::table('top_account_assets')
            ->alias('ta')
            ->field('ta.name,ta.uid,ta.id')
            ->join('users u','u.id=ta.uid')
            ->where('u.id','<>',1)
            ->select();

        $this->assign('channel_list',$channel_list);

        return $this->fetch(); 
    }

    public function order(){


        if(request()->isAjax()){

            $data = input();

            $tcid = Db::table('top_child_account')->where('pid',$data['pay_type'])->limit(1)->value('id');
            $top_data = Db::table('top_account_assets')->where('id',$data['pay_type'])->limit(1)->find();
            $add_data = [
            'uid'=>2,
            //用户id
            
            'order_num'=>'zdbd_'.date('YmdHis').mt_rand(1000,9999).mt_rand(1,100),
            //下级商户订单号
            
            'pay_amount'=>$data['pay_money']*100,
            //订单金额

            'notify_url'=>'http://all.jvapi.com/index/index/callback_url',
            //异步回调地址

            'return_url'=>'',
            //同步跳转地址

            'ext'=>'掉单补发空单',
            //扩展信息，备注

            'pay_type'=>$data['pay_type'],
            //支付方式，上级接口

            'tcid'=>$tcid,
            //支付方式，具体子账户

            'pay_status'=>2,
            //支付状态，默认为2未支付

            'accept_time'=>time(),
            //订单生成时间

            'order_type'=>1,
            //订单类型：充值
            'type'=>$top_data['type'],

            'ip'=>getRealIp(),
            //用户真实IP
            'app_id'=>$top_data['app_id'],

            'proxy_id'=>$top_data['uid'],
        ];

        }
        $result_id = Db::table('mch_order')->insertGetId($add_data);
        if ($result_id) {

            $ret['code'] = '0000';
            $ret['msg'] = '派单成功,平台单号：'.$result_id;
            return $ret;

        }else{
            $ret['code'] = '10009';
            $ret['msg'] = '读取数据失败，请重试';
            return $ret;
            exit;
        }
    }


  
}   