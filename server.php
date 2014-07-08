<?php
//define your token
include_once('class_database.php');
include_once('function.php');
include_once('response_text.php');
include_once('response_fallback.php');
include_once('response_php.php');
$wechatObj = new wechatCallbackapi();
$wechatObj->responseMsg();
//$wechatObj->valid();

class wechatCallbackapi
{
	public function valid()
	{
		$echoStr = $_GET["echostr"];

		//valid signature , option
		if($this->checkSignature()){
			echo $echoStr;
			exit;
		}
	}

	public function responseMsg()
	{
//		if(!$this->checkSignature())
//			exit;
		//get post data, May be due to the different environments
		$postStr = file_get_contents("php://input");

	  	//extract post data
		if (!empty($postStr))
		{
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$fromUsername = $postObj->FromUserName;
			$toUsername = $postObj->ToUserName;
			$keyword = trim($postObj->Content);
			$time = time();
			$textTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
						</xml>";			 
			if(!empty( $keyword ))
			{
				$msgType = "text";
				$contentStr = scan_wx_response_php_reply($keyword);
				if($contentStr === false)
					$contentStr = scan_wx_response_text($keyword);
				if($contentStr === false)
					$contentStr = scan_wx_response_fallback($keyword);
				$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
				echo $resultStr;
			} else {
				$msgType = "text";
				$contentStr = scan_wx_response_fallback();
				$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
				echo $resultStr;
			}
		} else {
			echo "";
			exit;
		}
	}
		
	private function checkSignature()
	{
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];	
				
		$token = SCAN_WX_TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>
