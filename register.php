<?php

include_once('class_database.php');
include_once('function.php');

if(!$wx->is_login())
	scan_error_exit(SCAN_WX_STATUS_NOLOGIN);
	
if($wx->get_user_info()['role'] != SCAN_WX_USER_ADMIN)
	scan_error_exit(SCAN_WX_STATUS_FORBIDDEN);

if(!isset($_POST['username']) || !isset($_POST['password']))
	scan_error_exit(SCAN_WX_STATUS_ERROR);

$username = $wx->escape_sql_string($_POST['username']);
$password = sha1($_POST['password'] . SCAN_WX_PASSWORD_SALT);

$row = $wx->get_first_row("SELECT * FROM `user` WHERE `username` = '$username'");
if($row) scan_error_exit(SCAN_WX_STATUS_ERROR);
$wx->query("INSERT INTO `user` (username, password, role) VALUES ('$username', '$password', 'common')");

scan_error_exit(SCAN_WX_STATUS_SUCCESS);

?>
