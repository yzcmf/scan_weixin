<?php

require_once('class_database.php');
require_once('function.php');

function scan_wx_response_fallback()
{
	global $wx;
	$sql = "SELECT m.reply_value
		    FROM `reply_meta` AS m
			INNER JOIN `reply_map` AS r
			ON m.id = r.id
			WHERE r.type = 'fallback'";

	$result = $wx->query($sql);
	return scan_select_from_result($result);
}

?>
