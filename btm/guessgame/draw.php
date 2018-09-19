<?php
/* PPK JoyBlock DEMO based Bytom Blockchain */
/*         PPkPub.org  20180917             */  
/*    Released under the MIT License.       */

require_once "ppk_joyblock.inc.php";

$square_size = floor($_GET['size']);
$square_size = $square_size>8 ? $square_size : 8;

$matrix_width=$square_size;
$matrix_height=$square_size;
$square_width_pixels=floor( 64*8  / $matrix_width );
$array_matrix_marked=array();
for($x=0;$x<$matrix_width;$x++){
  for($y=0;$y<$matrix_height;$y++){
      $array_matrix_marked[$x][$y]=1;
  }
}


?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PPkJoy发布画作</title>
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
  
  #container {
      left: 80px;
      top:  80px;
      width: <?php echo $matrix_width*$square_width_pixels;?>px;
      height: <?php echo $matrix_height*$square_width_pixels;?>px;
      margin: 20px auto;
      position: absolute;
  }

  #container img:hover {
      box-shadow: 15px 15px 20px rgba(50, 50, 50, 0.4);
      transform: rotate(0deg) scale(1.20);
      -webkit-transform: rotate(0deg) scale(1.20);
      z-index: 2;
  }

  #container img {
      border: -2px solid #dddddd;
      box-shadow: 2px 2px 3px rgba(50, 50, 50, 0.4);
      -webkit-transition: all 0.5s ease-in;
      -moz-transition: all 0.5s ease-in;
      -ms-transition: all 0.5s ease-in;
      -o-transition: all 0.5s ease-in;
      transition: all 0.5s ease-in;
      position: absolute;
      z-index: 1;
  }

  .square {
            width: <?php echo $square_width_pixels-1;?>px;
            height: <?php echo $square_width_pixels-1;?>px;
            background-color: #000; 
            position: absolute;
        }

  #yourmark {
      left: <?php echo $matrix_width*$square_width_pixels+100;?>px;
      top:  100px;
      width: 300px;
      height: <?php echo $matrix_height*$square_width_pixels;?>px;
      margin: 20px auto;
      position: absolute;
  }
</style>
</head>
<body>

<div id="container">
<?php 
for($y=0;$y<$matrix_height;$y++){
  for($x=0;$x<$matrix_width;$x++){
      $led_color=$array_matrix_marked[$x][$y]>0 ? '#fff':'#000';
      
      echo '<a href="#"><div id="square_'.$x.'_'.$y.'" class="square" style="left: '.($x*$square_width_pixels).'px;top: '.($y*$square_width_pixels).'px;background-color:',$led_color,'" onclick="clickSquare('.$x.','.$y.');"></div></a>';
  }
}

?>
</div>
<div id="gamenavi">
<p>PPkPub.org 20180917 V0.3a , <?php echo  '(Bytom network id: ',$gStrBtmNetworkId,')';?></p>
<p><br>重新选择画布尺寸：[<a href="draw.php?size=8"> 小 </a>] [<a href="draw.php?size=16"> 中 </a>] [<a href="draw.php?size=32"> 大 </a>]　　返回<a href="./">游戏主页</a></p>
</div>
<div id="yourmark">
<!--
<p>你的比原链账户:（待比原链类似Metamask浏览器插件出来完善）</p>
<p id='you_btm_address'>PPk2018...</p>
-->
<p>在左侧点击方块即可开始绘图。<br></p>
<hr>
<p>
生成比原链交易参数：<br>
猜谜合约地址：<input type=text id="guess_contract_id" value="" size=20 onchange="updateTransData();"  >
<br>
　或给定答案：<input type=text id="guess_answer" value="" size=10><input type='button' id="gen_guess_contract_btn" value='自动生成合约' onclick='genGuessContract();'>
<br>
<font size="-2">（注：自动生成合约如遇钱包交易繁忙请稍候再试）</font><br>
谜底提示说明：<input type=text id="guess_remark" value="" size=20 onchange="updateTransData();"  >
<br><br>
<form name="form_pub" id="form_pub" action="pub.php" method="post">
转账GAS费用：<input type="text" name="game_trans_fee_btm" id="game_trans_fee_btm" value="<?php echo TX_GAS_AMOUNT_mBTM/1000; ?>" size=10 readonly="true" style="background:#CCCCCC"> BTM<br>
Retire附加数据：<input type="text" name="game_trans_data_hex" id="game_trans_data_hex" value="" size=20 readonly="true" style="background:#CCCCCC" ><br>
<br>
　　　　　<input type='button' id="game_send_trans_btn" value=' 确认发布到比原链上 ' onclick='callMetamask();'> 
</form>
</p>

<!--
<p>二维码（可使用比原链钱包APP来扫码发送交易）:</p>
<p><img id="game_trans_qrcode" border=0 width=250 height=250 src="star.png" title="qrcode"></p>
<p><input type=text id="qrcode_text" value="..." size=30></p>
<hr>
</p>
<p><a target="_blank" href="https://bytom.io/"><img src="https://bytom.io/wp-content/uploads/2018/04/logo-white-v.png" alt="下载比原链钱包" width=200 height=50></a>
</p> 
-->
<p>预览：</p>
<center>
<canvas id="cvs" width="128" height="128"></canvas>
</center>
</div>
<!--
<script src="https://cdn.jsdelivr.net/gh/ethereum/web3.js/dist/web3.min.js"></script>
-->
<script type="text/javascript">
var MATRIX_MAX_WIDTH = <?php echo $matrix_width;?>;
var MATRIX_MAX_HEIGHT = <?php echo $matrix_height;?>;
var SQUARE_WIDTH_PIXELS = <?php echo $square_width_pixels;?>;
var MATRIX_MARK = <?php  echo json_encode($array_matrix_marked)?>;
var lastClickSquareX=0;
var lastClickSquareY=0;

var canvas;
var canvasContext;

window.addEventListener('load', function() {
    //document.getElementById('game_send_trans_btn').disabled = false;
    canvas = document.getElementById('cvs');
    canvasContext = canvas.getContext('2d');
    
    canvasContext.fillStyle= "#FFFFFF";
    canvasContext.fillRect(0,0,128,128);
});

function drawPoint(x, y, blackOrWhite) {
    var scale=128/MATRIX_MAX_WIDTH;
    canvasContext.fillStyle= blackOrWhite>0 ? "#FFFFFF" : "#000000" ;
    canvasContext.fillRect(x*scale,y*scale,1*scale,1*scale);
}


function genGuessContract(){
  if(document.getElementById('guess_answer').value.length == 0 ){
    alert("请输入有效的谜底答案才能生成猜谜合约！");
    return false;
  }
  
  document.getElementById("guess_contract_id").value="正在自动生成猜谜合约,请稍候...";
  document.getElementById("gen_guess_contract_btn").disabled=true;
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.open("GET","genGuessContract.php?answer="+document.getElementById("guess_answer").value);
  xmlhttp.send();
  xmlhttp.onreadystatechange=function()
  {
    if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
      document.getElementById("guess_contract_id").value=xmlhttp.responseText;
      document.getElementById("gen_guess_contract_btn").disabled=false;
    }
  }
}


function callMetamask() {
  if(document.getElementById('guess_contract_id').value.length == 0 ){
    alert("请输入有效的比原链猜谜合约ID！\nBytom猜谜合约使用参考指南：http://8btc.com/thread-223386-1-1.html");
    return false;
  }
  if(document.getElementById('game_trans_fee_btm').value.length == 0 ){
    alert('请输入有效的转账GAS费用，缺省为 <?php echo TX_GAS_AMOUNT_mBTM/1000; ?> BTM！');
    return false;
  }
  updateTransData();
  document.getElementById('form_pub').submit();
}

function clickSquare(x,y){ 
  resetAll();
  
  lastClickSquareX=x;
  lastClickSquareY=y;
  
  MATRIX_MARK[x][y]=MATRIX_MARK[x][y]>0 ? 0:1;
  
  var div=document.getElementById('square_'+x+'_'+y);
  div.style.backgroundColor= MATRIX_MARK[x][y] ? '#fff':'#000';
  div.style.border = " 1px solid #f00 ";
  div.style.width  = ""+(SQUARE_WIDTH_PIXELS-3)+'px';
  div.style.height = ""+(SQUARE_WIDTH_PIXELS-3)+'px';
  
  drawPoint(x,y,MATRIX_MARK[x][y]);
  
  updateTransData();
}

function updateTransData(){
  var game_trans_fee_btm = <?php echo TX_GAS_AMOUNT_mBTM/1000; ?>;

  var guess_contract_id=document.getElementById('guess_contract_id').value;
  if(guess_contract_id.length == 0 ){
    return false;
  }
  
  if(guess_contract_id.toLowerCase().indexOf('ppk:')!=0){
    guess_contract_id="<?php echo ODIN_BTM_CONTRACT;?>"+guess_contract_id;
  }
  var setting=new Object();
  setting.width=128;
  setting.height=128;
  setting.guess_contract_uri=guess_contract_id;
  setting.remark_hex= document.getElementById('guess_remark').value.length>0 ? 
                         stringToHex(utf16ToUtf8(document.getElementById('guess_remark').value))
                         :"";
  setting.img_data_url=canvas.toDataURL("image/png");                       
                         
 
  var game_trans_data="<?php  echo PPK_JOY_FLAG; ?>"+JSON.stringify(setting);
  console.log("game_trans_data="+game_trans_data);
  
  var game_trans_data_hex = stringToHex(game_trans_data);

  document.getElementById('game_trans_data_hex').value= game_trans_data_hex;
  
  //var btm_uri='bytom:'+document.getElementById('guess_contract_uri').value+'?value='+game_trans_fee_btm+'&data='+game_trans_data_hex;
  //document.getElementById('qrcode_text').value= btm_uri;
  //document.getElementById('game_trans_qrcode').src='http://qr.liantu.com/api.php?text='+encodeURIComponent(btm_uri);

}

function resetAll(){
  document.getElementById('game_trans_fee_btm').value=<?php echo TX_GAS_AMOUNT_mBTM/1000; ?>;
  document.getElementById('game_trans_data_hex').value='';
  
  //document.getElementById('qrcode_text').value= '';
  //document.getElementById('game_trans_qrcode').src='star.png';

  var div=document.getElementById('square_'+lastClickSquareX+'_'+lastClickSquareY);
  div.style.border = " 0px solid #000 ";
  div.style.width  = ""+(SQUARE_WIDTH_PIXELS-1)+'px';
  div.style.height = ""+(SQUARE_WIDTH_PIXELS-1)+'px';

}


function stringToHex(str){
  var val="";
  for(var i = 0; i < str.length; i++){
      if(val == "")
          val = str.charCodeAt(i).toString(16);
      else
          val += str.charCodeAt(i).toString(16);
  }
  return val;
}


function utf16ToUtf8(s){
	if(!s){
		return;
	}
	
	var i, code, ret = [], len = s.length;
	for(i = 0; i < len; i++){
		code = s.charCodeAt(i);
		if(code > 0x0 && code <= 0x7f){
			//单字节
			//UTF-16 0000 - 007F
			//UTF-8  0xxxxxxx
			ret.push(s.charAt(i));
		}else if(code >= 0x80 && code <= 0x7ff){
			//双字节
			//UTF-16 0080 - 07FF
			//UTF-8  110xxxxx 10xxxxxx
			ret.push(
				//110xxxxx
				String.fromCharCode(0xc0 | ((code >> 6) & 0x1f)),
				//10xxxxxx
				String.fromCharCode(0x80 | (code & 0x3f))
			);
		}else if(code >= 0x800 && code <= 0xffff){
			//三字节
			//UTF-16 0800 - FFFF
			//UTF-8  1110xxxx 10xxxxxx 10xxxxxx
			ret.push(
				//1110xxxx
				String.fromCharCode(0xe0 | ((code >> 12) & 0xf)),
				//10xxxxxx
				String.fromCharCode(0x80 | ((code >> 6) & 0x3f)),
				//10xxxxxx
				String.fromCharCode(0x80 | (code & 0x3f))
			);
		}
	}
	
	return ret.join('');
}

function utf8ToUtf16(s){
	if(!s){
		return;
	}
	
	var i, codes, bytes, ret = [], len = s.length;
	for(i = 0; i < len; i++){
		codes = [];
		codes.push(s.charCodeAt(i));
		if(((codes[0] >> 7) & 0xff) == 0x0){
			//单字节  0xxxxxxx
			ret.push(s.charAt(i));
		}else if(((codes[0] >> 5) & 0xff) == 0x6){
			//双字节  110xxxxx 10xxxxxx
			codes.push(s.charCodeAt(++i));
			bytes = [];
			bytes.push(codes[0] & 0x1f);
			bytes.push(codes[1] & 0x3f);
			ret.push(String.fromCharCode((bytes[0] << 6) | bytes[1]));
		}else if(((codes[0] >> 4) & 0xff) == 0xe){
			//三字节  1110xxxx 10xxxxxx 10xxxxxx
			codes.push(s.charCodeAt(++i));
			codes.push(s.charCodeAt(++i));
			bytes = [];
			bytes.push((codes[0] << 4) | ((codes[1] >> 2) & 0xf));
			bytes.push(((codes[1] & 0x3) << 6) | (codes[2] & 0x3f));			
			ret.push(String.fromCharCode((bytes[0] << 8) | bytes[1]));
		}
	}
	return ret.join('');
}
</script>
</body>
</html>
