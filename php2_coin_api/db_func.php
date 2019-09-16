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

function bindAddressToDB($owner_uri,$coin_uri,$address,$original,$sign){
    $db_link=connectDB();
    
    $sql_str="replace into more_address_list (owner_uri,coin_type, address,original,sign) values ('$owner_uri','$coin_uri','$address','$original','$sign')";
    //echo $sql_str;
    return @mysqli_query($db_link,$sql_str);
}

function getBindedAddressFromDB($owner_uri,$coin_uri){
    $db_link=connectDB();
    
    $sql_str="select owner_uri,coin_type as coin_uri, address,original,sign from more_address_list where owner_uri='$owner_uri' and coin_type='$coin_uri'";
    //echo $sql_str;
    $rs=@mysqli_query($db_link,$sql_str);
    if (false !== $rs) {
        if($row = mysqli_fetch_assoc($rs)){
            return $row;
        }
    }
    
    return null;
}


