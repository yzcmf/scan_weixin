<?php

include_once('class_database.php');
include_once('function.php');

$scan_wx_php_reply_seq = array();

include('php_reply/weather.php');

function scan_wx_register_php_reply($function_name, $checker)
{
	global $scan_wx_php_reply_seq;
	array_push($scan_wx_php_reply_seq, 
		array($function_name, $checker));
}

function scan_wx_response_php_reply($content, $from_user)
{
	global $scan_wx_php_reply_seq;
	foreach($scan_wx_php_reply_seq as $v)
	{
		$found = false;
		if(is_array($v[1]))
		{
			foreach($v[1] as $intro)
			{
				if(strpos($content, $intro) === 0)
				{
					$found = true;
					break;
				}
			}
		} else {
			$found = call_user_func($v[1], $content);
		}

		$ret = false;
		if($found) $ret = call_user_func($v[0], $content);
		if($ret !== false) return $ret;
	}

	return false;
}

?>
