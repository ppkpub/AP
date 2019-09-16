<?php
/*
  PPk AP based HTTP Sample 
    PPkPub.org   
  Released under the MIT License.
20180626: Using SHA256 as default hash
*/
define(PPK_URI_PREFIX,"ppk:");
define(TEST_CODE_UTF8,"A测B试C");

define(AP_NODE_NAME, 'AP DEMO by PHP, 20180626' );      //AP节点名称
define(AP_DEFAULT_ODIN, PPK_URI_PREFIX.'513468.490/' ); //缺省使用的ODIN标识
define(AP_RESOURCE_PATH, getcwd()."/apdemo-resource/" ); //资源内容存放路径
define(AP_KEY_PATH,      getcwd()."/apdemo-key/" );      //密钥文件存储路径
define(DEFAULT_SIGN_HASH_ALGO, 'SHA256' );      //缺省的签名用哈希算法

//在URL中指定例如 ?pttp_interest={"ver":1,"interest":{"uri":"ppk:513468.490/"}}  
//或者从POST FORM中提取
$array_pttp_interest=array();
$str_pttp_interest='';
$str_pttp_uri='';

if($_GET['pttp_interest']!=null){ 
  $str_pttp_interest=trim($_GET['pttp_interest']);
}elseif($_POST['pttp_interest']!=null){ 
  $str_pttp_interest=trim($_POST['pttp_interest']);
}

if(strlen($str_pttp_interest)>0){
  //提取出兴趣uri
  $array_pttp_interest=json_decode($str_pttp_interest,true);
  $str_pttp_uri=$array_pttp_interest['interest']['uri'];
}


if(!isset($str_pttp_uri)){
  respPttpStatus4XX( '400',"Bad Request : no valid uri " );
  exit(-1);
}

if( 0!=strncasecmp($str_pttp_uri,PPK_URI_PREFIX,strlen(PPK_URI_PREFIX)) ){
  respPttpStatus4XX( '400',"Bad Request : Invalid ppk-uri " );
  exit(-1);
}

$odin_chunks=array();
$parent_odin_path="";
$resource_id="";
$req_resource_versoin="";
$resource_filename="";

$tmp_chunks=explode("#",substr($str_pttp_uri,strlen(PPK_URI_PREFIX)));
if(count($tmp_chunks)>=2){
  $req_resource_versoin=$tmp_chunks[1];
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

//echo "str_pttp_uri=$str_pttp_uri\n";
//echo "parent_odin_path=$parent_odin_path , resource_id=$resource_id , req_resource_versoin=$req_resource_versoin \n";

if(strlen($parent_odin_path)==0){
  respPttpStatus4XX( '403',"Forbidden : The root ODIN should be parsed from bitcoin blockchain directly or from the cetified api service! " );
  exit(-1);
}

$tmp_posn1=strpos($resource_id,'?');
$tmp_posn2=strpos($resource_id,'(');
if($tmp_posn1!==false||$tmp_posn2!==false){
  //如果resource_id的第一个字符为?或(，按传入参数方式动态处理
  $function_name='';
  if($tmp_posn1!==false){
    $function_name=substr($resource_id,0,$tmp_posn1);
    $argvs_chunks=explode("&",substr($resource_id,$tmp_posn1+1));
  }else  if($tmp_posn2!==false){
    $function_name=substr($resource_id,0,$tmp_posn2);
    $argvs_chunks=explode(",",substr($resource_id,$tmp_posn2+1,strlen($resource_id)-$tmp_posn2-2));
  }
  //print_r($argvs_chunks);
  $tmp_resp=array();
  $tmp_resp['function_name']=$function_name;
  $tmp_resp['function_argvs']=$argvs_chunks;
  
  if($function_name=='sum'){
    $tmp_result=0;
    foreach($argvs_chunks as $tmp_value){
      $tmp_result += $tmp_value;
    }
    $tmp_resp['function_result']=$tmp_result;
  }else{
    $tmp_resp['function_result']="OK";
  }
  
  $tmp_resp['time']=strftime("20%y-%m-%d %H:%M:%S",time());
  $str_resp_content=json_encode($tmp_resp);
  $resp_resource_versoin=strftime("20%y%m%d%H%M%S",time()).'.0';//示例代码简单以时间值作为动态版本编号
  
  $str_resp_uri=PPK_URI_PREFIX.$parent_odin_path.$resource_id."#".$resp_resource_versoin;
  $str_content_type='text/html';
}else{
  //从静态资源目录下定位最新内容数据版本
  if(!file_exists( AP_RESOURCE_PATH.$parent_odin_path )){
    respPttpStatus4XX( '404',"Not Found : resource_dir:$parent_odin_path  not exist. ppk-uri:".$str_pttp_uri );
    exit(-1);
  }

  $d = dir(AP_RESOURCE_PATH.$parent_odin_path);
  while (($filename = $d->read()) !== false){
    //echo "filename: " . $filename . "<br>";
    $tmp_chunks=explode("#",$filename);
    
    if( strcmp($tmp_chunks[0],$resource_id)==0 && count($tmp_chunks)>=3){
      //print_r($tmp_chunks);
      if(strlen($req_resource_versoin)==0){
        if(strlen($resp_resource_versoin)==0){
          $resp_resource_versoin = $tmp_chunks[1];
          $resource_filename = $filename;
        }else if( cmpResourceVersion($tmp_chunks[1],$resp_resource_versoin)>0  ){ 
          $resp_resource_versoin = $tmp_chunks[1];
          $resource_filename = $filename;
        }
      }else if( strcmp($tmp_chunks[1],$req_resource_versoin)==0 ){
        $resp_resource_versoin=$req_resource_versoin;
        $resource_filename = $filename;
      }
    }
  }
  $d->close();

  if(strlen($resp_resource_versoin)==0)
    $resp_resource_versoin = '1.0'; //Default resource version

  $str_resp_uri=PPK_URI_PREFIX.$parent_odin_path.$resource_id."#".$resp_resource_versoin;

  $resource_path_and_filename = AP_RESOURCE_PATH.$parent_odin_path.$resource_filename;
  //echo "resource_path_and_filename=$resource_path_and_filename , resource_id=$resource_id , resp_resource_versoin=$resp_resource_versoin \n";

  if(!file_exists($resource_path_and_filename )){
    respPttpStatus4XX( '404',"Not Found : $resource_path_and_filename " );
    exit(-1);
  }

  //读取资源内容文件
  $str_resp_content=@file_get_contents($resource_path_and_filename);
  
  $ext = pathinfo($resource_path_and_filename, PATHINFO_EXTENSION);
  //简单判断内容类型
  if( strcasecmp($ext,'jpeg')==0 || strcasecmp($ext,'jpg')==0 || strcasecmp($ext,'gif')==0 || strcasecmp($ext,'png')==0)
    $str_content_type='image/'.$ext;
  else  
    $str_content_type='text/html';
}

$str_pttp_data=generatePTTPData( $str_resp_uri,'200','OK',$str_content_type,$str_resp_content );

//输出数据正文
header('Content-Type: text/json');
echo $str_pttp_data;


//获取请求资源对应的签名验证设置
function getOdinKeySet($parent_odin_path,$resource_id,$resp_resource_versoin=''){
  //echo "getOdinKeySet(): parent_odin_path=$parent_odin_path,resource_id=$resource_id,resp_resource_versoin=$resp_resource_versoin\n";
  
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
比较资源版本号
*/
function cmpResourceVersion($rv1,$rv2){
  //echo $rv1,'  vs ',$rv2,"<br>";
  $tmp_chunks=explode(".",$rv1);
  //print_r($tmp_chunks);
  $major1 =  intval($tmp_chunks[0]);
  $minor1 =  count($tmp_chunks)>1 ? intval($tmp_chunks[1]):0;
  
  $tmp_chunks=explode(".",$rv2);
  //print_r($tmp_chunks);
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

/*
 应答处理异常状态
*/
function respPttpStatus4XX( $status_code,$status_detail ) {
    echo generatePttpData(AP_DEFAULT_ODIN.'/'.$status_code.'#1.0',$status_code,$status_detail,'text/html','','no-store');
}


/*
 生成PTTP应答数据包
*/
function generatePttpData( $str_resp_uri,$status_code,$status_detail,$str_content_type,$str_resp_content,$str_cache_control='public' ) {
  $array_metainfo=array();

  //对于非html类型的正文内容都缺省采用base64编码
  if( $str_content_type !='text/html' ) {
    $str_resp_content=base64_encode($str_resp_content);
    $array_metainfo['content_encoding']='base64';
    //$str_resp_content=base64_encode(gzcompress($str_resp_content));
    //$array_metainfo['content_encoding']='gzip';
  }
 
  $array_metainfo['content_type']=$str_content_type;
  $array_metainfo['content_length']=strlen($str_resp_content);
  $array_metainfo['ap_node']=AP_NODE_NAME;
  $array_metainfo['cache-control']=$str_cache_control;
  
  $obj_data=array(
    "uri"=>$str_resp_uri,
    "utc"=>time(),
    "status_code" => $status_code,
    "status_detail" => $status_detail,
    "metainfo" => $array_metainfo,
    "content"=>$str_resp_content
  );
  
  $str_encoded_data = json_encode($obj_data);
  $str_sign=generatePttpSign($str_resp_uri,$str_encoded_data);
  
  $obj_resp=array(
    "ver"  => 1, 
    "data" => $str_encoded_data,
    "sign" => $str_sign
  );
  
  return json_encode($obj_resp);
}

//生成签名
function generatePttpSign($str_resp_uri,$str_resp_data){
  //echo "generatePttpSign() str_resp_data_hex=",strToHex($str_resp_data),"\n";
  
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
  
  //$str_resp_sign=rsaSign($str_resp_data,$vd_prv_key,);
  $str_resp_sign=rsaSign($str_resp_data,$vd_prv_key,DEFAULT_SIGN_HASH_ALGO);

  $pttp_sign=DEFAULT_SIGN_HASH_ALGO."withRSA:".$str_resp_sign;
  
  return $pttp_sign;
  
}