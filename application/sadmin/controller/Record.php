<?php
namespace app\sadmin\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\Validate;
class Record extends UserCommon{
    public function index(){
        

        //用户上下分记录表
        $uid = input('get.uid');
        if ($uid) {
            $uid_arr = explode('_',trim($uid,' '));
            if (!isset($uid_arr[1])||!is_numeric($uid_arr[1])) {
                echo '<script>alert("商户号错误");window.history.back(-1);</script>';
                die;
            }
            $where1['operator|child_id'] = $uid_arr[1];
          }else{
              $where1 = '';
          }

        $record_data =  Db::table('record')
             ->where($where1)
            ->order('time desc')
            ->paginate(15)
            ->each(function($item,$key){
                $item['top_name'] = Db::table('users')
                    ->field('name,merchant_cname,id as mch_id')
                    ->where('id',$item['operator'])
                    ->limit(1)
                    ->find();
                $item['child_name'] = Db::table('users')
                    ->field('name,merchant_cname,id as mch_id')
                    ->where('id',$item['child_id'])
                    ->limit(1)
                    ->find();
                    return $item;
            });

        $this->assign('record_data',$record_data);

        return $this->fetch();
    }


}