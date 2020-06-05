<?php
//Basic settings
define('PPK_LIB_DIR_PREFIX', dirname(__FILE__).'/../../ppk-lib2/php/');   //此处配置PPK SDK的引用路径

define('PPK_AP_NODE_NAME', 'AP Node Demo V2' );      //The label for your AP node

define('PPK_AP_PRIVATE_DIR_PREFIX', dirname(__FILE__).'/../your_private_path/');  //Your private data path
define('PPK_AP_RESOURCE_DIR_PREFIX', PPK_AP_PRIVATE_DIR_PREFIX."ap-resource/" ); //The static resource path
define('PPK_AP_PLUGIN_DIR_PREFIX', PPK_AP_PRIVATE_DIR_PREFIX."ap-plugin/" ); //The function plugin path
define('PPK_AP_KEY_DIR_PREFIX',      PPK_AP_PRIVATE_DIR_PREFIX."ap-key/" );      //The key file path

define('PPK_AP_DEFAULT_KEY_FILE',PPK_AP_KEY_DIR_PREFIX.'ap2.key.json');    //The default key for signing data

define('PPK_API_SERVICE_URL','http://tool.ppkpub.org/ppkapi2/');  //PTTP API service for parsing ROOT ODIN etc

define('MAX_FILE_KB',1024);    //Maximum readable file size(KB)


//数据库配置
$dbhost="localhost";                                    
$dbuser="xxxxxx";                                        
$dbpass="xxxxxx";                                          
$dbname="ppkpns";   

define('FORCE_HTTPS',false);   //强制使用HTTPS

define('ADMIN_ODIN_URI',"ppk:sysadmin*");   //默认的管理员用户

define('IS_DEMO',true);   //是否为演示版本
define('DEMO_LOGIN_USER_ODIN_URI',"ppk:83850*");   //默认的演示登录用户
define('DEMO_LOGIN_USER_LEVEL',1);   //演示用户权限，1:有限访客（不能发起拍卖） 2:普通用户权限

define('WEIXIN_QR_SERVICE_URL','https://ppk001.sinaapp.com/odin/');   //此处配置微信扫码登录服务网址

//内容节点配置
require_once dirname(__FILE__).'/vnode.inc.php';
