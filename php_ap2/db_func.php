<?php
/*  Database Funtions */
function connectDB(){
    global $dbhost,$dbuser,$dbpass,$dbname;
    global $g_dbLink;
    if(!isset($g_dbLink)){
        $g_dbLink=@mysqli_connect($dbhost,$dbuser,$dbpass,$dbname) or die("Can not connect to the mysql server!");
        @mysqli_query($g_dbLink,"Set Names 'UTF8'");
    }
    
    return $g_dbLink;
}    



