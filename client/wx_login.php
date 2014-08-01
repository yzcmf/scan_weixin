<?php include_once('../class_database.php'); ?>
<!-- 登陆部分 -->
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

<form id="user_manager_form" style="display: none">
</form>

<form id="change_passwd_form" style="display: none">
	<table border="0">
		<tr><td><label>Old password：</label></td>
		<td><input type="password" id="change_passwd_old" /></td></tr>
		<tr><td><label>New password：</label></td>
		<td><input type="password" id="change_passwd_new" /></td></tr>
		<tr><td><label>Repeat password：</label></td>
		<td><input type="password" id="change_passwd_repeat" /></td></tr>
	</table>
	<input type="button" id="change_passwd_submit" value="Change" />
	<input type="button" id="change_passwd_back" value="Back" />
</form>

<script type="text/javascript">
$("#login_form").submit( function() {
	$("#login_submit").attr("disabled", true);
	var passwd = $.md5($("#login_password").val());
	$("#login_md5").val(passwd);
	$.post("../account.php?action=login", $("#login_form").serialize(), 
		function(ret) {
			wx_login_solve(ret['status']);
		}, "json");
	return false;
} );

function wx_login_solve(status)
{
	if(status == SCAN_WX_STATUS_SUCCESS)
	{
		load_user_info();
		load_rule_list();
		$("#login_form").hide();
		$("#login_submit").attr("disabled", false);
		$("#rule_list").fadeIn();
		$("#user_manager_form").fadeIn();
	} else {
		alert("登陆失败");
	}
}

$("#change_passwd_back").click(change_passwd_go_back);
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
				change_passwd_go_back();
			}
		}, "json");
	return false;
} );

function change_passwd_go_back()
{
	$("#change_passwd_new").val("");
	$("#change_passwd_repeat").val("");
	$("#change_passwd_old").val("");
	$("#change_passwd_form").hide();
	$("#new_rule_form").fadeIn();
	$("#rule_list").fadeIn();
	$("#user_manager_form").fadeIn();
}

function change_passwd()
{
	$("#rule_list").hide();
	$("#user_manager_form").hide();
	$("#new_rule_form").hide();
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
						$("#new_rule_form").hide();
						$("body").append($("#new_rule_form"));
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

<?php if($wx->is_login()): ?>
<script type="text/javascript">
// 当前已经登陆
$("#login_submit").attr("disabled", true);
$(document).ready( function() {
	wx_login_solve(SCAN_WX_STATUS_SUCCESS);
} );
</script>
<?php endif ?>
<!-- /登陆部分 -->
