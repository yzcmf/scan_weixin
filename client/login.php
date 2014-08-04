<?php
	include('../class_database.php');
	if($wx->is_login())
	{
		header('Location: rule.html');
		exit();
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
		<script type="text/javascript" src="js/jquery.iDialog.min.js" dialog-theme="default"></script>
		<script type="text/javascript" src="js/jquery.md5.js"></script>
		<script type="text/javascript" src="js/function.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
		<link rel="stylesheet" href="css/style.css" />
		<title>SCAN 微信平台管理系统</title>
	</head>
	<body>
		<div id="login_form">
			<table border="0">
				<tr><td><label for="username">Username: </label></td>
				<td><input type="text" name="username" id="login_username" /></td></tr>
				<tr><td><label for="login_password">Password: </label></td>
				<td><input type="password" id="login_password" /></td></tr>
			</table>
			<input type="button" id="login_submit" class="jsbtn" value="登陆" />
		</div>
		<script type="text/javascript">
$("#login_submit").click( function() {
	if($("#login_submit").attr("disabled"))
		return;
	$("#login_submit").attr("disabled", true);
	$.post("../account.php?action=login", 
		{ username : $("#login_username").val(), 
		  password : $.md5($("#login_password").val()) },
		function(ret) {
			$("#login_submit").attr("disabled", false);
			if(ret['status'] != SCAN_WX_STATUS_SUCCESS)
			{
				scan_alert("错误", "用户名或密码错误！");
			} else {
				window.open("rule.html", "_self");
			}
		}, "json");
} );

$("#login_form table input").keyup( function(e) {
	if(e.which == 13) 
		$("#login_submit").click();
	return true;
} );
		</script>
	</body>
</html>

