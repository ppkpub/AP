<?php
/*
  PPk AP Common Defines&Functions 
    PPkPub.org   20190916
  Released under the MIT License.
*/
ini_set("display_errors", "On"); 
error_reporting(E_ALL | E_STRICT);

//Common defines
define('DID_URI_PREFIX','did:'); //DID  URI prefix
define('PPK_URI_PREFIX',"ppk:");
define('TEST_CODE_UTF8',"A测B试C");
define('COIN_TYPE_BITOIN','bitcoin:');   
define('COIN_TYPE_BITCOINCASH','ppk:bch/');   
define('COIN_TYPE_BYTOM','ppk:joy/btm/');   

//Root path
define('PLUS_SITE_HTDOCS_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR); 

//Local Config
require_once PLUS_SITE_HTDOCS_PATH.'config.inc.php';

//Include functions
require_once PLUS_SITE_HTDOCS_PATH.'common_func.php';
require_once PLUS_SITE_HTDOCS_PATH.'db_func.php';

//Get the signature verification settings corresponding to the requested resource
//获取请求资源对应的签名验证设置
function getOdinKeySet($parent_odin_path,$resource_id,$resp_resource_versoin=''){
  //echo "getOdinKeySet(): parent_odin_path=$parent_odin_path,resource_id=$resource_id,resp_resource_versoin=$resp_resource_versoin\n";
  
  $key_filename=AP_KEY_PATH.$parent_odin_path.'/'.$resource_id.'.json';
  
  //If the child extension ODIN does not have valid signature setting, try to use the signature setting of the parent ODIN.
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
  
  return getOdinKeySetFromFile($key_filename);

}

function getOdinKeySetFromFile($key_filename){
  $str_key_set = @file_get_contents($key_filename);
  if(!empty($str_key_set)){
    return json_decode($str_key_set,true);
  }else{
    return null;
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


//Compare resource version number
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
 Response exception status
*/
function respPttpStatus4XX( $status_code,$status_detail ) {
    echo generatePttpData(AP_DEFAULT_ODIN.'/'.$status_code.'#1.0',$status_code,$status_detail,'text/html','','no-store');
}


/*
 Generate a PTTP Data packet for responsing
*/
function generatePttpData( $str_resp_uri,$status_code,$status_detail,$str_content_type,$str_resp_content,$str_cache_control='public' ,$str_key_file=null) {
  $array_metainfo=array();

  //Base64 encoding is used by default for the contents which is not text/html or text/json type
  if( $str_content_type !='text/html' && $str_content_type !='text/json' ) {
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
  $str_sign=generatePttpSign($str_resp_uri,$str_encoded_data,$str_key_file);
  
  $obj_resp=array(
    "ver"  => 1, 
    "data" => $str_encoded_data,
    "sign" => $str_sign
  );
  
  return json_encode($obj_resp);
}


//Generate signature
function generatePttpSign($str_resp_uri,$str_resp_data,$str_key_file=null){
  //echo "generatePttpSign() str_resp_data_hex=",strToHex($str_resp_data),"\n";
  
  $parent_key_set=null;
  if(isset($str_key_file)){
    $parent_key_set=getOdinKeySetFromFile($str_key_file);
  }else{
    //Read the corresponding parent ODIN key
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
  }
  
  if($parent_key_set==null)
    return '';
  
  if(strlen($parent_key_set['RSAPrivateKey'])==0)
    return "";
  
  $vd_prv_key="-----BEGIN PRIVATE KEY-----\n".$parent_key_set['RSAPrivateKey']."-----END PRIVATE KEY-----";

  
  //$str_resp_sign=rsaSign($str_resp_data,$vd_prv_key,);
  $str_resp_sign=rsaSign($str_resp_data,$vd_prv_key,DEFAULT_SIGN_HASH_ALGO);

  $pttp_sign=DEFAULT_SIGN_HASH_ALGO."withRSA:".$str_resp_sign;
  
  return $pttp_sign;
  
}

//Obtain the actual wallet address  after removing the possible URI prefix
//去掉可能的币链标识前缀后得到实际使用的地址或资产标识
function removeCoinPrefix($address_uri,$coin_type){
    if($coin_type=='bitcoin'){ //Special handling for Bitcoin
        $coin_type='bitcoin:';
    }
    return startsWith($address_uri,$coin_type) ? substr($address_uri,strlen($coin_type)):$address_uri ;
}

//Verify the ODIN related signature
function verifyOwnerSign($user_odin_uri,$str_original,$user_sign){
    //Get the public key of the ODIN 
    $tmp_user_info = getPubUserInfo($user_odin_uri);
    $str_pubkey=$tmp_user_info['pubkey'];
    //echo "pubkey=",$str_pubkey,"\n";

    if(strlen($str_pubkey)==0 ){
        return false;
    }
    
    //Verification signature
    $array_sign_chunks=explode(':',$user_sign);
    if($array_sign_chunks[0]=='bitcoin_secp256k1'){ //Verify the signature with the Bitcoin signature algorithm
        $tmp_check_url=PTTP_NODE_API_URL.'check_sign.php?pubkey='.urlencode($str_pubkey).'&sign='.urlencode($array_sign_chunks[1]).'&algo='.urlencode($array_sign_chunks[0]).'&original='.urlencode($str_original);
        
        //echo $tmp_check_url;
        $result=trim(file_get_contents($tmp_check_url));
        //echo "result=",strToHex($result),"<br>";
        if(strcasecmp($result,'OK')!=0){
            return false;
        }
    }else if(!rsaVerify($str_original, $str_pubkey, $array_sign_chunks[1],$array_sign_chunks[0])){ //Other default attempts to verify with the RSA algorithm
        return false;
    }
    
    return true;
}

//Get PPk resource information
function  getPPkResource($ppk_uri){
    if( strcasecmp(substr($ppk_uri,0,strlen(DID_URI_PREFIX)),DID_URI_PREFIX)==0){ //Compatible with the user ID starting with did:
        $ppk_uri=substr($ppk_uri,strlen(DID_URI_PREFIX));
    }
    //echo '$ppk_uri=',$ppk_uri;
    $ppk_url=PTTP_NODE_API_URL.'?pttp_interest='.urlencode('{"ver":1,"hop_limit":6,"interest":{"uri":"'.$ppk_uri.'"}}');
    $tmp_ppk_resp_str=file_get_contents($ppk_url);
    //echo '$ppk_url=',$ppk_url,',$tmp_ppk_resp=',$tmp_ppk_resp_str;
    $tmp_obj_resp=@json_decode($tmp_ppk_resp_str,true);
    $tmp_data=@json_decode($tmp_obj_resp['data'],true);
    
    return $tmp_data;
}


//Get user information by ODIN
function  getPubUserInfo($user_odin){
    if(isset($g_cachedUserInfos[$user_odin]))
        return $g_cachedUserInfos[$user_odin];

    $default_user_info=array(
        'user_odin'=> $user_odin,
        'full_odin_uri'=> "",
        'name'=>"",
        'email'=>"",
        'avtar'=>"image/user.png"
    );
  
    $tmp_data=getPPkResource($user_odin);
    //print_r($tmp_data);
    if($tmp_data['status_code']==200){
        $default_user_info['full_odin_uri']=$tmp_data['uri'];
        $tmp_user_info=@json_decode($tmp_data['content'],true);
        //print_r($tmp_user_info);
        $default_user_info['original_content']=$tmp_data['content'];
        if($tmp_user_info!=null){
            if(array_key_exists('@type',$tmp_user_info) && $tmp_user_info['@type']=='PPkDID' ){ //In DID format
                $default_user_info['name']=$tmp_user_info['attributes']['name'];
                $default_user_info['email']=$tmp_user_info['attributes']['email'];
                $default_user_info['avtar']=$tmp_user_info['attributes']['avtar'];
                $default_user_info['register']='bytom:'.$tmp_user_info['attributes']['wallet_address'];
                $default_user_info['pubkey']=$tmp_user_info['authentication'][0]['publicKeyPem'];
            }else if(array_key_exists('register',$tmp_user_info)){ //Use the properties of ODIN
                $default_user_info['name']=@$tmp_user_info['title'];
                $default_user_info['email']=@$tmp_user_info['email'];
                $default_user_info['register']='bitcoin:'.$tmp_user_info['register'];
                $default_user_info['pubkey']= strlen(@$tmp_user_info['vd_set']['pubkey']>0) 
                                             ? $tmp_user_info['vd_set']['pubkey'] : $tmp_user_info['authentication'][0]['publicKeyHex'];
            }
        }
        $g_cachedUserInfos[$user_odin]=$default_user_info;
    }
    return $default_user_info;

}

//Locate the latest content from the static resource directory
function locateStaticResource($str_pttp_uri,$parent_odin_path,$resource_id,$req_resource_versoin){
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
        if(strlen(@$resp_resource_versoin)==0){
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
    $resp_resource_versoin = '1.0'; //Default static resource version

  $str_resp_uri=PPK_URI_PREFIX.$parent_odin_path.$resource_id."#".$resp_resource_versoin;

  $resource_path_and_filename = AP_RESOURCE_PATH.$parent_odin_path.$resource_filename;
  //echo "resource_path_and_filename=$resource_path_and_filename , resource_id=$resource_id , resp_resource_versoin=$resp_resource_versoin \n";

  if(!file_exists($resource_path_and_filename )){
    respPttpStatus4XX( '404',"Bad Request : resource not exists. " );
    exit(-1);
  }

  //Read resource content file
  $str_resp_content=@file_get_contents($resource_path_and_filename);
  
  $ext = pathinfo($resource_path_and_filename, PATHINFO_EXTENSION);
  //Simple process of content type
  if( strcasecmp($ext,'jpeg')==0 || strcasecmp($ext,'jpg')==0 || strcasecmp($ext,'gif')==0 || strcasecmp($ext,'png')==0)
    $str_content_type='image/'.$ext;
  else  
    $str_content_type='text/html';

  return array(
            'resp_uri'=>$str_resp_uri,
            'content_type'=>$str_content_type,
            'content'=>$str_resp_content,
        );
}

