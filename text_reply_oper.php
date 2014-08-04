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
 * @param keyword   关键字数组[opt]
 * @param reply     回复内容数组[opt]
 * @param match_require 匹配次数需要[opt]
 * @param match_type 匹配类型（full_match, sub_match, fallback） */
case 'insert':
	if(!isset($_POST['rule_name']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(isset($_POST['match_require']))
		$match_require = intval($_POST['match_require'], 10);
	else $match_require = 1;

	if(!isset($_POST['keyword']))
		$keyword = array();
	else $keyword = $_POST['keyword'];

	if(!isset($_POST['reply']))
		$reply = array();
	else $reply = $_POST['reply'];

	if(!isset($_POST['match_type']))
		$match_type = 'sub_match';
	else $match_type = $_POST['match_type'];

	$ret = $wx->insert_text_reply(
		$_POST['rule_name'], 
		$keyword, 
		$reply,
		$match_require, 
		$match_type, 
		$uid);
	scan_error_exit($ret);
	break;
/* action = 'remove'
 * @brief 删除文本自动回复规则
 * @param rid 规则的 ID */
case 'remove':
	if(!isset($_POST['rid']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->remove_text_reply($_POST['rid'], $uid);
	scan_error_exit($ret);
	break;
case 'remove_content':
/* action = 'remove_content'
 * @brief 删除文本自动回复规则某个关键字或回复内容
 * @param content_index 要删除的内容 id */
	if(!isset($_POST['content_index']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$content_index = intval($_POST['content_index'], 10);
	scan_error_exit($wx->remove_text_reply_content($content_index, $uid));
	break;
case 'update_content':
/* action = 'update_content'
 * @brief 更新文本自动回复规则某个关键字或回复内容
 * @param content_index 要更新的内容 id 
 * @param content       要更新的内容 */
	if(!isset($_POST['content_index'])
		|| !isset($_POST['content']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$content_index = intval($_POST['content_index'], 10);
	scan_error_exit($wx->update_text_reply_content($content_index, $_POST['content'], $uid));
	break;
case 'insert_key':
/* action = 'insert_key'
 * @brief 插入文本自动回复规则某个关键字
 * @param rid     规则的 ID 
 * @param value   关键字数组 */
	if(!isset($_POST['rid']) || 
	   !isset($_POST['value']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->insert_text_reply_content(
		$_POST['rid'], 
		$_POST['value'], 
		array(), $uid);
	scan_error_exit($ret);
	break;
case 'insert_reply':
/* action = 'insert_reply'
 * @brief 插入文本自动回复规则某个回复内容
 * @param rid     规则的 ID 
 * @param value   回复内容数组 */
	if(!isset($_POST['rid']) || 
	   !isset($_POST['value']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->insert_text_reply_content(
		$_POST['rid'], 
		array(), 
		$_POST['value'], $uid); 
	scan_error_exit($ret);
	break;
case 'change_rule_name':
/* action = 'change_rule_name'
 * @brief 修改规则名称
 * @param rid            规则的 ID 
 * @param rule_name_new  规则的新名字 */
	if(!isset($_POST['rid']) || 
	   !isset($_POST['rule_name_new']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	scan_error_exit($wx->rename_rule(
		$_POST['rid'], $_POST['rule_name_new'], $uid));
	break;
case 'get_rule_info':
/* action = 'get_rule_info'
 * @brief 获取规则信息
 * @param rid            规则的 ID */
	if(!isset($_POST['rid'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->get_text_reply($_POST['rid'], $uid);
	if(!is_array($ret))
		scan_error_exit($ret);
	else scan_error_exit(SCAN_WX_STATUS_SUCCESS, $ret);
	break;
case 'get_all_rules':
/* action = 'get_all_rules'
 * @brief 所有规则名称 */
	$ret = $wx->get_all_rules($uid);
	if($ret === false)
		scan_error_exit(SCAN_WX_STATUS_FORBIDDEN);
	scan_error_exit(SCAN_WX_STATUS_SUCCESS, $ret);
	break;
case 'set_reply_time':
/* action = 'set_reply_time'
 * @brief 设置回复时间
 * @param rid        规则的 ID 
 * @param time_type 文本回复时间类型
 * @param time_str 文本回复时间串 */
	if(!isset($_POST['rid']) || !isset($_POST['time_type'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	if(!isset($_POST['time_str']) && $_POST['time_type'] != SCAN_WX_TIME_ALL)
		scan_error_exit(SCAN_WX_STATUS_ERROR);

	if($_POST['time_type'] == SCAN_WX_TIME_ALL) {
		$ret = $wx->set_text_reply_time(
			$_POST['rid'], $_POST['time_type'], "", $uid);
	} else {
		$ret = $wx->set_text_reply_time(
			$_POST['rid'],
			$_POST['time_type'],
			$_POST['time_str'], $uid);
	}
	scan_error_exit($ret);
case 'insert_reply_time':
/* action = 'insert_reply_time'
 * @brief 添加回复时间
 * @param rid        规则的 ID 
 * @param time_str 文本回复时间串 */
	if(!isset($_POST['rid']) || !isset($_POST['time_str'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	scan_error_exit($wx->add_text_reply_time(
		$_POST['rid'], $_POST['time_str'], $uid));
case 'remove_reply_time':
/* action = 'remove_reply_time'
 * @brief 删除回复时间
 * @param rid        规则的 ID 
 * @param time_str 文本回复时间串 */
	if(!isset($_POST['rid']) || !isset($_POST['time_str'])) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	scan_error_exit($wx->remove_text_reply_time(
		$_POST['rid'], $_POST['time_str'], $uid));
case 'change_reply_time':
/* action = 'change_reply_time'
 * @brief 修改回复时间
 * @param rid        规则的 ID 
 * @param time_old 文本回复时间串 
 * @param time_new 文本回复时间串 */
	if(!isset($_POST['rid'])
		|| !isset($_POST['time_old']) 
		|| !isset($_POST['time_new']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	scan_error_exit($wx->update_text_reply_time(
		$_POST['rid'], $_POST['time_old'], $_POST['time_new'], $uid));
case 'get_rule_record':
/* action = 'get_rule_record'
 * @brief 获取记录的消息
 * @param rid        规则的 ID  */
	if(!isset($_POST['rid']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$ret = $wx->get_rule_record($_POST['rid'], $uid);
	if(!is_array($ret))
		scan_error_exit($ret);
	scan_error_exit(SCAN_WX_STATUS_SUCCESS, $ret);
case 'set_rule_record':
/* action = 'set_rule_record'
 * @brief 设置是否记录消息
 * @param rid             规则的 ID 
 * @param record_require  是否记录消息（1或0） */
	if(!isset($_POST['rid']) || !isset($_POST['record_require']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	scan_error_exit($wx->set_rule_record(
		$_POST['rid'], $_POST['record_require'], $uid));
case 'set_match_require':
/* action = 'set_match_require'
 * @brief 设置最少需要匹配的关键字数
 * @param rid             规则的 ID 
 * @param match_require   需要匹配次数 */
	if(!isset($_POST['rid']) || !isset($_POST['match_require']))
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	$match_require = intval($_POST['match_require'], 10);
	if($match_require < 1) 
		scan_error_exit(SCAN_WX_STATUS_ERROR);
	scan_error_exit($wx->update_meta_public(
		$_POST['rid'], 'match_require', $match_require, $uid));
default:
	scan_error_exit(SCAN_WX_STATUS_ERROR);
}
?>
