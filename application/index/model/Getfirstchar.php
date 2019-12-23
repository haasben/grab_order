<?php
namespace app\index\model;
use think\Model;
class Getfirstchar extends Model
{
	protected $name = 'field';

	/** 
 * @description: 获取汉子首字母 
 * @param: string 
 * @return: mixed 
 * @author: 
**/  
	public function getfirstchar($s0)
	{    
	    $fchar = ord($s0{0});  
	    if($fchar >= ord("a") and $fchar <= ord("z") )return strtoupper($s0{0});  
	    //$s1 = iconv("UTF-8","gb2312//IGNORE", $s0);  
	    // $s2 = iconv("gb2312","UTF-8//IGNORE", $s1);  
	    $s1 = $this->get_encoding($s0,'GB2312');  
	    $s2 = $this->get_encoding($s1,'UTF-8');  
	    if($s2 == $s0){$s = $s1;}else{$s = $s0;}  
	    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;  
	   if($asc>=-20319 && $asc<=-20284) return 'a';
       if($asc>=-20283 && $asc<=-19776) return 'b';
       if($asc>=-19775 && $asc<=-19219) return 'c';
       if($asc>=-19218 && $asc<=-18711) return 'd';
       if($asc>=-18710 && $asc<=-18527) return 'e';
       if($asc>=-18526 && $asc<=-18240) return 'f';
       if($asc>=-18239 && $asc<=-17923) return 'g';
       if($asc>=-17922 && $asc<=-17418) return 'h';
       if($asc>=-17417 && $asc<=-16475) return 'j';
       if($asc>=-16474 && $asc<=-16213) return 'k';
       if($asc>=-16212 && $asc<=-15641) return 'l';
       if($asc>=-15640 && $asc<=-15166) return 'm';
       if($asc>=-15165 && $asc<=-14923) return 'n';
       if($asc>=-14922 && $asc<=-14915) return 'o';
       if($asc>=-14914 && $asc<=-14631) return 'p';
       if($asc>=-14630 && $asc<=-14150) return 'q';
       if($asc>=-14149 && $asc<=-14091) return 'r';
       if($asc>=-14090 && $asc<=-13319) return 's';
       if($asc>=-13318 && $asc<=-12839) return 't';
       if($asc>=-12838 && $asc<=-12557) return 'w';
       if($asc>=-12556 && $asc<=-11848) return 'x';
       if($asc>=-11847 && $asc<=-11056) return 'y';
       if($asc>=-11055 && $asc<=-10247) return 'z';  
	    return null;  
	}  
/** 
 * @name: get_encoding 
 * @description: 自动检测内容编码进行转换 
 * @param: string data 
 * @param: string to  目标编码 
 * @return: string 
**/  
	public function get_encoding($data,$to)
	{
	    $encode_arr=array('UTF-8','ASCII','GBK','GB2312','BIG5','JIS','eucjp-win','sjis-win','EUC-JP');   
	    $encoded=mb_detect_encoding($data, $encode_arr);   
	    $data = mb_convert_encoding($data,$to,$encoded);   
	    return $data;  
	}
	public function pinyin($zh)
	{
	    $ret = "";  
	    $s1 = iconv("UTF-8","gb2312", $zh);  
	    $s2 = iconv("gb2312","UTF-8", $s1);  
	    if($s2 == $zh){$zh = $s1;}  
	    for($i = 0; $i < strlen($zh); $i++){  
	        $s1 = substr($zh,$i,1);  
	        $p = ord($s1);  
	        if($p > 160){  
	            $s2 = substr($zh,$i++,2);  
	            $ret .= $this->getfirstchar($s2);  
		        }else{
		            $ret .= $s1;  
		        }
		    }
		    return $ret;  
	}
}