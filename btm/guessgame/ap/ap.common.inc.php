<?php
/*
  PPk AP Common Defines&Functions 
    PPkPub.org   20180915
  Released under the MIT License.
*/
define('PPK_URI_PREFIX',"ppk:");
define('TEST_CODE_UTF8',"A测B试C");

define('AP_NODE_NAME', 'JOYBLOCK-AP DEMO by PHP, 20180915' );      //AP节点名称
define('AP_DEFAULT_ODIN', PPK_URI_PREFIX.'527064.583/' ); //缺省使用的ODIN标识
define('AP_RESOURCE_PATH', getcwd()."/resource/" ); //资源内容存放路径
define('AP_KEY_PATH',      getcwd()."/key/" );      //密钥文件存储路径
define('DEFAULT_SIGN_HASH_ALGO', 'SHA256' );      //缺省的签名用哈希算法


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
/*
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
*/
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