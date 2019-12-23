<?php
namespace app\push\controller;

use think\Controller;
use think\Db;
use think\Validate;
use app\index\model\Assets;
use GatewayClient\Gateway;
use think\Cache;

class Room extends Controller
{

    public function _initialize(){
       // if(session('user_data')){
            //Db::name('user')->where('uid',session('user_data')['uid'])->update(['login_time'=>time()]);
       // }
        
        Gateway::$registerAddress = '127.0.0.1:1238';

    }
    
    //计算体验场
    public function experience($result_data,$prople_num){
      
      
                $challengeModel = Db::name('challenge_record');
                $data = $result_data;
                $uid = '';
                $room_id = '';
                foreach ($data as $k => $v) {
                    $data[$k]['value'] = abs(1000-$v['result']);
                    $uid .= $v['uid'].',';
                    $room_id = $v['room_id'];

                }
                $uid = rtrim($uid,',');
                $user_id = $uid;

                //根据结果返回排序数组
                $arr = array_map(create_function('$n', 'return $n["value"];'), $data);
                array_multisort($arr, SORT_ASC, $data);
                //Db::name('ceshi')->insert(['info'=>json_encode($data)]);
                //实例化room表 assets表
                $room = Db::name('room');
                $assertModel = Db::name('assets');
                //房费
                $bet = $room->where('room_id',$room_id)->value('room_gold');
                
                //本局金币池
                $gold = $bet*$prople_num*0.9;

                //本局总费用
                $fee = $bet*$prople_num*0.1;


                //只有两个人开始游戏
                if($prople_num == 2){

                    if($data[0]['value'] != $data[1]['value']){

                        //两个人的结果不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/10,
                        ]);
                    
                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[1]['result'],
                            'is_win'=>0
                        ]);

                        //修改资产表
                        
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold),//加金币
                            'integral'=>Db::raw('integral+'.$gold/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                
                        ]);

                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }else{

                        //两个人相同结果
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
       
                        ]);


                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
               
                        ]);


                    }
                }elseif ($prople_num == 3) {
                    //房间为三个人对战时的结果
                    //三个人的结果不相等
                    if(($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value']) || ($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'])){

                        //三个人的结果不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/10,
    
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[1]['result'],
                            'is_win'=>0,

                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[2]['result'],
                            'is_win'=>0
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold),//加金币
                            'integral'=>Db::raw('integral+'.$gold/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
          
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'].','.$data[2]['uid'])->update([

                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] != $data[2]['value']){

                        //前两个人的结果相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
       
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
            
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[2]['result'],
                            'is_win'=>0,

                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
     
                        ]);

                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value']){

                        //三个人的结果相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
     
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
           
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
          
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold/3),//加金币
                            'integral'=>Db::raw('integral+'.$gold/3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
         
                        ]);


                    }
                    //房间为四人对战
                }elseif($prople_num == 4){
                    if($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value']){
                        //四个人的结果不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
           
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
  
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,

                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0,
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
      
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
        
                        ]);
                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.2),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
 
                        ]);
                        $assertModel->where('user_id',$data[3]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value']){
                        //一二结果相等 三四不等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.4/10,
          
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.4/10,
       
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
         
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0,
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.4),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.4/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
            
                        ]);

                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                
                        ]);
                        $assertModel->where('user_id',$data[3]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value']){
                        //二三结果相等 一四不等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
       
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
        
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
     
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0,
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
           
                        ]);
                        $assertModel->where('user_id','in',$data[1]['uid'].','.$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
              
                        ]);
                        $assertModel->where('user_id',$data[3]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value']){
                        //三四结果相等，一二不等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
              
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
               
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
             
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
            
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                    
                        ]);
                        $assertModel->where('user_id','in',$data[2]['uid'].','.$data[3]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.1),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
               
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value']){
                        //一二三结果相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                  

                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                 
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
            
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0,
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.33),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
              

                        ]);

                        $assertModel->where('user_id',$data[3]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value']){
                        //二三四结果相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                         
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,
                   

                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,
                 
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,
                  
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                         
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.16),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.15/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                          
                        ]);


                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value']){
                        //一二三四结果都相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                    
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                      
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
            
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
               
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                     
                        ]);
                       

                    }
                    //房间为五个人对战时的结果
                }elseif($prople_num == 5){
                    if(($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value']) || ($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] == $data[4]['value'])){
                        //结果都不相等或者 45结果相同
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                   
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                     
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                   
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);

                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
           
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                   
                        ]);
                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.2),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                  
                        ]);
                        $assertModel->where('user_id','in',$data[3]['uid'].','.$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);
                        
                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //一二结果相等，其他的结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.4/10,
                 
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.4/10,
               
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
           
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);

                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'].','.$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.4),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.4/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
        
                        ]);

                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.2),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
             
                        ]);
                        $assertModel->where('user_id',$data[3]['uid'].','.$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);
                        
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //二三结果相等，其他的都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
               
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
          
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);

                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
       
                        ]);
                        $assertModel->where('user_id','in',$data[1]['uid'].','.$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
       
                        ]);

                        $assertModel->where('user_id','in',$data[3]['uid'].','.$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);
                        
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //三四结果相等，其他的都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
             
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
           
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
       
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
                

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);

                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
           

                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                 
                        ]);

                        $assertModel->where('user_id','in',$data[2]['uid'].','.$data[3]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.1),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                
                        ]);
                        $assertModel->where('user_id',$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);
                        
                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //123结果相等，其他的不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
              
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                  
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                  
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);

                        //修改资产表
                        $uid = $data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.33),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
          
                        ]);


                        $assertModel->where('user_id','in',$data[3]['uid'].','.$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);
                        
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //234结果相等，其他的不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
               
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,
             
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,
           
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,
                  

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
             
                        ]);
                        $assertModel->where('user_id','in',$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.16),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.16/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                
                        ]);

                        $assertModel->where('user_id',$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);
                        
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value']){
                        //345结果相等，其他的不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                   
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                   
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.06/10,
             
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.06/10,
               

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.06/10,
                  

                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                       
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                      
                        ]);
                        $assertModel->where('user_id','in',$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.06),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.06/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                    
                        ]);

                        
                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //1234结果相等，其他的不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                       
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                     
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                      
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                         

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                     
                        ]);

                        $assertModel->where('user_id',$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                        
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value']){
                        //2345结果相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                 
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
               
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
              
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
             

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
                  

                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                      
                        ]);

                        $assertModel->where('user_id','in',$data[4]['uid'].','.$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.1),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                     
                        ]);
                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value']){
                        //结果都相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                    
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                  
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                         
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                       

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                        
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.2),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                         
                        ]);

                    }

                }elseif($prople_num == 6){
                    //房间为六个人对战时的结果
                    //六个人的结果相同
                    if($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] == $data[5]['value']){
                        //结果都相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                          
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                       
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                     
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                        

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                          
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[5]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
           
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold/6),//加金币
                            'integral'=>Db::raw('integral+'.$gold/6/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                     
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                      
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                         
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
             
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
               
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                   
                        ]);
                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.2),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                      
                        ]);

                        $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //12结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.4/10,
                          
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.4/10,
                        
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                       
                        ]);



                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.4),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.4/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                         
                        ]);

                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.2),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                        
                        ]);

                        $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //23结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                    
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                 
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                   
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            
                        ]);

                        $assertModel->where('user_id','in',$data[1]['uid'].','.$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                          
                        ]);

                        $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //34结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                            
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                         
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
                          
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
                         

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
            
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
           
                        ]);

                        $assertModel->where('user_id','in',$data[2]['uid'].','.$data[3]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.1),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $uid = $data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif(($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] != $data[5]['value'])|| ($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] == $data[5]['value']) || ($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] == $data[5]['value'])){
                        //45结果相同,其他结果都不相等 或者56结果相同，其他的都不相同 或者456相同 其他的都不相同
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                     
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
  
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,

                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.2),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //123结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
 
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,

                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,

                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);
                        $uid = $data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'];
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold/3),//加金币
                            'integral'=>Db::raw('integral+'.$gold/3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);
                       

                        $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //234结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,

                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,

                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,

                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.15/10,
      

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);

                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $uid = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'];
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.16),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.15/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);
                       
                        $uid = $data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //345结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,

                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,

                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.06/10,

                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.06/10,


                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.06/10,

                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);

                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $uid = $data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.06),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.06/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);
                       
                        $assertModel->where('user_id',$data[5]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //1234结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
   
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,

                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,

                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,


                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);

                        //修改资产表
                        $uid = $data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $uid = $data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //2345结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
 
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.125-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.125/10,
        
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.125-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.125/10,

                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.125-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.125/10,


                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.125-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.125/10,

                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);

                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $uid = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.125),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.125/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $assertModel->where('user_id',$data[5]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] == $data[5]['value']){
                        //3456结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
         
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
         
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.05-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.05/10,
            
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.05-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.05/10,
      

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.05-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.05/10,
    
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.05-$bet)),
                            'result'=>$data[5]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.05/10,

                        ]);

                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);
                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.3),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                        $uid = $data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.05),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.05/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //12345结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0,
                        ]);

                        //修改资产表
                        $uid = $data[0]['uid'].','.$data[1]['uid'].$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                        ]);

                        $assertModel->where('user_id',$data[5]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] == $data[5]['value']){
                        //23456结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,

                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,


                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                            'result'=>$data[5]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.1/10,
  
                        ]);

                        //修改资产表
                        
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                        ]);

                        $uid = $data[5]['uid'].','.$data[1]['uid'].$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                        $assertModel->where('user_id',$data[5]['uid'])->update([
                            'silver' => Db::raw('silver+'.$gold*0.1),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                    }

            }
        
             $return_data = Db::name('challenge_record')
                    ->alias('cr')
                    ->field('cr.this_integral,cr.win_gold,cr.is_win,cr.result,u.username,u.uid,u.user_level,cr.fee')
                    ->join('user u','cr.user_id = u.uid')
                    ->where('u.uid','in',$user_id)
                    ->where('cr.room_order',$result_data[0]['room_order'])
                    ->order('cr.win_gold desc')
                    ->select();  

                //修改房间为空闲状态
                $room->where('room_id',$result_data[0]['room_id'])->update(['is_in_game'=>0]);;
                Db::name('online_user')->where('room_id',$result_data[0]['room_id'])->update(['is_ready'=>0]);

                Gateway::sendToGroup($result_data[0]['room_id'],json_encode(['type'=>'rank_result','data'=>$return_data]));
                Cache::store('redis')->set('result_'.$result_data[0]['room_order'],NULL);


                echo json(return_msg('0000','请求成功'));die;



    }
  
  
  
  
  
  
  
  
  
  
  
  
/**
*@param 参数 room_id 房间号 
*@param uid 用户ID 
*@param result结果 
*@param room_order 该局比赛订单
*计算房间挑战结果
**/
    public function chall_res(){


        $receive_data = input();
      
        //添加数据到缓存
        $data = $this->room_result($receive_data,$receive_data['room_order']);

        $result_data = cache::get('result_'.$receive_data['room_order']);

        //数组长度
        $count = count($result_data);
        //该房间人数

        //实例化ceallenge_record表格
        $challengeModel = Db::name('challenge_record');
        //该局游戏人数
        $prople_num = $challengeModel->where('room_order',$receive_data['room_order'])->count();

    //如果不是一维数组
        if($count != count($result_data,1)){

            foreach ($result_data as $k => $v) {
                $return_data[] = ['uid'=>$v['uid'],'result'=>$v['result']];
            }

            Gateway::sendToGroup($receive_data['room_id'],json_encode(['type'=>'user_result','data'=>$return_data]));

            //数组长度为6表示房间人数已满
            if($count >= $prople_num){
                $cha_type = $challengeModel->where('room_order',$receive_data['room_order'])->value('cha_type');
                if($cha_type == 1){
                    $this->experience($result_data,$prople_num);die;

                }
                $data = $result_data;
                $uid = '';
                foreach ($data as $k => $v) {
                    $data[$k]['value'] = abs(1000-$v['result']);
                    $uid .= $v['uid'].',';

                }
                $uid = rtrim($uid,',');
                $user_id = $uid;

                //根据结果返回排序数组
                $arr = array_map(create_function('$n', 'return $n["value"];'), $data);
                array_multisort($arr, SORT_ASC, $data);

                //实例化room表 assets表
                $room = Db::name('room');
                $assertModel = Db::name('assets');
                //房费
                $bet = $room->where('room_id',$receive_data['room_id'])->value('room_gold');
                
                //本局金币池
                $gold = $bet*$prople_num*0.9;

                //本局总费用
                $fee = $bet*$prople_num*0.1;

                //$assertModel->where('user_id',1)->setInc('room_fee',$fee);


                //只有两个人开始游戏
                if($prople_num == 2){

                    if($data[0]['value'] != $data[1]['value']){

                        //两个人的结果不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/10,
                            'fee'=>$fee
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[1]['result'],
                            'is_win'=>0
                        ]);

                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'gold' => Db::raw('gold+'.$gold),//加金币
                            'integral'=>Db::raw('integral+'.$gold/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee),
                        ]);

                        $assertModel->where('user_id',$data[1]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);



                    }else{

                        //两个人相同结果
                            //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                            'fee'=>$fee/2
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                            'fee'=>$fee/2
                        ]);


                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee/2),
                        ]);


                    }
                }elseif ($prople_num == 3) {
                    //房间为三个人对战时的结果
                    //三个人的结果不相等
                    if(($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value']) || ($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'])){

                        //三个人的结果不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/10,
                            'fee'=>$fee
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[1]['result'],
                            'is_win'=>0,

                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[2]['result'],
                            'is_win'=>0
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'])->update([
                            'gold' => Db::raw('gold+'.$gold),//加金币
                            'integral'=>Db::raw('integral+'.$gold/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee)
                        ]);
                        $assertModel->where('user_id',$data[1]['uid'].','.$data[2]['uid'])->update([

                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] != $data[2]['value']){

                        //前两个人的结果相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                            'fee'=>$fee/2
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                            'fee'=>$fee/2
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[2]['result'],
                            'is_win'=>0,

                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'])->update([
                            'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee/2)
                        ]);

                        $assertModel->where('user_id',$data[2]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value']){

                        //三个人的结果相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
                            'fee'=>$gold/3
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
                            'fee'=>$gold/3
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
                            'fee'=>$gold/3
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold/3),//加金币
                            'integral'=>Db::raw('integral+'.$gold/3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee/3)
                        ]);


                    }
                    //房间为四人对战
                }elseif($prople_num == 4){
                    if($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value']){
                        //四个人的结果不相等
                        //修改订单结果
                        //10S绝杀

                        if($data[0]['value'] == 0){

                                 $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{

                             $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.5/10,
                            'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.3/10,
                                'fee'=>$fee*0.3
                            ]);

                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.2/10,
                                'fee'=>$fee*0.2
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[3]['result'],
                                'is_win'=>0,
                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                            ]);
                            $assertModel->where('user_id',$data[2]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.2),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.2)
                            ]);
                            $assertModel->where('user_id',$data[3]['uid'])->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);


                        }


                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value']){
                        //一二结果相等 三四不等
                        //修改订单结果


                        if($data[0]['value'] == 0){


                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold/2-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/2/10,
                                    'fee'=>$fee/2
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold/2-$bet)),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/2/10,
                                    'fee'=>$fee/2
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold/2),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/2/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee/2)
                                ]);

                                $uid_str = $data[2]['uid'].','.$data[3]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);




                        }else{

                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.4/10,
                                'fee'=>$fee*0.4
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.4/10,
                                'fee'=>$fee*0.4
                            ]);

                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.2/10,
                                'fee'=>$fee*0.2
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[3]['result'],
                                'is_win'=>0,
                            ]);
                            //修改资产表
                            $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.4),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.4/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.4),
                            ]);

                            $assertModel->where('user_id',$data[2]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.2),
                            ]);
                            $assertModel->where('user_id',$data[3]['uid'])->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value']){
                        //二三结果相等 一四不等
                        //修改订单结果

                        if($data[0]['value'] == 0){

                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{


                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.5/10,
                                    'fee'=>$fee*0.4
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.25/10,
                                    'fee'=>$fee*0.25
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.25/10,
                                    'fee'=>$fee*0.25
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.5),
                                ]);
                                $assertModel->where('user_id','in',$data[1]['uid'].','.$data[2]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.25),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.25),
                                ]);
                                $assertModel->where('user_id',$data[3]['uid'])->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);
                            }

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value']){
                        //三四结果相等，一二不等
                        //修改订单结果

                        if($data[0]['value'] == 0){

                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{

                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.3/10,
                                'fee'=>$fee*0.3,
                            ]);

                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5),
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.3),
                            ]);
                            $assertModel->where('user_id','in',$data[2]['uid'].','.$data[3]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.1),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.1),
                            ]);
                        }

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value']){
                        //一二三结果相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                            'fee'=>$fee*0.33

                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                            'fee'=>$fee*0.33
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                            'fee'=>$fee*0.33
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0,
                        ]);
                        //修改资产表
                        $assertModel->where('user_id',$data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'])->update([
                            'gold' => Db::raw('gold+'.$gold*0.33),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee*0.33),

                        ]);

                        $assertModel->where('user_id',$data[3]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value']){
                        //二三四结果相等
                        //修改订单结果

                        if($data[0]['value'] == 0){

                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);




                        }else{

                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.15

                            ]);

                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.15
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.15
                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5),
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.16),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.15/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.16),
                            ]);
                        }

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value']){
                        //一二三四结果都相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);

                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee*0.25),
                        ]);
                       

                    }
                    //房间为五个人对战时的结果
                }elseif($prople_num == 5){
                    if(($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value']) || ($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] == $data[4]['value'])){
                        //结果都不相等或者 45结果相同
                        //修改订单结果
                        if($data[0]['value'] == 0){


                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);

                        }else{

                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.3/10,
                                'fee'=>$fee*0.3
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.2/10,
                                'fee'=>$fee*0.2
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[3]['result'],
                                'is_win'=>0

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[4]['result'],
                                'is_win'=>0

                            ]);

                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5),
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                            ]);
                            $assertModel->where('user_id',$data[2]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.2),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.2)
                            ]);
                            $assertModel->where('user_id','in',$data[3]['uid'].','.$data[4]['uid'])->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }
                        
                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //一二结果相等，其他的结果都不相等
                        //修改订单结果


                        if($data[0]['value'] == 0){

                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold/2-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/2/10,
                                    'fee'=>$fee/2
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold/2-$bet)),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/2/10,
                                    'fee'=>$fee/2
                                ]);


                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'].','.$data[1]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold/2),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/2/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee/2)
                                ]);

                                $uid_str = $data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{


                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.4/10,
                                'fee'=>$fee*0.4
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.4/10,
                                'fee'=>$fee*0.4
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.2/10,
                                'fee'=>$fee*0.2
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[3]['result'],
                                'is_win'=>0

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[4]['result'],
                                'is_win'=>0

                            ]);

                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'].','.$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.4),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.4/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.4),
                            ]);

                            $assertModel->where('user_id',$data[2]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.2),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.2),
                            ]);
                            $assertModel->where('user_id',$data[3]['uid'].','.$data[4]['uid'])->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //二三结果相等，其他的都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){


                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);



                        }else{

                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.5/10,
                                    'fee'=>$fee*0.5
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.25/10,
                                    'fee'=>$fee*0.25
                                ]);
                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.25/10,
                                    'fee'=>$fee*0.25
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0

                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0

                                ]);

                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                                ]);
                                $assertModel->where('user_id','in',$data[1]['uid'].','.$data[2]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.25),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.25)
                                ]);

                                $assertModel->where('user_id','in',$data[3]['uid'].','.$data[4]['uid'])->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);
                            }
                        
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //三四结果相等，其他的都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){


                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);




                        }else{

                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.5/10,
                                    'fee'=>$fee*0.5
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.3/10,
                                    'fee'=>$fee*0.3
                                ]);
                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.1/10,
                                    'fee'=>$fee*0.1
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.1/10,
                                    'fee'=>$fee*0.1

                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0

                                ]);

                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.5)

                                ]);
                                $assertModel->where('user_id',$data[1]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                                ]);

                                $assertModel->where('user_id','in',$data[2]['uid'].','.$data[3]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.1),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.1)
                                ]);
                                $assertModel->where('user_id',$data[4]['uid'])->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);
                        }
                        
                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //123结果相等，其他的不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                            'fee'=>$fee*0.33
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                            'fee'=>$fee*0.33
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.33-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.3/10,
                            'fee'=>$fee*0.33
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);

                        //修改资产表
                        $uid = $data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold*0.33),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee*0.33)
                        ]);


                        $assertModel->where('user_id','in',$data[3]['uid'].','.$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);
                        
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //234结果相等，其他的不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){


                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);



                        }else{

                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.16
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.16
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.16

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[4]['result'],
                                'is_win'=>0

                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);
                            $assertModel->where('user_id','in',$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.16),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.16/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.16)
                            ]);

                            $assertModel->where('user_id',$data[4]['uid'])->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value']){
                        //345结果相等，其他的不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){


                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);




                        }else{


                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.3/10,
                                'fee'=>$fee*0.3
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.06/10,
                                'fee'=>$fee*0.06
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.06/10,
                                'fee'=>$fee*0.06

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                                'result'=>$data[4]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.06/10,
                                'fee'=>$fee*0.06

                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                            ]);
                            $assertModel->where('user_id','in',$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.06),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.06/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.06)
                            ]);

                        }
                        
                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value']){
                        //1234结果相等，其他的不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0

                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'])->update([
                            'gold' => Db::raw('gold+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee*0.25)
                        ]);

                        $assertModel->where('user_id',$data[4]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                        
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value']){
                        //2345结果相等
                        //修改订单结果

                        if($data[0]['value'] == 0){



                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);



                        }else{

                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[4]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1

                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);

                            $assertModel->where('user_id','in',$data[4]['uid'].','.$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.1),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.1)
                            ]);
                        }
                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value']){
                        //结果都相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold*0.2),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee*0.2)
                        ]);

                    }

                }elseif($prople_num == 6){
                    //房间为六个人对战时的结果
                    //六个人的结果相同
                    if($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] == $data[5]['value']){
                        //结果都相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                            'fee'=>$fee/6
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                            'fee'=>$fee/6
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                            'fee'=>$fee/6
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                            'fee'=>$fee/6

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                            'fee'=>$fee/6
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/6-$bet)),
                            'result'=>$data[5]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/6/10,
                            'fee'=>$fee/6
                        ]);
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold/6),//加金币
                            'integral'=>Db::raw('integral+'.$gold/6/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee/6)
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //结果都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){



                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);




                        }else{


                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.5/10,
                                    'fee'=>$fee*0.5
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.3/10,
                                    'fee'=>$fee*0.3
                                ]);
                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold*0.2/10,
                                    'fee'=>$fee*0.2
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0

                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                                ]);
                                $assertModel->where('user_id',$data[1]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                                ]);
                                $assertModel->where('user_id',$data[2]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold*0.2),//加金币
                                    'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee*0.2)
                                ]);

                                $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);
                            }

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //12结果相同,其他结果都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){
                                $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold/2-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/2/10,
                                    'fee'=>$fee/2
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold/2-$bet)),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/2/10,
                                    'fee'=>$fee/2
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'].','.$data[1]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold/2),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/2/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee/2)
                                ]);

                                $uid_str = $data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);




                        }else{


                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.4/10,
                                'fee'=>$fee*0.4
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.4-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.4/10,
                                'fee'=>$fee*0.4
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.2/10,
                                'fee'=>$fee*0.2
                            ]);



                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[3]['result'],
                                'is_win'=>0

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[4]['result'],
                                'is_win'=>0
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[5]['result'],
                                'is_win'=>0
                            ]);
                            //修改资产表
                            $assertModel->where('user_id','in',$data[0]['uid'].','.$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.4),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.4/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.4)
                            ]);

                            $assertModel->where('user_id',$data[2]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.2),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.2)
                            ]);

                            $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                            $assertModel->where('user_id','in',$uid)->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //23结果相同,其他结果都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{

                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.25/10,
                                'fee'=>$fee*0.25
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.25/10,
                                'fee'=>$fee*0.25
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[3]['result'],
                                'is_win'=>0

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[4]['result'],
                                'is_win'=>0
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[5]['result'],
                                'is_win'=>0
                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);

                            $assertModel->where('user_id','in',$data[1]['uid'].','.$data[2]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.25),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.25)
                            ]);

                            $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                            $assertModel->where('user_id','in',$uid)->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //34结果相同,其他结果都不相等
                        //修改订单结果


                        if($data[0]['value'] == 0){
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{


                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.3/10,
                                'fee'=>$fee*0.3
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[4]['result'],
                                'is_win'=>0
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[5]['result'],
                                'is_win'=>0
                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                            ]);

                            $assertModel->where('user_id','in',$data[2]['uid'].','.$data[3]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.1),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.1)
                            ]);

                            $uid = $data[4]['uid'].','.$data[5]['uid'];
                            $assertModel->where('user_id','in',$uid)->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }

                    }elseif(($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] != $data[5]['value'])|| ($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] == $data[5]['value']) || ($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] == $data[5]['value'])){
                        //45结果相同,其他结果都不相等 或者56结果相同，其他的都不相同 或者456相同 其他的都不相同
                        //修改订单结果

                        if($data[0]['value'] == 0){
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{




                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.3/10,
                                'fee'=>$fee*0.3
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.2/10,
                                'fee'=>$fee*0.2
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[3]['result'],
                                'is_win'=>0

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[4]['result'],
                                'is_win'=>0
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[5]['result'],
                                'is_win'=>0
                            ]);
                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                            ]);

                            $assertModel->where('user_id',$data[2]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.2),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.2/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.2)
                            ]);

                            $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                            $assertModel->where('user_id','in',$uid)->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] != $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //123结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
                            'fee'=>$fee/3
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
                            'fee'=>$fee/3
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold/3-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold/3/10,
                            'fee'=>$fee/3
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[3]['result'],
                            'is_win'=>0

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);
                        $uid = $data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'];
                        //修改资产表
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold/3),//加金币
                            'integral'=>Db::raw('integral+'.$gold/3/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee/3)
                        ]);
                       

                        $uid = $data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //234结果相同,其他结果都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{

                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.16
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.16
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.16-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.15/10,
                                'fee'=>$fee*0.16

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[4]['result'],
                                'is_win'=>0
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[5]['result'],
                                'is_win'=>0
                            ]);

                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);

                            $uid = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'];
                            //修改资产表
                            $assertModel->where('user_id','in',$uid)->update([
                                'gold' => Db::raw('gold+'.$gold*0.16),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.15/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.16)
                            ]);
                           
                            $uid = $data[4]['uid'].','.$data[5]['uid'];
                            $assertModel->where('user_id','in',$uid)->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //345结果相同,其他结果都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{


                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.3/10,
                                'fee'=>$fee*0.3
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.06/10,
                                'fee'=>$fee*0.06
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.06/10,
                                'fee'=>$fee*0.06

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.06-$bet)),
                                'result'=>$data[4]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.06/10,
                                'fee'=>$fee*0.06
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[5]['result'],
                                'is_win'=>0
                            ]);

                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                            ]);

                            $uid = $data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                            //修改资产表
                            $assertModel->where('user_id','in',$uid)->update([
                                'gold' => Db::raw('gold+'.$gold*0.06),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.06/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.06)
                            ]);
                           
                            $assertModel->where('user_id',$data[5]['uid'])->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] != $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //1234结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.25-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.25/10,
                            'fee'=>$fee*0.25

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[4]['result'],
                            'is_win'=>0
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0
                        ]);

                        //修改资产表
                        $uid = $data[0]['uid'].','.$data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold*0.25),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.25/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee*0.25)
                        ]);

                        $uid = $data[4]['uid'].','.$data[5]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'total_office'=>Db::raw('total_office+1')
                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //2345结果相同,其他结果都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.125-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.125/10,
                                'fee'=>$fee*0.125
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.125-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.125/10,
                                'fee'=>$fee*0.125
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.125-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.125/10,
                                'fee'=>$fee*0.125

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.125-$bet)),
                                'result'=>$data[4]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.125/10,
                                'fee'=>$fee*0.125
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold-'.$bet),
                                'result'=>$data[5]['result'],
                                'is_win'=>0
                            ]);

                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);

                            $uid = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                            $assertModel->where('user_id','in',$uid)->update([
                                'gold' => Db::raw('gold+'.$gold*0.125),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.125/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.125)
                            ]);

                            $assertModel->where('user_id',$data[5]['uid'])->update([
                                'total_office'=>Db::raw('total_office+1')
                            ]);
                        }
                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] != $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] == $data[5]['value']){
                        //3456结果相同,其他结果都不相等
                        //修改订单结果

                        if($data[0]['value'] == 0){
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.3-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.3/10,
                                'fee'=>$fee*0.3
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.05-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.05/10,
                                'fee'=>$fee*0.05
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.05-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.05/10,
                                'fee'=>$fee*0.05

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.05-$bet)),
                                'result'=>$data[4]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.05/10,
                                'fee'=>$fee*0.05
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.05-$bet)),
                                'result'=>$data[5]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.05/10,
                                'fee'=>$fee*0.05
                            ]);

                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);
                            $assertModel->where('user_id',$data[1]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.3),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.3/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.3)
                            ]);

                            $uid = $data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                            $assertModel->where('user_id','in',$uid)->update([
                                'gold' => Db::raw('gold+'.$gold*0.05),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.05/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.05)
                            ]);
                        }

                    }elseif($data[0]['value'] == $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] != $data[5]['value']){
                        //12345结果相同,其他结果都不相等
                        //修改订单结果
                        $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[0]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2
                        ]);

                        $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[1]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2
                        ]);
                        $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[2]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2
                        ]);
                        $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[3]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2

                        ]);
                        $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold+'.($gold*0.2-$bet)),
                            'result'=>$data[4]['result'],
                            'is_win'=>1,
                            'this_integral'=>$gold*0.2/10,
                            'fee'=>$fee*0.2
                        ]);
                        $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                            'win_gold' => Db::raw('win_gold-'.$bet),
                            'result'=>$data[5]['result'],
                            'is_win'=>0,
                        ]);

                        //修改资产表
                        $uid = $data[0]['uid'].','.$data[1]['uid'].$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                        $assertModel->where('user_id','in',$uid)->update([
                            'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                            'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                            'victory'=>Db::raw('victory+1'),//加胜利场次
                            'total_office'=>Db::raw('total_office+1'),
                            'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                        ]);

                        $assertModel->where('user_id',$data[5]['uid'])->update([
                            'total_office'=>Db::raw('total_office+1'),

                        ]);

                    }elseif($data[0]['value'] != $data[1]['value'] && $data[1]['value'] == $data[2]['value'] && $data[2]['value'] == $data[3]['value'] && $data[3]['value'] == $data[4]['value'] && $data[4]['value'] == $data[5]['value']){
                        //23456结果相同,其他结果都不相等
                        //修改订单结果
                        if($data[0]['value'] == 0){
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold+'.($gold-$bet)),
                                    'result'=>$data[0]['result'],
                                    'is_win'=>1,
                                    'this_integral'=>$gold/10,
                                    'fee'=>$fee
                                ]);

                                $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[1]['result'],
                                    'is_win'=>0,
                                ]);

                                $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[2]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[3]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[4]['result'],
                                    'is_win'=>0,
                                ]);
                                $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                    'win_gold' => Db::raw('win_gold-'.$bet),
                                    'result'=>$data[5]['result'],
                                    'is_win'=>0,
                                ]);
                                //修改资产表
                                $assertModel->where('user_id',$data[0]['uid'])->update([
                                    'gold' => Db::raw('gold+'.$gold),//加金币
                                    'integral'=>Db::raw('integral+'.$gold/10),//加积分
                                    'victory'=>Db::raw('victory+1'),//加胜利场次
                                    'total_office'=>Db::raw('total_office+1'),
                                    'room_fee'=>Db::raw('room_fee+'.$fee)
                                ]);

                                $uid_str = $data[1]['uid'].','.$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'].','.$data[5]['uid'];
                                $assertModel->where('user_id','in',$uid_str)->update([
                                    'total_office'=>Db::raw('total_office+1')
                                ]);


                        }else{
                            $challengeModel->where('user_id',$data[0]['uid'])->where('room_order',$data[0]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.5-$bet)),
                                'result'=>$data[0]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.5/10,
                                'fee'=>$fee*0.5
                            ]);

                            $challengeModel->where('user_id',$data[1]['uid'])->where('room_order',$data[1]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[1]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);
                            $challengeModel->where('user_id',$data[2]['uid'])->where('room_order',$data[2]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[2]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);
                            $challengeModel->where('user_id',$data[3]['uid'])->where('room_order',$data[3]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[3]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1

                            ]);
                            $challengeModel->where('user_id',$data[4]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[4]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);
                            $challengeModel->where('user_id',$data[5]['uid'])->where('room_order',$data[4]['room_order'])->update([
                                'win_gold' => Db::raw('win_gold+'.($gold*0.1-$bet)),
                                'result'=>$data[5]['result'],
                                'is_win'=>1,
                                'this_integral'=>$gold*0.1/10,
                                'fee'=>$fee*0.1
                            ]);

                            //修改资产表
                            $assertModel->where('user_id',$data[0]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.5),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.5/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.5)
                            ]);

                            $uid = $data[5]['uid'].','.$data[1]['uid'].$data[2]['uid'].','.$data[3]['uid'].','.$data[4]['uid'];
                            $assertModel->where('user_id',$data[5]['uid'])->update([
                                'gold' => Db::raw('gold+'.$gold*0.1),//加金币
                                'integral'=>Db::raw('integral+'.$gold*0.1/10),//加积分
                                'victory'=>Db::raw('victory+1'),//加胜利场次
                                'total_office'=>Db::raw('total_office+1'),
                                'room_fee'=>Db::raw('room_fee+'.$fee*0.1)
                            ]);
                        }

                    }

                }

               $return_data = Db::name('challenge_record')
                    ->alias('cr')
                    ->field('cr.this_integral,cr.win_gold,cr.is_win,cr.result,u.username,u.uid,u.user_level,cr.cha_type,cr.fee')
                    ->join('user u','cr.user_id = u.uid')
                    ->where('u.uid','in',$user_id)
                    ->where('cr.room_order',$receive_data['room_order'])
                    ->order('cr.win_gold desc')
                    ->select();  
                
                 //给他的上级加房费
                foreach ($return_data as $k => $v) {
                    if($v['is_win'] == 1){
                        $assertModel->where('user_id','in',$v['user_level'])->update([
                            'today_money'=>Db::raw('today_money+'.$v['fee']),
                            'money'=>Db::raw('money+'.$v['fee'])

                        ]);
                    }
                }
              
              
                //修改房间为空闲状态
                $room->where('room_id',$receive_data['room_id'])->update(['is_in_game'=>0]);;
                Db::name('online_user')->where('room_id',$receive_data['room_id'])->update(['is_ready'=>0]);

                Gateway::sendToGroup($receive_data['room_id'],json_encode(['type'=>'rank_result','data'=>$return_data]));
                Cache::store('redis')->set('result_'.$receive_data['room_order'],NULL);

            }

        }else{

            $return_data[] = ['uid'=>$receive_data['uid'],'result'=>$receive_data['result']];


            Gateway::sendToGroup($receive_data['room_id'],json_encode(['type'=>'user_result','data'=>$return_data]));


        }
      
        echo json(return_msg('0000','请求成功'));
        


    }

    //缓存用户提交的数据
/**
*@param $data 需要储存的数组
*@param $param 键名
*/
public function room_result($data,$param){

    $param = 'result_'.$param;
    
    if(cache::get($param)){
            
            $result_data = cache($param);

            if(count($result_data) == count($result_data,1)){
                if($result_data['uid'] == $data['uid']){
                    echo json(return_msg('50001','请不要重复提交'));die;
                }
                $result_data = [$result_data,$data];
                
            }else{
                    foreach ($result_data as $k => $v) {
                        if($v['uid'] == $data['uid']){
                        echo json(return_msg('50001','请不要重复提交'));die;
                    }
                }
                $result_data[] = $data;
            }
            
            Cache::store('redis')->set($param,$result_data);
            
        }else{
            
            Cache::store('redis')->set($param,$data);
            
        }

        $data = cache::get($param);
        return $data;
}




//开始游戏
    public function begin_game()
    {

        $data = input();
        $result = $this->validate($data,
        [
            'room_id|房间号'  => 'require',
            'uid|用户ID'   => 'require',
            'type|房间类型' => 'require',
        ]);
        if(true !== $result){
            // 验证失败 输出错误信息
            echo json(return_msg(40001,$result));die;
        } 
        $uid = explode(',', $data['uid']);
        
        
        //判断用户是否准备就绪
        
        $onlineUserModel = Db::name('online_user');

        $count = $onlineUserModel->where('room_id',$data['room_id'])
            ->where('user_type',1)
            ->where('is_ready',0)
            ->count();

        if($count >=2){

            echo json(return_msg(30005,'用户还未准备就绪'));die;

        }

        $roomModel = Db::name('room');
        $assetsModel = Db::name('assets');

        $room_data = $roomModel->where('room_id',$data['room_id'])->limit(1)->find();
        
        //验证金币是否充足
        $user_gold = $assetsModel->field('gold,silver')->where('user_id','in',$uid)->select();


        foreach($user_gold as $k=>$v){
            if($room_data['level'] == 1){
                if($room_data['room_gold'] > $v['silver']){
                    echo json_encode(['code'=>40001,'type'=>'no_gold','msg'=>'银币不足','data'=>null]);die;
                }
                
            }else{
                if($room_data['room_gold'] > $v['gold']){
                    echo json_encode(['code'=>40001,'type'=>'no_gold','msg'=>'金币不足','data'=>null]);die;
                }
            }
            
        
        }
        if($room_data['level'] == 1){
        
            $assetsModel->where('user_id','in',$uid)->setDec('silver',$room_data['room_gold']);
        }else{
            $assetsModel->where('user_id','in',$uid)->setDec('gold',$room_data['room_gold']);
            
        }
      
      
        //修改房间的状态为游戏中
        $roomModel->where('room_id',$data['room_id'])->update(['is_in_game'=>1]);


        $room_order = $data['room_id'].'_'.date('YmdHis');

        //添加在线游戏的人
        $cha_time = time();
        foreach ($uid as $k => $v) {

            $add_data[] = ['user_id'=>$v,'type'=>2,'gold'=>$room_data['room_gold'],'result'=>NULL,'payment'=>1,'is_win'=>3,'room_order'=>$room_order,'cha_time'=>$cha_time,'cha_type'=>$data['type']];

        }

        Db::name('challenge_record')->insertAll($add_data);
        $return_data = json_encode(['type'=>'begin_game','msg'=>'开始游戏','room_order'=>$room_order]);
        GateWay::sendToGroup($data['room_id'],$return_data);
        $return_data = json_encode(['code'=>'0000','type'=>'begin_game','msg'=>'开始游戏','data'=>null]);
        echo $return_data;


    }

  
  
    //房主获取当前用户准备状态
    public function user_reday_home(){

        $room_id = input('room_id');
        $onlineUserModel = Db::name('online_user');

        $user_data = $onlineUserModel->field('user_id as uid,is_ready')
            ->where('room_id',$room_id)
            ->where('user_type',1)
            ->order('join_time')
            ->select();

        $return_data = json_encode(['type'=>'ready','msg'=>'用户准备就绪开始游戏','data'=>$user_data]);
        GateWay::sendToGroup($room_id,$return_data);
        $return_data = json_encode(['code'=>'0000','type'=>'ready','msg'=>'用户准备就绪开始游戏','data'=>null],JSON_UNESCAPED_UNICODE);
        echo $return_data;




    }
  
  
  
//用户准备就绪开始游戏
//返回用户准备状态

    public function user_ready()

    {

        $data = input();
        $result = $this->validate($data,
        [
            'uid|用户ID'   => 'require',
            'room_id|房间号'=>'require',
        ]);
        if(true !== $result){
            // 验证失败 输出错误信息
            echo json(return_msg(40001,$result));die;
        } 
        $uid = $data['uid'];
        

        $room = Db::name('room')->where('room_id',$data['room_id'])->find();

         if($room['level'] == 1){
            $gold = Db::name('assets')->where('user_id',$uid)->value('silver');
                if($room['room_gold'] > $gold){
                     echo json(return_msg(40001,'银币不足'));die;
                }
                
            }else{

                $gold = Db::name('assets')->where('user_id',$uid)->value('gold');
                if($room['room_gold'] > $gold){
                     echo json(return_msg(40001,'金币不足'));die;
                }
            }

        $onlineUserModel = Db::name('online_user');
        //更改用户状态
        $is_ready = $onlineUserModel->where('user_id',$uid)->value('is_ready');

        if($is_ready == 1){
            $value = 0;
        }else{
            $value = 1;
        }

        $onlineUserModel->where('user_id',$uid)->update(['is_ready'=>$value]);

        $user_data = $onlineUserModel->field('user_id as uid,is_ready')
            ->where('room_id',$data['room_id'])
            ->where('user_type',1)
            ->order('join_time')
            ->select();

        $return_data = json_encode(['type'=>'ready','msg'=>'用户准备就绪开始游戏','data'=>$user_data]);
        GateWay::sendToGroup($data['room_id'],$return_data);
        $return_data = json_encode(['code'=>'0000','type'=>'ready','msg'=>'用户准备就绪开始游戏','data'=>null]);
        echo $return_data;

    }
//邀请好友
    public function invite_friend()
    {

        $uid = input('uid');
        if(empty($uid)){
            echo json(return_msg('60001',lang('LACK_PARAM')));die;
        }

        $room_id = input('room_id');

        $username = Db::name('user')->where('uid',session('user_data')['uid'])
            ->value('username');

        $user_data = Db::name('user')
            ->field('uid')
            ->where('shield',0)
            ->where('uid','in',$uid)
            ->where('status',1)
            ->select();

        $return_data = json_encode(['type'=>'invite','msg'=>'邀请加入房间','data'=>['username'=>$username,'room_id'=>$room_id,'is_friend'=>1]]);
        foreach ($user_data as $k => $v) {

            Gateway::sendToUid($v['uid'],$return_data);
        }

        echo json(return_msg('0000','邀请成功'));

    }

    public function stranger(){

        $uid = session('user_data')['uid'];
        //获取好友列表
        $room_id = input('room_id');
        //自己的用户名
        $username = Db::name('user')->where('uid',$uid)->value('username');

        //陌生人列表
        $friend = Db::name('assets')->where('user_id',$uid)->value('friend');
        $friend = $friend.','.$uid;
        $friend_data = Db::name('user')
          ->field('uid')
          ->where('uid','not in',$friend)
          ->where('status',1)
          ->where('shield',0)
          ->orderRaw('rand()')
          ->limit(10)
          ->select();
        //循环发送请求
        $return_data = json_encode(['type'=>'invite','msg'=>'邀请加入房间','data'=>['username'=>$username,'room_id'=>$room_id,'is_friend'=>0]]);
        foreach ($friend_data as $k => $v) {

            Gateway::sendToUid($v['uid'],$return_data);
        }

        echo json(return_msg('0000','邀请成功'));


    }
    
  //房主踢人
    public function Kick_people()
    {

        $uid = input('uid');
            
        $return_data = json_encode(['type'=>'kick','msg'=>'你长时间为准备已被提出房间']);
        
        Gateway::sendToUid($uid,$return_data);

        echo json_encode(['code'=>'0000','msg'=>'踢出成功','data'=>null]);

    }
    
 //红包接口
    public function red_envelope()
    {
        $uid = input('uid');
        $money = input('money');
        $type = input('type');

        $activeModel = Db::name('active');

        $is_recived = $activeModel
            ->where('user_id',$uid)
            ->where('type',$type)
            ->whereTime('time','today')
            ->limit(1)
            ->find();
        if($is_recived){
            echo json(return_msg(50006,'已领取本次活动奖励'));die;
        }
        //添加活动中奖纪录
        $activeModel->insert([
            'money'=>$money,
            'user_id'=>$uid,
            'time'=>time(),
            'type'=>$type
        ]);
        //添加金币
        Db::name('assets')->where('user_id',$uid)->setInc('gold',$money);
        Db::name('gold_recode')->insert(['gold'=>$money,'type'=>4,'time'=>time(),'user_id'=>$uid]);
        echo json(return_msg('0000','添加成功'));

    }

    //系统推送信息
    public function sys_send_message()
    {
        $uid = input('uid');
        $name = input('name');
        $type = input('type');  
        $username = Db::name('user')->where('uid',$uid)->limit(1)->value('username');

        //推送消息
        $data = [$username,$type,$name,date('Y.m.d H:i:s')];
        $message = ['type'=>'sys_hint','data'=>$data];
        // 向任意uid的网站页面发送数据
        Gateway::sendToAll(json($message,JSON_UNESCAPED_UNICODE));
        echo json(return_msg('0000','推送成功'));

    }

    //用户发言
    public function speak(){

            
        $msg = input('msg');

        $uid = session('user_data')['uid'];

        //用户名
        $username = Db::name('user')->where('uid',$uid)->limit(1)->value('username');


        $replace=array();
        $find=array('B','操','你妈','母','垃圾','黑','瓜','二哈','锤子','逼');

        foreach ($find as $k => $v) {
            $replace[] = str_repeat('*',mb_strlen($v,'utf8'));
        }
        $str=str_replace($find,$replace,$msg);
        $data = [$username,$str];

        Gateway::sendToAll(json_encode(['type'=>'speak','data'=>$data]));

        echo json(return_msg('0000','请求成功'));

    
    }

    


}
