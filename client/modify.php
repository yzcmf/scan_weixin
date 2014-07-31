<?php
require_once('../class_database.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
		<script type="text/javascript">
(function($) {
	$.extend({       
		urlGet:function()
		{
			var aQuery = window.location.href.split("?");  //取得Get参数
			var aGET = new Array();
			if(aQuery.length > 1)
			{
				var aBuf = aQuery[1].split("&");
				for(var i=0, iLoop = aBuf.length; i<iLoop; i++)
				{
					var aTmp = aBuf[i].split("=");  //分离key与Value
					aGET[aTmp[0]] = aTmp[1];
				}
			}
			return aGET;
		}
	})
})(jQuery);
		</script>
	</head>
	<body>
<?php if(!isset($_GET['rule_name'])): ?>
	访问非法！缺少 rule name
<?php elseif($wx->is_login() == false): ?>
	请先登陆！
<?php else: ?>
<script type="text/javascript">

function change_post()
{
	var mid = $(this).prev().prev().val();
	var key = $(this).prev().val();
	if(!confirm("真的要修改吗？"))
		return;
	$.post("../text_reply_oper.php?action=update_content", 
		{ 'content_index' : mid, 
		  'content' : key }, 
		function(data) {
			if(data['status'] == SCAN_WX_STATUS_SUCCESS)
			{
//				window.location.reload();
			} else {
				alert("修改失败!\n" + data['status']);
			}
		}, "json");
}

function change_remove()
{
	var mid = $(this).prev().prev().prev().val();
	if(confirm("真的要删除吗？"))
	{
		var th = $(this);
		$.post("../text_reply_oper.php?action=remove_content", 
			{ 'content_index' : mid },
			function(data) {
				if(data['status'] == SCAN_WX_STATUS_SUCCESS)
				{
					for(var i = 0; i != 3; ++i)
						th.prev().remove();
					th.next().remove();
					th.remove();
				} else {
					alert("删除失败!\n" + data['status']);
				}
			}, "json");
	}
}

function change_add(rule_name, elem, is_key)
{
	var rn = $("<input type=\"hidden\"/>");
	rn.val(rule_name);
	elem.append(rn);

	var elem_add = $("<input type=\"button\"/>");
	if(is_key) elem_add.val("Add Keyword");
	else elem_add.val("Add Content");
	elem_add.click(function() {
		var action;
		if(is_key) action = "insert_key";
		else action = "insert_reply";
		$.post("../text_reply_oper.php?action=" + action, 
			{ 'rule_name' : $(this).prev().val(), 
			  'value' : [""] }, 
			function(data) {
				if(data['status'] == SCAN_WX_STATUS_SUCCESS)
				{
					window.location.reload();
				} else {
					alert("插入失败!\n" + data['status']);
				}
			}, "json");
	} );

	elem.append(elem_add);
}

function add_tool(elem)
{
	var change_key = $("<input type=\"button\"/>");
	change_key.val("Modify");
	change_key.click(change_post);
	elem.append(change_key);
	
	var remove_key = $("<input type=\"button\"/>");
	remove_key.val("Remove");
	remove_key.click(change_remove);
	elem.append(remove_key);

	elem.append($("<br/>"));
}

var rule_name = decodeURIComponent($.urlGet()["rule_name"]);

function init_rule(cont)
{
	$.post("../text_reply_oper.php?action=get_rule_info", 
		{ 'rule_name' : rule_name }, function(data) {
			if(!check_status(data['status']))
				check_status(data['status']);

			// 规则名称设置操作
			var rule_name_setting = $("<div></div>");
			rule_name_setting.append($("<label></label>").text("Rule name"));
			var rns_name = $("<input type=\"text\"/>");
			rns_name.val(rule_name);
			rule_name_setting.append(rns_name);
			var rns_change = $("<input type=\"button\"/>");
			rns_change.val("Change name");
			rns_change.click(function() {
				if(!confirm("真的要修改吗？"))
					return;
				$.post("../text_reply_oper.php?action=change_rule_name", 
					{ 'rule_name' : rule_name, 
					  'rule_name_new' : rns_name.val() }, 
					function(data) {
						if(data['status'] == SCAN_WX_STATUS_SUCCESS)
						{
							window.location.search = "?rule_name=" 
							  + encodeURIComponent(rns_name.val());
						} else {
							alert("修改失败!\n" + data['status']);
						}
					}, "json");
			} );
			rule_name_setting.append(rns_change);
			cont.append(rule_name_setting);

			// 添加关键字操作
			var keyword = $("<form></form>");
			for(k in data['keyword'])
			{
				var mid = $("<input type=\"hidden\"/>");
				mid.val(data['keyword'][k][0]);
				keyword.append(mid);

				var txt = $("<input type=\"text\"/>");
				txt.val(data['keyword'][k][1]);
				keyword.append(txt);
				add_tool(keyword);
			}

			change_add(rule_name, keyword, true);
			cont.append(keyword);

			// 添加回复操作
			var content = $("<form></form>");
			for(k in data['reply'])
			{
				var mid = $("<input type=\"hidden\"/>");
				mid.val(data['reply'][k][0]);
				content.append(mid);

				var txt = $("<textarea>");
				txt.val(data['reply'][k][1]);
				txt.attr("name", "reply[]");
				content.append(txt);
				add_tool(content);
			}

			change_add(rule_name, content, false);
			cont.append(content);
		}, "json");
}

init_rule($("body"));
</script>
<?php endif ?>
	</body>
</html>
