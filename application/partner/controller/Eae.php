<?php
namespace app\partner\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
class Eae extends Common{
    public function index(){

        return $this->fetch('index');
    }

    public function eae_list(){

            $id = input('get.id');

        if ($id) {
            $where1 = 1;
            $where2 = 1;
            $where3 = 1;
            $where4['w.id'] = $id;
        }else{
            $where = $this->sql_where();
            $where1 = $where['where1'];
            $where2 = $where['where2'];
            $where3 = $where['where3'];
            $where4 = 1;
        }

    	$order_data = Db::table('withdrawal')
            ->alias('w')
            ->field('w.*,u.merchant_cname,t.channel_name as show_name')
            ->join('users u','u.id=w.uid')
            ->join('channel_type t','w.pay_type=t.id','left')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where('uid',$this->login_user['id'])
            ->order('id desc')
            ->paginate(15,false,[
            	'query' => request()->param()
            	]);


        if (input('get.excel')) {

            $excel_order_data = Db::table('withdrawal')
            ->alias('w')
            ->field('w.*,u.merchant_cname,t.channel_name as show_name')
            ->join('users u','u.id=w.uid')
            ->join('channel_type t','w.pay_type=t.id','left')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where('uid',$this->login_user['id'])
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
            'get_srt'=>$get_srt,
    	];
      	
         //dump($order_data);die;
    	return $this->fetch('eae_list',$html_data);
    }

    protected function sql_where(){
        $start_time = input('get.start_time');
        //获取开始时间与结束时间
        $end_time = input('get.end_time');

        if ($start_time&&$end_time) {
            $where1['w.add_time'] = ['>',strtotime($start_time)];
            $where2['w.add_time'] = ['<',strtotime($end_time)];
        }else{
            $where1 = 1;
            $where2 = 1;
        }

        $status = input('get.status');

        if ($status) {
            $where3['w.status'] = $status;
        }else{
            $where3 = 1;
        }

        $where['where1'] = $where1;
        $where['where2'] = $where2;
        $where['where3'] = $where3;
        return $where;

    }
    public function excel($order_data){

        $excel_order_data = array();
        foreach ($order_data as $key => $value) {

            $arr = array();

            $arr['mch_id'] = $value['merchant_cname'].'_'.$value['uid'];


            if ($value['status']==1) {
                $arr['status'] = '已完成';
            }elseif ($value['status']==2) {
                $arr['status'] = '已提交';
            }elseif ($value['status']==3) {
                $arr['status'] = '已作废';
            }

            $arr['id'] = $value['id'];

            $arr['order_num'] = $value['order_num'];

            $arr['name'] = $value['name'];

            $arr['pay_type'] = $value['show_name'].' '.$value['withdrawal_type'];

            $arr['id_num'] = $value['id_num'];

            $arr['add_time'] = $value['add_time']!=0?date('Y-m-d H:i:s',$value['add_time']):0;

            $arr['w_amount'] = $value['w_amount']/100;

            $arr['fee'] = $value['fee']/100;

            $arr['this_money'] = $value['this_money']/100;

            $arr['ext'] = $value['ext'];

            $excel_order_data[] = $arr;
        }

        $filename = '订单记录'.date('YmdHis',time());
        $header = array('商户编号','状态','提现订单号','商户订单号','收款人姓名','出金账户','收款账户号','提交时间','代付金额','手续费','余额','备注');
        $index = array('mch_id','status','id','order_num','name','pay_type','id_num','add_time','w_amount','fee','this_money','ext');

        $excel_order_data = array_reverse($excel_order_data);

        createtable($excel_order_data,$filename,$header,$index);

        die;
    }
}