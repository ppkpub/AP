<?php
/*  Common Funtions */
function strToHex($string){
    $hex='';
    for ($i=0; $i < strlen($string); $i++){
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}

function hexToStr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
         $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

/**
 * 将内容进行UNICODE编码得到原始二进制字符串
 * @param string $name 要转换的中文字符串
 * @param string $in_charset 输入中文编码，默认为uft8
 * @param string $out_charset 输出unicode编码，'UCS-2BE'或'UCS-2LE'
 * Linux 服务器上 UCS-2 编码方式与 Winodws 不一致，linux编码为UCS-2BE，windows为UCS-2LE，即big-endian和little-endian
 * @return string
 */
function unicode_encode($name,$in_charset='UTF-8',$out_charset='UCS-2BE')
{
	$name = iconv($in_charset, $out_charset, $name);
	$len = strlen($name);
	$str = '';
	for ($i = 0; $i < $len - 1; $i = $i + 2){
		$c = $name[$i];
		$c2 = $name[$i + 1];
		if (ord($c) > 0){    // 两个字节的文字
			$str .= $c.$c2;
		}
		else{
			$str .= $c2;
		}
	}
	return $str;
}
 

//格式化单位到秒的时间值的显示
//$timestamp: 自1970年1月1日0时起的秒数
//$onlydate: 是否只显示日期，缺省为false
//$sepc_time_zone:指定时区，不指定时，将按照当前已登录用户设定时区->服务器设定时区->北京时区为优先级来依次判断取值
function formatTimestampForView($timestamp,$onlydate=false,$sepc_time_zone=NULL)
{
   global $g_fUserLogonTimeZone;
   
   if(isset($sepc_time_zone))
      $time_zone=$sepc_time_zone;
   else if(isset($g_fUserLogonTimeZone))
      $time_zone=$g_fUserLogonTimeZone;
   else if(defined('SERVER_TIME_ZONE'))
      $time_zone=SERVER_TIME_ZONE;
   else
      $time_zone=8;
   
   if($onlydate)
       return $timestamp==0? '--------' :gmdate("Y-m-d", $timestamp+$time_zone*3600);
   else
       return $timestamp==0? '--------' :gmdate("Y-m-d H:i", $timestamp+$time_zone*3600);
}