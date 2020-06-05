<?php
/*
  PPk AP Common Defines&Functions 
    PPkPub.org   20200303
  Released under the MIT License.
*/
ini_set("display_errors", "On"); 
error_reporting(E_ALL | E_STRICT);

//Root path
define('PLUS_SITE_HTDOCS_PREFIX', dirname(__FILE__).DIRECTORY_SEPARATOR); 

//Local Config
require_once PLUS_SITE_HTDOCS_PREFIX.'config/config.inc.php';

//Include PPk Lib
require_once(PPK_LIB_DIR_PREFIX.'Util.php');
require_once(PPK_LIB_DIR_PREFIX.'ODIN.php');
require_once(PPK_LIB_DIR_PREFIX.'PTTP.php');
require_once(PPK_LIB_DIR_PREFIX.'AP.php');

//AP Common defines
define('TEST_CODE_UTF8',"A测B试C");

//Include DB functions
require_once PLUS_SITE_HTDOCS_PREFIX.'db_func.php';

