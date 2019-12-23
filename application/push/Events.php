<?php

/**
*@param
*/
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
// use Workerman\Lib\Timer;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events 
{

  /**
     * 新建一个类的静态成员，用来保存数据库实例
     */
    public static $db = null;
  	public static $url = null;
    public static function onConnect($client_id)
    {

        // $data = ['type'=>'init','client_id'=>$client_id];
        // Gateway::sendToClient($client_id,json_encode($data));

        // 向所有人发送
      //  Gateway::sendToAll("$client_id login\r\n");
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {

      $message_data = json_decode($message, true);

        if(!$message_data)
        {
            return ;
        }

        switch ($message_data['type']) {
          case 'bind':
            //绑定用户
              Gateway::joinGroup($client_id, $message_data['group_id']);
              $data = json_encode(['type'=>'bind','msg'=>'绑定成功']);
              Gateway::sendToClient($client_id,$data);
            break;
		  case 'ping':
              return ;
          break;
            
          case 'order':
            	
            	$data = file_get_contents('http://all.jvapi.com/index/callback/get_is_order?uid='.$message_data['uid']);
            	Gateway::sendToClient($client_id,$data);
            
           break;
            
           case 'nagent_bind':
            //绑定用户
              Gateway::bindUid($client_id, $message_data['uid']);
              $data = json_encode(['type'=>'nagent_bind']);
              Gateway::sendToClient($client_id,$data);
            break;
            
            
            
          default:
            
            return ;
            break;
        }


        
   }

   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
    

   }

//主进程链接数据库
   public static function onWorkerStart($worker)
    {

      // self::$db = new \Workerman\MySQL\Connection('127.0.0.1', '3306', 'root', 'root', '10sgame');
      // self::$url = 'http://game.io';
    }
  	

  
  
}
