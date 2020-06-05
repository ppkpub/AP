<br>
<p align=center>
<?php 
if(strlen($g_currentUserODIN)>0)
{
    echo '<a href="pns.php"><img src="image/user.png" width=16 height=16>',getLang('我的帐号'),'[',\PPkPub\Util::getSafeEchoTextToPage(\PPkPub\Util::friendlyLongID($g_currentUserODIN)),']</a>';
} else { 
    echo '<a href="login.php">',getLang('以奥丁号登录'),'</a>&emsp;|&emsp;<a href="https://www.chainnode.com/post/386612">',getLang('注册奥丁号'),'</a>';
}   

?>
</p>

<center>
<p><a href="http://tool.ppkpub.org/ap2/browser.html"><?php echo getLang('浏览PPk网络');?></a>&emsp;|&emsp;<a href="new_msg.php"><?php echo getLang('留言建议');?></a>&emsp;|&emsp;<a href="https://www.chainnode.com/post/434454"><?php echo getLang('帮助');?></a></p>
</center>

<div class="container-fluid footer">
PPkPub PNS Toolkit 0.1.0528 &copy; 2020. Released under the <a href="http://opensource.org/licenses/mit-license.php">MIT License</a>.
</div>

</body>
</html>

