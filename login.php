<?php

/* login.php 
 * @brief 用于登陆管理系统
 * @param username 用户名
 * @param password 密码（MD5 散列后的值）
 */

include_once('class_database.php');
include_once('function.php');

header('Content-Type: application/json');
if(!isset($_POST['username']) || !isset($_POST['password']))
{
	scan_error_exit(SCAN_WX_STATUS_ERROR);
} else {
	$username = $wx->escape_sql_string($_POST['username']);
	$password = sha1(trim($_POST['password']) . SCAN_WX_PASSWORD_SALT);
	$row = $wx->get_first_row("SELECT * FROM `user` WHERE `username` = '$username'");
	if(!$row || $row['password'] != $password) {
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	} else {
		session_start();
		$_SESSION['uid'] = $row['uid'];
		$_SESSION['time'] = time();
		scan_error_exit(SCAN_WX_STATUS_SUCCESS);
	}
}

?>
