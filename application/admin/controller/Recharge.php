<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Model;
class Recharge extends UserCommon{
    public function index(){

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
          	 ->field('ta.show_code as show_name,m.*')
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
            ->where('uid',$this->login_user['id'])
            ->order('id desc')
            ->paginate(15,false,[
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
            ->where('uid',$this->login_user['id'])
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
            ->where('uid',$this->login_user['id'])
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
                ->join('channel_type ta','ta.id=m.type')
                ->where($where6)
                ->where($where5)
                ->where($where1)
                ->where($where2)
                ->where($where3)
                ->where($where4)
                ->where($where7)
                ->where('m.uid',$this->login_user['id'])
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
    
        return $this->fetch('index',$html_data);
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
            $where3['m.pay_status'] = 1;
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
    public function del_order(){
        if (!request()->isAjax()) {
            return $this->index();
        }
        $id = input('post.id');
        $data = Db::table('mch_order')->field('accept_time,uid,order_type')->where('id',$id)->where('pay_status',2)->find();
        
        if ($data['uid']!=$this->login_user['id']||time()-$data['accept_time']<36000||$data['order_type']==2) {
            return "不可删除";
        }

        $bool = Db::table('mch_order')->where('id',$id)->where('pay_status',2)->delete();
        if ($bool) {
            return "已删除";
        }else{
            return "删除失败";
        }
    }
}