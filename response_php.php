<?php

include_once('class_database.php');
include_once('function.php');

$scan_wx_php_reply_seq = array();
function scan_wx_register_php_reply($function_name)
{
	global $scan_wx_php_reply_seq;
	array_push($scan_wx_php_reply_seq, $function_name);
}

function scan_wx_response_php_reply($content)
{
	global $scan_wx_php_reply_seq;
	foreach($scan_wx_php_reply_seq as $v)
	{
		$ret = call_user_func($v, $content);
		if($ret !== false) return $ret;
	}

	return false;
}

?>
