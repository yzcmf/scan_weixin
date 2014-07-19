<?php

require_once('class_database.php');
require_once('function.php');

function scan_wx_response_fallback($uid = -1)
{
	global $wx;
	$uid = intval($uid, 10);
	if($uid == -1) $uid = 1;
	$sql = "SELECT m.reply_value
		    FROM `reply_meta` AS m
			INNER JOIN `reply_map` AS r
			ON m.id = r.id
			WHERE r.type = 'fallback'
			  AND r.uid = $uid";

	$result = $wx->query($sql);
	if($result->num_rows == 0) 
	{
		$result->free();
		return false;
	}
	return scan_select_from_result($result);
}

?>
