<?php
namespace app\sadmin\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Valcodeate;
use think\Cache;
class Channel extends UserCommon{

//通道列表
    public function index(){



        $channel_data = Db::table('channel_type')->select();
        $this->assign('channel_data',$channel_data);    

        return $this->fetch(); 
    }


//通道分析
    public function analysis(){

        $channel_data = Db::table('channel_type')->select();

        // dump($channel);die;

        $mch_orderModel = Db::name('mch_order');

        foreach ($channel_data as $k => $v) {
            
            //交易金额
            $channel_data[$k]['sum_amount'] = $mch_orderModel
            ->where('type',$v['code'])
            ->where('order_type',1)
            ->sum('pay_amount');
            //手续费
            $channel_data[$k]['sum_fee'] = $mch_orderModel
            ->where('type',$v['code'])
            ->sum('this_fee');
            //入金总额
            $channel_data[$k]['succ_sum_amount'] = $mch_orderModel
            ->where('type',$v['code'])
            ->where('pay_status',1)
            ->where('order_type',1)
            ->sum('pay_amount');
            //交易笔数
            $channel_data[$k]['count'] = $mch_orderModel
            ->where('type',$v['code'])
            ->where('order_type',1)
            ->count();
            //成功笔数
            $channel_data[$k]['succ_count'] = $mch_orderModel
            ->where('type',$v['code'])
             ->where('pay_status',1)
            ->where('order_type',1)
            ->count();

        }
        $this->assign('channel_data',$channel_data);
        // dump($channel_data);

        return $this->fetch();


    }

//修改通道状态
    public function edit_status(){


        $data = input();
  
        if($data['status'] == 'true'){
            $status = 1;
            $msg = ' 开通 成功';
        }else{
            $status = 0;
            $msg = ' 关闭 成功';
        }

        $bool = Db::table('channel_type')->where('id',$data['id'])->update(['status'=>$status]);

        if($bool){
            $data = ['code'=>'0000','msg'=>'通道'.$msg];

        }else{
            $data = ['code'=>'1111','msg'=>'通道'.$msg.'，请稍后再试'];
        }

        return $data;
    }

//修改通道信息
    public function edit_channel($id){

        if(request()->isPost()){

            $data = input();
            $bool = Db::table('channel_type')->update($data);
            if($bool){
                $data = ['code'=>'0000','msg'=>'修改成功'];

            }else{
                $data = ['code'=>'1111','msg'=>'没有修改任何数据'];
            }
            return $data;
        }else{

            $channel_data = Db::table('channel_type')->where('id',$id)->limit(1)->find();
            $this->assign('channel_data',$channel_data);
            return $this->fetch();
        }


    }

  
}   