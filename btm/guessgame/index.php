<?php
/* PPK JoyBlock DEMO based Bytom Blockchain */
/*         PPkPub.org  20180917             */  
/*    Released under the MIT License.       */

require_once "ppk_joyblock.inc.php";

//查询带有图形数据的retire交易
$array_sets=array();

$tmp_url=BTM_NODE_API_URL.'list-transactions';
//$tmp_post_data='{"account_id": "'.BTM_NODE_API_ACCOUNT_ID_PUB.'"}';
$tmp_post_data='{"unconfirmed":true}';

$obj_resp=commonCallBtmApi($tmp_url,$tmp_post_data);

if(strcmp($obj_resp['status'],'success')===0){
  for($kk=0;$kk<count($obj_resp['data']);$kk++){
    //echo "<!-- ",$obj_resp['data'][$kk]['tx_id'],"-->\n";
    for($pp=0;$pp<count($obj_resp['data'][$kk]['outputs']);$pp++){
      $tmp_out=$obj_resp['data'][$kk]['outputs'][$pp];
      if($tmp_out['type']=='retire' && $tmp_out['asset_id']==JIYBLOCK_TOEKN_ASSET_ID ){
        $tmp_tx_data=getBtmTransactionDetail($obj_resp['data'][$kk]['tx_id']);
        if($tmp_tx_data!=null){
          $obj_set=parseGameRecordFromBtmTransaction($tmp_tx_data);
          if($obj_set!=null)
            $array_sets[]=$obj_set;
        } 
      }
    }
  }
}

//print_r($array_sets);

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PPkJoy-我画你猜（基于比原链+PPk开放协议的DAPP小游戏Demo）</title>
<style type="text/css">
  * {
      margin: 0;
      padding: 0;
  }

  body {
      background-color: #ffc772;
      background-image:url(bg1.png);
      background-repeat:no-repeat;
      width: 1000px;
  }
  
  #gamenavi {
      left: 600px;
      top:  0px;
      width: 400px;
      height: 50px;
      margin: 0px auto;
      position: absolute;
      font-size:9px;
  }
  
  #popwin {
      left: -600px;
      top:  0px;
      width: 400px;
      height: 400px;
      background-color: #eeeeee;
      margin: 10px;
      position: absolute;
      font-size:9px;
      
      border: 5px solid #dddddd;
      box-shadow: 5px 5px 6px rgba(50, 50, 50, 0.4);
      -webkit-transition: all 0.5s ease-in;
      -moz-transition: all 0.5s ease-in;
      -ms-transition: all 0.5s ease-in;
      -o-transition: all 0.5s ease-in;
      transition: all 0.5s ease-in;
  }
  
  .square {
            width: 280px;
            height: 300px;
            background-color: #fff; 
            margin: 10px auto;
            font-size:9px;
            position: absolute;
            
            border: -2px solid #dddddd;
            box-shadow: 2px 2px 3px rgba(50, 50, 50, 0.4);
            -webkit-transition: all 0.5s ease-in;
            -moz-transition: all 0.5s ease-in;
            -ms-transition: all 0.5s ease-in;
            -o-transition: all 0.5s ease-in;
            transition: all 0.5s ease-in;
        }
</style>
</head>
<body style="gamenavi">
<div id="gamenavi">
<p>PPkPub.org 20180917 V0.3a , <?php echo  '(Bytom network id: ',$gStrBtmNetworkId,')';?></p>
<!--<p>试试猜猜下面的谜图，首先猜对者抢得小红包。</p>-->
<p>欢迎发布你的谜图到Bytom比原链上并悬赏让大家来猜，<br>点选画布尺寸开始体验：[<a href="draw.php?size=8"> 小 </a>] [<a href="draw.php?size=16"> 中 </a>] [<a href="draw.php?size=32"> 大 </a>]</p>
<p><a href="faucet.php">免费领取比原测试币深入体验...</a></p>
</div>

<?php
for($ss=0;$ss<count($array_sets) && $ss<6;$ss++){
  $obj_set=$array_sets[$ss];
  $str_img_data_url=$obj_set['img_data_url'];
  $str_guess_contract_uri=$obj_set['guess_contract_uri'];
  $str_remark=hexToStr($obj_set['remark_hex']);
  $str_guess_odin_uri=ODIN_PPKJOY_BTM_RESOURCE.$obj_set['tx_id'];
  $str_pub_time = formatTimestampForView($obj_set['block_time'],false);
  
  if(stripos($str_guess_contract_uri,ODIN_BTM_CONTRACT)===0){
      $str_guess_contract_id=substr($str_guess_contract_uri,strlen(ODIN_BTM_CONTRACT));
      //获取猜谜合约状态
      $is_guess_contract_active=isBtmOuputUnspent($str_guess_contract_id,true);
      
  }else if(stripos($str_guess_contract_uri,'ppk:')!==0){
      $str_guess_contract_uri='';
      $is_guess_contract_active=false;
  }
  
  $guess_status=0;
  if( !$is_guess_contract_active ){
      if($obj_set['block_height']>0)
          $guess_status=1;  //已解锁
      else
          $guess_status=2;  //未确认
  }

  $leftx = 55+($ss % 3)*300 ;
  $topy  = 65+floor($ss / 3)*310;
  
  echo '<div class="square" style="left: ',$leftx,'px;top: ',$topy,'px;" onclick="popDetail(';
  echo $ss,",",$leftx-10,",",$topy-10,",'",$str_pub_time,"',",$guess_status,",'",$str_guess_contract_id,"','",$str_remark,"','",$str_guess_odin_uri,"'";
  echo ')"><center>',$str_pub_time,'<br><img id="guess_img_',$ss,'" width="256" height="256" src="',$str_img_data_url,'" border=0><br>';
  
  if( $guess_status == 0 )
      echo '试试猜这个，首先猜对者抢得小红包...';
  else if( $guess_status == 1 )
      echo '<font color=#F00>该猜谜合约已被解锁，红包已被抢走了</font>';
  else if( $guess_status == 2 )
      echo '<font color=#00F>猜谜合约暂时不可用，等待交易被确认中...</font>';
  else  
      echo '状态未知';
    
  echo '</center></div>';
}

echo "<!--当前接入比原网络ID：",$gStrBtmNetworkId," -->";
echo "<!--后端交易账户信息：",file_get_contents('LastAccountSN.txt'),"\n";
print_r($btm_netinfo);
echo "\n-->";
?>
<div id='popwin'>
<!--<p><font size="+2"><strong>详细信息</strong></font></p>-->
<p><img id="guess_img_detail" width="128" height="128" src="" border=0><strong>发布于 </strong><span id="pub_time">...</p></p>
<p><br></p>
<p><strong>猜谜合约地址：</strong><input type=text id="guess_contract_id" value="" size=35 disabled=true></p>
<p id="guess_status">...</p>
<p><br></p>
<p><strong>猜 图 提 示 ：</strong><textarea id="guess_remark" rows=3 cols=50 disabled=true></textarea></p>
<p><br></p>
<p><strong>跨链全网唯一<a href="http://ppkpub.org/#odinproject" target="_blank">ODIN标识</a>(URI)：</strong><br>
<textarea id="guess_odin_uri" rows=3 cols=50 disabled=true></textarea><br><br>
</p>
<p align="center"><button onclick="hideDetail();"> 关  闭 </button></p>
</div> 
<script type="text/javascript">
function popDetail(sn,leftx,topy,pub_time,guess_status,contract_id,remark_base64,odin_uri){
  document.getElementById('guess_img_detail').src=document.getElementById('guess_img_'+sn).src;
  
  document.getElementById('pub_time').innerHTML=pub_time;
  document.getElementById('guess_contract_id').value=contract_id;
  document.getElementById('guess_remark').value=remark_base64;
  document.getElementById('guess_odin_uri').value=odin_uri;
  
  if(guess_status==0)
      document.getElementById('guess_status').innerHTML='请使用比原链官方钱包输入上述合约地址来解锁获得奖励，具体操作方法请参考指南：<a href="http://8btc.com/thread-223386-1-1.html" target="_blank">http://8btc.com/thread-223386-1-1.html</a>';
  else if(guess_status==1)
      document.getElementById('guess_status').innerHTML='<font color=#F00>该猜谜合约已被解锁，红包已被抢走了</font>';
  else if(guess_status==1)
      document.getElementById('guess_status').innerHTML='<font color=#00F>猜谜合约暂时不可用，等待交易被确认中...</font>';
  else
      document.getElementById('guess_status').innerHTML='状态未知';
    
  var div=document.getElementById('popwin');
  div.style.left = leftx +'px';
  div.style.top  = topy +'px'; 
}

function hideDetail(){
  var div=document.getElementById('popwin');
  div.style.left = '-600px';
}
</script>
</body>
</html>
