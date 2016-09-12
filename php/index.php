<?php
/*
  PPk AP Sample
*/
define(PPK_URI_PREFIX,"ppk:");
define(TEST_CODE_UTF8,"A测B试C");

define(AP_RESOURCE_PATH, getcwd()."/resource/" ); //资源内容存放路径
define(AP_KEY_PATH,      getcwd()."/key/" );      //密钥文件存储路径

//从header中提取请求数据
//  ppk-uri :  ppk:385617.1822/
//或者在URL中指定 ?ppk-uri=ppk:385617.1822/
$ppk_reqs=array();

if($_GET['ppk-uri']!=null){ 
  $ppk_reqs['ppk-uri']=$_GET['ppk-uri'];
}else{
  if (!function_exists('getallheaders')) { 
      function getallheaders() { 
         $headers = ''; 
         foreach ($_SERVER as $name => $value) { 
             if(strcasecmp(substr($name, 0, 4) ,'ppk_')==0) { 
                $ppk_reqs[strtolower($name)]= $value; 
             } 
         } 
         return $headers; 
      } 
  }
}

if(!isset($ppk_reqs['ppk-uri'])){
  echo "ppk-uri is empty";
  exit(-1);
}
  
$str_req_ppk_uri=$ppk_reqs['ppk-uri'];

if( 0!=strncasecmp($str_req_ppk_uri,PPK_URI_PREFIX,strlen(PPK_URI_PREFIX)) ){
  echo "Invalid ppk-uri";
  exit(-1);
}

$odin_chunks=array();
$parent_odin_path="";
$resource_id="";
$resource_versoin="";
$resource_filename="";

$tmp_chunks=explode("#",substr($str_req_ppk_uri,strlen(PPK_URI_PREFIX)));
if(count($tmp_chunks)>=2){
  $resource_versoin=$tmp_chunks[1];
}

$odin_chunks=explode("/",$tmp_chunks[0]);
if(count($odin_chunks)==1){
  $parent_odin_path="";
  $resource_id=$odin_chunks[0];
}else{
  $resource_id=$odin_chunks[count($odin_chunks)-1];
  $odin_chunks[count($odin_chunks)-1]="";
  $parent_odin_path=implode('/',$odin_chunks);
}

//echo "str_req_ppk_uri=$str_req_ppk_uri\n";
//echo "parent_odin_path=$parent_odin_path , resource_id=$resource_id , resource_versoin=$resource_versoin \n";

if(strlen($parent_odin_path)==0){
  echo "The root ODIN should be parsed from bitcoin blockchain directly or from the cetified api service!";
  exit(-1);
}

//从资源目录下定位最新版本
if(!file_exists( AP_RESOURCE_PATH.$parent_odin_path )){
  //header("HTTP/1.1 404 Not Found");
  echo "resource_dir:$parent_odin_path  not exist";
  exit(-1);
}

$d = dir(AP_RESOURCE_PATH.$parent_odin_path);
while (($filename = $d->read()) !== false){
  //echo "filename: " . $filename . "<br>";
  $tmp_chunks=explode("#",$filename);
  $req_resource_versoin=$resource_versoin;
  if( strcmp($tmp_chunks[0],$resource_id)==0 && count($tmp_chunks)>=3){
    if(strlen($req_resource_versoin)==0){
      if(strlen($resource_versoin)==0){
        $resource_versoin = $tmp_chunks[1];
        $resource_filename = $filename;
      }else if( cmpResourceVersion($tmp_chunks[1],$resource_versoin)>0  ){ 
        $resource_versoin = $tmp_chunks[1];
        $resource_filename = $filename;
      }
    }else if( strcmp($tmp_chunks[1],$req_resource_versoin)==0 ){
      $resource_filename = $filename;
    }
  }
}
$d->close();

if(strlen($resource_versoin)==0)
  $resource_versoin = '1.0';

$str_resp_uri=PPK_URI_PREFIX.$parent_odin_path.$resource_id."#".$resource_versoin;

$resource_path_and_filename = AP_RESOURCE_PATH.$parent_odin_path.$resource_filename;
//echo "resource_path_and_filename=$resource_path_and_filename , resource_id=$resource_id , resource_versoin=$resource_versoin \n";

if(!file_exists($resource_path_and_filename )){
  header("HTTP/1.1 404 Not Found");
  //echo "$resource_path_and_filename not exist";
  exit(-1);
}

//读取资源内容文件
$str_resp_content=@file_get_contents($resource_path_and_filename);

//生成签名
$ppk_sign=generatePPkSign($str_resp_uri,$str_resp_content);

//在header或cookie部分加上签名
//header("ppk-sign: "+base64_encode($ppk_sign));
setcookie("ppk-sign",$ppk_sign);

//输出内容类型头定义
$ext = pathinfo($resource_path_and_filename, PATHINFO_EXTENSION);
if( strcasecmp($ext,'jpeg')==0 || strcasecmp($ext,'jpg')==0 || strcasecmp($ext,'gif')==0 || strcasecmp($ext,'png')==0)
  header('Content-Type: image/'.$ext);
else  
  header('Content-Type: text/html');
  
//输出数据正文
echo $str_resp_content;

//生成签名
function generatePPkSign($str_resp_uri,$str_resp_content){
  //echo "str_resp_uri=$str_resp_uri\n";
  //读取对应的父级ODIN密钥
  $parent_odin=substr($str_resp_uri,strlen(PPK_URI_PREFIX));
  $tmp_posn=strrpos($parent_odin,'/');
  if($tmp_posn<1)
    return '';
  
  $parent_odin=substr($parent_odin,0,$tmp_posn);
  
  $tmp_posn=strrpos($parent_odin,'/');
  if($tmp_posn>0){
    $up_parent_odin=substr($parent_odin,0,$tmp_posn);
    $up_resource_id=substr($parent_odin,$tmp_posn+1);
  }else{
    $up_parent_odin="";
    $up_resource_id=$parent_odin;
  }
  $parent_key_set=getOdinKeySet($up_parent_odin,$up_resource_id);
  if($parent_key_set==null)
    return '';
  
  $vd_prv_key="-----BEGIN PRIVATE KEY-----\n".$parent_key_set['RSAPrivateKey']."-----END PRIVATE KEY-----";
  
  if(strlen($vd_prv_key)==0){
    return "";
  }
  
  $str_resp_sign=rsaSign($str_resp_content.$str_resp_uri,$vd_prv_key,OPENSSL_ALGO_MD5);

  $ppk_sign=json_encode(array("ppk-uri"=>$str_resp_uri,"algo"=>"MD5withRSA","debug_pubkey"=>$parent_key_set['RSAPublicKey'],"sign_base64"=>$str_resp_sign));
  
  return $ppk_sign;
  
}

//获取请求资源对应的签名验证设置
function getOdinKeySet($parent_odin_path,$resource_id,$resource_versoin=''){
  //echo "getOdinKeySet(): parent_odin_path=$parent_odin_path,resource_id=$resource_id,resource_versoin=$resource_versoin\n";
  
  $key_filename=AP_KEY_PATH.$parent_odin_path.'/'.$resource_id.'.json';
  
  //如果子级扩展ODIN标识没有设定签名参数，则递归采用上一级ODIN标识的签名参数
  if(!file_exists($key_filename)){
    if(strlen($parent_odin_path)==0)
      return null;
    
    $tmp_posn=strrpos($parent_odin_path,'/');
    if($tmp_posn>0){
      $up_parent_odin_path=substr($parent_odin_path,0,$tmp_posn);
      $up_resource_id=substr($parent_odin_path,$tmp_posn+1);
    }else{
      $up_parent_odin_path="";
      $up_resource_id=$parent_odin_path;
    }
    
    return getOdinKeySet($up_parent_odin_path,$up_resource_id,'');
  }
  
  $str_key_set = @file_get_contents($key_filename);
  if(!empty($str_key_set)){
    return json_decode($str_key_set,true);
  }
}

//For generating signature using RSA private key
function rsaSign($data,$strValidationPrvkey,$algo){
    //$p = openssl_pkey_get_private(file_get_contents('private.pem'));
    $p=openssl_pkey_get_private($strValidationPrvkey);
    openssl_sign($data, $signature, $p,$algo);
    openssl_free_key($p);
    return base64_encode($signature);
}


/*
比较资源版本
*/
function cmpResourceVersion($rv1,$rv2){
  $tmp_chunks=explode(".",$rv1);
  $major1 =  intval($tmp_chunks[0]);
  $minor1 =  count($tmp_chunks)>1 ? intval($tmp_chunks[1]):0;
  
  $tmp_chunks=explode(".",$rv2);
  $major2 =  intval($tmp_chunks[0]);
  $minor2 =  count($tmp_chunks)>1 ? intval($tmp_chunks[1]):0;
  
  if($major1==$major2 && $minor1==$minor2){
    return 0;
  }if($major1>$major2 || ( $major1==$major2 && $minor1>$minor2 )){
    return 1;
  } else {
    return -1;
  }
  
}

/*  
php 输出字符串的16进制数据  
*/ 
function strToHex($data, $newline="n")  
{  
  static $from = '';  
  static $to = '';  
   
  static $width = 16; # number of bytes per line  
   
  static $pad = '.'; # padding for non-visible characters  
   
  if ($from==='')  
  {  
    for ($i=0; $i<=0xFF; $i++)  
    {  
      $from .= chr($i);  
      $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;  
    }  
  }  
   
  $hex = str_split(bin2hex($data), $width*2);  
  $chars = str_split(strtr($data, $from, $to), $width);  
  
  $str_hex="";
  $offset = 0;  
  foreach ($hex as $i => $line)  
  {  
    $str_hex .= sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline;  
    $offset += $width;  
  }  
  
  return $str_hex;
}  

