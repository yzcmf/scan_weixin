<!-- 登陆部分 -->
<form id="login_form">
	<label for="username">Username: </label>
	<input type="text" name="username" id="login_username" />
	<br />
	<label for="login_password">Password: </label>
	<input type="password" id="login_password" />
	<input type="hidden" name="password" id="login_md5" />
	<br />
	<input type="submit" id="login_submit" value="Login" />
</form>

<script type="text/javascript">
$("#login_form").submit( function() {
	var passwd = $.md5($("#login_password").val());
	$("#login_md5").val(passwd);
	$.post("../login.php", $("#login_form").serialize(), 
		function(ret) {
			wx_login_solve(ret['status']);
		}, "json");
	return false;
} );

function wx_login_solve(status)
{
	if(status == SCAN_WX_STATUS_SUCCESS)
	{
		load_rule_list();
		$("#login_form").hide();
		$("#rule_list").fadeIn();
	} else {
		alert("登陆失败");
	}
}
</script>
<!-- /登陆部分 -->
