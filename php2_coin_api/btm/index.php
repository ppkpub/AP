<?php
/*
  Response the Bytom coin interface  
    PPkPub.org   20190916
  Released under the MIT License.
*/
require '../ap.common.inc.php';

define('FULLODIN_JOYDID_URI_PREFIX','ppk:527064.583/btm/'); //Allowed the long identification prefix in request 
define('SHORTODIN_JOYDID_URI_PREFIX','ppk:joy/btm/'); //Allowed the short identification prefix in request 

define('PTTP_DEFAULT_KEY_FILE', AP_KEY_PATH.'527064.583/btm.json' ); //Default PTTP signature private key file

//Extract PTTP request from HTTP GET/POST
$array_pttp_interest=array();
$str_pttp_interest='';
$str_pttp_uri='';

if(@$_GET['pttp_interest']!=null){ 
  $str_pttp_interest=trim($_GET['pttp_interest']);
}elseif(@$_POST['pttp_interest']!=null){ 
  $str_pttp_interest=trim($_POST['pttp_interest']);
}

if(strlen($str_pttp_interest)>0){
  //Extract Interest URI of PTTP
  $array_pttp_interest=json_decode($str_pttp_interest,true);
  $str_pttp_uri=$array_pttp_interest['interest']['uri'];
}

if(!isset($str_pttp_uri)){
  respPttpStatus4XX( '400',"Bad Request : no valid uri " );
  exit(-1);
}

//Determine the resource prefix used by the response
if( 0==strncasecmp($str_pttp_uri,FULLODIN_JOYDID_URI_PREFIX,strlen(FULLODIN_JOYDID_URI_PREFIX))
  ){
  $str_reply_odin_prefix=FULLODIN_JOYDID_URI_PREFIX;
}else if( 0==strncasecmp($str_pttp_uri,SHORTODIN_JOYDID_URI_PREFIX,strlen(SHORTODIN_JOYDID_URI_PREFIX))
  ){
  $str_reply_odin_prefix=SHORTODIN_JOYDID_URI_PREFIX;
}else{
  respPttpStatus4XX( '400',"Bad Request : not supported uri: ".$str_pttp_uri );
  exit(-1);
}

//Parse PTTP Interest request
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

$tmp_posn_func_flag=strpos($resource_id,'(');
if($tmp_posn_func_flag!==false){
  //If the resource_id contains characters '(',  processed as calling function with parameters
  //如果resource_id的含有字符(，按传入参数方式动态处理
  $obj_resp_content=processFunctionResource($str_pttp_uri,$parent_odin_path,$resource_id);
}else{
  //按静态内容处理 processed as static content
  $obj_resp_content=locateStaticResource($str_pttp_uri,$parent_odin_path,$resource_id,$req_resource_versoin);
}

//Generate and output the PTTP data package
$str_pttp_data=generatePTTPData( 
    $obj_resp_content['resp_uri'],
    '200',
    'OK',
    $obj_resp_content['content_type'],
    $obj_resp_content['content'],
    'public',
    PTTP_DEFAULT_KEY_FILE 
);
header('Content-Type: text/json');
echo $str_pttp_data;

//Process the function resources
function processFunctionResource($str_pttp_uri,$parent_odin_path,$resource_id){
  $tmp_posn_func_flag=strpos($resource_id,'(');
  $function_name=substr($resource_id,0,$tmp_posn_func_flag);
  $argvs_chunks=explode(",",substr($resource_id,$tmp_posn_func_flag+1,strlen($resource_id)-$tmp_posn_func_flag-2));

  $array_result=array();

  //This sample code dedaults to use the time as the dynamic resource version number
  $resp_resource_versoin=@strftime("20%y%m%d%H%M%S",time()).'.0';
  
  if($function_name=='metadata'){
    $array_result=array(
        "type"=>"coin",
        "name"=>"Bytom",
        "name_cn"=>"比原",
        "symbol"=>"BTM",
        "total_supply"=>"140700000000000000",
        "base_miner_fee"=>100000,
        "min_transfer_amount"=>103000,
        "decimals"=>8,
        "max_data_length"=>1000,
        "tx_explorer_url"=>"https://blockmeta.com/tx/",
    );
    $resp_resource_versoin='1.0'; //Custom response resource version number
  }else if($function_name=='marketPrice'){
    $array_result=array(
        "bitcoin"=>'0.00000747',
     );
  }else if($function_name=='bindAddress'){
    $argv_json_hex = $argvs_chunks[0];
    if(strlen($argv_json_hex)>0){
        $tmp_array=@json_decode(@hexToStr($argv_json_hex),true);
        
        $tmp_original=@$tmp_array['original'];
        //echo '$tmp_original=',$tmp_original,"\n";
        $tmp_sign=$tmp_array['sign'];
        //echo '$tmp_sign=',$tmp_sign,"\n";
        
        $tmp_orginal_array=@json_decode($tmp_original,true);
        //print_r($tmp_orginal_array);
        $owner_uri=$tmp_orginal_array['owner_uri'];
        $coin_uri=$tmp_orginal_array['coin_uri'];
        $address=$tmp_orginal_array['address'];
        $sign_timestamp=$tmp_orginal_array['timestamp'];
        
        if( $coin_uri!== COIN_TYPE_BYTOM ){
            respPttpStatus4XX( '400',"Not supported coin_uri: ".$coin_uri );
            exit(-1);
        }
        
        //The timestamp needs to be verified within 1 hour before and after the current time to prevent replication of historical signature data attacks.
        if(abs(time()-$sign_timestamp)>3600){ 
            respPttpStatus4XX( '401',"Invalid signature timestamp : " .$sign_timestamp );
            exit(-1);
        }
        
        if(verifyOwnerSign($owner_uri,$tmp_original,$tmp_sign)){
            if(bindAddressToDB($owner_uri,$coin_uri,$address,$tmp_original,$tmp_sign))
            {
                $array_result=array(
                    "owner_uri"=>$tmp_orginal_array['owner_uri'],
                    "address"=>$tmp_orginal_array['address'],
                    //"sign"=>$tmp_sign,
                );
            }else{
                respPttpStatus4XX( '400',"Invalid argus" );
                exit(-1);
            }
        }else{
            respPttpStatus4XX( '400',"Invalid signature" );
            exit(-1);
        }
    }else{
        respPttpStatus4XX( '400',"No argus" );
        exit(-1);
    }
  }else if($function_name=='bindedAddress'){
    $argv_json_hex = $argvs_chunks[0];
    if(strlen($argv_json_hex)>0){
        $owner_uri=@hexToStr($argv_json_hex);
        $coin_uri=COIN_TYPE_BYTOM;
        
        $array_result=getBindedAddressFromDB($owner_uri,$coin_uri);
        if($array_result==null){
            respPttpStatus4XX( '404',"Not binded address!" );
            exit(-1);
        }
    }else{
        respPttpStatus4XX( '400',"No argus" );
        exit(-1);
    }
  }else if($function_name=='qrCodeOfPay'){
    $argv_json_hex = $argvs_chunks[0];
    if(strlen($argv_json_hex)>0){
        $array_tx_define=@json_decode(@hexToStr($argv_json_hex),true);
        
        $array_result=array(
                'qrcode'=>'bytom:'.removeCoinPrefix($array_tx_define['to_uri'],COIN_TYPE_BYTOM).'?amount='.$array_tx_define['amount_satoshi'].'&asset='.removeCoinPrefix(@$array_tx_define['asset_uri'],COIN_TYPE_BYTOM).'&memo='.urlencode(@$array_tx_define['data']),
                'prompt'=>'Please scan with Bycoin APP, and send transaction from the address('.friendlyLongID(removeCoinPrefix($array_tx_define['from_uri'],COIN_TYPE_BYTOM)).')',
                'prompt_cn'=>'请使用Bycoin应用扫码，从地址('.friendlyLongID(removeCoinPrefix($array_tx_define['from_uri'],COIN_TYPE_BYTOM)).')发送交易',
            );
            
    }else{
        respPttpStatus4XX( '400',"No argus" );
        exit(-1);
    }
  }else if($function_name=='txOfQrCode'){
    $argv_json_hex = $argvs_chunks[0];
    if(strlen($argv_json_hex)>0){
        $array_tx_define=@json_decode(@hexToStr($argv_json_hex),true);

        $address=removeCoinPrefix($array_tx_define['to_uri'],COIN_TYPE_BYTOM); 
        $amount=$array_tx_define['amount_satoshi'];
        
        $tmp_content=@file_get_contents('https://blockmeta.com/api/v2/address/'.$address);

        $array_resp=@json_decode($tmp_content,true);

        if(@array_key_exists('transactions',$array_resp)){
           for($kk=0;$kk<count($array_resp['transactions']);$kk++){
               $tmp_tx=$array_resp['transactions'][$kk];
               if($tmp_tx['transaction_amount']==$amount || $tmp_tx['transaction_amount']==$amount+1 || $tmp_tx['transaction_amount']==$amount-1){
                   //Matches transaction record that match the corresponding address & amount
                   $array_result = array('txid'=>$tmp_tx['id']);
                   break;
               }
           }
        }
        
        if(!array_key_exists('txid',$array_result)){
           respPttpStatus4XX( '404',"The transaction not found." );
           exit(-1);
        }
 
    }else{
        respPttpStatus4XX( '400',"No argus" );
        exit(-1);
    }
  }else{
    respPttpStatus4XX( '404',"NOT EXISTED FUNCTION:".$function_name );
    exit(-1);
  }
  
  /*
  //Only for debug
  $array_result['ppk_debug_info']=array(
    'function_name'=>$function_name,
    'function_argvs'=>$argvs_chunks,
    'time'=>@strftime("20%y-%m-%d %H:%M:%S",time()),
  );
  */  
  
  $str_resp_uri=PPK_URI_PREFIX.$parent_odin_path.$resource_id."#".$resp_resource_versoin;
  $str_content_type='text/json';
  
  return array(
            'resp_uri'=>$str_resp_uri,
            'content_type'=>$str_content_type,
            'content'=>json_encode($array_result),
        );
}