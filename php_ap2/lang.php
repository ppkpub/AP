<?php
//Set language
$g_currentLang=@$_COOKIE['ppktool_lang'];

if(strlen($g_currentLang)==0 ) //Default is chinese
    $g_currentLang='cn';

require_once 'lang_'.$g_currentLang.'.php';

//setcookie("ppktool_lang", $g_currentLang, time()+3600);
