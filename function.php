<?php

require_once('config.php');
function scan_error_exit($status, $info = array())
{
	header('Content-Type: application/json');
	$info['status'] = $status;
	exit(json_encode(array($info)));
}

function scan_select_from_result($result)
{
	$candidate = array();
	while($row = $result->fetch_row())
		array_push($candidate, $row[0]);
	$result->free();
	return $candidate[rand(0, count($candidate) - 1)];
}

function scan_time_to_array($time_type, $time_str)
{
	$time_arr = explode('|', $time_str);
	for($i = 0; $i < count($time_arr); ++$i)
	{
		$time_arr[$i] = explode(',', $time_arr[$i]);
		for($j = 0; $j != 2; ++$j)
			$time_arr[$i][$j] = explode(':', $time_arr[$i][$j]);
	}
	return $time_arr;
}

function scan_array_to_time($time_type, $time_arr)
{
	$time_str = '';
	foreach($time_arr as $t)
		$time_str .= implode(':', $t[0]) . ',' . implode(':', $t[1]) . '|';
	return substr($time_str, 0, -1);
}

function scan_time_in_range($time_type, $range)
{
	date_default_timezone_set("Asia/Shanghai");
	$now = getdate();
	switch($time_type)
	{
	case SCAN_WX_TIME_MONTHLY:
		if($now['mday'] < $range[0][0] || $now['mday'] > $range[0][1])
			return false;
		if($now['hours'] < $range[1][0] || $now['hours'] > $range[1][1])
			return false;
		if($now['minutes'] < $range[2][0] || $now['minutes'] > $range[2][1])
			return false;
		if($now['seconds'] < $range[3][0] || $now['seconds'] > $range[3][1])
			return false;
		return true;
	case SCAN_WX_TIME_WEEKLY:
		if($now['wday'] < $range[0][0] || $now['wday'] > $range[0][1])
			return false;
		if($now['hours'] < $range[1][0] || $now['hours'] > $range[1][1])
			return false;
		if($now['minutes'] < $range[2][0] || $now['minutes'] > $range[2][1])
			return false;
		if($now['seconds'] < $range[3][0] || $now['seconds'] > $range[3][1])
			return false;
		return true;
	case SCAN_WX_TIME_DAILY:
		if($now['hours'] < $range[0][0] || $now['hours'] > $range[0][1])
			return false;
		if($now['minutes'] < $range[1][0] || $now['minutes'] > $range[1][1])
			return false;
		if($now['seconds'] < $range[2][0] || $now['seconds'] > $range[2][1])
			return false;
		return true;
	}

	return false;
}

function scan_wx_check_time($rule_id)
{
	global $wx;
	$time_type = $wx->get_result("
		SELECT `reply_value` 
		FROM `reply_meta`
		WHERE `id` = $rule_id
		  AND `reply_key` = 'time_type'");
	if($time_type == SCAN_WX_TIME_ALL)
		return true;
	$time_str = $wx->get_result("
		SELECT `reply_value` 
		FROM `reply_meta`
		WHERE `id` = $rule_id
		  AND `reply_key` = 'time_range'");

	$time_arr = scan_time_to_array($time_type, $time_str);
	foreach($time_arr as $v)
		if(scan_time_in_range($time_type, $v))
			return true;
	return false;
}

?>
