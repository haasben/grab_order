<?php
namespace app\nagent\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Valcodeate;
use think\Cache;
class Channel extends UserCommon{

//通道列表
    public function index(){

        $uid = input('uid');

        if(empty($uid)){
            $uid = $this->login_user['id'];
        }

        $this->assign('uid',$uid);
        $channel_data = Db::table('channel_type')
            ->alias('ct')
            ->field('ct.channel_name,ct.show_name,uf.status,uf.taid,uf.fee')
            ->join('user_fee uf','uf.taid=ct.code')
            ->where('uf.uid',$uid)
          	->order('ct.order')
            ->paginate(12,false,[
                'query' => request()->param()
                ]);
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

        if(!request()->isAjax()){
            echo '干啥呢';die;
        }
        $data = input();
        if($data['status'] == 'true'){
            $status = 1;
            $msg = ' 开通 成功';
        }else{
            $status = 2;
            $msg = ' 关闭 成功';
        }
        $uid = input('uid');
        
        if(empty($uid)){
            $uid = $this->login_user['id'];
        }
        $bool = Db::table('user_fee')
            ->where('uid',$uid)
            ->where('taid',$data['id'])
            ->update(['status'=>$status]);

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
  	
  
  
//超时回调信息
    public function drop_order(){

        $drop_list = Db::table('drop_order')
            ->alias('d')
            ->field('ta.name,d.*')
            ->join('top_account_assets ta','ta.app_id = d.app_id AND ta.type = d.type')
            // ->where('d.status',0)
            ->order('d.id desc')
            ->paginate(12,false,[
                'query' => request()->param()
                ]);

        $this->assign('drop_list',$drop_list);
        return $this->fetch();

    }

//修改状态
    public function edit_drop_status($id){


        $bool = Db::table('drop_order')->where('id',$id)->update(['status'=>1]);
        if($bool){
                $data = ['code'=>'0000','msg'=>'修改状态成功'];

        }else{
            $data = ['code'=>'1111','msg'=>'修改状态失败，请稍后再试'];
        }
        return $data;


        
    }
  //添加通道
    public function add_channel(){

        if(request()->isAjax()){

            $data = input();
        
            $result = $this->validate($data,
            [
                'channel_name|通道名称'  => 'require',
                'show_name|展示给下游的名称'    => 'require',
                'code|通道编码'=>'require',
                'fee|成本费率'=>'<:1',
                'mch_id|商户号'=>'require',
                'private_key|商户私钥'=>'require',
            ]);
        
        //验证判断必填项
            if(true !== $result){
                // 验证失败 输出错误信息
                return ['code'=>20001,'msg'=>$result];exit;
            }
            $channel_add_data = [
                'code'=>$data['code'],
                'channel_name'=>$data['channel_name'],
                'show_name'=>$data['show_name'],
                'fee'=>$data['fee'],
                'status'=>0

            ];

            $account_assets_data = [
                'sql_name'=>$data['show_name'],
                'name'=>$data['channel_name'],
                'show_name'=>$data['show_name'],
                'type'=>$data['code']

            ];
            
            $bool = Db::table('channel_type')->where('code',$data['code'])->limit(1)->value('id');
            if($bool){
                return ['code'=>20001,'msg'=>'通道编码 '.$bool.' 已存在'];exit;
            }

            Db::startTrans();
            $bool1 = Db::table('channel_type')->insert($channel_add_data);
            $bool2 = Db::table('top_account_assets')->insertGetId($account_assets_data);
            if($bool2){
                $child_account_data = [
                    'pid'=>$bool2,
                    'private_key'=>base64_encode(base64_encode($data['private_key'])),
                    'mch_id'=>$data['mch_id'],
                    'fee'=>$data['fee']

                ];

                if(!empty($data['public_key'])){

                    $child_account_data['public_key'] = base64_encode(base64_encode($data['public_key']));

                }
                $bool3 = Db::table('top_child_account')->insert($child_account_data);
                if($bool3&&$bool2&&$bool1){

                    Db::commit();
                    return ['code'=>'0000','msg'=>'添加成功'];exit;

                }
            }
            Db::rollback();
            return ['code'=>'1111','msg'=>'添加失败，请稍后再试'];exit;

        }else{

            return $this->fetch();

        }

    }

  
}   