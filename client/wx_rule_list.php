<!-- 主要规则部分 -->
<div id="rule_list">
</div>

<script type="text/javascript">
function display_rule(rule, father)
{
	if(!check_status(rule['status']))
		return;
	var c = $("<div></div>");

	c.append($("<p></p>").text("回复时间类型: " + rule['time_type']));
	if(rule['time_str'] != undefined)
		c.append($("<p></p>").text("回复时间: " + rule['time_str']));
	if(rule['match_require'] == undefined)
		rule['match_require'] = 1;
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
	father.append(c);
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
				var g = $("<div></div>");
				g.append($("<span></span>").addClass("rule_name").text(rule['name']));
	//			g.append($("<span></span>").addClass("rule_type").text(rule['type']));
				g.append($("<a></a>").attr("href", "modify.php?rule_name=" 
					+ encodeURIComponent(rule['name'])).text("Modify"));
				$.post("../text_reply_oper.php?action=get_rule_info", 
					{ 'rule_name' : rule['name'] }, 
					function(cap) {
						return function(info) {
							display_rule(info, cap);
						}
					}(p), "json");
				p.append(g);
				container.append(p);
				container.append($("<hr/>"));
			}
			container.show();
		} );
}
</script>
<!-- /主要规则部分 -->
