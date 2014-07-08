<?php

include_once('class_database.php');
include_once('function.php');
if(!$wx->is_login())
	scan_error_exit(SCAN_WX_STATUS_NOLOGIN);
	
if(!isset($_POST['action']))
	scan_error_exit(SCAN_WX_STATUS_ERROR);

$action = $_POST['action'];
switch($action)
{
/* action = 'insert'
 * @brief 插入文本自动回复规则
 * @param rule_name 文本自动回复规则的名字
 * @param keyword   关键字数组
 * @param reply     回复内容数组
 * @param is_full_match 是否是完全匹配（true 或者false） */
case 'insert':
	if(!isset($_POST['rule_name']) || 
	   !isset($_POST['keyword']) ||
	   !isset($_POST['reply']) ||
	   !isset($_POST['is_full_match'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->insert_text_reply(
		$_POST['rule_name'], $_POST['keyword'], $_POST['reply'], 
		$_POST['is_full_match'] == 'true' ? true : false);
	scan_error_exit($ret);
	break;
/* action = 'remove'
 * @brief 删除文本自动回复规则
 * @param rule_name 文本自动回复规则的名字 */
case 'remove':
	if(!isset($_POST['rule_name']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->remove_text_reply($_POST['rule_name']);
	scan_error_exit($ret);
	break;
case 'remove_key':
	break;
case 'remove_reply':
	break;
case 'insert_key':
	break;
case 'insert_reply':
	break;
default:
	scan_error_exit(SCAN_WX_STATUS_ERROR);
}


?>
