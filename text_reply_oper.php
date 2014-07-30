<?php

include_once('class_database.php');
include_once('function.php');
if(!$wx->is_login())
	scan_error_exit(SCAN_WX_STATUS_NOLOGIN);
	
if(!isset($_GET['action']))
	scan_error_exit(SCAN_WX_STATUS_ERROR);

$action = $_GET['action'];
if(isset($_POST['uid'])) $uid = intval($_POST['uid'], 10);
else $uid = -1;

switch($action)
{
/* action = 'insert'
 * @brief 插入文本自动回复规则
 * @param rule_name 文本自动回复规则的名字
 * @param keyword   关键字数组
 * @param reply     回复内容数组
 * @param match_require 匹配次数需要[opt]
 * @param match_type 匹配类型（full_match, sub_match, fallback） */
case 'insert':
	if(!isset($_POST['rule_name']) || 
	   !isset($_POST['keyword']) ||
	   !isset($_POST['reply']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(isset($_POST['match_require']))
		$match_require = intval($_POST['match_require'], 10);
	else $match_require = 1;
	if(!isset($_POST['match_type']))
		$match_type = 'sub_match';
	else $match_type = $_POST['match_type'];
	$ret = $wx->insert_text_reply(
		$_POST['rule_name'], 
		$_POST['keyword'], 
		$_POST['reply'], 
		$match_require, 
		$match_type, 
		$uid);
	scan_error_exit($ret);
	break;
/* action = 'remove'
 * @brief 删除文本自动回复规则
 * @param rule_name 文本自动回复规则的名字 */
case 'remove':
	if(!isset($_POST['rule_name']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->remove_text_reply($_POST['rule_name'], $uid);
	scan_error_exit($ret);
	break;
case 'remove_key':
/* action = 'remove_content'
 * @brief 删除文本自动回复规则某个关键字或回复内容
 * @param content_index 要删除的内容 id */
	if(!isset($_POST['content_index']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$content_index = intval($_POST['content_index'], 10);
	scan_error_exit($wx->remove_text_reply_content($content_index, $uid));
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
		array(), $uid);
	scan_error_exit($ret);
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
		$_POST['content'], $uid); 
	scan_error_exit($ret);
	break;
case 'get_rule_info':
/* action = 'get_rule_info'
 * @brief 获取规则信息
 * @param rule_name 文本自动回复规则的名字 */
	if(!isset($_POST['rule_name'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->get_text_reply($_POST['rule_name'], $uid);
	if(!is_array($ret))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	else scan_error_exit(SCAN_WX_STATUS_SUCCESS, $ret);
	break;
case 'get_all_rules':
/* action = 'get_all_rules'
 * @brief 所有规则名称 */
	$ret = $wx->get_all_rules($uid);
	if($ret === false)
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	scan_error_exit(SCAN_WX_STATUS_SUCCESS, $ret);
	break;
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
			$_POST['reply_type'], $uid);
	} else {
		$ret = $wx->set_text_reply_time(
			$_POST['rule_name'],
			$_POST['reply_type'],
			scan_time_to_array($_POST['reply_type'], $_POST['reply_time']), $uid);
	}
	scan_error_exit($ret);
default:
	scan_error_exit(SCAN_WX_STATUS_ERROR);
}
?>
