<?php

$url = 'http://real_weixin_server';

$raw_data = file_get_contents('php://input');
$query_string = $_SERVER['QUERY_STRING'];

$ch = curl_init();
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $raw_data);
}

curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_URL, $url . '?' . $query_string);
curl_exec($ch);
curl_close($ch);

?>
