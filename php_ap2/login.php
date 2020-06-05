<?php
require_once "ppk_pns.inc.php";

switch(@$_REQUEST['backpage']){ //严格检查和组织网址，避免注入风险
    case 'pns_set':
        $back_url='pns_set.php?odin_uri='.\PPkPub\Util::safeReqChrStr('odin_uri');
        break;
    default:
        $back_url='./';
}

if ($g_logonUserInfo!=null){ //Had logon
    header("location:".$back_url);
    exit(-1);
}

require_once "page_header.inc.php";
?>
<h3><?php echo getLang('以奥丁号登录');?></h3>

<?php 
//演示环境下允许显示测试登录入口
if(IS_DEMO){ 
?>
<div id="test_loginform_area" style="display:true;">
<form class="form-horizontal"  id="test_login_form" action="login_verify.php">
<div class="form-group">
    <label for="exist_odin_uri" class="col-sm-2 control-label"><?php echo getLang('测试奥丁号');?></label>
    <div class="col-sm-10">
      <input type="text" class="form-control"  id="test_odin_uri" value="<?php echo DEMO_LOGIN_USER_ODIN_URI;?>" readonly  >
    </div>
</div>
<div class="form-group">
    <label for="use_exist_odin" class="col-sm-2 control-label"></label>
    <div class="col-sm-10">
       <input type='button' class="btn btn-danger"  id="test_login_btn" value=' 测试体验点这里（无需验证直接登录） ' onclick='confirmExistODIN(document.getElementById("test_odin_uri").value,"","","");' ><br><br>
    </div>
</div>
</form>
<?php } ?>
<div id="loginform_area" style="display:none;">
<form class="form-horizontal"  id="form_login">
<div class="form-group">
    <label for="exist_odin_uri" class="col-sm-2 control-label"><?php echo getLang('你的奥丁号');?></label>
    <div class="col-sm-10">
      <input type="text" class="form-control"  id="exist_odin_uri" value="ppk:YourODIN*"  onchange="getUserOdinInfo();" readonly >
    </div>
</div>
<div class="form-group">
    <label for="use_exist_odin" class="col-sm-2 control-label"></label>
    <div class="col-sm-10">
      <input type='button' class="btn btn-success"  id="use_exist_odin" value=' <?php echo getLang('使用支持奥丁号的APP自主验证身份');?> ' onclick='authAsOdinOwner();' disabled="true">
    </div>
</div>
</form>
</div>

<div id="qrcode_area" align="center" style="display:none;">
<p><strong><?php echo getLang('使用支持奥丁号的APP扫码登录（如PPk浏览器、微信等）');?></strong></p>
    <div id="qrcode_img" ></div><br>
</div>

<p align="center">
<font size="-2">(<?php echo getLang('注：需升级到PPkBrowser安卓版0.305以上版本，');?><a href="https://ppkpub.github.io/docs/help_ppkbrowser/#s05"><?php echo getLang('请点击阅读这里的操作说明安装和使用。');?></a><?php echo getLang('更多信息，');?><a href="https://ppkpub.github.io/docs/" target="_blank"><?php echo getLang('可以参考奥丁号和PPk开放协议的资料进一步了解。');?></a>)</font>
</p>


<input type=hidden  id="user_name" value="" >
<input type=hidden  id="user_avtar_url" value="http://ppkpub.org/images/user.png" >

<!--
<p align="center">对应的用户信息</p>
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
-->

</form>


<script src="js/common_func.js"></script>
<script type="text/javascript" src="js/qrcode.js"></script>
<script type="text/javascript">
var mObjUserInfo;
var mObjUserPubKey;
var mTempDataHex;

window.onload=function(){
    init();
}

function init(){
    console.log("init...");
    if(typeof(PeerWeb) == 'undefined'){ //检查PPk开放协议相关PeerWeb JS接口可用性
        console.log("PeerWeb not valid");
        //alert("PeerWeb not valid. Please visit by PPk Browser For Android v0.2.6 above.");

        //显示扫码登录
        document.getElementById('qrcode_area').style.display="";
        makeQrCode();
    }else{
        console.log("PeerWeb enabled");
        //document.getElementById("use_exist_odin").disabled=false;
        
        //显示登录表单
        document.getElementById('loginform_area').style.display="";
        
        //读取PPk浏览器内置钱包中缺省用户身份标识
        PeerWeb.getDefaultODIN(
            'callback_getDefaultODIN'  //回调方法名称
        );
    }
}


//打开扫码登录
function makeQrCode() {		
    $.ajax({
            type: "GET",
            url: "login_uuid.php",
            data: {},
            success: function (result) {
                var obj_resp = (typeof(result)=='string') ? JSON.parse(result) : result ;
                if (obj_resp.code == 0) {
                    //在后端登记成功后，显示对应二维码
                    var poll_url=obj_resp.data.poll_url;
                    var confirm_url=obj_resp.data.confirm_url;
                    generateQrCodeImg(confirm_url);

                    //轮询 查询该qruuid的状态 直到登录成功或者过期(过期这里暂没判断，待完善)
                    var interval1= setInterval(function () {
                        console.log("Polling "+poll_url);
                        $.ajax({
                            type: "GET",
                            url: poll_url,
                            data: {},
                            success: function (result) {
                                var obj_resp = (typeof(result)=='string') ? JSON.parse(result) : result ;
                                if (obj_resp.code == 0) {
                                    //alert('扫码成功（即登录成功），进行跳转.....');
                                    //停止轮询
                                    clearInterval(interval1);
                                    //然后跳转
                                    self.location="<?php echo $back_url;?>";
                                    //document.getElementById("exist_odin_uri").value=obj_resp.data.user_odin_uri;
                                    //useExistODIN(obj_resp.level);
                                }
                            }
                        });
                    }, 2000);//2秒钟  频率按需求
                }
            }
        });
}

function generateQrCodeImg(str_url){
    var typeNumber = 0;
    var errorCorrectionLevel = 'L';
    var qr = qrcode(typeNumber, errorCorrectionLevel);
    qr.addData(str_url);
    qr.make();
    document.getElementById('qrcode_img').innerHTML = '<a href="'+str_url+'">' + qr.createImgTag() + '</a>';
}

function callback_getDefaultODIN(status,obj_data){
    if('OK'==status){
        if(obj_data.odin_uri!=null || obj_data.odin_uri.trim().length>0){
            document.getElementById("exist_odin_uri").value=obj_data.odin_uri;
            getUserOdinInfo();
        }
    }else{
        alert("<?php echo getLang('请先设置所要使用的奥丁号！');?>");
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
    //document.getElementById("use_exist_odin").disabled=true;
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
            
            document.getElementById("use_exist_odin").disabled=false;
        }catch(e){
            alert("<?php echo getLang('获得的用户信息有误!');?>\n"+e);
        }
    }else{
        alert("<?php echo getLang('无法获取对应用户信息！');?>\n<?php echo getLang('');?><?php echo getLang('奥丁号');?>:\n"+document.getElementById("exist_odin_uri").value);
    }
}

function authAsOdinOwner(){
    $.ajax({
        type: "GET",
        url: "login_uuid.php",
        data: {},
        success: function (result) {
            var obj_resp = (typeof(result)=='string') ? JSON.parse(result) : result ;
            if (obj_resp.code == 0) {
                //在后端登记成功，获得相应的登录事务号
                var qruuid=obj_resp.data.qruuid;
                
                var exist_odin_uri=getUserPPkURI(document.getElementById("exist_odin_uri").value);
                var requester_uri='<?php echo APP_BASE_URL;?>';
                var auth_txt=requester_uri+','+exist_odin_uri+','+qruuid;  //需要签名的原文
                //alert('auth_txt:'+auth_txt);
                mTempDataHex = stringToHex(auth_txt);
                
                //请求用指定资源密钥来生成签名
                PeerWeb.signWithPPkResourcePrvKey(
                    exist_odin_uri,
                    requester_uri ,
                    mTempDataHex,
                    'callback_signWithPPkResourcePrvKey'  //回调方法名称
                );
            }else{
                alert("<?php echo getLang('登录失败！');?>\n"+result);
            }
        }
    });
}

function callback_signWithPPkResourcePrvKey(status,obj_data){
    try{
        if('OK'==status){
            //alert("res_uri="+obj_data.res_uri+" \nsign="+obj_data.sign+" \algo="+obj_data.algo);

            /*
            //本地验证签名
            PeerWeb.verifySign(
                mTempDataHex,
                mObjUserPubKey ,
                obj_data.sign,
                obj_data.algo,
                'callback_verifySign'  //回调方法名称
            );
            */
            
            //提交服务器验证签名登录
            var exist_odin_uri=getUserPPkURI(document.getElementById("exist_odin_uri").value);
            confirmExistODIN(
                    exist_odin_uri,
                    mTempDataHex,
                    obj_data.algo+':'+obj_data.sign,
                    "<?php echo getLang('验证用户身份成功');?>\n<?php echo getLang('奥丁号');?>:"+exist_odin_uri
                );
        }else{
            alert("<?php echo getLang('无法签名指定资源！');?>\n<?php echo getLang('请检查确认该资源已配置有效的验证密钥。');?>");
        }
    }catch(e){
        alert("<?php echo getLang('获得的签名信息有误!');?>\n"+e);
    }
}
/*
function callback_verifySign(status,obj_data){
    try{
        if('OK'==status){
            var user_uri=document.getElementById("exist_odin_uri").value;
            alert("<?php echo getLang('验证用户身份成功');?>\n<?php echo getLang('奥丁号');?>:"+user_uri);
            useExistODIN(2);
        }else{
            alert("<?php echo getLang('用户身份标识签名验证未通过！');?>");
        }
    }catch(e){
        alert("<?php echo getLang('验证签名信息有误!');?>\n"+e);
    }
}
*/

function checkExistODIN(){
    var user_uri=document.getElementById("exist_odin_uri").value.trim();
    if(typeof(mObjUserInfo.vd_set) !== 'undefined'){
        //设置有奥丁号缺省格式的验证参数
        mObjUserPubKey=mObjUserInfo.vd_set.pubkey;
        //alert('mObjUserPubKey:'+mObjUserPubKey);
        authAsOdinOwner();
    }else if(typeof(mObjUserInfo.authentication) !== 'undefined'){
        //设置有兼容DID规范的验证参数
        if(typeof(mObjUserInfo.authentication[0].publicKeyHex) !== 'undefined')
            mObjUserPubKey=mObjUserInfo.authentication[0].publicKeyHex;
        else if(typeof(mObjUserInfo.authentication[0].publicKeyPem) !== 'undefined')
            mObjUserPubKey=mObjUserInfo.authentication[0].publicKeyPem;
        //alert('publicKey:'+mObjUserPubKey);
        authAsOdinOwner();
    }else{
        alert("<?php echo getLang('指定用户标识尚未设置身份密钥，无法验证登录！');?>");
    }
}

function confirmExistODIN(user_odin_uri,auth_txt_hex,user_sign,success_info){
    var confirm_url='login_verify.php?user_odin_uri='+encodeURIComponent(user_odin_uri)+'&auth_txt_hex='+auth_txt_hex+'&user_sign='+encodeURIComponent(user_sign);
    $.ajax({
        type: "GET",
        url: confirm_url,
        data: {},
        success: function (result) {
            var obj_resp = (typeof(result)=='string') ? JSON.parse(result) : result ;
            if (obj_resp.code == 0) {
                if(success_info.length>0)
                    alert(success_info);
                self.location="<?php echo $back_url;?>";
            }else{
                alert("<?php echo getLang('用户身份标识签名验证未通过！');?>\n"+result);
            }
        }
    });
}


</script>
<?php
require_once "page_footer.inc.php";
?>
