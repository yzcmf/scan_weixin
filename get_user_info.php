<?php

include('class_database.php');
include('function.php');

header('Content-Type: application/json');
if(!$wx->is_login())
	scan_error_exit(SCAN_WX_STATUS_NOLOGIN);

$uid = $wx->get_user_info()['uid'];
$sql = "SELECT * FROM `user` WHERE `uid` = $uid";
$info = $wx->get_first_row($sql);
scan_error_exit(SCAN_WX_STATUS_SUCCESS, 
	array( 'uid' => $info['uid'], 
		   'username' => $info['username'], 
		   'role' => $info['role']));
?>
