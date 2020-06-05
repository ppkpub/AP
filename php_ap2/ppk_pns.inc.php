<?php
/*       PPK PNS Toolkit Setting          */
/*         PPkPub.org  20200528           */  
/*    Released under the MIT License.     */

ini_set("display_errors", "On"); 
error_reporting(E_ALL | E_STRICT);

require_once 'config/config.inc.php';

//Include PPk Lib
require_once(PPK_LIB_DIR_PREFIX.'Util.php');
require_once(PPK_LIB_DIR_PREFIX.'ODIN.php');
require_once(PPK_LIB_DIR_PREFIX.'PTTP.php');
require_once(PPK_LIB_DIR_PREFIX.'AP.php');
require_once(PPK_LIB_DIR_PREFIX.'PTAP01DID.php');
require_once(PPK_LIB_DIR_PREFIX.'PTAP02ASSET.php');

require_once "lang.php";
 
define('APP_BASE_URL',\PPkPub\Util::getCurrentPagePath(true)); //应用网址的基础路径

//初始化数据库连接
$g_dbLink=@mysqli_connect($dbhost,$dbuser,$dbpass,$dbname) or die("Can not connect to the mysql server!");
@mysqli_query($g_dbLink,"Set Names 'UTF8'");

//已登录用户信息
require_once('ppk_pns.user.php');

require_once 'ppk_pns.function.php';


