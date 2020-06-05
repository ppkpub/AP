<?php
/*
  The default AP for PTTP Interest  
    PPkPub.org   20200417
  Released under the MIT License.
*/
require_once 'ap.common.inc.php';

$array_req = \PPkPub\AP::parsePttpInterest();

$str_req_uri = $array_req['uri'];

if( $str_req_uri==null || strlen($str_req_uri)==0)
{
  /*
  \PPkPub\AP::respPttpException( 
        $str_req_uri,
        null,
        '400',
        "Bad Request : no valid uri "
    );
    */
  Header('Location: pns.php');
  exit(-1);
}

//Determine the resource prefix used by the response
$str_map_uri=null;
$spec_key_set=null;
$spec_plugin_set=null;

//先从配置文件里查询匹配项
foreach($gMapSiteURI as $tmp_req_uri_prefix => $tmp_map_set)
{
   if( 0==strncasecmp($str_req_uri,$tmp_req_uri_prefix,strlen($tmp_req_uri_prefix)) )
   {
      $str_map_uri = $tmp_map_set['dest'].substr($str_req_uri,strlen($tmp_req_uri_prefix));
      
      if(array_key_exists('key_file',$tmp_map_set)){
        $spec_key_set = \PPkPub\AP::getOdinKeySetFromFile(@$tmp_map_set['key_file']);
      } 
      
      if(array_key_exists('plugin',$tmp_map_set)){
        $spec_plugin_set = $tmp_map_set['plugin'];
      } 
      
      if($tmp_map_set['redirect']){
        //直接返回跳转应答
        \PPkPub\AP::respPttpRedirect( 
            $str_req_uri,
            null,
            'text/html',
            $str_map_uri,
            $spec_key_set
        );
        exit;
      }
      
      break;
   } 
}

if(strlen($str_map_uri)==0){
    //如果配置文件没有匹配项，则从解析设置数据库里查询匹配项
    $g_dbLink=connectDB();
    $sqlstr = "select * from pns where  odin_uri='".addslashes($str_req_uri)."';";
    $rs = mysqli_query($g_dbLink,$sqlstr);
    if (false !== $rs) {
        $existed_pns_record = mysqli_fetch_assoc($rs);
        
        \PPkPub\AP::outputPttpData($existed_pns_record['pttp_data']);
        
        exit(0);
    }
}

//echo " str_req_uri=$str_req_uri\n str_map_uri=$str_map_uri\n"; exit(-1);

if(strlen($str_map_uri)==0)
{
  \PPkPub\AP::respPttpException( 
        $str_req_uri,
        null,
        '400',
        "Bad Request : not supported uri: ".$str_req_uri
    );
  exit(-1);
}

//Parse mapped URI segments
$map_uri_segments = \PPkPub\ODIN::splitPPkURI($str_map_uri);

$parent_odin_path=$map_uri_segments['parent_odin_path'];
$resource_id=$map_uri_segments['resource_id'];
$req_resource_versoin=$map_uri_segments['resource_versoin'];
//$odin_chunks=$map_uri_segments['odin_chunks'];

$tmp_posn_func_flag=strpos($resource_id,'(');
if($tmp_posn_func_flag!==false){
  //If the resource_id contains characters '(',  processed as calling function with parameters
  //如果resource_id的含有字符(，按传入参数方式动态处理
  if($spec_plugin_set!=null){
    require_once(PPK_AP_PLUGIN_DIR_PREFIX.$spec_plugin_set);
    $obj_result=plugInProcessFunctionResource($parent_odin_path,$resource_id,$req_resource_versoin);
  }else{
    $obj_result=defaultProcessFunctionResource($parent_odin_path,$resource_id,$req_resource_versoin);
  }
}else{
  //按静态内容处理 processed as static content
  $obj_result=\PPkPub\AP::locateStaticResource($parent_odin_path,$resource_id,$req_resource_versoin);
}
//print_r($obj_result);
//Generate and output the PTTP data package
if($obj_result['code']==0){
    $obj_result_data = $obj_result['result_data'];
    $str_resp_uri = $str_req_uri.substr($obj_result_data['local_uri'],strlen($str_map_uri));

    \PPkPub\AP::respPttpData( 
        $str_resp_uri,
        $obj_result_data['local_uri'],
        '200',
        'OK',
        $obj_result_data['content_type'],
        $obj_result_data['content'],
        @$obj_result_data[\PPkPub\PTTP::PTTP_KEY_CACHE_AS_LATEST],
        $spec_key_set
    );
}else if($obj_result['code']>=300 && $obj_result['code']<=399){ 
    //3xx：重定向类型的应答
    \PPkPub\AP::respPttpRedirect( 
        $str_req_uri,
        $str_map_uri,
        $obj_result['content_type'],
        $obj_result['content'],
        $spec_key_set,
        $obj_result['code'],
        $obj_result['msg']
    );
}else{
    //其它异常应答
    //print_r($obj_result);exit;
    \PPkPub\AP::respPttpException( 
        $str_req_uri,
        $str_map_uri,
        $obj_result['code'],
        $obj_result['msg'],
        'text/html',
        '',
        @$obj_result[\PPkPub\PTTP::PTTP_KEY_CACHE_AS_LATEST]
    );
}

//Process the function resources
function defaultProcessFunctionResource($parent_odin_path,$resource_id,$req_resource_versoin){
  global $gArrayCoinTypeSet;
  
  $tmp_posn_func_flag=strpos($resource_id,'(');
  $function_name=substr($resource_id,0,$tmp_posn_func_flag);
  $argvs_chunks=explode(",",substr($resource_id,$tmp_posn_func_flag+1,strlen($resource_id)-$tmp_posn_func_flag-2));

  //默认不带具体版本号，表示内容是动态生成的，且下一次同样标识请求的处理结果是相同的，允许缓存生效
  $resp_resource_versoin=""; 
  
  
  //带时间作为版本号，表示内容是动态生成的，且下一次同样标识请求的处理结果是不同的，缓存应禁止
  //$resp_resource_versoin=@strftime("20%y%m%d%H%M%S",time()); 
  
  //可具体实现对请求中req_resource_versoin的支持处理，允许或不支持返回指定历史结果
  if(strlen($req_resource_versoin)>0){
     return array('code'=>410,"msg"=>"History result not supported!");
  }

  if($function_name=='helloworld'){
    $tmp_function_result = array(
            'code'=>0,
            "result_data"=> "helloworld() OK"
        );
  }else if($function_name=='sum'){
    $sum_result=0;
    
    for($kk=0;$kk<count($argvs_chunks);$kk++){
        $sum_result += $argvs_chunks[$kk];
    }
    $tmp_function_result = array(
            'code'=>0,
            "result_data"=>array(
                    "sum"=>$sum_result,
                    "time1"=>time(),
                    "time2"=>\PPkPub\Util::formatTimestampForView(time())
                 )
        );
  }else{
    $tmp_function_result = array('code'=>404,"msg"=>"Not existed function:".$function_name);
  }
  
  if($tmp_function_result['code'] != 0 ) {
    return $tmp_function_result;
  }
  
  $array_result=array();
    
  /*
  //Only for debug
  $array_result['ppk_debug_info']=array(
    'function_name'=>$function_name,
    'function_argvs'=>$argvs_chunks,
    'time'=>@strftime("20%y-%m-%d %H:%M:%S",time()),
  );
  */  
  
  $str_local_uri=\PPkPub\ODIN::PPK_URI_PREFIX
                .$parent_odin_path
                ."/".$resource_id
                .\PPkPub\ODIN::PPK_URI_RESOURCE_MARK
                .$resp_resource_versoin;
                
  $str_content_type='text/json';
  
  return array(
              'code'=>0,
              'result_data'=>array(
                  'local_uri'=>$str_local_uri,
                  'content_type'=>$str_content_type,
                  'content'=>json_encode($tmp_function_result['result_data']),
                  \PPkPub\PTTP::PTTP_KEY_CACHE_AS_LATEST=>\PPkPub\AP::DYNAMIC_CACHE_AS_LATEST,
              )  
          );
}