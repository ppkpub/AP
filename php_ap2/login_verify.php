<?php
/**
 * 传入用户奥丁号、签名等信息进行登录验证
 */
require_once "ppk_pns.inc.php";

$qruuid=\PPkPub\Util::safeReqChrStr('qruuid');
$user_odin_uri=\PPkPub\Util::safeReqChrStr('user_odin_uri');
$auth_txt_hex=\PPkPub\Util::safeReqChrStr('auth_txt_hex');
$user_sign = \PPkPub\Util::safeReqChrStr('user_sign');
$response_type=\PPkPub\Util::safeReqChrStr('response_type');

$local_qruuid = generateSessionSafeUUID(); 

if(empty($qruuid) )
{
    $qruuid=$local_qruuid; 
}

//判断扫码验证与所要登录页面是在同一个WEB浏览器里
$in_same_browser = $qruuid===$local_qruuid;

$user_odin_uri = \PPkPub\ODIN::formatPPkURI($user_odin_uri,true);

if( !empty($user_odin_uri) ){
    $user_loginlevel=0;
    if(IS_DEMO && strcmp($user_odin_uri,DEMO_LOGIN_USER_ODIN_URI)==0){
        //允许测试体验帐户登录
        $user_loginlevel=DEMO_LOGIN_USER_LEVEL;
    }else if( !empty($auth_txt_hex)  && !empty($user_sign)){
        $str_original= \PPkPub\Util::hexToStr($auth_txt_hex);
        
        if(strpos($str_original,$qruuid)===false){
            $arr = array('code' => 500, 'msg' => '所签名的内容标识不一致. Invalid auth_txt without same qruuid!');
            responseResult($response_type,$arr);
            exit(-1);
        }
        
        $current_page_path=\PPkPub\Util::getCurrentPagePath();
        if(strpos($str_original,$current_page_path)!==0){
            $arr = array('code' => 500, 'msg' => '所签名的登录网址路径不一致. Mismatched login URL!');
            responseResult($response_type,$arr);
            exit(-1);
        }
        
        $arr=\PPkPub\PTAP01DID::authSignatureOfODIN($user_odin_uri,$str_original,$user_sign);
        
        if($arr['code']==0){
            $user_loginlevel=2;
        }else{
            responseResult($response_type,$arr);
            exit(-1);
        }
    }
    
    if($user_loginlevel<=0){
        $arr = array('code' => 504, 'msg' => '无效请求. Invalid request!');
        responseResult($response_type,$arr);
        exit(-1);
    }
    
    //保存登录状态
    $sql = "REPLACE INTO qrcodelogin (qruuid,user_odin_uri,user_sign,status_code) values ('$qruuid','$user_odin_uri','$user_sign',$user_loginlevel)";
    $result = @mysqli_query($g_dbLink,$sql);

    if($result===false)
    {
        $arr = array('code' => 504, 'msg' => '无效参数. Invalid argus');
        responseResult($response_type,$arr);
        exit(-1);
    }

    if($in_same_browser){
        header("location: ./");    
    }else{
        $arr = array(
            'code' => 0, 
            'msg' => '<h3>扫码验证奥丁号通过<br>ODIN verified OK</h3><br><P><font color="#FF7026">'.\PPkPub\Util::getSafeEchoTextToPage($user_odin_uri).'</font><br><br>请回到所登录设备或网站上继续访问。<br>Please go back the device or page to continue. </p>'
        );
        responseResult($response_type,$arr);
    }
    exit(0);

}

function responseResult($response_type,$array_result){
    if($response_type==='html'){
        echo '<html xmlns="http://www.w3.org/1999/xhtml"><head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ODIN verified OK</title>
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://netdna.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>';
        echo '<center><br><br>',$array_result['msg'],'</center>';
        echo "<p align=center><br><input type=button value=' << 返回 '  name=B1 onclick='history.back(-1)'></p>";
    }else if($response_type=='image'){//微信小程序的图片形式
        //需安装php-gd库  apt-get install php7.2-gd  
        //然后编辑php.ini 搜索“ extension=gd2 ” 把前面的“ ； ”去掉
        //再重启apache2
        
        //字体大小
        $size = 12;
        //字体类型，本例为宋体
        $font ="/usr/share/fonts/truetype/ubuntu/simhei.ttf";
        //显示的文字
        $text = $array_result['msg'];
        //创建一个长为500高为50的空白图片
        $img = imagecreate(500, 50);
        //给图片分配颜色
        imagecolorallocate($img, 0xff, 0xcc, 0xcc);
        //设置字体颜色
        $black = imagecolorallocate($img, 0, 0, 0);
        //将ttf文字写到图片中
        imagettftext($img, $size, 0, 10, 20, $black, $font, $text);
        //发送头信息
        header('Content-Type: image/gif');
        //输出图片
        imagegif($img);
    }else{
        echo json_encode($array_result);
    }
}

require_once "page_header.inc.php";

?>

<h3>确认扫码登录</h3>

<form class="form-horizontal"  action="login_verify.php" method="post" id="form_confirm">
<input type="hidden" name="qruuid"  id="qruuid" value="<?php \PPkPub\Util::safeEchoTextToPage($qruuid) ;?>">
<input type="hidden" name="auth_txt_hex" id="auth_txt_hex" value="">
<input type="hidden" name="user_sign" id="user_sign" value="">
<input type="hidden" name="response_type" value="html">

<div class="form-group">
    <label for="exist_odin_uri" class="col-sm-2 control-label">用户奥丁号</label>
    <div class="col-sm-10">
      <input type="text" class="form-control"  id="exist_odin_uri" name="user_odin_uri" value="<?php \PPkPub\Util::safeEchoTextToPage($user_odin_uri) ;?>"  onchange="getUserOdinInfo();"  >
    </div>
</div>
  
<p align="center"><input type='button' class="btn btn-success"  id="btn_use_exist_odin" value='请使用PPk浏览器、微信等APP来扫码登录！' onclick='authAsOdinOwner();' disabled="true"></p>

<input type=hidden id="user_name" value="" >
<input type=hidden id="user_avtar_url" value="http://ppkpub.org/images/user.png" >
<!--
<p align="center">对应的用户信息设置</p>
<div class="form-group">
    <label for="user_name" class="col-sm-2 control-label">用户昵称</label>
    <div class="col-sm-10">
      <input type=text class="form-control"  id="user_name" value="" >
    </div>
</div>

<div class="form-group">
    <label for="user_avtar_url" class="col-sm-2 control-label">头像URL</label>
    <div class="col-sm-10">
      <input type=text class="form-control"  id="user_avtar_url" value="http://ppkpub.org/images/user.png" >
    </div>
</div>

<div class="form-group">
    <label for="user_avtar_img" class="col-sm-2 control-label">头像预览</label>
    <div class="col-sm-10">
    <img id="user_avtar_img" width="128" height="128" src="http://ppkpub.org/images/user.png" >
    </div>
</div>
</form>
-->
<p align="center">QRUUID: <?php \PPkPub\Util::safeEchoTextToPage($qruuid) ;?></p>

<script src="js/common_func.js"></script>
<script type="text/javascript">
var mObjUserInfo;
var mTempDataHex;

var mBoolBytomLoaded=false;

document.addEventListener('chromeBytomLoaded', bytomExtension => {
    mBoolBytomLoaded=true;
    window.bytom.enable().then(accounts => {
        //init();
        alert("Bytom enabled");
    });

    initBytom();
});

function initBytom(){
    window.bytom.setChain('vapor').then(function (resp) {
        if(resp.status=="success"){
            console.log("Bytom enabled");
            
            currentAddress = window.bytom.defaultAccount.address;
            
            if(currentAddress.length>0){
                //document.getElementById("exist_odin_uri").value='<?php echo \PPkPub\PTAP02ASSET::COIN_TYPE_MOV ;?>'+currentAddress;
                document.getElementById("exist_odin_uri").value=currentAddress;
                
                document.getElementById("btn_use_exist_odin").value=" 使用比原MOV侧链地址登录 ";
                document.getElementById("btn_use_exist_odin").disabled=false;
            }
        }else{
            myalert(resp.status);
        }
    }).catch(function (err){
        myalert(err)
    })
}

window.onload=function(){
    init();
}

function init(){
    console.log("init...");
    if(typeof(PeerWeb) !== 'undefined'){ //检查PPk开放协议相关PeerWeb JS接口可用性
        console.log("PeerWeb enabled");
        
        document.getElementById("btn_use_exist_odin").value=" 确 认 登 录 ";
        
        var exist_odin_uri=getUserPPkURI(document.getElementById("exist_odin_uri").value);
        if(exist_odin_uri.length==0){
            //读取PPk浏览器内置钱包中缺省用户身份标识
            PeerWeb.getDefaultODIN(
                'callback_getDefaultODIN'  //回调方法名称
            );
        }else{
            getUserOdinInfo();
        }
    }else{ //检查其他浏览器类型
        console.log("PeerWeb not valid");
        //alert("PeerWeb not valid. Please visit by PPk Browser For Android v0.2.6 above.");
        
        /*
        var ua = navigator.userAgent.toLowerCase();//获取判断用的对象\
        if (ua.match(/MicroMessenger/i) == "micromessenger") {
            //在微信中打开
            
        }
        if (ua.match(/WeiBo/i) == "weibo") {
                //在新浪微博客户端打开
        }
        if (ua.match(/QQ/i) == "qq") {
                //在QQ空间打开
        }
        */
        var int=self.setInterval(function(){
                 if(!mBoolBytomLoaded)
                    window.location.href = "<?php echo WEIXIN_QR_SERVICE_URL;?>?login_confirm_url=<?php echo urlencode(\PPkPub\Util::getCurrentUrl());?>";
              },1000) //等待1秒后，如果发现没有比原插件，则调用PPk网页版小工具
    }
}

function makeConfirm () {		
    document.getElementById("form_confirm").submit();
}

function callback_getDefaultODIN(status,obj_data){
    if('OK'==status){
        if(obj_data.odin_uri!=null || obj_data.odin_uri.trim().length>0){
            document.getElementById("exist_odin_uri").value=obj_data.odin_uri;
            getUserOdinInfo();
        }
    }else{
        alert("请先在浏览器里配置所要使用的奥丁号！");
    }
}

//兼容DID的用户标识处理，得到以ppk:起始的URI
function getUserPPkURI(user_uri){ 
    if(user_uri.substring(0,"did:ppk:".length).toLowerCase()=="did:ppk:" ) { 
        user_uri=user_uri.substring("did:".length);
    }
    return user_uri;
}

function getUserOdinInfo(){
    //document.getElementById("btn_use_exist_odin").disabled=true;
    var exist_odin_uri=getUserPPkURI(document.getElementById("exist_odin_uri").value);
    //读取用户身份标识URI对应说明
    PeerWeb.getPPkResource(
        exist_odin_uri,
        'content',
        'callback_getUserOdinInfo'  //回调方法名称
    );
}

function callback_getUserOdinInfo(status,obj_data){
    if('OK'==status){
        try{
            var content=window.atob(obj_data.content_base64);
            //var content=obj_data.content_base64;
            //alert("type="+obj_data.type+" \nlength="+obj_data.length+"\nurl="+obj_data.url+"\ncontent="+content);
            mObjUserInfo = JSON.parse(content);
            
            var default_avtar_url='http://ppkpub.org/images/user.png';
            var exist_odin_uri=document.getElementById("exist_odin_uri").value;
            
            if(typeof(mObjUserInfo.attributes) !== 'undefined'){  //DID格式的用户定义
                document.getElementById("user_name").value=mObjUserInfo.attributes.name;
                document.getElementById("user_avtar_url").value=mObjUserInfo.attributes.avtar;
                //document.getElementById('user_avtar_img').src=mObjUserInfo.attributes.avtar;
            }else if(typeof(mObjUserInfo.title) !== 'undefined'){  //直接使用奥丁号的属性
                document.getElementById("user_name").value=mObjUserInfo.title.length>0 ? mObjUserInfo.title : exist_odin_uri ;
                document.getElementById("user_avtar_url").value=default_avtar_url;
                //document.getElementById('user_avtar_img').src=default_avtar_url;
            }else{
                document.getElementById("user_name").value="anonymous";
                document.getElementById("user_avtar_url").value=default_avtar_url;
                //document.getElementById('user_avtar_img').src=default_avtar_url;
            }
            
            document.getElementById("btn_use_exist_odin").disabled=false;
        }catch(e){
            alert("获得的用户信息有误!\n"+e);
        }
    }else{
        alert("无法获取对应用户信息！\n请检查确认下述奥丁号:\n"+document.getElementById("exist_odin_uri").value);
    }
}

function authAsOdinOwner(){
    var exist_odin_uri=getUserPPkURI(document.getElementById("exist_odin_uri").value);
    var requester_uri='<?php echo APP_BASE_URL;?>';
    var auth_txt=requester_uri+','+exist_odin_uri+','+document.getElementById("qruuid").value;  //需要签名的原文

    //alert('auth_txt:'+auth_txt);
    mTempDataHex = stringToHex(auth_txt);
    document.getElementById("auth_txt_hex").value=mTempDataHex;
    
    if(typeof(PeerWeb) !== 'undefined'){
        //请求PeerWeb插件用指定资源密钥来生成签名
        PeerWeb.signWithPPkResourcePrvKey(
            exist_odin_uri,
            requester_uri ,
            mTempDataHex,
            'callback_signWithPPkResourcePrvKey'  //回调方法名称
        );
    }else if(typeof(window.bytom) !== 'undefined'){
        //请求Bytom插件
        if(exist_odin_uri.length == 0 ){
            alert("没有可用的钱包地址！\n请使用Bycoin钱包应用并授权访问账户信息再试下。");       
            return;
        }
        
        window.location.href= "bapp.php?address="+exist_odin_uri+"&qruuid=<?php \PPkPub\Util::safeEchoTextToPage($qruuid) ;?>";
    }else{
        alert("不支持的浏览器！");
    }
}

function callback_signWithPPkResourcePrvKey(status,obj_data){
    try{
        if('OK'==status){
        
            //alert("res_uri="+obj_data.res_uri+" \nsign="+obj_data.sign+" \algo="+obj_data.algo);
            
            document.getElementById("user_sign").value=obj_data.algo+":"+obj_data.sign;
        
            makeConfirm();
        }else{
            alert("无法签名指定资源！\n请检查确认该资源已配置有效的验证密钥.");
        }
    }catch(e){
        alert("获得的签名信息有误!\n"+e);
    }
}

</script>