<?php
namespace app\partner\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
use think\Cache;
class Bill extends Common{


    //用户抢单
    public function grab_order(){

        if(request()->isAjax()){

            $data = input();
  
            if(!isset($data['type']) || !isset($data['id'])){
                return ['code'=>'1111','msg'=>'请求错误']; 
            }
            $uid = $this->login_user['id'];
          	
          	 $bans = Db::table('users')->where('id',$uid)->limit(1)->value('bans_time');

            if($bans >time()){
                return ['code'=>'1111','msg'=>'你的账号违规，被封禁到'.date('Y-m-d H:i:s',$bans)]; 
            }
          	
          	
          	//if($margin < 10000){
              //	return ['code'=>'1111','msg'=>'保证金余额小于100元']; 
           // }
          
           // cache('have_order_'.$uid,null);die;
            if(cache('have_order_'.$uid)){
                return ['code'=>'1111','msg'=>'3分钟内只能匹配一个订单'];die; 
            }

            $is_confirm = Db::table('mch_order')->where('proxy_id',$uid)->where('is_confirm',0)->where('order_type',1)->limit(1)->find();
          	
            if(!empty($is_confirm)){
                return ['code'=>'1111','msg'=>'您订单编号为'.$is_confirm['id'].'的订单为完成操作，请完成后再试'];die; 
            }


            if($data['type'] == 1){
                $channel = 'wechat';
            }else{
                $channel = 'alipay';
            }

            $margin = Db::table('assets')->where('uid',$uid)->limit(1)->value('margin');

            //$redis_data = [$uid=>$data['id'].'_'.$margin];
            if(!cache('have_order_'.$uid)){
              	
              	//Cache::store('redis')->set($channel.'_channel_id',$redis_data,1);
              
                $this->room_result($uid,$data['id'],$margin,$channel.'_channel_id_'.date('s'));
            }
            

            return ['code'=>'0000','msg'=>'系统智能匹配抢单中']; 


        }else{
            $channelModel = Db::name('channel_type');
            $channel = $channelModel->where('status',1)->where('id','in',[1,2])->select();

            $this->assign('channel',$channel);

            return $this->fetch();
        }





    }


    public function is_confirm($id){


        if(request()->isAjax()){

            $bool = Db::table('mch_order')->where('id',$id)->update(['is_confirm'=>1]);
            if($bool){
                return ['code'=>'0000','msg'=>'确认成功']; 
            }else{
                return ['code'=>'1111','msg'=>'确认失败，请稍后再试']; 
            }

        }


    }


//获取抢到的订单
    public function get_order(){

        // if(request()->isAjax()){
            $uid = $this->login_user['id'];
            $end_time = time();

            $bengin_time = $end_time-10;

            $order_data = Db::table('mch_order')
                ->field('id,pay_amount,pay_status')
                ->where('proxy_id',$uid)
                ->whereTime('accept_time','between',[$bengin_time,$end_time])
                ->limit(1)
                ->find();
            // dump($order_data);die;
            if(empty($order_data)){
                return ['code'=>'1111']; 
            }else{
                return ['code'=>'0000','msg'=>'抢单成功','data'=>$order_data]; 
            }


        // }


    }

    public function formcancel(){

        if(request()->isAjax()){

            $data = input();
             if(!isset($data['id'])){
                return ['code'=>'1111','msg'=>'请求错误']; 
            }

            //sleep(1);
            //  //cache('channel_id',null);die;
            // $this->room_result($data['id'],'channel_id');

            return ['code'=>'0000','msg'=>'匹配取消成功']; 

        }

    }



    //缓存用户提交的数据
/**
*@param $data 需要储存的数组
*@param $param 键名
*/
public function room_result($uid,$top_id,$margin,$param){

  	$result_data = cache::get($param);
	
    if($result_data){
       
            $result_data[$uid] = $top_id.'_'.$margin;
      	
            Cache::store('redis')->set($param,$result_data,1);
            
        }else{
            Cache::store('redis')->set($param,[$uid=>$top_id.'_'.$margin],1);
            
        }
        $data = cache::get($param);
        //return $data;
}
public function alipay_channel_id(){

	dump(cache('alipay_channel_id_'.date('s')));
  	dump(cache('wechat_channel_id_'.date('s')));
}
  
  

    public function channel_child_data(){

        if(request()->isAjax()){
            $channelModel = Db::name('channel_type');
            $id = input('id');
            $uid = $this->login_user['id'];

            $child_data = $channelModel
                ->alias('c')
                ->field('ta.id as taid,ta.uid,ta.name,ta.recharge_sum,c.id,c.channel_name,tca.receive_account')
                ->join('top_account_assets ta','ta.type = c.id')
                ->join('top_child_account tca','tca.pid = ta.id')
                ->where('c.status',1)
                 ->where('ta.uid',$uid)
                 ->where('ta.type',$id)
                 ->where('ta.is_show',1)
                ->select();

            if(empty($child_data)){

                $channel_name = $channelModel->where('id',$id)->value('channel_name');

                return ['code'=>'1111','msg'=>'请先添加'.$channel_name.'收款账号'];

            }else{
                return ['code'=>'0000','msg'=>'获取成功','data'=>$child_data];
            }
            


        }


    }









    //收单列表
    public function order_list(){

        $uid = $this->login_user['id'];
        
        //收款账号ID
        $account_id = Db::table('top_account_assets')->field('id')->where('uid',$uid)->select();
        $account_id = array_column($account_id, 'id');
       
        $order_num = input('get.order_num');
        //商户单号

        $id = input('get.id');
        //机构订单号
        if ($order_num||$id){
            $where1 = 1;
            $where2 = 1;
            $where3 = 1;
            $where4 = 1;
            $where7 = 1;

            if ($order_num) {
                $where5['m.order_num'] = $order_num;
                $where6 = 1;
            }else{
                $where5 = 1;

                $id_arr = explode('_',$id);
                if (!isset($id_arr[1])) {
                    $where6['m.id'] = $id;
                }else{
                    $where6['m.id'] = $id_arr[1];
                }
            }

            
        }else{
            $where = $this->sql_where();
            $where1 = $where['where1'];
            $where2 = $where['where2'];
            $where3 = $where['where3'];
            $where4 = $where['where4'];
            $where7 = $where['where7'];
            $where5 = 1;
            $where6 = 1;
        }

        $order_data = Db::table('mch_order')
            ->alias('m')
             ->field('ta.code as show_name,m.*')
            ->join('channel_type ta','ta.id=m.type')
           // ->field('ta.show_name,m.*')
           // ->join('top_account_assets ta','ta.id=m.pay_type')
            ->where($where6)
            ->where($where5)
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where7)
            ->where('pay_type','in',$account_id)
            ->order('id desc')
            ->paginate(14,false,[
                'query' => request()->param()
                ]);


        $sum_amount = Db::table('mch_order')
            ->alias('m')
            ->where($where6)
            ->where($where5)
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where7)
            ->where('pay_type','in',$account_id)
            ->sum('pay_amount');

        $list_number = Db::table('mch_order')
            ->alias('m')
            ->where($where6)
            ->where($where5)
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where7)
            ->where('pay_type','in',$account_id)
            ->count();

        $get_select_data = [
            //传入input框默认参数
            'pay_status'=>input('get.pay_status'),
            'notify_url_info'=>input('get.notify_url_info'),
            'order_type'=>input('get.order_type'),
        ];

        if (input('get.excel')) {

            $excel_order_data = Db::table('mch_order')
                ->alias('m')
                ->field('ta.show_name,m.*,u.merchant_cname')
                ->join('users u','m.uid=u.id')
                ->join('top_account_assets ta','ta.id=m.pay_type')
                ->where($where6)
                ->where($where5)
                ->where($where1)
                ->where($where2)
                ->where($where3)
                ->where($where4)
                ->where($where7)
                ->where('pay_type','in',$account_id)
                ->order('id desc')
                ->select();
            $this->excel($excel_order_data);

        }

        $get_srt = '';

        foreach (input('get.') as $key => $value) {
            $get_srt .= $key.'='.$value.'&';
        }

        $html_data = [
            'order_data'=>$order_data,
            'sum_amount'=>$sum_amount,
            'get_select_data'=>$get_select_data,
            'get_srt'=>$get_srt,
            'list_number'=>$list_number,
        ];
        $this->assign($html_data);
      	
      	if(isMobilePhone()){
        	$path = 'order_list_phone';
        }else{
        	$path = 'order_list';
        }
      	
      
        return $this->fetch($path);
    }

        public function sql_where(){
        //拼接where语句
        
        $start_time = input('get.start_time');
        //获取开始时间与结束时间
        $end_time = input('get.end_time');

        if ($start_time&&$end_time) {
            $where1['m.accept_time'] = ['>',strtotime($start_time)];
            $where2['m.accept_time'] = ['<',strtotime($end_time)];
        }elseif($start_time){
            $where1['m.accept_time'] = ['>',strtotime($start_time)];
            $where2 = 1;
        }elseif($end_time){
            $where1 = 1;
            $where2['m.accept_time'] = ['<',strtotime($end_time)];
        }else{
            $where1 = 1;
            $where2 = 1;
        }

        $pay_status = input('get.pay_status');
        //订单状态
        if ($pay_status==='0') {
            $where3 = 1;
        }elseif ($pay_status==1) {
            $where3['m.pay_status'] = 1;
        }elseif ($pay_status==2) {
            $where3['m.pay_status'] = 2;
        }else{
            $where3 = '';
        }


        $notify_url_info = input('get.notify_url_info');
        //回调状态
        if (input('get.order_off')==1) {
            $where4['m.notify_url_info'] = ['<>',1];
        }elseif (is_numeric($notify_url_info)) {
            $where4['m.notify_url_info'] = $notify_url_info;
        }else{
            $where4 = 1;
        }

        $order_type = input('get.order_type');

        if ($order_type==='0') {
            $where7 = 1;
        }elseif($order_type==1){
            $where7['order_type'] = 1;
        }elseif($order_type==2){
            $where7['order_type'] = 2;
        }else{
            $where7['order_type'] = 1;
        }

        $where['where1'] = $where1;
        $where['where2'] = $where2;
        $where['where3'] = $where3;
        $where['where4'] = $where4;
        $where['where7'] = $where7;
        return $where;
    }
        public function excel($order_data){
        $excel_order_data = array();
        foreach ($order_data as $key => $value) {

            $arr = array();

            $arr['id'] = $value['merchant_cname'].'_'.$value['id'];


            $arr['order_num'] = $value['order_num'];

            $arr['order_type'] = $value['order_type']==1?'入金':'出金';

            $arr['pay_status'] = $value['pay_status']==1?'已支付':'未支付';

            $arr['accept_time'] = $value['accept_time']!=0?date('Y-m-d H:i:s',$value['accept_time']):0;

            $arr['pay_time'] = $value['pay_time']!=0?date('Y-m-d H:i:s',$value['pay_time']):0;

            $arr['this_fee'] = $value['this_fee']/100;

            $arr['pay_amount'] = $value['pay_amount']/100;

            $arr['this_money'] = $value['this_money']/100;

            if ($value['pay_time']==0||$value['order_num']=='提现') {
                continue;
            }else{
                if ($value['order_type']==1) {
                    $arr['real_money'] = ($value['pay_amount']-$value['this_fee'])/100;
                }else{
                    $arr['real_money'] = -$value['pay_amount']/100;
                }
            }
            

            

            $arr['ext'] = $value['ext'];

            $excel_order_data[] = $arr;
        }

        $filename = '订单记录'.date('YmdHis',time());
        $header = array('平台订单号','商户订单号','入金/出金','支付状态','订单时间','付款时间','手续费','订单金额','实际到账','余额','订单备注');
        $index = array('id','order_num','pay_status','order_type','accept_time','pay_time','this_fee','pay_amount','real_money','this_money','ext');

        $excel_order_data = array_reverse($excel_order_data);

        createtable($excel_order_data,$filename,$header,$index);

        die;
    }














    //收款账号列表
    public function account(){
	
        $uid = $this->login_user['id'];

        $account = Db::table('top_account_assets')
            ->alias('ta')
            ->field('ta.*,ct.channel_name,ct.code,tca.receive_account')
            ->join('channel_type ct','ct.id = ta.type')
            ->join('top_child_account tca','tca.pid = ta.id')
            ->where('ta.uid',$uid)
            ->where('ta.is_show',1)
            ->paginate(10);
     
      


        $this->assign('account',$account);
        
		if(isMobilePhone()){
        	$path = 'account_phone';
        }else{
        	$path = 'account';
        }
      	//dump($path);die;
        return $this->fetch($path);
    }




//添加收款账号
    public function add_account(){

        if(request()->isAjax()){

        $data = input();

        $validate = [
            'receive_account|实名姓名'  => 'require',
            'name|账户昵称'   => 'require',
            
        ];

        $add_data['sql_name'] = 'wechat';
        $mch_id = '';
        if($data['type'] == 2){
            $validate['mch_id|账户ID'] = 'require|number|length:16';
            $add_data['sql_name'] = 'alipay';
            $mch_id = $data['mch_id'];
        }

        $result = $this->validate($data,$validate);
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['code'=>20001,'msg'=>$result];die;
        } 
        $uid = $this->login_user['id'];
        $key = Db::table('users')->where('id',$uid)->value('key');

        $add_data['uid'] = $uid;
        $add_data['name'] = $data['name'];
        $add_data['type'] = $data['type'];
        $add_data['app_id'] = 'Pcode_cloudesc_Bind_'.substr(md5($key), 0,8);


        Db::startTrans();
        $id = Db::table('top_account_assets')->insertGetId($add_data);

        if($id){
            $fee = Db::table('user_fee')->where('uid',$uid)->where('taid',$data['type'])->value('fee');
            $bool = Db::table('top_child_account')->insert([
                'pid'=>$id,
                'mch_id'=>$mch_id,
                'receive_account'=>$data['receive_account'],
                'fee'=>$fee,
            ]);
            if($bool){
                Db::commit();
                return ['code'=>'0000','msg'=>'添加成功'];die;
            }

        }

        Db::rollback();
        return ['code'=>20001,'msg'=>'添加失败，请稍后再试'];die;
        


        }else{

            return $this->fetch();

        }



        


    }





//软删除收款账号
    public function dell_act(){

        if(request()->isAjax()){

            $id = input('id');

            $bool = Db::name('top_account_assets')->where('id',$id)->update(['is_show'=>0]);

            if($bool){
                return ['code'=>'0000','msg'=>'删除成功'];
            }else{
                return ['code'=>'1111','msg'=>'删除失败，请稍后再试'];
            }
            

        }


    }

}