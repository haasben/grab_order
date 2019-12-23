<?php
namespace app\partner\controller;
use think\Controller;
use think\Validate;
use think\Db;
use think\Model;
class Download extends controller{

    public function index(){

        return $this->fetch('index');
    }

//详细说明
    public function des(){




        return $this->fetch();


    }


}