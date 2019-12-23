<?php
namespace app\nagent\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Valcodeate;
use think\Cache;
class DailyData extends UserCommon{

//通道列表
    public function index(){

        $code = input('code');
        $where1 = '';
        if($code){
            $where1['d.type'] = $code;
        }

        $uid = $this->login_user['id'];
        //日切数据
        $daily_data = Db::table('daily_data')
            ->alias('d')
            ->field('d.*,c.channel_name')
            ->join('channel_type c','c.code=d.type')
            ->where('uid',$uid)
            ->where($where1)
            ->order('date desc')
            ->paginate(15,false,[
                'query' => request()->param()
                ]);

        $this->assign('daily_data',$daily_data);

        //用户通道类型

        $channel = Db::table('user_fee')
            ->alias('uf')
            ->field('c.channel_name,c.code')
            ->join('channel_type c','c.code=uf.taid')
            ->where('uid',$uid)
            ->select();
        $this->assign('channel',$channel);

        return $this->fetch(); 
    }
  
      public function get_excel($id){

      $daily_data = Db::table('daily_data')->where('id',$id)->limit(1)->find();
      if($daily_data['uid'] != $this->login_user['id']){
          echo '非法操作';die;
      }
        $top_account_assets = Db::table('top_account_assets')
            ->alias('ta')
            ->field('ta.name,ct.channel_name,ta.id')
            ->join('channel_type ct','ct.code=ta.type')
            ->where('uid',$this->login_user['id'])
            ->where('type',$daily_data['type'])
            ->select();
        $mchOrderModel = Db::name('mch_order');
        foreach ($top_account_assets as $k => $v) {
            $top_account_assets[$k]['sum_succ_order'] = $mchOrderModel
                ->whereTime('accept_time','between',[$daily_data['date'].' 00:00:00',$daily_data['date'].'23:59:59'])
                ->where('pay_type',$v['id'])
                ->where('order_type',1)
                ->where('pay_status',1)
                ->count();
            $top_account_assets[$k]['sum_order'] = $mchOrderModel
                ->whereTime('accept_time','between',[$daily_data['date'].' 00:00:00',$daily_data['date'].'23:59:59'])
                ->where('pay_type',$v['id'])
                ->where('order_type',1)
                ->count();
            //派单金额
            $top_account_assets[$k]['sum_money'] = $mchOrderModel
                ->whereTime('accept_time','between',[$daily_data['date'].' 00:00:00',$daily_data['date'].'23:59:59'])
                ->where('pay_type',$v['id'])
                ->where('order_type',1)
                ->sum('pay_amount');
            //收单金额
            $top_account_assets[$k]['sum_succ_money'] = $mchOrderModel
                ->whereTime('accept_time','between',[$daily_data['date'].' 00:00:00',$daily_data['date'].'23:59:59'])
                ->where('pay_type',$v['id'])
                ->where('order_type',1)
                ->where('pay_status',1)
                ->sum('pay_amount');
            $top_account_assets[$k]['date'] = $daily_data['date'];

        }
        
        if(empty($top_account_assets)){

            echo '<script>alert("个人收款账户为空，没有数据哦");window.location.href="/nagent/daily_data/index"</script>';die;

        }
        $this->excel($top_account_assets);
        }


        public function excel($order_data){
        $excel_order_data = array();
        foreach ($order_data as $key => $value) {

            $arr = array();


            $arr['name'] = $value['name'];

            $arr['channel_name'] = $value['channel_name'];

            $arr['sum_succ_money'] = $value['sum_succ_money']/100;

            $arr['sum_money'] = $value['sum_money']/100;

            $arr['sum_succ_order'] = $value['sum_succ_order'];

            $arr['sum_order'] = $value['sum_order'];

            $arr['date'] = $value['date'];
            $date =$value['date'];
            $name = $value['channel_name']; 

            $excel_order_data[] = $arr;
        }

        $filename = $name.'账号收单记录表'.$date;
        $header = array('收款账户','通道名称','收款金额','派单金额','收单笔数','派单笔数','日期');
        $index = array('name','channel_name','sum_succ_money','sum_money','sum_succ_order','sum_order','date');

        $excel_order_data = array_reverse($excel_order_data);

        createtable($excel_order_data,$filename,$header,$index);

        die;
    }

}   