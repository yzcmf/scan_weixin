<?php

include_once('class_database.php');
include_once('function.php');

if(!isset($_GET['action']))
	scan_error_exit(SCAN_WX_STATUS_ERROR);
$action = $_GET['action'];

function wx_login()
{
	global $wx;
	if(!isset($_POST['username']) || !isset($_POST['password']))
	{
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	} else {
		$username = $wx->escape_sql_string(trim($_POST['username']));
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
}

function wx_logout()
{
	global $wx;
	if($wx->is_login())
		session_destroy();
	else scan_error_exit(SCAN_WX_STATUS_NOLOGIN);
	scan_error_exit(SCAN_WX_STATUS_SUCCESS);
}

function wx_register()
{
	global $wx;
	if(!$wx->is_login())
		scan_error_exit(SCAN_WX_STATUS_NOLOGIN);
		
	if($wx->get_user_info()['role'] != SCAN_WX_USER_ADMIN)
		scan_error_exit(SCAN_WX_STATUS_FORBIDDEN);

	if(!isset($_POST['username']) || !isset($_POST['password']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);

	$username = $wx->escape_sql_string($_POST['username']);
	$username = trim($username);
	$password = sha1($_POST['password'] . SCAN_WX_PASSWORD_SALT);

	$row = $wx->get_first_row("SELECT * FROM `user` WHERE `username` = '$username'");
	if($row) scan_error_exit(SCAN_WX_STATUS_ERROR);
	$wx->query("INSERT INTO `user` (username, password, role) VALUES ('$username', '$password', 'common')");

	scan_error_exit(SCAN_WX_STATUS_SUCCESS);
}

function wx_get_user_info()
{
	global $wx;
	if(!$wx->is_login())
		scan_error_exit(SCAN_WX_STATUS_NOLOGIN);

	$uid = $wx->get_user_info()['uid'];
	$sql = "SELECT * FROM `user` WHERE `uid` = $uid";
	$info = $wx->get_first_row($sql);
	scan_error_exit(SCAN_WX_STATUS_SUCCESS, 
		array( 'uid' => $info['uid'], 
			   'username' => $info['username'], 
			   'role' => $info['role']));
}

function wx_change_passwd()
{
	global $wx;
	if(!$wx->is_login())
		scan_error_exit(SCAN_WX_STATUS_NOLOGIN);

	if(!isset($_POST['old_password']) || !isset($_POST['new_password']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);

	$is_admin = false;
	if($wx->get_user_info()['role'] == SCAN_WX_USER_ADMIN)
		$is_admin = true;

	$current_uid = $wx->get_user_info()['uid'];
	if(!isset($_POST['uid']))
		$uid = $current_uid;
	else $uid = intval($_POST['uid'], 10);

	if($uid != $current_uid && !$is_admin)
		scan_error_exit(SCAN_WX_STATUS_FORBIDDEN);

	$old_passwd = sha1(trim($_POST['old_password']) . SCAN_WX_PASSWORD_SALT);
	$new_passwd = sha1(trim($_POST['new_password']) . SCAN_WX_PASSWORD_SALT);

	$sql = "SELECT * FROM `user` WHERE `uid` = $uid";
	$info = $wx->get_first_row($sql);
	if(!$is_admin && $old_passwd != $info['password'])
		scan_error_exit(SCAN_WX_STATUS_FORBIDDEN);

	$wx->query("UPDATE `user`
			    SET `password` = '$new_passwd'
				WHERE `uid` = $uid");

	scan_error_exit(SCAN_WX_STATUS_SUCCESS);
}

switch($action)
{
/* action = 'login'
 * @brief 用于登陆管理系统
 * @param username 用户名
 * @param password 密码（MD5 散列后的值） */
case 'login':
	wx_login();
	break;
/* action = 'logout'
 * @brief 用于登出管理系统 */
case 'logout':
	wx_logout();
	break;
/* action = 'register'
 * @brief 用于注册账户（只有管理员可以）
 * @param username 用户名
 * @param password 密码（MD5 散列后的值） */
case 'register':
	wx_register();
	break;
/* action = 'get_user_info'
 * @brief 用于获取当前登陆用户信息 */
case 'get_user_info':
	wx_get_user_info();
	break;
/* action = 'change_password'
 * @brief 修改密码
 * @param old_password 旧密码（MD5 散列后的值，管理员可以不用提供）
 * @param new_password 新密码（MD5 散列后的值） 
 * @param uid[opt]     要修改密码的用户 UID */
case 'change_password':
	wx_change_passwd();
	break;
default:
	scan_error_exit(SCAN_WX_STATUS_ERROR);
}

?>
