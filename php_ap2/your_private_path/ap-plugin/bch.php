<?php
/*
  Response the BitcoinCash coin interface  
    PPkPub.org   20200427
  Released under the MIT License.
*/
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'coin_common.inc.php';

define('BASE_COIN_ODIN_URI', \PPkPub\PTAP02ASSET::COIN_TYPE_BITCOINCASH ); //The base coin ODIN URI

//Process the function resources
function plugInProcessFunctionResource($parent_odin_path,$resource_id,$req_resource_versoin=null){
  global $gArrayCoinTypeSet;
  
  $tmp_posn_func_flag=strpos($resource_id,'(');
  $function_name=substr($resource_id,0,$tmp_posn_func_flag);
  $argvs_chunks=explode(",",substr($resource_id,$tmp_posn_func_flag+1,strlen($resource_id)-$tmp_posn_func_flag-2));
  $array_result=array();

  //默认不带具体版本号，表示内容是动态生成的，且下一次同样标识请求的处理结果是相同的，允许缓存生效
  $resp_resource_versoin=""; 

  //带时间作为版本号，表示内容是动态生成的，且下一次同样标识请求的处理结果是不同的，缓存应禁止
  //$resp_resource_versoin=@strftime("20%y%m%d%H%M%S",time()); 
  
  //可具体实现对请求中req_resource_versoin的支持处理，允许或不支持返回指定历史结果
  if(strlen($req_resource_versoin)>0){
     return array('code'=>410,"msg"=>"History result not supported!");
  }
  
  $coin_uri_prefix = \PPkPub\ODIN::PPK_URI_PREFIX.$parent_odin_path."/";
  
  $coin_info=$gArrayCoinTypeSet[$coin_uri_prefix];
  /*
  echo "parent_odin_path=",$parent_odin_path,",  resource_id=",$resource_id," , function_name=",$function_name,"\n";
  echo "coin_uri_prefix=",$coin_uri_prefix,"\n";
  print_r($coin_info);
  exit(-1);
  */
  if($function_name=='metadata'){
    //$resp_resource_versoin='1.0'; //Custom response resource version number
    $tmp_function_result = array(
            'code'=>0,
            "result_data"=> $coin_info
        );
  }else if($function_name=='marketPrice'){
    $tmp_function_result = array(
            'code'=>0,
            "result_data"=>array(
                    "bitcoin"=>'0.03123520',
                 )
        );
  }else if($function_name=='bindAddress'){
    $tmp_function_result = bindAddress($argvs_chunks[0],$coin_uri_prefix);
  }else if($function_name=='bindedAddress'){
    $tmp_function_result = bindedAddress($argvs_chunks[0],$coin_uri_prefix);
  }else if($function_name=='qrCodeOfPay'){
    $tmp_function_result = qrCodeOfPay($argvs_chunks[0]);
  }else if($function_name=='txOfQrCode'){
    $tmp_function_result = txOfQrCode($argvs_chunks[0]);
  }else{
    $tmp_function_result = array('code'=>404,"msg"=>"NOT EXISTED FUNCTION:".$function_name);
  }
  
  if($tmp_function_result['code'] != 0 ) {
    $tmp_function_result[\PPkPub\PTTP::PTTP_KEY_CACHE_AS_LATEST] = \PPkPub\PTTP::CACHE_AS_LATEST_NO_STORE ;
    return $tmp_function_result;
  }
    
  $array_result = $tmp_function_result['result_data'];
  
  //Only for debug
  $array_result['ppk_debug_info']=array(
    'function_name'=>$function_name,
    'function_argvs'=>$argvs_chunks,
    'time'=>@strftime("20%y-%m-%d %H:%M:%S",time()),
  );

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

function qrCodeOfPay($argv_json_hex)
{
    if(strlen($argv_json_hex)==0){
        return array('code'=>400,"msg"=>"No argus");
    }
    
    global $gArrayCoinTypeSet;
    
    $array_tx_define=@json_decode(@\PPkPub\Util::hexToStr($argv_json_hex),true);

    $tmp_to_address = \PPkPub\PTAP02ASSET::removeCoinPrefix($array_tx_define['to_uri'],BASE_COIN_ODIN_URI);
    $tmp_from_address = \PPkPub\PTAP02ASSET::removeCoinPrefix($array_tx_define['from_uri'],BASE_COIN_ODIN_URI);
    
    $asset_uri = $array_tx_define['asset_uri'];
    if( array_key_exists($asset_uri,$gArrayCoinTypeSet) )
        $pay_native_asset_id = $gArrayCoinTypeSet[$asset_uri]['native_id'];
    else
        $pay_native_asset_id = ""; //默认币种
    
    $amount=$array_tx_define['amount_satoshi'];

    $array_result=array(
        'qrcode'=>'http://tool.ppkpub.org/swap/qr_bch.php?from='.$tmp_from_address.'&to='.$tmp_to_address.'&amount='.$amount.'&asset='.$pay_native_asset_id.'&memo='.urlencode(@$array_tx_define['data']),
        'prompt'=>'Please scan with PPkAndroid APP, , and send transaction from the address('.\PPkPub\Util::friendlyLongID($tmp_from_address).')',
        'prompt_cn'=>'请使用PPk安卓应用扫码，从地址('.\PPkPub\Util::friendlyLongID($tmp_from_address).')发送交易',
    ) ;
        
    return array('code'=>0,"result_data"=>$array_result);
}

function txOfQrCode($argv_json_hex)
{
    if(strlen($argv_json_hex)==0){
        return array('code'=>400,"msg"=>"No argus");
    }
    
    global $gArrayCoinTypeSet;
    
    $array_tx_define=@json_decode(@\PPkPub\Util::hexToStr($argv_json_hex),true);

    $from_address=\PPkPub\PTAP02ASSET::removeCoinPrefix($array_tx_define['from_uri'],BASE_COIN_ODIN_URI); 
    $to_address=\PPkPub\PTAP02ASSET::removeCoinPrefix($array_tx_define['to_uri'],BASE_COIN_ODIN_URI); 
    $amount=$array_tx_define['amount_satoshi'];

    $tmp_content=@file_get_contents('https://bch-chain.api.btc.com/v3/address/'.$from_address.'/tx');

    $array_resp=@json_decode($tmp_content,true);
    
    if(array_key_exists('data',$array_resp)){
       for($kk=0;$kk<count($array_resp['data']['list']);$kk++){
           $tmp_tx=$array_resp['data']['list'][$kk];
           //print_r($tmp_tx);echo '<hr>';
           for($pp=0;$pp<count($tmp_tx['outputs']);$pp++){
               $tmp_out=$tmp_tx['outputs'][$pp];
               //print_r($tmp_out);
               if( $tmp_out['value'] == $amount &&  in_array($to_address,$tmp_out['addresses']) ){
                   return array('code'=>0,"result_data"=>array('txid'=>$tmp_tx['hash']));
               }
           }
        }
    }

    return array('code'=>404,"msg"=>"The transaction not found.");
}