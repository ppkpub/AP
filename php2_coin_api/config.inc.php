<?php
//Basic settings
define('AP_NODE_NAME', 'AP node by PHP, 20190910' );      //The label for your AP node
define('AP_DEFAULT_ODIN', PPK_URI_PREFIX.'527064.583/' ); //The dafault ODIN 

define('PLUS_SITE_PRIVATE_PATH', dirname(__FILE__).'/your_private_path/');  //Your private data path
define('AP_RESOURCE_PATH', PLUS_SITE_PRIVATE_PATH."ap-resource/" ); //The static resource path
define('AP_KEY_PATH',      PLUS_SITE_PRIVATE_PATH."ap-key/" );      //The key file path
define('DEFAULT_SIGN_HASH_ALGO', 'SHA256' );      //The default signature hashing algorithm in the PTTP protocol

define('PTTP_NODE_API_URL','http://tool.ppkpub.org/odin/');  //PTTP protocol proxy node

//Mysql Database
$dbhost="localhost";                                    
$dbuser="root";                                        
$dbpass="xm123";                                          
$dbname="odinswap";                                       
