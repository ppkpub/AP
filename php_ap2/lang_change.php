<?php
//Set language
$g_currentLang=$_COOKIE['ppktool_lang'];

if($g_currentLang=='en') 
    $g_currentLang='cn';
else
    $g_currentLang='en';

setcookie("ppktool_lang", $g_currentLang, time()+3600);

header('location:./');