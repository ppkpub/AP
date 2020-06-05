<?php
/**
 * 用于前端页轮询 查询当前登录事务是否被确认
 */
require_once "ppk_pns.inc.php";

if ($g_logonUserInfo!=null){
    //$_SESSION["swap_user_uri"]=$row['user_odin_uri'];
    //$_SESSION["swap_user_name"]=$row['user_odin_uri']; //待完善用户名称
    //$_SESSION["swap_user_level"]=$row['status_code'];
    
    $arr = array('code' => 0, 'msg' => 'Confirmed OK', 'logon_user_uri' => $g_logonUserInfo["user_uri"], 'level' => $g_logonUserInfo['level']);
}else
    $arr = array('code' => 500, 'msg' => 'Pending','uuid'=>generateSessionSafeUUID());

echo json_encode($arr);