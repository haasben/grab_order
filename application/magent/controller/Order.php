<?php


namespace app\magent\controller;


use think\Db;

class Order extends Common
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    // 订单列表
    public function index()
    {
        $id = $this->login_user['id'];
        // 分页第几页 默认第1
        $page = input('param.page/d', 1);
        // 查询自己的订单
        $data['proxy_id'] = $id;
        // 通过订单状态查看 默认2
        input('param.pay_status/d') ? $data['pay_status'] = input('param.pay_status/d', 1) : '';
        // 通过回调状态查看 默认0
        input('param.notify_status/d') ? $data['notify_url_info'] = input('param.notify_status/d', 0) : '';
        // 通过支付方式查看 默认2
      	input('param.type/d') ? $data['type'] = input('param.type/d') : '';
      	$group = Db::name('group')
            ->alias('g')
            ->field('g.type,ct.channel_name')
            ->join('channel_type ct', 'ct.code = g.type')
            ->where('uid', $id)
            ->order('g.type desc')
            ->group('g.type')
            ->select();
        // 订单列表
        $list = Db::name('mch_order')->where($data)
            ->field('id,order_num,pay_time,accept_time,pay_type,type,pay_amount,pay_status,notice_num,notify_url_info,ext,order_type')
            ->limit(5)->page($page)->order('id', 'desc')->select();
        foreach ($list as $item => $value) {
            $list[$item]['pay_time'] = $value['pay_time'] ? date('Y.m.d H:i:s', $value['pay_time']) : '';
            $list[$item]['accept_time'] = date('Y.m.d H:i:s', $value['accept_time']);
            $list[$item]['pay_type'] = Db::table('top_account_assets')->where('id', $value['pay_type'])->value('name');
          	$list[$item]['code'] = $value['type'];
            $list[$item]['type'] = Db::table('channel_type')->where('id', $value['type'])->value('channel_name');
            $list[$item]['pay_amount'] = number_format($value['pay_amount'] / 100, 2);
        }
        $data['page'] = $page;
        if (request()->isPost()) {
            $data['list'] = $list;
            return $data;
        }
        $this->assign('list', $list);
        $this->assign('data', $data);
      	$this->assign('group', $group);

        return $this->fetch('order');
    }
}