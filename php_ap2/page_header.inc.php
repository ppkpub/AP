<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo getLang('奥丁号解析服务工具');?><?php if(IS_DEMO) echo getLang('[演示]'); ?></title>
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://netdna.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
<nav class="navbar navbar-default" role="navigation">
<div class="container-fluid">
  <!-- Brand and toggle get grouped for better mobile display -->
  <div class="navbar-header">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-navbar-collapse-1">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    <a class="navbar-brand logo" href="./"><?php echo getLang('托管解析服务');?></a>
  </div>

  <!-- Collect the nav links, forms, and other content for toggling -->
  <div class="collapse navbar-collapse" id="bs-navbar-collapse-1">
    <ul class="nav navbar-nav">
        <?php 
        if(strlen($g_currentUserODIN)>0){
            echo '<li><a href="pns.php"><img src="image/user.png" width=16 height=16>',getLang('我的帐号'),'[',\PPkPub\Util::getSafeEchoTextToPage(\PPkPub\Util::friendlyLongID($g_currentUserODIN)),']</a></li>';
            echo '</a></li>';
        } else { 
            echo '<li><a href="login.php">',getLang('以奥丁号登录'),'</a></li> <li><a href="https://www.chainnode.com/post/386612">',getLang('注册奥丁号'),'</a></li>';
        } ?>
        <li><a href="http://tool.ppkpub.org/ap2/browser.html"><?php echo getLang('浏览PPk网络');?></a></li>
        <li><a href="https://www.chainnode.com/post/434454"><?php echo getLang('帮助');?></a></li>
        <li><a href="lang_change.php"><?php echo getLang('English');?></a></li>
    </ul>
  </div>
</div>
</nav>
