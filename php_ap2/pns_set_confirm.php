<?php
/*        PPK ODIN Swap Toolkit           */
/*         PPkPub.org  20190415           */  
/*    Released under the MIT License.     */
require_once "ppk_swap.inc.php";

if(strlen($g_currentUserODIN)==0){
  Header('Location: login.php');
  exit(-1);
}

$sell_rec_id=\PPkPub\Util::safeReqNumStr('sell_rec_id');
$coin_type=\PPkPub\Util::safeReqChrStr('coin_type');
$bid_amount=\PPkPub\Util::safeReqNumStr('bid_amount');

if(strlen($sell_rec_id)==0){
  echo 'Invalid auction record ID.';
  exit(-1);
}

$sqlstr = "SELECT * FROM sells where sell_rec_id='$sell_rec_id';";
$rs = mysqli_query($g_dbLink,$sqlstr);
if (!$rs) {
  echo 'Not existed auction record.';
  exit(-1);  
}
$tmp_sell_record = mysqli_fetch_assoc($rs);
$asset_id=$tmp_sell_record['asset_id'] ;
$full_odin_uri=$tmp_sell_record['full_odin_uri'] ;

//检查被允许参拍该标识
if($g_currentUserODIN == $tmp_sell_record['seller_uri']  ){
  echo '不能参拍自己的资产. Unable to bid asset belong to yourself.';
  exit(-1);
}

//检查出价是否有效
if( $bid_amount<=0  ){
  echo '报价数额需大于0. Invalid bid amount.';
  exit(-1);
}
/*
if( $bid_amount < $tmp_sell_record['start_amount']  ){
  echo '报价数额不能少于拍卖底价. Invalid bid amount.';
  exit(-1);
}
*/

//$tmp_user_info=\PPkPub\PTAP01DID::getPubUserInfo($g_currentUserODIN);
//echo '<p>参拍用户: ',$g_currentUserODIN,'  , ',$tmp_user_info['register'],'</p>';


//检查是否已存在重复拍卖记录
//待加

//在本地数据库保存拍卖纪录

$remark=\PPkPub\Util::safeReqChrStr('remark');

$bid_utc=time();

$sql_str="insert into bids (bidder_uri,sell_rec_id,full_odin_uri,asset_id ,remark, coin_type, bid_amount, status_code, bid_utc) values ('$g_currentUserODIN','$sell_rec_id','$full_odin_uri','$asset_id','$remark','$coin_type','$bid_amount','".PPK_ODINSWAP_STATUS_BID."','$bid_utc')";
//echo $sql_str;
$result=@mysqli_query($g_dbLink,$sql_str);
if(!$result)
{
    echo '无效参数. Invalid argus';
    exit(-1);
}
$new_sell_rec_id=mysqli_insert_id($g_dbLink);

//发送通知
sendMsg(
    PPK_ODINSWAP_MSG_USER_SYSTEM,
    $tmp_sell_record['seller_uri'],
    PPK_ODINSWAP_MSG_TYPE_SYSTEM,
    '你拍卖的奥丁号['.\PPkPub\ODIN::PPK_URI_PREFIX.$tmp_sell_record['asset_id'].']收到了新报价.<a href="sell.php?sell_rec_id='.$sell_rec_id.'">查看&gt;&gt;</a>'
 );

require_once "page_header.inc.php";
?>

<center>
<p><?php echo getLang('对应奥丁号');?>[<?php \PPkPub\Util::safeEchoTextToPage( $asset_id );?>]<?php echo getLang('的报价已提交。');?></p> 
<p><a class="btn btn-success" role="button" href="sell.php?sell_rec_id=<?php echo $sell_rec_id;?>"><?php echo getLang('点击这里查看');?></a></p>
</center>

<?php
require_once "page_footer.inc.php";
?>