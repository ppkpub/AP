<?php
/* PPK JoyBlock DEMO based Bytom Blockchain */
/*         PPkPub.org  20180917             */  
/*    Released under the MIT License.       */

require_once "common_func.php";

/* CoomonDefine */
define('PPK_JOY_FLAG','PJOY');
define('BTM_ASSET_ID','ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff');
define('TX_GAS_AMOUNT_mBTM',100);
define('BTM_NODE_API_URL','http://x.x.x.x:9888/');  //此处配置你的比原API的访问地址
define('BTM_EXPLORER_API_URL','https://blockmeta.com/api/v2/');

define('ODIN_PPKJOY_ROOT','ppk:JOY/');
define('ODIN_PPKJOY_BTM_RESOURCE','ppk:JOY/guessgame/bytom/');
define('ODIN_BTM_ROOT','ppk:BTM/');
define('ODIN_BTM_CONTRACT',ODIN_BTM_ROOT.'contract/');
define('ODIN_BTM_TRANSACTION',ODIN_BTM_ROOT.'tx/');
define('ODIN_BTM_ASSET',ODIN_BTM_ROOT.'asset/');

define('BTM_BONUS_TOKEN_AMOUNT',100);

//查询网络信息，注意需根据你的节点账户信息相应配置，可以多个账户，相应提供账户ID，密码和缺省比原地址
$tmp_url=BTM_NODE_API_URL.'net-info';
$obj_resp=commonCallBtmApi($tmp_url,"");
if(strcmp($obj_resp['status'],'success')!==0){
  echo "Network Error! Please retry later...";
  exit(-1);
}
$btm_netinfo=$obj_resp['data'];
$gStrBtmNetworkId=$btm_netinfo['network_id'];
if(strcasecmp($gStrBtmNetworkId,'mainnet')==0){
  //mainnet
  define('JOYBLOCK_TOEKN_ASSET_ID','xxxxxxxxxxxxxxxxxxxxxxxxxxxx');
  define('FUND_BTM_ADDRESS','xxxxxxxxxx');
  define('BTM_NODE_API_TOKEN','');

  $gArrayNodeAccounts=array(
    array('id'=>'0IJ01RCU00A02','pwd'=>'xxxxxxx','address'=>'xxxxxxx'),
    array('id'=>'0IJ8PH48G0A02','pwd'=>'xxxxxxx','address'=>'xxxxxxx'),
    array('id'=>'','pwd'=>'xxxxxx','address'=>'xxxxxxx'),
    array('id'=>'','pwd'=>'xxxxxx','address'=>'xxxxxxx'),
    array('id'=>'','pwd'=>'xxxxxx','address'=>'xxxxxxx'),
  );
}else if(strcasecmp($gStrBtmNetworkId,'solonet')==0){
  //solonet
  define('JOYBLOCK_TOEKN_ASSET_ID','xxxxxxxxxxxxxxxxxxxxxxxxxxxx');
  define('FUND_BTM_ADDRESS','xxxxxxx');
  define('BTM_NODE_API_TOKEN','');

  $gArrayNodeAccounts=array(
    array('id'=>'0IIUI0SP00A02','pwd'=>'******','address'=>'xxxxxxx'),
    array('id'=>'0IMIUPSA00A02','pwd'=>'******','address'=>'xxxxxxx'),
    array('id'=>'0IPBF361G0A02','pwd'=>'******','address'=>'xxxxxxx'),
    array('id'=>'0IPBFF6L00A04','pwd'=>'******','address'=>'xxxxxxx'),
    array('id'=>'0IPBFNN4G0A06','pwd'=>'******','address'=>'xxxxxxx'),
  );
}else{
  //testnet
  define('JOYBLOCK_TOEKN_ASSET_ID','xxxxxxxxxxxxxxxxxxxxxxxxxxxx');  //自己发行的猜谜游戏资产ID
  define('FUND_BTM_ADDRESS','xxxxxxx'); //游戏发布者钱包地址
  define('BTM_NODE_API_TOKEN','');

  $gArrayNodeAccounts=array(  
    array('id'=>'0IOE8PIMG0A02','pwd'=>'******','address'=>'xxxxxxx'), 
    array('id'=>'0IOEFJFRG0A04','pwd'=>'******','address'=>'xxxxxxx'),
    array('id'=>'0IP461VU00A02','pwd'=>'******','address'=>'xxxxxxx'),
    array('id'=>'0IP46IDC00A04','pwd'=>'******','address'=>'xxxxxxx'),
    array('id'=>'0IP472OJ00A06','pwd'=>'******','address'=>'xxxxxxx'),
  );
}

//获得用于发送交易的可用钱包账户
function getNextAccountInfo(){
  global $gArrayNodeAccounts;
  
  $nextAccountSN=1+intval(file_get_contents('LastAccountSN.txt'));
  if($nextAccountSN>=count($gArrayNodeAccounts))
     $nextAccountSN=0;
  file_put_contents('LastAccountSN.txt',$nextAccountSN);
  return $gArrayNodeAccounts[$nextAccountSN];
}

//获得指定交易ID的详细信息
function getBtmTransactionDetail($tx_id){
  $tmp_url=BTM_NODE_API_URL.'list-transactions';
  $tmp_post_data='{"id":"'.$tx_id.'","detail": true,"unconfirmed":true}';

  $obj_resp=commonCallBtmApi($tmp_url,$tmp_post_data);
  if(strcmp($obj_resp['status'],'success')===0){
      return $obj_resp['data'][0];
  }else{
      return null;
  }
}

//从交易详情中提取出符合特定起始前缀标识的附加信息（HEX编码）
function parseSpecHexFromBtmTransaction($obj_tx_data,$str_flag){
  foreach($obj_tx_data['outputs'] as $tmp_out ){
    if($tmp_out['type']=='retire' && $tmp_out['asset_id']==JOYBLOCK_TOEKN_ASSET_ID ){
      $str_hex= $tmp_out['control_program'];
      //echo 'str_hex=',$str_hex;
      $str_flag_hex=strtohex($str_flag);
      $flag_posn=strpos($str_hex,$str_flag_hex);
      //echo 'flag_posn=',$flag_posn;
      if($flag_posn>0){ //符合特征
        return substr($str_hex,$flag_posn+strlen($str_flag_hex));
      }
    }
  }
  return null;
}

//从交易详情中解析出猜谜游戏定义数据
function parseGameRecordFromBtmTransaction($obj_tx_data){
  $str_hex=parseSpecHexFromBtmTransaction($obj_tx_data,PPK_JOY_FLAG);
  if(strlen($str_hex)>0){
    $obj_set=json_decode(hexToStr($str_hex),true);
    if(isset($obj_set['img_data_url'])>0){ //有效数据
      $obj_set['tx_id'] = $obj_tx_data['tx_id'];
      $obj_set['block_time'] = $obj_tx_data['block_time'];
      $obj_set['block_height'] = $obj_tx_data['block_height'];
      $obj_set['block_hash'] = $obj_tx_data['block_hash'];
      $obj_set['block_index'] = $obj_tx_data['block_index']; //position of the transaction in the block.

      return $obj_set;
    }
  }
  return null;
}

//检查指定OUTPUT_ID是否未被使用
function isBtmOuputUnspent($output_id,$is_contract){
  if(strlen($output_id)==0)
      return null;
  
  $tmp_url=BTM_NODE_API_URL.'list-unspent-outputs';
  $tmp_post_data='{"id":"'.$output_id.'","smart_contract":'.($is_contract?'true':'false').'}';

  $obj_resp=commonCallBtmApi($tmp_url,$tmp_post_data);
  if(strcmp($obj_resp['status'],'success')===0){
      return count($obj_resp['data'])>0 ? true:false;
  }else{
      return null;
  }
}

//发送比原交易
function sendBtmTransaction($tx_data,$current_account_info){
  $tmp_url=BTM_NODE_API_URL.'build-transaction';
  $obj_resp=commonCallBtmApi($tmp_url,$tx_data);

  if(strcmp($obj_resp['status'],'success')===0){
    $tmp_url=BTM_NODE_API_URL.'sign-transaction';
    $tmp_post_data='{"password":"'.$current_account_info['pwd'].'","transaction":'.json_encode($obj_resp['data']).'}';

    $obj_resp=commonCallBtmApi($tmp_url,$tmp_post_data);
    
    if(strcmp($obj_resp['status'],'success')===0){
        $tmp_url=BTM_NODE_API_URL.'submit-transaction';
        $tmp_post_data='{"raw_transaction":"'.$obj_resp['data']['transaction']['raw_transaction'].'"}';

        $obj_resp=commonCallBtmApi($tmp_url,$tmp_post_data);
    }
  }
  return $obj_resp;
}

//调用比原API的方法
function commonCallBtmApi(
         $api_url,    
         $post_data
    )
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $api_url);
    //设置头文件的信息不作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);

    return json_decode($data,true);
}

