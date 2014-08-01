<!-- 主要规则部分 -->
<div id="rule_list">
</div>

<div id="new_rule_form" style="display: none">
	<table border="0">
		<tr><td><label>New rule name：</label></td>
		<td><input type="text" id="new_rule_name"/></td></tr>
		<tr><td><label>New rule type：</label></td>
		<td><select id="new_rule_type">
			<option value="sub_match">部分匹配</option>
			<option value="full_match">完全匹配</option>
			<option value="fallback">Fallback</option>
		</select></td></tr>
	</table>
	<input id="new_rule_create" type="button" value="Add new rule" />
</div>

<script type="text/javascript">
function display_rule(rule, father)
{
	if(!check_status(rule['status']))
		return;
	var c = $("<div></div>");

	var match_type_str = "Fallback";
	if(rule['match_type'] == "sub_match")
		match_type_str = "部分匹配";
	else if(rule['match_type'] == "full_match")
		match_type_str = "完全匹配";
	c.append($("<p></p>").text("匹配类型: " + match_type_str));

	c.append($("<p></p>").text("回复时间类型: " + rule['time_type']));
	if(rule['time_str'] != undefined)
		c.append($("<p></p>").text("回复时间: " + rule['time_str']));
	if(rule['match_require'] == undefined)
		rule['match_require'] = 1;
	if(rule['match_type'] != 'fallback')
		c.append($("<p></p>").text("要求匹配的最少关键字数: " + rule['match_require']));

	// 显示规则关键字
	var key = $("<div></div>").addClass("rule_info_key");
	var key_list = $("<ul></ul>");
	for(keyword in rule['keyword'])
		key_list.append($("<li></li>").text(rule['keyword'][keyword][1]));
	key.append(key_list);
	c.append(key);

	// 显示规则回复内容
	var cont = $("<div></div>").addClass("rule_info_content");
	var cont_list = $("<ul></ul>");
	for(content in rule['reply'])
		cont_list.append($("<li></li>").text(rule['reply'][content][1]));
	cont.append(cont_list);
	c.append(cont);

	c.append($("<hr/>"));
	father.append(c);
}

function create_modify_url(rule_name)
{
	return "modify.php?rule_name=" 
		+ encodeURIComponent(rule_name);
}

function add_new_rule_btn(cont)
{
	$("#new_rule_create").click( function() {
		var new_rule_name = $("#new_rule_name").val();
		if(new_rule_name == '')
		{
			alert("规则名称不能为空");
			return;
		}

		$.post("../text_reply_oper.php?action=insert", 
			{ 'rule_name'  : new_rule_name, 
			  'keyword[]'  : "请尽快补全关键字",
			  'reply[]'    : '',
			  'match_type' : $("#new_rule_type").val() }, 
			function(data) {
				if(!check_status(data['status']))
					return true;
				window.open(create_modify_url(new_rule_name), "_self");
			}, "json" );

	} );

	$("#new_rule_form").show();
	cont.append($("#new_rule_form"));
}

function load_rule_list()
{
	var container = $("#rule_list");
	$.getJSON("../text_reply_oper.php?action=get_all_rules", 
		function(rules) {
			if(!check_status(rules['status']))
				return;
			container.hide();
			for(rule_index in rules)
			{
				var rule = rules[rule_index];
				if(rule == SCAN_WX_STATUS_SUCCESS)
					continue;
				var p = $("<div></div>").addClass("rule");
				p.data("rule_name", rule['name']);
				var g = $("<div></div>");
				g.append($("<div></div>").addClass("rule_name").text(rule['name']));
				// 添加修改按钮
				var modify_btn = $("<input type=\"button\"/>");
				modify_btn.val("Modify");
				modify_btn.click( function(rname) {
					return function() {
						window.open(create_modify_url(rname), "_self");
					}
				}(rule['name']) );
				g.append(modify_btn);

				// 添加删除规则按钮
				var delete_btn = $("<input type=\"button\"/>");
				delete_btn.val("Delete");
				delete_btn.click( function(cont) {
					return function() {
						if(!confirm("删除后将不可恢复\n确定要删除该规则？"))
							return;
						$.post("../text_reply_oper.php?action=remove", 
							{ 'rule_name' : cont.data("rule_name") }, 
							function(data) {
								if(!check_status(data['status']))
									return;
								cont.fadeOut(function() {
									$(this).remove();
								} );
							} , "json");
					}
				}(p) );
				g.append(delete_btn);

				// 获取并显示规则信息
				$.post("../text_reply_oper.php?action=get_rule_info", 
					{ 'rule_name' : rule['name'] }, 
					function(cap) {
						return function(info) {
							display_rule(info, cap);
							cap.fadeIn();
						}
					}(p), "json");
				p.append(g);
				p.hide();
				container.append(p);
			}
			add_new_rule_btn($("body"));
			container.show();
		} );
}
</script>
<!-- /主要规则部分 -->
