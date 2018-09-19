<?php
/*
  PPk AP based HTTP Sample 
    PPkPub.org   
  Released under the MIT License.
20180626: Using SHA256 as default hash
*/
require 'ap.common.inc.php';

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
