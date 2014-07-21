<?php

define('SCAN_WX_BAIDUAPI_AK', 'LltX6WkWxZSw4VCUpEL3jn5H');
scan_wx_register_php_reply(
	'scan_wx_response_weather_php', 
	array('#天气#', '#weather#', '#tianqi#'));

function scan_wx_response_weather_php($content)
{
	$content = trim(substr($content, 1 + strpos($content, '#', 1)));
	$format_str = 'http://api.map.baidu.com/telematics/v3/weather?location=%s&output=json&ak=' . SCAN_WX_BAIDUAPI_AK;
	if($content) $city = $content;
	else $city = '福州';
	$weather_json = file_get_contents(sprintf($format_str, $city));
	$weather = json_decode($weather_json);
	$ret = "";
	if($weather->status != 'success')
	{
		$ret .= "没有找到" . $city . "的天气。>_<||| \n";
		$weather_json = file_get_contents(sprintf($format_str, '福州'));
		$weather = json_decode($weather_json);
		if($weather->status != 'success')
			return "今天好像不能告诉你天气了、( ¯▽¯；)";
	}

	$sep = "--------\n";
	$ret .= $weather->results[0]->currentCity . "的天气情况：\n";
	for($i = 0; $i < count($weather->results[0]->weather_data); ++$i)
	{
		$w = $weather->results[0]->weather_data[$i];
		$k = $weather->results[0]->index[$i];
		$ret .= $w->date . "\n" 
			 . $w->temperature . "\n"
			 . $w->weather . " "
			 . $w->wind . "\n";
		$ret .= $k->des . "\n";
		$ret .= $sep;
	}

	return substr($ret, 0, -strlen($sep));
}

?>
