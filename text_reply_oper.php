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
 * @param match_require 匹配次数需要[opt]
 * @param is_full_match 是否是完全匹配（true 或者false） */
case 'insert':
	if(!isset($_POST['rule_name']) || 
	   !isset($_POST['keyword']) ||
	   !isset($_POST['reply']) ||
	   !isset($_POST['is_full_match'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(isset($_POST['match_require']))
		$match_require = intval($_POST['match_require'], 10);
	else $match_require = 1;
	$ret = $wx->insert_text_reply(
		$_POST['rule_name'], 
		$_POST['keyword'], 
		$_POST['reply'], 
		$match_require, 
		$_POST['is_full_match'] == 'true' ? true : false);
	scan_error_exit($ret);
	break;
/* action = 'remove'
 * @brief 删除文本自动回复规则
 * @param rule_name 文本自动回复规则的名字 
 * @param uid       要查询用户的 UID（非 admin 则无效） */
case 'remove':
	if(!isset($_POST['rule_name']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(isset($_POST['uid'])) $uid = intval($_POST['uid'], 10);
	else $uid = -1;
	$ret = $wx->remove_text_reply($_POST['rule_name'], $uid);
	scan_error_exit($ret);
	break;
case 'remove_key':
/* action = 'remove_key'
 * @brief 删除文本自动回复规则某个关键字
 * @param rule_name 文本自动回复规则的名字 
 * @param keyword   关键字数组 
 * @param uid       要查询用户的 UID（非 admin 则无效） */
	if(!isset($_POST['rule_name']) || 
	   !isset($_POST['keyword']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(isset($_POST['uid'])) $uid = intval($_POST['uid'], 10);
	else $uid = -1;
	$ret = $wx->remove_text_reply_content(
		$_POST['rule_name'], 
		$_POST['keyword'], 
		array(), $uid);
	if($ret) scan_error_exit(SCAN_WX_STATUS_SUCCESS);
	else scan_error_exit(SCAN_WX_STATUS_ERROR);
	break;
case 'remove_reply':
/* action = 'remove_reply'
 * @brief 删除文本自动回复规则某个回复内容
 * @param rule_name 文本自动回复规则的名字 
 * @param content   回复内容数组 
 * @param uid       要查询用户的 UID（非 admin 则无效） */
	if(!isset($_POST['rule_name']) || 
	   !isset($_POST['content']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(isset($_POST['uid'])) $uid = intval($_POST['uid'], 10);
	else $uid = -1;
	$ret = $wx->remove_text_reply_content(
		$_POST['rule_name'], 
		array(), 
		$_POST['content'], $uid); 
	if($ret) scan_error_exit(SCAN_WX_STATUS_SUCCESS);
	else scan_error_exit(SCAN_WX_STATUS_ERROR);
	break;
case 'insert_key':
/* action = 'insert_key'
 * @brief 插入文本自动回复规则某个关键字
 * @param rule_name 文本自动回复规则的名字 
 * @param keyword   关键字数组 */
	if(!isset($_POST['rule_name']) || 
	   !isset($_POST['keyword']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->insert_text_reply_content(
		$_POST['rule_name'], 
		$_POST['keyword'], 
		array());
	if($ret) scan_error_exit(SCAN_WX_STATUS_SUCCESS);
	else scan_error_exit(SCAN_WX_STATUS_ERROR);
	break;
case 'insert_reply':
/* action = 'insert_reply'
 * @brief 插入文本自动回复规则某个回复内容
 * @param rule_name 文本自动回复规则的名字 
 * @param content   回复内容数组 */
	if(!isset($_POST['rule_name']) || 
	   !isset($_POST['content']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->insert_text_reply_content(
		$_POST['rule_name'], 
		array(), 
		$_POST['content']); 
	if($ret) scan_error_exit(SCAN_WX_STATUS_SUCCESS);
	else scan_error_exit(SCAN_WX_STATUS_ERROR);
	break;
case 'get_rule_info':
/* action = 'get_rule_info'
 * @brief 获取规则信息
 * @param rule_name 文本自动回复规则的名字 
 * @param uid       要查询用户的 UID（非 admin 则无效） */
	if(!isset($_POST['rule_name'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(isset($_POST['uid'])) $uid = intval($_POST['uid'], 10);
	else $uid = -1;
	$ret = $wx->get_text_reply($_POST['rule_name'], $uid);
	if($ret === false) scan_error_exit(SCAN_WX_STATUS_ERROR);
	scan_error_exit(SCAN_WX_STATUS_SUCCESS, $ret);
case 'set_reply_time':
/* action = 'set_reply_time'
 * @brief 设置回复时间
 * @param rule_name  文本自动回复规则的名字 
 * @param reply_type 文本回复时间类型
 * @param reply_time 文本回复时间串 */
	if(!isset($_POST['rule_name']) || !isset($_POST['reply_type'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(!isset($_POST['reply_time']) && $_POST['reply_type'] != SCAN_WX_TIME_ALL)
		scan_error_exit(SCAN_WX_STATUS_ERROR);

	if($_POST['reply_type'] == SCAN_WX_TIME_ALL) {
		$ret = $wx->set_text_reply_time(
			$_POST['rule_name'], 
			$_POST['reply_type']);
	} else {
		$ret = $wx->set_text_reply_time(
			$_POST['rule_name'],
			$_POST['reply_type'],
			scan_time_to_array($_POST['reply_type'], $_POST['reply_time']));
	}
	scan_error_exit($ret);
default:
	scan_error_exit(SCAN_WX_STATUS_ERROR);
}
?>
