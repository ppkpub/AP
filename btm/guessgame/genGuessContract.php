<?php
/* PPK JoyBlock DEMO baes Bytom Blockchain */
/* Send Gen Guess Contract Transaction */
require_once "ppk_joyblock.inc.php";

$answer = $_REQUEST['answer'];
if(strlen($answer)==0){
  echo 'ERROR:请提供预设的答案！Please input the answer!';
  exit(-1);
}

$answer_hash=hash( 'SHA3-256', unicode_encode($answer));

$tmp_post_data='{
  "contract": "contract RevealPreimage(hash: Hash) locks value {  clause reveal(string: String) {  verify sha3(string) == hash  unlock value }}",
  "args": [
    {
      "string": "'.$answer_hash.'"
    }
  ]
}';

$tmp_url=BTM_NODE_API_URL.'compile';
$obj_resp=commonCallBtmApi($tmp_url,$tmp_post_data);

if(strcmp($obj_resp['status'],'success')===0){
  $current_account_info=getNextAccountInfo();

  $tmp_url=BTM_NODE_API_URL.'build-transaction';
  $compiled_control_program=$obj_resp['data']['program'];
  $tmp_post_data='{
    "base_transaction": null,
    "actions": [
      {
        "account_id": "'.$current_account_info['id'].'",
        "amount": '.TX_GAS_AMOUNT_mBTM.'00000,
        "asset_id": "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff",
        "type": "spend_account"
      },
      {
        "account_id": "'.$current_account_info['id'].'",
        "amount": '.BTM_BONUS_TOKEN_AMOUNT.',
        "asset_id": "'.JIYBLOCK_TOEKN_ASSET_ID.'",
        "type": "spend_account"
      },
      {
        "amount": '.BTM_BONUS_TOKEN_AMOUNT.',
        "asset_id": "'.JIYBLOCK_TOEKN_ASSET_ID.'",
        "control_program": "'.$compiled_control_program.'",
        "type": "control_program"
      }
    ],
    "ttl": 0,
    "time_range": '.time().'
  }';

  $obj_resp=sendBtmTransaction($tmp_post_data,$current_account_info);
  if(strcmp($obj_resp['status'],'success')===0){
    //echo $obj_resp['data']['tx_id'];
    
    $tmp_url=BTM_NODE_API_URL.'get-transaction';
    $tmp_post_data='{"tx_id": "'.$obj_resp['data']['tx_id'].'"}';

    $obj_resp=commonCallBtmApi($tmp_url,$tmp_post_data);
    if(strcmp($obj_resp['status'],'success')===0){
        $outputs=$obj_resp['data']['outputs'];
        for($kk=0;$kk<count($outputs);$kk++){
          if(strlen($outputs[$kk]['control_program'])>0 && $outputs[$kk]['amount']==100){
            echo $outputs[$kk]['id'];
            exit(0);
          }
        }
    }
  }
  
}
echo "ERROR:创建比原合约交易失败，请稍候重试！Failed to send contract to Bytom blockchain!\n",json_encode($obj_resp);


