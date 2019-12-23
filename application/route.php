<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

    'm'=>'mobile/index/index',
    'cash_reg'=>'api/recharge/cash_reg',
    'order_status'=>'api/query_order/order_status',
    'tcard'=>'api/recharge/tcard',
  	'station'=>'api/recharge/station',
  	'cash_index'=>'api/Cash/cash_index',
 	  'alipay_cash'=>'api/Cash/alipay_cash',
  	'demo/:id/:type' => 'api/demo/index',
  	'nongxin_order/:order_id' => 'api/cash/nongxin_order',
  	'chat/:orderId' => 'magent/chat/index',



];
