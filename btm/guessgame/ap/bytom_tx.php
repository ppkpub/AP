<?php
/*
  Response the game record in bytom transaction  
    PPkPub.org   20180915
  Released under the MIT License.
*/
require 'ap.common.inc.php';

define('BYTOM_TX_ODIN_PREFIX', PPK_URI_PREFIX.'527064.583/guessgame/bytom/' ); //含有游戏数据的比原交易对应的ODIN标识前缀
//ppk:JOY/guessgame/bytom/773958009d8b15ad6582aa727557b4a3b58b0c00c3e785d2a6cd5ee75e73105d

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

if( 0!=strncasecmp($str_pttp_uri,BYTOM_TX_ODIN_PREFIX,strlen(BYTOM_TX_ODIN_PREFIX)) ){
  respPttpStatus4XX( '400',"Bad Request : Invalid bytom-tx-uri " );
  exit(-1);
}

$odin_chunks=array();
$parent_odin_path="";
$resource_id="";
$req_resource_versoin="";
$resource_filename="";

$tmp_chunks=explode("#",substr($str_pttp_uri,strlen(BYTOM_TX_ODIN_PREFIX)));
$parent_odin_path="";
$bytom_tx_id=$tmp_chunks[0];

if(count($tmp_chunks)>=2){
  $req_resource_versoin=$tmp_chunks[1];
}

//echo "str_pttp_uri=$str_pttp_uri\n";
//echo "parent_odin_path=$parent_odin_path , resource_id=$resource_id , req_resource_versoin=$req_resource_versoin \n";

require_once "../ppk_joyblock.inc.php";

$tmp_tx_data=getBtmTransactionDetail($bytom_tx_id);
if($tmp_tx_data==null){
  respPttpStatus4XX( '404',"Bad Request : resource not exists. " );
  exit(-1);
}  

$obj_set=parseGameRecordFromBtmTransaction($tmp_tx_data);
$str_resp_content=json_encode($obj_set);

$resp_resource_versoin='1';//区块链上的交易数据版本缺省都是1

$str_resp_uri=BYTOM_TX_ODIN_PREFIX.$bytom_tx_id."#".$resp_resource_versoin;
$str_content_type='text/json';

$str_pttp_data=generatePTTPData( $str_resp_uri,'200','OK',$str_content_type,$str_resp_content );

//输出数据正文
header('Content-Type: text/json');
echo $str_pttp_data;
