<?php
/*          PPK PNS Toolkit               */
/*         PPkPub.org  20200528           */  
/*    Released under the MIT License.     */
require_once "ppk_pns.inc.php";

$odin_uri= strtolower(\PPkPub\Util::safeReqChrStr('odin_uri'));
$is_fast_upload=\PPkPub\Util::safeReqChrStr('form')==='fast_upload';
$is_confirm_update=\PPkPub\Util::safeReqChrStr('form')==='confirm_update';
$is_signed=\PPkPub\Util::safeReqChrStr('form')==='signed';

if(strlen($odin_uri)==0){
  \PPkPub\Util::error_exit('./', 'Invalid odin_uri.');
}

if( !\PPkPub\Util::startsWith( $odin_uri,\PPkPub\ODIN::PPK_URI_PREFIX )  ){
   $odin_uri = \PPkPub\ODIN::PPK_URI_PREFIX.$odin_uri.\PPkPub\ODIN::PPK_URI_RESOURCE_MARK;
}

if(strlen($g_currentUserODIN)==0){
  Header('Location: login.php?backpage=pns_set&odin_uri='.$odin_uri);
  exit(-1);
}



$array_odin_chunks=\PPkPub\ODIN::splitPPkURI($odin_uri);
if( strlen($array_odin_chunks['parent_odin_path'])!=0 ){
    echo "Only supprt ROOT ODIN now!";
    exit(0);
}
$root_odin = $array_odin_chunks['resource_id'];
$base_short_odin = \PPkPub\ODIN::convertLetterToNumberInRootODIN($root_odin );
//echo $root_odin,",",$base_short_odin ;
$base_odin_uri = \PPkPub\ODIN::PPK_URI_PREFIX.$base_short_odin.\PPkPub\ODIN::PPK_URI_RESOURCE_MARK;

$user_homepage_uri = \PPkPub\ODIN::PPK_URI_PREFIX.$root_odin.'/';

$array_odin_chunks=\PPkPub\ODIN::splitPPkURI( strtolower($g_currentUserODIN) );
$user_root_odin = $array_odin_chunks['resource_id'];
//echo '  user_root_odin=',$user_root_odin ;//
if( $user_root_odin != $root_odin && $user_root_odin !=$base_short_odin ){
    $listEscaped = \PPkPub\ODIN::getEscapedListOfShortODIN($base_short_odin);
    
    if( !in_array( $user_root_odin , $listEscaped )  ){
        echo "No right to update!";
        exit(0);
    }
}

$existed_pns_record = array();
$sqlstr = "select * from pns where  odin_uri='".$odin_uri."'";
//echo $sqlstr ;
$rs = mysqli_query($g_dbLink,$sqlstr);
if ($rs) {
  $existed_pns_record = mysqli_fetch_assoc($rs);
}

$show_fast_wizard = \PPkPub\Util::safeReqChrStr('form')==='wizard'
                   || !isset($existed_pns_record['pttp_data']);

require_once "page_header.inc.php";

if($is_fast_upload){
    $ap_demo_content = \PPkPub\Util::originalReqChrStr('ap_demo_content');
    
    require_once(PPK_LIB_DIR_PREFIX.'ipfs-php/IPFS.php');
    $ipfs = new Cloutier\PhpIpfsApi\IPFS("tool.ppkpub.org", "8080", "5001"); 
    $hash = $ipfs->add($ap_demo_content);
    $demo_url = "ipfs:".$hash;
    
    $ap_list =array( 'ap-0'=> array('url'=>$demo_url) );
    
    $array_set_data=array(
        "title" => \PPkPub\Util::safeReqChrStr('title'),
        "email" => \PPkPub\Util::safeReqChrStr('email'),
        "ap_set" => $ap_list,
        "vd_set" => array(
            'type' => \PPkPub\Util::safeReqChrStr('vd_set_type'),
            'pubkey' => \PPkPub\Util::safeReqChrStr('vd_set_pubkey'),
        ),
    );
    
    $str_set_data = json_encode($array_set_data);
    
    savePnsRecord($odin_uri,$base_odin_uri,$str_set_data,null);
    
    echo '<center>',getLang('已设置成功'),'</p>';
    echo '<p>',getLang('示例内容已上传到'),' ',$demo_url,'</p>';
    echo '<p>',getLang('你的标识已关联指向该内容，现在就能通过PPk浏览器访问下述PPk网址了'),'<br><a  class="btn btn-primary" role="button" href="http://tool.ppkpub.org/ap2/browser.html?go=', \PPkPub\Util::getSafeEchoTextToPage($user_homepage_uri),'">', \PPkPub\Util::getSafeEchoTextToPage($user_homepage_uri), '</a></p>';
    echo '<p><a href="https://ppkpub.org/docs/help_ppkbrowser/" target="_blank">',getLang('详见PPk浏览器使用说明...'),'</a>'; 
    
    echo '</center>';

}else if($is_signed){
    $str_set_data = \PPkPub\Util::originalReqChrStr('set_data');
    
    $base_odin_prvkey = \PPkPub\Util::originalReqChrStr('base_odin_prvkey');
    $tmp_key_set=null;
    if(strlen($base_odin_prvkey)>0){
        if( \PPkPub\Util::startsWith($base_odin_prvkey,'{') )
            $tmp_key_set=json_decode($base_odin_prvkey,true);
        else
            $tmp_key_set=array('prvkey' => $base_odin_prvkey);
    } 

    savePnsRecord($odin_uri,$base_odin_uri,$str_set_data,$tmp_key_set);
    
    echo '<p align="center">',getLang('已保存更新'),'</p>';
}else if($is_confirm_update){
    $ap_list=array();
    for($aa=0;$aa<5;$aa++){
        $tmp_ap_url = \PPkPub\Util::safeReqChrStr('ap'.$aa.'_url');
        if(strlen($tmp_ap_url)>0)
            $ap_list['ap-'.$aa] = array('url'=>$tmp_ap_url);
    }
    
    $array_set_data=array(
        "title" => \PPkPub\Util::safeReqChrStr('title'),
        "email" => \PPkPub\Util::safeReqChrStr('email'),
        "ap_set" => $ap_list,
        "vd_set" => array(
            'type' => \PPkPub\Util::safeReqChrStr('vd_set_type'),
            'pubkey' => \PPkPub\Util::safeReqChrStr('vd_set_pubkey'),
        ),
    );

    $base_odin_info=\PPkPub\PTTP::getRootOdinSettingByRemoteAPI($base_odin_uri);
    $base_odin_set = @$base_odin_info['setting'];
    $need_sign_by_base_odin_prvkey = strlen( @$base_odin_set->vd_set->pubkey )>0;
    
    $str_set_data = json_encode($array_set_data);
    
    /*
    if($need_sign_by_base_odin_prvkey){
        $array_pttp_data = json_decode($str_pttp_data,true);
        $str_header = $array_pttp_data['spec'];
        $str_payload = \PPkPub\PTTP::SIGN_MARK_DATA.$array_pttp_data['uri'].$array_pttp_data['metainfo'].$array_pttp_data['content'];
    }
    */
?>
<div class="row section">
  <div class="form-group">
    <label for="top_buttons" class="col-sm-5 control-label"><h3><?php echo getLang('确认解析设置'),' ',\PPkPub\Util::getSafeEchoTextToPage($odin_uri);?> </h3></label>
    <div class="col-sm-7" id="top_buttons" align="right">
    </div>
  </div>
</div>

<form class="form-horizontal" action="pns_set.php" method="post">
  <input type="hidden" name="odin_uri" value="<?php \PPkPub\Util::safeEchoTextToPage($odin_uri);?>">
  <input type="hidden" name="form" value="signed">

  <div class="form-group">
    <label for="set_data" class="col-sm-2 control-label"><?php echo getLang('解析设置数据');?></label>
    <div class="col-sm-10">
     <textarea class="form-control" name="set_data" id="set_data" rows=5><?php echo $str_set_data;?></textarea>
    </div>
  </div>
  
<?php 
if($need_sign_by_base_odin_prvkey){
?>
  <div class="form-group">
    <label for="base_odin_prvkey" class="col-sm-2 control-label"><?php echo getLang('根标识内容私钥');?></label>
    <div class="col-sm-10">
     <textarea class="form-control" name="base_odin_prvkey" id="base_odin_prvkey" rows=5></textarea>
     <br><?php echo getLang('需要提供根标识在比特币区块链上关联的内容验证私钥进行签名验证');?>
    </div>
  </div>
<?php 
}
?>

  <div class="form-group" align="center">
    <div class="col-sm-offset-2 col-sm-10">
      <button class="btn btn-warning btn-lg" type="submit"  ><?php echo getLang('确认更新');?></button>
    </div>
  </div>

</form>
<script type="text/javascript">

</script>
<?php
}else if( $show_fast_wizard ) {
    //使用快速向导
    $array_pttp_data = @json_decode($existed_pns_record['pttp_data'],true);
    $array_set_data = @json_decode($array_pttp_data['content'],true);
    
    $ap_demo_content = '';
    $default_ap_url = @$array_set_data['ap_set']['ap-0']['url'];
    if( \PPkPub\Util::startsWith( $default_ap_url,'ipfs:' ) ){
        $hash = substr( $default_ap_url,5);
        require_once(PPK_LIB_DIR_PREFIX.'ipfs-php/IPFS.php');
        $ipfs = new Cloutier\PhpIpfsApi\IPFS("tool.ppkpub.org", "8080", "5001"); 
        $ap_demo_content = $ipfs->cat($hash);
    }else if( \PPkPub\Util::startsWith( $default_ap_url,'http' ) ){
        $ap_demo_content = file_get_contents($default_ap_url);
    }
?>
<div class="row section">
  <div class="form-group">
    <label for="top_buttons" class="col-sm-5 control-label"><h3><?php echo getLang('快速设置向导'),' ',\PPkPub\Util::getSafeEchoTextToPage($odin_uri);?> </h3></label>
    <div class="col-sm-7" id="top_buttons" align="right">
    </div>
  </div>
</div>

<form class="form-horizontal" action="pns_set.php" method="post">
  <input type="hidden" name="odin_uri" value="<?php \PPkPub\Util::safeEchoTextToPage($odin_uri);?>">
  <input type="hidden" name="form" value="fast_upload">

  <div class="form-group">
    <label for="title" class="col-sm-2 control-label"><?php echo getLang('附注名称');?></label>
    <div class="col-sm-10">
     <input class="form-control" type="text" name="title" id="title" value="<?php echo @$array_set_data['title'];?>">
    </div>
  </div>
  
  <div class="form-group">
    <label class="col-sm-2 control-label"><?php echo getLang('展示内容');?></label>
    <div class="col-sm-10">
     <br><?php echo getLang('请在这里输入一段文字或网页内容，提交后会保存到网络上，并与你的标识关联，然后就能通过PPk浏览器访问了');?> 
    </div>
  </div>

  <div class="form-group">
    <label for="ap_demo_content" class="col-sm-2 control-label"></label>
    <div class="col-sm-10">
     <textarea class="form-control" name="ap_demo_content" id="ap_demo_content" rows=5><?php \PPkPub\Util::safeEchoTextToPage($ap_demo_content); ?></textarea>
    </div>
  </div>
  
  
  <div class="form-group" align="center">
    <div class="col-sm-offset-2 col-sm-10">
      <button class="btn btn-primary btn-lg" type="submit"  ><?php echo getLang(' 提 交 ');?></button>
    </div>
  </div>

</form>
<?php
}else{
    //完整设置界面
    $array_pttp_data = @json_decode($existed_pns_record['pttp_data'],true);
    $array_set_data = @json_decode($array_pttp_data['content'],true);
    //print_r($array_set_data);
?>
<div class="row section">
  <div class="form-group">
    <label for="top_buttons" class="col-sm-5 control-label"><h3><?php echo getLang('解析设置'),' ',\PPkPub\Util::getSafeEchoTextToPage($odin_uri);?> </h3></label>
    <div class="col-sm-7" id="top_buttons" align="right">
    <a class="btn btn-primary" role="button" href="pns_set.php?odin_uri=<?php echo urlencode($odin_uri);?>&form=wizard"><?php echo getLang(' 使用快速设置向导... ');?></a> </p>
    </div>
  </div>
</div>

<form class="form-horizontal" action="pns_set.php" method="post">
  <input type="hidden" name="odin_uri" value="<?php \PPkPub\Util::safeEchoTextToPage($odin_uri);?>">
  <input type="hidden" name="form" value="confirm_update">

  <div class="form-group">
    <label for="title" class="col-sm-2 control-label"><?php echo getLang('附注名称');?></label>
    <div class="col-sm-10">
     <input class="form-control" type="text" name="title" id="title" value="<?php echo @$array_set_data['title'];?>">
    </div>
  </div>
  
  <div class="form-group">
    <label for="email" class="col-sm-2 control-label"><?php echo getLang('电子邮箱');?></label>
    <div class="col-sm-10">
     <input class="form-control" type="text" name="email" id="email" value="<?php echo @$array_set_data['email'];?>">
    </div>
  </div>
  
  <div class="form-group">
    <label class="col-sm-2 control-label"><?php echo getLang('内容访问点(AP)');?></label>
    <div class="col-sm-10">
     <a  class="btn btn-success" role="button" href="http://tool.ppkpub.org/ap2/browser.html?go=<?php echo urlencode($user_homepage_uri);?>"><?php echo getLang('测试访问'),' ',\PPkPub\Util::getSafeEchoTextToPage($user_homepage_uri);  ?></a>
     <br><?php echo getLang('可以在下面设置1个或多个内容访问点，支持指向HTTP网址或者IPFS/DAT等分布式存储网址。');?> <a href="https://pinata.cloud/" target="_blank">第三方的IPFS上传工具</a> 
    </div>
  </div>

  <?php for($kk=0;$kk<5;$kk++){ ?>
  <div class="form-group">
    <label for="ap<?php echo $kk;?>_url" class="col-sm-2 control-label"><?php echo 'ap-',$kk;?>:</label>
    <div class="col-sm-10">
     <input class="form-control" type="text" name="ap<?php echo $kk;?>_url" id="ap<?php echo $kk;?>_url" value="<?php echo @$array_set_data['ap_set']['ap-'.$kk]['url'];?>">
    </div>
  </div>
  <?php } ?>
  
  <div class="form-group">
    <label class="col-sm-2 control-label"><?php echo getLang('内容可信验证设置');?></label>
    <div class="col-sm-10">
     <br><?php echo getLang('以下为可选高级设置，启用后可起到类似SSL证书的作用，增强访问内容的安全可信性。如需启用，可以通过本地或在线工具生成RSA 2048位密钥，并将公钥填到这里。');?> <a href="https://www.bm8.com.cn/webtool/rsa/" target="_blank">RSATOOL</a> 
    </div>
  </div>
  
  <div class="form-group">
    <label for="vd_set_type" class="col-sm-2 control-label"><?php echo getLang('编码格式');?></label>
    <div class="col-sm-10">
     <select class="form-control"  name="vd_set_type" id="vd_set_type">
        <option value="PEM">PEM</option>
        <option value="BASE64" <?php if( @$array_set_data['vd_set']['type']=='BASE64' ) echo "selected";?>>BASE64</option>
        <option value="HEX" <?php if( @$array_set_data['vd_set']['pubkey']=='HEX' ) echo "selected";?>>HEX</option>
     </select>
    </div>
  </div>

  <div class="form-group">
    <label for="vd_set_pubkey" class="col-sm-2 control-label"><?php echo getLang('公钥');?></label>
    <div class="col-sm-10">
     <textarea class="form-control" name="vd_set_pubkey" id="vd_set_pubkey" rows=3><?php echo @$array_set_data['vd_set']['pubkey'];?></textarea>
    </div>
  </div>
  
  
  <div class="form-group" align="center">
    <div class="col-sm-offset-2 col-sm-10">
      <button class="btn btn-primary btn-lg" type="submit"  ><?php echo getLang(' 提 交 ');?></button>
    </div>
  </div>

</form>

<?php
}

require_once "page_footer.inc.php";

function savePnsRecord($odin_uri,$base_odin_uri,$str_set_data,$content_key_set)
{       
    global $g_dbLink;
    
    $str_pttp_data = \PPkPub\AP::generatePttpData(  
          $odin_uri,
          $odin_uri,
          '200',
          'OK',
          'text/json',
          $str_set_data,
          \PPkPub\AP::DIR_CACHE_AS_LATEST ,
          $content_key_set
        );
    
    $str_pttp_data = addslashes($str_pttp_data);
    
    //生成数据库表    
    $sqlstr ='CREATE TABLE IF NOT EXISTS `pns` (  `odin_uri` varchar(80) NOT NULL,  `pttp_data` text NOT NULL,  `base_odin_uri` varchar(80) DEFAULT NULL,  PRIMARY KEY (`odin_uri`),  KEY `base_odin_uri` (`base_odin_uri`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
    mysqli_query($g_dbLink,$sqlstr);

    $sqlstr = "replace into pns ( odin_uri ,pttp_data ,base_odin_uri ) values ('$odin_uri','$str_pttp_data','$base_odin_uri')";    
    
    mysqli_query($g_dbLink,$sqlstr);
}