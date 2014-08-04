<?php

require_once('class_database.php');
require_once('function.php');
require_once('response_fallback.php');

function scan_wx_response_full_match($content, $from_user, $uid)
{
	global $wx;
	// 查询 full_match 部分
	$esc_content = $wx->escape_sql_string($content);
	$sql = "SELECT DISTINCT(m.id)
			FROM `reply_meta` AS m 
			INNER JOIN `reply_map` AS r
			ON r.id = m.id
			WHERE r.type = 'full_match'
			  AND m.reply_key = 'keyword'
			  AND m.reply_value = '$esc_content'";
	if($uid != -1) $sql .= " AND r.uid = '$uid'";
	$result = $wx->query($sql);

	// 存在 full_match 则开始随机选择
	if($result->num_rows != 0)
	{
		$candidate = array();
		while($row = $result->fetch_row())
		{
			if(scan_wx_check_time($row[0]))
				array_push($candidate, $row[0]);
		}
		$result->free();
		if(count($candidate) == 0)
			return false;
		$rule_id = $candidate[rand(0, count($candidate) - 1)];
		$result = $wx->query(
			"SELECT `index_key`
			 FROM `reply_meta`
			 WHERE `id` = $rule_id");

		$wx->record_message($rule_id, $from_user, $content);
		$reply_id = scan_select_from_result($result);
		if($reply_id === false) return false;
		return $wx->get_result(
			"SELECT `reply_value`
			 FROM `reply_meta`
			 WHERE `index_key` = $reply_id");
	}

	return false;
}

function scan_wx_response_sub_match($content, $from_user, $uid)
{
	global $wx;
	// 检查 sub_match
	$sql = "SELECT m.reply_value, m.id
			FROM `reply_meta` AS m 
			INNER JOIN `reply_map` AS r
			ON r.id = m.id
			WHERE r.type = 'sub_match'
			  AND m.reply_key = '%s'";
	if($uid != -1) $sql .= " AND r.uid = '$uid'";

	// 记录每一条规则需要匹配的关键字个数
	$result = $wx->query(sprintf($sql, 'match_require'));
	$match_require = array();
	while($row = $result->fetch_row())
	{
		$rule_id = intval($row[1], 10);
		$match_require[$rule_id] = intval($row[0], 10);
	}
	$result->free();

	$result = $wx->query(sprintf($sql, 'keyword'));
	$match_record = array();
	$keyword_count = array();
	$rule_check_time = array();
	while($row = $result->fetch_row())
	{
		$k = $row[0];
		$rule_id = intval($row[1], 10);
		if(array_key_exists($rule_id, $keyword_count))
			++$keyword_count[$rule_id];
		else $keyword_count[$rule_id] = 1;

		if(strpos($content, $k) !== false)
		{
			if(!array_key_exists($rule_id, $rule_check_time))
				$rule_check_time[$rule_id] = scan_wx_check_time($rule_id);
			if($rule_check_time[$rule_id])
			{
				if(array_key_exists($rule_id, $match_record))
					++$match_record[$rule_id];
				else $match_record[$rule_id] = 1;
			}
		}
	}
	$result->free();

	$candidate = array();
	foreach($match_record as $k => $v)
	{
		if(!array_key_exists($k, $match_require))
			$r = 1;
		else $r = $match_require[$k];
		if($r == -1) $r = $keyword_count[$k];
		if($v >= $r) array_push($candidate, $k);
	}

	if(count($candidate) == 0)
		return false;

	$ret = array();
	foreach($candidate as $rule_id)
	{
		$sql = "SELECT m.reply_value
			    FROM `reply_meta` AS m
				WHERE m.reply_key = 'reply'
				  AND m.id = $rule_id";
		$result = $wx->query($sql);
		while($row = $result->fetch_row())
			array_push($ret, array($rule_id, $row[0]));
		$result->free();
	}

	if(count($ret) == 0) return false;
	$index = rand(0, count($ret) - 1);
	$wx->record_message($ret[$index][0], $from_user, $content);
	return $ret[$index][1];
}

function scan_wx_response_check_uid($content)
{
	global $wx;
	$sql = "SELECT `uid`, `user_value`
		    FROM `user_meta`
		    WHERE `user_key` = 'keyword'";
	$result = $wx->query($sql);
	$candidate = array();
	while($row = $result->fetch_row())
	{
		$uid = $row[0];
		if(strpos($content, $row[1]) === 0)
			array_push($candidate, $uid);
	}

	$len = count($candidate);
	if($len == 0) return -1;
	return $candidate[rand(0, $len - 1)];
}

function scan_wx_response_text($content, $from_user)
{
	global $wx;
	$uid = scan_wx_response_check_uid($content);
	// 检测全匹配
	$result = scan_wx_response_full_match($content, $from_user, $uid);
	if($result !== false) return $result;
	// 检测部分匹配
	$result = scan_wx_response_sub_match($content, $from_user, $uid);
	if($result !== false) return $result;
	// 检测 fallback
	if($uid != -1) return scan_wx_response_fallback($uid);
	return false;
}

?>
