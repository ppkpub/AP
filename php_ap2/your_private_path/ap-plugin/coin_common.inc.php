<?php
/*
  The Common Defines of the MovTest interface  
    PPkPub.org   2020301
  Released under the MIT License.
*/
require_once(PPK_LIB_DIR_PREFIX.'PTAP01DID.php');
require_once(PPK_LIB_DIR_PREFIX.'PTAP02ASSET.php');

define('NEED_SIGNATURE_FOR_BINDING_ADDRESS',true);

$gArrayCoinTypeSet=json_decode('{"bitcoin:":{"type":"coin","name":"Bitcoin","name_cn":"\u6bd4\u7279\u5e01","symbol":"BTC","base_miner_fee":1000,"min_transfer_amount":1000,"decimals":8,"max_data_length":75,"tx_explorer_url":"https:\/\/btc.com\/"},"ppk:bch\/":{"type":"coin","name":"BitcoinCash","name_cn":"\u6bd4\u7279\u73b0\u91d1","symbol":"BCH","total_supply":"2100000000000000","base_miner_fee":1000,"min_transfer_amount":1000,"decimals":8,"max_data_length":220,"tx_explorer_url":"https:\/\/bch.btc.com\/"},"ppk:joy\/btm\/":{"type":"coin","name":"Bytom","name_cn":"\u6bd4\u539f","symbol":"BTM","total_supply":"140700000000000000","base_miner_fee":100000,"min_transfer_amount":103000,"decimals":8,"max_data_length":1000,"tx_explorer_url":"https:\/\/blockmeta.com\/tx\/"},"ppk:joy\/movtest\/":{"testnet":true,"type":"coin","name":"MOVTest-BTM","name_cn":"\u6bd4\u539f\u4fa7\u94feMOV\u6d4b\u8bd5-BTM","symbol":"MOVTEST-BTM","native_id":"ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff","total_supply":"140700000000000000","base_miner_fee":100000,"min_transfer_amount":103000,"decimals":8,"max_data_length":1000,"tx_explorer_url":"http:\/\/52.82.24.162:8081\/tx\/"},"ppk:joy\/movtest\/asset\/usdt\/":{"testnet":true,"type":"token","base_coin_uri":"ppk:joy\/movtest\/","name":"MOVTest-USDT","name_cn":"\u6bd4\u539f\u4fa7\u94feMOV\u6d4b\u8bd5-USDT","symbol":"MOVTEST-USDT","native_id":"9090fa534ec05423663be7c78e9571d7a04d6d5f567ce2df71eee838f944ff61","total_supply":"0","min_transfer_amount":10000,"decimals":6,"tx_explorer_url":"http:\/\/52.82.24.162:8081\/tx\/"},"ppk:joy\/mov\/asset\/usdt\/":{"testnet":false,"type":"token","base_coin_uri":"ppk:joy\/mov\/","name":"MOV-USDT","name_cn":"\u6bd4\u539fMOV-USDT","symbol":"MOV-USDT","native_id":"184e1cc4ee4845023888810a79eed7a42c02c544cf2c61ceac05e176d575bd46","total_supply":"0","min_transfer_amount":10000,"decimals":6,"tx_explorer_url":"http:\/\/vapor.blockmeta.com\/tx\/"}}  ',true);

function bindAddress($argv_json_hex,$asset_uri)
{
    if(strlen($argv_json_hex)==0){
        return array('code'=>400,"msg"=>"No argus");
    }
    
    $tmp_array=@json_decode(@\PPkPub\Util::hexToStr($argv_json_hex),true);
        
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
    
    if(  strcasecmp($coin_uri,$asset_uri)!=0 ){
        return array('code'=>400,"msg"=>"Mismatched coin_uri: ".$coin_uri . "\nPlease use " . $asset_uri );
    }
    
    if( \PPkPub\Util::startsWith($coin_uri, \PPkPub\PTAP02ASSET::COIN_TYPE_MOV ) 
        && !\PPkPub\Util::startsWith($address,'v')){
        return array('code'=>400,"msg"=>"The address of MOV should be start with v !");
    }else if( \PPkPub\Util::startsWith($coin_uri, \PPkPub\PTAP02ASSET::COIN_TYPE_MOVTEST ) 
        && !\PPkPub\Util::startsWith($address,'t')){
        return array('code'=>400,"msg"=>"The address of mov testnet should be start with t !");
    }
    
    //if(strlen($address)!=42){
    //    return array('code'=>400,"msg"=>"The address length should be 42 !");
    //}
    
    if(NEED_SIGNATURE_FOR_BINDING_ADDRESS){
        //The timestamp needs to be verified within 1 hour before and after the current time to prevent replication of historical signature data attacks.
        if( abs(time()-$sign_timestamp)>3600 ){ 
            return array('code'=>401,"msg"=>"Invalid signature timestamp : " .$sign_timestamp);
        }
        
        $array_result = \PPkPub\PTAP01DID::authSignatureOfODIN($owner_uri,$tmp_original,$tmp_sign);
        if($array_result['code'] != 0 ){
            return $array_result;
        }
    }
    
    if(bindAddressToDB($owner_uri,$coin_uri,$address,$tmp_original,$tmp_sign))
    {
        $array_result=array(
            "owner_uri"=>$tmp_orginal_array['owner_uri'],
            "address"=>$tmp_orginal_array['address'],
            //"sign"=>$tmp_sign,
        );
        return array('code'=>0,"result_data"=>$array_result);
    }else{
        return array('code'=>400,"msg"=>"Invalid argus");
    }


}  

function bindedAddress($argv_json_hex,$asset_uri, $base_coin_odin_uri = null)
{
    if(strlen($argv_json_hex)==0){
        return array('code'=>400,"msg"=>"No argus");
    }
    
    $owner_uri=@\PPkPub\Util::hexToStr($argv_json_hex);
    //echo "test:$owner_uri,$asset_uri;";
    
    $array_result = getBindedAddressFromDB($owner_uri,$asset_uri);
    
    if( $array_result==null && $base_coin_odin_uri!=null && $asset_uri!=$base_coin_odin_uri ){
        //尝试使用指定的基础币种绑定地址
        $array_result=getBindedAddressFromDB($owner_uri,$base_coin_odin_uri);
    }
    
    //尝试兼容用户标识的旧版本
    if( $array_result==null){
        $old_owner_uri = str_replace(\PPkPub\ODIN::PPK_URI_RESOURCE_MARK,'#',$owner_uri);
        //echo "test:$owner_uri,$old_owner_uri;$asset_uri";exit(-1);
        $array_result=getBindedAddressFromDB($old_owner_uri,$asset_uri);
        
        if( $array_result==null && $base_coin_odin_uri!=null && $asset_uri!=$base_coin_odin_uri ){
            //尝试使用指定的基础币种绑定地址
            $array_result=getBindedAddressFromDB($old_owner_uri,$base_coin_odin_uri);
        }
    }

    if($array_result==null){
        return array('code'=>404,"msg"=>"Not binded address!(owner_uri=$owner_uri ; asset_uri=$asset_uri)");
    }else{
        return array('code'=>0,"result_data"=>$array_result);
    }
}  


function bindAddressToDB($owner_uri,$coin_uri,$address,$original,$sign){
    $db_link=connectDB();
    
    $sql_str="replace into more_address_list (owner_uri,coin_type, address,original,sign) values ('$owner_uri','$coin_uri','$address','$original','$sign')";
    //echo $sql_str;exit(-1);
    return @mysqli_query($db_link,$sql_str);
}

function getBindedAddressFromDB($owner_uri,$coin_uri){
    $db_link=connectDB();
    
    $sql_str="select owner_uri,coin_type as coin_uri, address,original,sign from more_address_list where owner_uri='$owner_uri' and coin_type='$coin_uri'";
    //echo $sql_str;exit(-1);
    $rs=@mysqli_query($db_link,$sql_str);
    if (false !== $rs) {
        if($row = mysqli_fetch_assoc($rs)){
            $row['owner_uri'] = \PPkPub\ODIN::formatPPkURI($row['owner_uri'],true);
            return $row;
        }
    }
    
    return null;
}
