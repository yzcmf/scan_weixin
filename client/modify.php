<?php
require_once('../class_database.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="style.css" />
		<link rel="stylesheet" href="modify.css" />
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
				for(var i = 0, iLoop = aBuf.length; i < iLoop; i++)
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
	<p id="please_waiting">Please Waiting...</p>
<script type="text/javascript">

function change_post()
{
	var mid = $(this).parent().data("meta_id");
	var key = $(this).parent().children().first().val();
//	if(!confirm("真的要修改吗？"))
//		return;
	var th = $(this).parent();
	$.post("../text_reply_oper.php?action=update_content", 
		{ 'content_index' : mid, 
		  'content' : key }, 
		function(data) {
			if(data['status'] == SCAN_WX_STATUS_SUCCESS)
			{
				th.fadeOut();
				th.fadeIn();
			} else {
				alert("修改失败!\n" + data['status']);
			}
		}, "json");
}

function change_remove()
{
	var mid = $(this).parent().data("meta_id");
	if(confirm("真的要删除吗？"))
	{
		var th = $(this);
		$.post("../text_reply_oper.php?action=remove_content", 
			{ 'content_index' : mid },
			function(data) {
				if(data['status'] == SCAN_WX_STATUS_SUCCESS)
				{
					th.parent().fadeOut("slow", function() {
						$(this).remove(); } );
				} else {
					alert("删除失败!\n" + data['status']);
				}
			}, "json");
	}
}

function change_add(rule_name, elem, is_key)
{
	elem.data("rule_name", rule_name);

	var elem_add = $("<input type=\"button\"/>");
	if(is_key) elem_add.val("Add Keyword");
	else elem_add.val("Add Content");
	elem_add.click(function() {
		var action;
		if(is_key) action = "insert_key";
		else action = "insert_reply";
		var rule_name = $(this).parent().data("rule_name");
		$.post("../text_reply_oper.php?action=" + action, 
			{ 'rule_name' : rule_name, 
			  'value' : [""] }, 
			function(data) {
				if(data['status'] == SCAN_WX_STATUS_SUCCESS)
				{
					// 获取新加入元素的 ID 并显示
					$.post("../text_reply_oper.php?action=get_rule_info", 
						{ 'rule_name' : rule_name }, 
						function(data) {
							if(!check_status(data['status']))
								check_status(data['status']);

							// 获取已经存在元素的 ID
							var existed_elem = $(is_key ? ".keyword_elem" : ".reply_elem");
							var existed_max = 0;
							existed_elem.each( function() {
								existed_max = Math.max(existed_max, $(this).data("meta_id"));
							} );

							// 检测新元素 ID
							var new_elem = -1;
							var type = is_key ? "keyword" : "reply";
							var kr = data[type];
							for(var len = kr.length, i = 0; i != len; ++i)
							{
								var mid = kr[i][0];
								if(mid > existed_max)
								{
									new_elem = i;
									break;
								}
							}

							if(new_elem == -1)
							{
								alert("错误！");
							} else {
								var block = create_tool_single(type, data[type][new_elem]);
								block.hide();
								var t = $("#" + type + "_box").children("." + type + "_elem");
								if(t.length == 0)
								{
									$("#" + type + "_box").children().first().before(block);
								} else {
									t.last().after(block);
								}
								block.fadeIn("slow");
							}
						}, "json" );
				} else {
					alert("插入失败!\n" + data['status']);
				}
			}, "json");
	} );

	elem.append(elem_add);
}

function create_tool_single(type, data)
{
	var block = $("<div></div>");
	block.addClass(type + "_elem");
	block.data("meta_id", data[0]);

	var txt;
	if(type == 'keyword')
		txt = $("<input type=\"text\"/>");
	else txt = $("<textarea></textarea>");
	txt.val(data[1]);
	block.append(txt);

	if(type == 'reply')
		block.append($("<br/>"));

	var change_key = $("<input type=\"button\"/>");
	change_key.val("Modify");
	change_key.click(change_post);
	block.append(change_key);
	
	var remove_key = $("<input type=\"button\"/>");
	remove_key.val("Remove");
	remove_key.click(change_remove);
	block.append(remove_key);

	return block;
}

function add_tool(cont, type, data)
{
	var elem = $("<div></div>");
	elem.attr("id", type + "_box");
	for(k in data[type])
		elem.append(create_tool_single(type, data[type][k]));

	change_add(rule_name, elem, type == 'keyword');
	cont.append(elem);
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

			// 添加返回按钮
			var go_back_button = $("<input type=\"button\"/>");
			go_back_button.val("Go back");
			go_back_button.click( function() {
				window.open("index.php", "_self");
			} );
			rule_name_setting.append(go_back_button);

			cont.append(rule_name_setting);


			if(data['match_type'] != 'fallback')
				add_tool(cont, "keyword", data);
			add_tool(cont, "reply", data);
			$("#please_waiting").remove();
			cont.fadeIn();
		}, "json");
}

var box = $("<div></div>");
box.attr("id", "reply_rule_editor");
init_rule(box);
box.hide();
$("body").append(box);
</script>
<?php endif ?>
	</body>
</html>
