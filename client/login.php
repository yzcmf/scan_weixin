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
	</head>
	<body>
		<form id="login_form">
			<table border="0">
				<tr><td><label for="username">Username: </label></td>
				<td><input type="text" name="username" id="login_username" /></td></tr>
				<tr><td><label for="login_password">Password: </label></td>
				<td><input type="password" id="login_password" /></td></tr>
			</table>
			<input type="hidden" name="password" id="login_md5" />
			<input type="submit" id="login_submit" value="Login" />
		</form>
		<script type="text/javascript">
$("#login_form").submit( function() {
	$("#login_submit").attr("disabled", true);
	var passwd = $.md5($("#login_password").val());
	$("#login_md5").val(passwd);
	$.post("../account.php?action=login", $("#login_form").serialize(), 
		function(ret) {
			if(ret['status'] != SCAN_WX_STATUS_SUCCESS)
			{
				alert("用户名或密码错误！");
			} else {
				window.open("rule.html", "_self");
			}
		}, "json");
	return false;
} );
		</script>
	</body>
</html>

