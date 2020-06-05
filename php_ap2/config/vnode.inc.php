<?php
//Vitual Nodes Setting
//可处理的请求标识列表和转义映射配置
$gMapSiteURI 
   = array(
        'ppk:ap2/' => array(  //支持对扩展标识的内容服务
                    'dest'=>'ppk:ap2/', //实际处理标识
                    'redirect'=>false,   //redirect取值false表示为站内映射，应答的数据报文里的资源标识与请求相同
                    'key_file'=>PPK_AP_DEFAULT_KEY_FILE, //可选指定签名使用的密钥文件，不指定时将去key存放路径下自动匹配合适的密钥（相应会多消耗些处理时间），匹配不到则返回无签名数据报文
                ), 
        'ppk:apb/' => array(
                    'dest'=>'ppk:ap2/',
                    'redirect'=>false,
                ),
     ) ; 

