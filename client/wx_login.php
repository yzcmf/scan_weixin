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

<form id="user_manager_form" style="display: none">
</form>

<form id="change_passwd_form" style="display: none">
	<label>Old password：</label>
	<input type="password" id="change_passwd_old" /> <br />
	<label>New password：</label>
	<input type="password" id="change_passwd_new" /> <br />
	<label>Repeat password：</label>
	<input type="password" id="change_passwd_repeat" /> <br />
	<input type="button" id="change_passwd_submit" value="Change" />
</form>

<script type="text/javascript">
$("#login_form").submit( function() {
	var passwd = $.md5($("#login_password").val());
	$("#login_md5").val(passwd);
	$.post("../account.php?action=login", $("#login_form").serialize(), 
		function(ret) {
			wx_login_solve(ret['status']);
		}, "json");
	return false;
} );

// 检测当前是否已经登陆
$.getJSON("../account.php?action=get_user_info",
	function(data) {
		if(data['status'] == SCAN_WX_STATUS_SUCCESS)
			wx_login_solve(SCAN_WX_STATUS_SUCCESS);
	} );

function wx_login_solve(status)
{
	if(status == SCAN_WX_STATUS_SUCCESS)
	{
		load_user_info();
		load_rule_list();
		$("#login_form").hide();
		$("#rule_list").fadeIn();
		$("#user_manager_form").fadeIn();
	} else {
		alert("登陆失败");
	}
}

$("#change_passwd_submit").click( function() {
	var new_passwd = $("#change_passwd_new").val();
	var rep_passwd = $("#change_passwd_repeat").val();
	var old_passwd = $("#change_passwd_old").val();

	if(new_passwd != rep_passwd)
	{
		alert("两次输入的密码不同！")
		return false;
	}

	$.post("../account.php?action=change_password", 
		{ 'old_password' : $.md5(old_passwd),
		  'new_password' : $.md5(new_passwd) },
		function(data) {
			if(data['status'] != SCAN_WX_STATUS_SUCCESS)
			{ 
				alert("密码错误！");
			} else {
				$("#change_passwd_new").val("");
				$("#change_passwd_repeat").val("");
				$("#change_passwd_old").val("");
				$("#change_passwd_form").hide();
				$("#rule_list").fadeIn();
				$("#user_manager_form").fadeIn();
			}
		}, "json");
	return false;
} );

function change_passwd()
{
	$("#rule_list").hide();
	$("#user_manager_form").hide();
	$("#change_passwd_form").fadeIn();
}

function load_user_info()
{
	$.getJSON("../account.php?action=get_user_info",
		function(data) {
			var cont = $("#user_manager_form");
			cont.append($("<p></p>").text("Welcome " + data['username']));

			var logout_button = $("<input type=\"button\"/>");
			logout_button.val("Logout");
			logout_button.click( function() {
				$.getJSON("../account.php?action=logout", 
					function(data) {
						$("#rule_list").hide();
						$("#user_manager_form").empty();
						$("#user_manager_form").hide();
						$("#rule_list").empty();
						$("#rule_list").hide();
						$("#login_username").val("");
						$("#login_password").val("");
						$("#login_md5").val("");
						$("#login_form").fadeIn();
					} );
				} );

			cont.append(logout_button);

			var passwd_button = $("<input type=\"button\"/>");
			passwd_button.val("Change password");
			passwd_button.click(change_passwd);
			cont.append(passwd_button);

			cont.append("<hr/>");
		} );
}
</script>
<!-- /登陆部分 -->
