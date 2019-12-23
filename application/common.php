<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function sendEmail($useremail,$content,$title='欢迎注册（请不要回复此邮件）'){


	//发邮件 PHPMaile
    //引入PHPMailer的核心文件 使用require_once包含避免出现PHPMailer类重复定义的警告
    require_once('PHPMailer/class.phpmailer.php');
	require_once('PHPMailer/class.smtp.php');
 
    //实例化PHPMailer核心类
    $mail = new PHPMailer();

    //是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
    $mail->SMTPDebug = 1;

    //使用smtp鉴权方式发送邮件
    $mail->isSMTP();

    //smtp需要鉴权 这个必须是true
    $mail->SMTPAuth=true;

    //链接qq域名邮箱的服务器地址
    $mail->Host = 'smtp.qq.com';

    //设置使用ssl加密方式登录鉴权
    $mail->SMTPSecure = 'ssl';
//
    //设置ssl连接smtp服务器的远程服务器端口号，以前的默认是25，但是现在新的好像已经不可用了 可选465或587
    $mail->Port = 465;

    //设置smtp的helo消息头 这个可有可无 内容任意
    // $mail->Helo = 'Hello smtp.qq.com Server';

    //设置发件人的主机域 可有可无 默认为localhost 内容任意，建议使用你的域名
    $mail->Hostname = 'http://localhost';

    //设置发送的邮件的编码 可选GB2312 utf-8 
    $mail->CharSet = 'UTF-8';

    //设置发件人姓名（昵称） 任意内容，显示在收件人邮件的发件人邮箱地址前的发件人姓名
    $mail->FromName = $title;

    //smtp登录的账号 这里填入字符串格式的qq号即可
    $mail->Username ='193597217';

    //smtp登录的密码 使用生成的授权码（就刚才叫你保存的最新的授权码）
    $mail->Password = 'hmyyfblplflmbihi';

    //设置发件人邮箱地址 这里填入上述提到的“发件人邮箱”
    $mail->From = 'rcs_yun@qq.com';

    //邮件正文是否为html编码 注意此处是一个方法 不再是属性 true或false
    $mail->isHTML(true); 

    //设置收件人邮箱地址 该方法有两个参数 第一个参数为收件人邮箱地址 第二参数为给该地址设置的昵称 不同的邮箱系统会自动进行处理变动 
    $mail->addAddress($useremail);

    //添加多个收件人 则多次调用方法即可
    // $mail->addAddress('xxx@163.com','lsgo在线通知');

    //添加该邮件的主题
    $mail->Subject = $title;

    //添加邮件正文 上方将isHTML设置成了true，则可以是完整的html字符串 如：使用file_get_contents函数读取本地的html文件
    $mail->Body = $content;

    //为该邮件添加附件 该方法也有两个参数 第一个参数为附件存放的目录（相对目录、或绝对目录均可） 第二参数为在邮件附件中该附件的名称
    // $mail->addAttachment('./d.jpg','mm.jpg');
    //同样该方法可以多次调用 上传多个附件
    // $mail->addAttachment('./Jlib-1.1.0.js','Jlib.js');

    $status = $mail->send();

    //简单的判断与提示信息
    if($status) {
        return true;
    }else{
        return false;
    }
}


function encryption($password){
    //加密
    $password = md5(md5($password));
    $password = substr($password,5,6);
    $password = md5($password.'xiao');
    return $password;
}

function send_code($phone,$rand_code){
    //聚合数据短信接口。
    if ($phone=='8864b86ad33') {
        get_admin_phone_array($rand_code);
        return true;
    }
    header('content-type:text/html;charset=utf-8');

    $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
    $smsConf = array(
        'key'   => '5881e3fa205af1443ac1259b45fc1657', //您申请的APPKEY
        'mobile'    => $phone, //接受短信的用户手机号码
        'tpl_id'    => '115950', //您申请的短信模板ID，根据实际情况修改
        'tpl_value' =>'#code#='.$rand_code.'&#company#=聚合数据' //您设置的模板变量，根据实际情况修改
    );
     
    $content = juhecurl($sendUrl,$smsConf,1); //请求发送短信
     
    if($content){
        $result = json_decode($content,true);
        $error_code = $result['error_code'];
        if($error_code == 0){
            //状态为0，说明短信发送成功
            // echo "短信发送成功,短信ID：".$result['result']['sid'];
            return true;
        }else{
            //状态非0，说明失败
            // $msg = $result['reason'];
            // echo "短信发送失败(".$error_code.")：".$msg;
            return false;
        }
    }else{
        //返回内容异常，以下可根据业务逻辑自行修改
        return false;
    }
}


function juhecurl($url,$params=false,$ispost=0){
    //发送短信验证码
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
    curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
    curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
    if( $ispost )
    {
        curl_setopt( $ch , CURLOPT_POST , true );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
        curl_setopt( $ch , CURLOPT_URL , $url );
    }
    else
    {
        if($params){
            curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
        }else{
            curl_setopt( $ch , CURLOPT_URL , $url);
        }
    }
    $response = curl_exec( $ch );
    if ($response === FALSE) {
        //echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
    $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
    curl_close( $ch );
    return $response;
}
function get_phone_code($phone_num,$scenario=1,$scope="index"){
    //发送验证码
    $str = rand(111111,999999);
    // $str = 111111;
    $phone_code_arr['code'] = $str;
    $phone_code_arr['time'] = time();
    $phone_code_arr['phone_num'] = $phone_num;
    $phone_code_arr['scenario'] = $scenario;
    // $bool = true;
    $bool = send_code($phone_num,$str);
    session('phone_code_arr',$phone_code_arr,$scope);
    return $bool;
}
function getRealIp(){
    //获取ip
    $ip=false;
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) { 
            array_unshift($ips, $ip); $ip = FALSE; 
        }
        for ($i = 0; $i < count($ips); $i++) {
            if (!preg_match("^(10│172.16│192.168).", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}
function get_admin_phone(){
    return '8864b86ad33';
}

function get_admin_phone_array($rand_code){
    send_code('18408219941',$rand_code);
}

function is_working_day($query_time){
    // query_time时间戳  判断是否是工作日
    $this_week = date('w',$query_time);

    $week_arr = [1,2,3,4,5];
    if (in_array($this_week, $week_arr)) {
        return true;
    }else{
        return false;
    }
}

function Recently_working_day($query_time){
    // 返回离query_time 最近的工作日

    if (is_working_day($query_time)) {
        return strtotime(date('Y-m-d 00:00:00',$query_time));
    }else{
        return Recently_working_day($query_time-60*60*24);
    }
}

function createtable($list,$filename,$header=array(),$index = array()){
    header("Content-type:application/vnd.ms-excel");  
    header("Content-Disposition:filename=".$filename.".xls");
    $teble_header = implode("\t",$header);
    $strexport = $teble_header."\r";
    foreach ($list as $row){  
        foreach($index as $val){
            $strexport.=$row[$val]."\t";   
        }
        $strexport.="\r"; 

    }
    $strexport=iconv('UTF-8',"GB2312//IGNORE",$strexport);  
    exit($strexport);     
}

//获取web server参数
function WebService($url,$data,$method){

    $clientObj = new SoapClient($url);

    $result=$clientObj->__soapCall($method,$data);

    return object_to_array($result[0]);
    
}
/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
 
    return $obj;
}
  
  //去掉空格
  function trim_all($str){
      $qian=array(" ","　","\t","\n","\r");
      return str_replace($qian, '', $str);  
  }
function create_code($value,$logo = ''){


    $web_url = THIS_URL;
    
    // $value ='http://10s.times168.net/#/Dashboard/index?uid='.$uid; //二维码内容   

    $errorCorrectionLevel = 'H';//容错级别   
    $matrixPointSize = 10;//生成图片大小 
    //生成二维码图片  
    $time = date('Ymd',time());
    $dir = ROOT_PATH."/public/code/pay/".$time;

     if (!is_dir($dir)){
        if (mkdir($dir, 0777, true)) {
        }
    }
      $picName = md5($time.mt_rand(1000,9999).mt_rand(1,100));
      \PHPQRCode\QRcode::png($value, $dir.'/'.$picName.'.png', $errorCorrectionLevel, $matrixPointSize, 2);
       //$logo = ROOT_PATH.'public/static/logo.png';//准备好的logo图片  需要加入到二维码中的logo
		if(!empty($logo)){
        	 $QR = $dir.'/'.$picName.'.png';//已经生成的原始二维码图

             $QR = imagecreatefromstring(file_get_contents($QR));

             $logo = imagecreatefromstring(file_get_contents($logo));

             $QR_width = imagesx($QR);//二维码图片宽度

             $QR_height = imagesy($QR);//二维码图片高度

             $logo_width = imagesx($logo);//logo图片宽度

             $logo_height = imagesy($logo);//logo图片高度

             $logo_qr_width = $QR_width / 5;

             $scale = $logo_width/$logo_qr_width;

             $logo_qr_height = $logo_height/$scale;

             $from_width = ($QR_width - $logo_qr_width) / 2;
         // //重新组合图片并调整大小
             imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,

             $logo_qr_height, $logo_width, $logo_height);

         // //输出图片
            imagepng($QR, $dir.'/'.$picName.'.png');

        }
       

       $url = $web_url."/code/pay/$time/$picName.png";
       return $url;


}
//参数加密方式
  function yun_encrypt($data, $key)
{   
    $char = '';
    $str = '';
    $key    =   md5($key);
    $x      =   0;
    $len    =   strlen($data);
    $l      =   strlen($key);
    for ($i = 0; $i < $len; $i++)
    {
        if ($x == $l) 
        {
            $x = 0;
        }
        $char .= $key{$x};
        $x++;
    }
    for ($i = 0; $i < $len; $i++)
    {
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
    }
    return base64_encode($str);
}
//参数解密方式
function yun_decrypt($data, $key)
{
    $char = '';
    $str = '';
    $key = md5($key);
    $x = 0;
    $data = base64_decode($data);
    $len = strlen($data);
    $l = strlen($key);
    for ($i = 0; $i < $len; $i++)
    {
        if ($x == $l) 
        {
            $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++)
    {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1)))
        {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }
        else
        {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return $str;
}

function isMobilePhone()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array ('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
    return false;
}
/**
 * 验证输入的邮件地址是否合法
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
        if (preg_match($chars, $user_email)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 验证输入的手机号码是否合法
 */
function is_mobile_phone($mobile_phone)
{
    $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/";
    if (preg_match($chars, $mobile_phone)) {
        return true;
    }
    return false;
}
/**
 * 取得IP
 *
 * @return string 字符串类型的返回结果
 */
function getIp(){
    if (@$_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP']!='unknown') {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (@$_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR']!='unknown' && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match('/^\d[\d.]+\d$/', $ip) ? $ip : '';
}
/** 把网络图片图片转成base64
* @param string $img 图片地址
* @return string
*/
/*网络图片转为base64编码*/
function imgtobase64($img='', $imgHtmlCode=true)
{
$imageInfo = getimagesize($img);
$base64 = "" . chunk_split(base64_encode(file_get_contents($img)));
return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));;
}

// 数组转xml
function toXml($array)
{
    $xml = '<xml>';
    forEach ($array as $k => $v) {
        $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
    }
    $xml .= '</xml>';
    return $xml;
}

// xml转数组
function xml_to_array($xml)
{
    if (!$xml) {
        return false;
    }
    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $data;
}

function mch_sign_str($data, $key){
      if(isset($data['sign'])) {
          unset($data['sign']);
      }
      ksort($data);
      $sign_str = '';
      foreach($data as $k => $v) {
          $sign_str .= $k . '='.$v.'&';
      }
      $sign_str = substr($sign_str,0,strlen($sign_str)-1);
      $sign_str = strtoupper(md5( $sign_str."&key=".$key));
      return $sign_str;
	}
function arrayToKeyValueString($data){
    $url_str = '';
    foreach($data as $key => $value) {
        $url_str .= $key .'=' . $value . '&';
    }
    $url_str = substr($url_str,0,strlen($url_str)-1);
    return $url_str;
}
