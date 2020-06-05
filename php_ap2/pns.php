<?php
/*          PPK PNS Toolkit               */
/*         PPkPub.org  20200528           */  
/*    Released under the MIT License.     */
require_once "ppk_pns.inc.php";

if(strlen($g_currentUserODIN)==0){
  Header('Location: login.php');
  exit(-1);
}

$user_odin_uri=$g_currentUserODIN;

$array_odin_chunks=\PPkPub\ODIN::splitPPkURI($user_odin_uri);

if( strlen($array_odin_chunks['parent_odin_path'])!=0 ){
    echo "Only supprt ROOT ODIN now!";
    exit(0);
}

$user_root_odin = $array_odin_chunks['resource_id'];

$base_short_odin = \PPkPub\ODIN::convertLetterToNumberInRootODIN($user_root_odin );
$base_odin_uri = \PPkPub\ODIN::PPK_URI_PREFIX.$base_short_odin.\PPkPub\ODIN::PPK_URI_RESOURCE_MARK;

$base_odin_info=\PPkPub\PTTP::getRootOdinSettingByRemoteAPI($base_odin_uri);
$base_odin_set = @$base_odin_info['setting'];

$str_pns_url = trim(@$obj_setting->pns_url);
if(strlen($str_pns_url)==0)
    $str_pns_url = trim(@$base_odin_set->from_pns_url) ;

//统计该标识的相关解析设置记录
$related_pns_records=array();

$sqlstr = "select * from pns where  odin_uri='".addslashes($user_odin_uri)."' or base_odin_uri='".addslashes($user_odin_uri)."';";
$rs = mysqli_query($g_dbLink,$sqlstr);
if (false !== $rs) {
    while($row = mysqli_fetch_assoc($rs)){
        $related_pns_records[ $row['odin_uri'] ] = $row;
    }
}
//print_r($related_pns_records);


require_once "page_header.inc.php";
?>

<div id='pub_top'>
  <table width="100%" border="0">
  <tr>
  <td align="left" width="100">
  <img  style="float:left"  src="image/user.png" width=64 height=64>
  </td>
  <td>
  <h1><?php \PPkPub\Util::safeEchoTextToPage( $user_odin_uri );?> <a class="btn btn-warning btn-xs" role="button"  href="logout.php"><?php echo getLang('退出登录');?></a></h1>
  </td>
  </tr>
  </table>
</div>

<ul>
<div id='user_info'>
  <hr>
  <div class="form-group">
    <label for="user_odin_name" class="col-sm-2 control-label"><?php echo getLang('当前标识');?></label>
    <div class="col-sm-10">
       <span id='user_odin_name'><?php \PPkPub\Util::safeEchoTextToPage( $user_root_odin ); ?>  <a class="btn btn-primary" role="button" href="pns_set.php?odin_uri=<?php echo urlencode($user_root_odin);?>"><?php echo getLang('设置该标识的解析记录');?></a></span>
    </div>
  </div>
    
  <div class="form-group">
    <label for="base_odin_info" class="col-sm-2 control-label"><?php echo getLang('所属根奥丁号信息');?></label>
    <div class="col-sm-10">
     <span id='base_odin_info'><a href="http://tool.ppkpub.org:9876/odin-detail?odin=<?php \PPkPub\Util::safeEchoTextToPage( $base_short_odin); ?>" target="_blank"><?php \PPkPub\Util::safeEchoTextToPage( $base_short_odin); ?></a><br>
     <?php 
     if(strlen($str_pns_url)>0){
       echo getLang('该根奥丁号已设置标识托管(PNS)服务网址为'),' ',\PPkPub\Util::getSafeEchoTextToPage( $str_pns_url),'<br>';
       echo getLang('本PNS服务网址为'),' ',\PPkPub\Util::getSafeEchoTextToPage( APP_BASE_URL),'<br>';
       echo getLang('请确认两者一致，在此处设置的解析记录才会生效'),'<br>';
     }
     else{
       echo getLang('提示：该根奥丁号尚未指定标识托管服务，缺省自动使用本PNS服务仅供测试体验。'),'<br>';
       //echo '本PNS服务网址:',\PPkPub\Util::getSafeEchoTextToPage( APP_BASE_URL),'<br>';    
     }
     echo '<a href="https://ppkpub.org/docs/help_ppkbrowser/#s04" target="_blank">',getLang('如需指定根奥丁号的托管服务(PNS)网址配合实际应用需求，请参考PPk浏览器的使用说明'),'</a>';
     
     ?>
     </span>
    </div>
  </div>
</div>
</ul>

<?php

if($user_root_odin==$base_short_odin){
    $listEscaped = \PPkPub\ODIN::getEscapedListOfShortODIN($base_short_odin);
?>
  <h3><?php echo getLang('相关转义名称');?></h3>
  <form class="form-horizontal" action="pns_set.php" method="get">
  <div class="form-group">
    <label for="odin_uri" class="col-sm-2 control-label"><?php echo getLang('指定转义名称');?></label>
    <div class="col-sm-10">
     <input class="form-control" type="text" name="odin_uri" id="odin_uri" value="<?php \PPkPub\Util::safeEchoTextToPage( @$listEscaped[0] );?>">
     <button class="btn btn-primary" type="submit"  ><?php echo getLang('设置该转义名称的解析记录');?></button>
    </div>
  </div>
  
  </form>
<?php    
    for($ss=0;$ss<count($listEscaped ) ;$ss++){
        $tmp_odin_name=$listEscaped[$ss];
        
        $tmp_odin_uri=\PPkPub\ODIN::PPK_URI_PREFIX.strtolower($tmp_odin_name).\PPkPub\ODIN::PPK_URI_RESOURCE_MARK;
        
        echo '<a href="pns_set.php?odin_uri=',urlencode($tmp_odin_uri),'" ';
        if(isset($related_pns_records[$tmp_odin_uri])){
            echo ' class="btn btn-info btn-xs" role="button" ';
        }
        
        echo '>',\PPkPub\Util::getSafeEchoTextToPage($tmp_odin_name),'</a> , ';
    }
}
?>

<br>

<?php
require_once "page_footer.inc.php";
?>