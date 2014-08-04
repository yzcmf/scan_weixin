var flush_count = 0;
$(document).ready( function() {
	$.getJSON("../account.php?action=get_user_info", 
		function(data) {
			if(data.status != SCAN_WX_STATUS_SUCCESS)
				window.open("index.php", "_self");
			if(data.role == "administrator")
			{
				$("#register_user").show();
				$("#register_user").click( function() {
					var elem = $(".register_user_dialog").clone(true);
					confirm_dialog("auto", "添加用户", elem, function() {
						return event_register_user(elem);
					} );
				} );
			}
		} );

	$("#rule_logout").click( function() {
		$.getJSON("../account.php?action=logout",
			{}, function(data) {
				window.open("login.php", "_self");
			} );
	} );

	$("#rule_add").click( function() {
		var elem = $(".add_dialog").clone(true);
		var rule_num = $(".rule_wrap_item").length;
		var dlg_name = elem.children(".dlg_rule_name");
		dlg_name.val(dlg_name.val() + (rule_num + 1))
		confirm_dialog("auto", "添加规则", elem, function() {
			return event_add_rule(elem, rule_num + 1);
		} );
	} );
	
	$("#change_passwd").click( function() {
		var elem = $(".change_passwd_dialog").clone(true);
		confirm_dialog("auto", "修改密码", elem, function() {
			return event_change_passwd(elem);
		} );
	} );

	$("#flush_rule").data("running", false);
	$("#flush_rule").click( function() {
		if($(this).data("running"))
			return;
		$(this).data("running", true);
		var rtemp;
		$.getJSON("../text_reply_oper.php?action=get_all_rules", 
			function(rules) {
				++flush_count;
				if(!check_status(rules.status))
					return;
				if(flush_count % 2 == 0)
					reflush_rule(rules, $("#rule_wrap"));
				else rtemp = rules;
			} );
		$("#rule_wrap").fadeOut( function() {
			$(this).empty();
			$(this).fadeIn();
			if(++flush_count % 2 == 0)
				reflush_rule(rtemp, $("#rule_wrap"));
		} );
	} );

	$.getJSON("../text_reply_oper.php?action=get_all_rules", 
		function(rules) {
			if(!check_status(rules['status']))
				return;

			$("#rule_tool").fadeIn();

			$(".lw").keypress( function(e) {
				if(!e.which || e.which == 8) return true;
				if(e.which < 48 || e.which > 57)
					return false;
				return true;
			} );

			$("#waiting_msg").remove();
			reflush_rule(rules, $("#rule_wrap"));
		} );
} );

function reflush_rule(rules, wrap)
{
	var rules_list = $("<ul></ul>");
	rules_list.attr("id", "rules_list");
	for(var i = rules.count - 1; i >= 0; --i)
		create_single_rule(i + 1, rules[i]).appendTo(rules_list);
		
	rules_list.hide();
	rules_list.fadeIn();
	rules_list.appendTo(wrap);
	$("#flush_rule").data("running", false);
}

function create_single_rule(rule_num, rule_info)
{
	var wrap = $("<li></li>");
	wrap.data("rid", rule_info.rid);
	wrap.data("modify_mode", false);
	wrap.addClass("rule_wrap_item");

	create_rule_head(wrap, rule_num, rule_info);
	$.post("../text_reply_oper.php?action=get_rule_info", 
		{ rid : rule_info.rid },
		function(wrap) {
			return function(rule) {
				wrap.children(".rule_head").click( function() {
					var rule_body = wrap.children(".rule_body");
					var rule_modify = wrap.children(".rule_modify");
					if(wrap.data("modify_mode"))
					{
						var icon = wrap.find("a.icon_drop_up");
						icon.removeClass("icon_drop_up");
						icon.addClass("icon_drop_down");
						rule_body.slideDown();
						rule_modify.slideUp();
						wrap.data("modify_mode", false);
					} else {
						var icon = wrap.find(".icon_drop_down");
						icon.removeClass("icon_drop_down");
						icon.addClass("icon_drop_up");
						rule_body.slideUp();
						rule_modify.slideDown();
						wrap.data("modify_mode", true);
					}
				} );
				create_rule_body(wrap, rule);
				create_rule_modify(wrap, rule);
			};
		}(wrap), "json");
	return wrap;
}

function find_wrap(elem)
{
	while(elem && !elem.hasClass("rule_wrap_item"))
		elem = elem.parent();
	return elem;
}

function check_ul_border(body)
{
	var ul = body.children("ul");
	if(ul.children("li").length == 0)
		body.css("border-bottom", "0px");
	else body.css("border-bottom", "");

	if(body.hasClass("head_check"))
	{
		body.css("border-bottom", "0px");
		if(ul.children("li").length == 0)
			body.prev().css("border-bottom", "0px");
		else body.prev().css("border-bottom", "");
	}
}

function create_rule_head(wrap, num, rule)
{
	// 添加头部信息
	var rule_head = $("<div></div>");
	rule_head.addClass("rule_head");
	rule_head.addClass("rule_head_meta");

	// 添加规则名称描述信息
	var rule_info = $("<div></div>");
	rule_info.addClass("info");

	$("<span></span>").addClass("rule_num")
		.text("规则" + num + ":").appendTo(rule_info);
	$("<span></span>").addClass("rule_name")
		.text(rule.name).appendTo(rule_info);

	rule_info.appendTo(rule_head);

	// 添加动作按钮
	var slide_down = $("<div></div>");
	slide_down.addClass("oper");
	var drop_down_btn = $("<a></a>");
	drop_down_btn.attr("href", "javascript:;");
	drop_down_btn.val(" ");
	drop_down_btn.addClass("icon_drop_down");
	
	slide_down.append(drop_down_btn);
	rule_head.append(slide_down);
	wrap.append(rule_head);
}

function generate_rule_body_div(text)
{
	var list = $("<div></div>");
	list.addClass("rule_body_list_item");
	$("<div></div>")
		.text(text)
		.addClass("rule_body_list_item_title")
		.appendTo(list);
	return list;
}

function create_rule_body_item(title, content)
{
	var item = generate_rule_body_div(title);
	$("<div></div>")
		.text(content)
		.addClass("rule_body_list_item_content")
		.appendTo(item);
	return item;
}

function create_rule_body(wrap, rule)
{
	// 添加主体信息
	var rule_body = $("<div></div>");
	rule_body.addClass("rule_body");
	rule_body.hide();

	create_rule_body_item("回复类型", 
		map_reply_type(rule.match_type)).appendTo(rule_body);
	if(rule.match_type != "fallback")
	{
		if(rule.match_require == undefined)
			rule.match_require = 1;
		create_rule_body_item("匹配次数",
			rule.match_require).appendTo(rule_body);
	}
	create_rule_body_item("回复时间类型",
		rule.time_type).appendTo(rule_body);

	wrap.append(rule_body);
	rule_body.slideDown();
}

function generate_span_input(default_value)
{
	var span_wrap = $("<span></span>");
	span_wrap.addClass("span_input_wrap");
	span_wrap.append($("<input type=\"text\"/>").val(default_value));
	return span_wrap;
}

function create_rule_modify(wrap, rule)
{
	// 添加修改信息
	var rule_modify = $("<div></div>");
	rule_modify.addClass("rule_modify");
	rule_modify.hide();

	// 规则名区域
	var rule_name_wrap = $("<div></div>");
	rule_name_wrap.addClass("rule_head_meta");
	rule_name_wrap.data("rid", rule.rid);
	var rule_name_area = $("<div></div>");
	rule_name_area.css("float", "left");

	$("<label></label>")
		.addClass("rule_modify_detail")
		.addClass("circle_bk")
		.text("规则名称")
		.appendTo(rule_name_area);

	var rule_name_input = generate_span_input(rule.rule_name);
	rule_name_input.children("input").data("rid", rule.rid);
	rule_name_input.children("input").keyup(event_rename_rule);

	rule_name_area.append(rule_name_input);
	rule_name_wrap.append(rule_name_area);

	// 添加删除规则按钮
	var rule_remove= $("<div></div>");
	rule_remove.addClass("oper");
	var rule_remove_link = $("<a></a>");
	rule_remove_link.attr("href", "javascript:;");
	rule_remove_link.text("删除规则");
	rule_remove_link.click(event_remove_rule(rule.rid, wrap));
	rule_remove.append(rule_remove_link);
	rule_name_wrap.append(rule_remove);

	rule_modify.append(rule_name_wrap);

	// 回复时间类型
	create_rule_modify_time(rule_modify, rule.time_type, rule.time_str);

	// 关键字区域
	if(rule.match_type != "fallback")
	{
		create_rule_modify_content(rule_modify, 
			rule.keyword, 'key', "关键词");
	}

	// 回复区域
	create_rule_modify_content(rule_modify, 
		rule.reply, 'reply', "回复");
	rule_modify.children().last().addClass("head_check");
	check_ul_border(rule_modify.children().last());

	wrap.append(rule_modify);
}

function format_content(item, content, title)
{
	var content_v = content
				 .replace(/&/g, "&amp")
				 .replace(/</g, "&lt;")
				 .replace(/>/g, "&gt;")
				 .replace(/ /g, "&nbsp;")
				 .replace(/\n/g, "<br />");

	if(content.replace(/\s/g, "") == "")
	{
		item.addClass("content_empty");
		content_v = "<i>该" + title + "为空！"
			+ "请赶快填写或删除该" + title + "</i>";
	} else if(item.hasClass("content_empty")) {
		item.removeClass("content_empty");
	}

	return content_v;
}

function create_single_item_oper(modify_func, remove_func)
{
	var oper = $("<div></div>");
	oper.addClass("oper");
	$("<a></a>").attr("href", "javascript:;")
				.addClass("modify_btn")
				.text("修改")
				.click(modify_func)
				.appendTo(oper);

	$("<a></a>").attr("href", "javascript:;")
				.addClass("remove_btn")
				.text("删除")
				.click(remove_func)
				.appendTo(oper);
	return oper;
}

function create_single_item(content, title)
{
	var item = $("<div></div>");

	var info = $("<div></div>");
	info.addClass("rule_modify_content");
	info.data("raw_content", content[1]);
	info.data("meta_id", content[0]);

	var content_v = format_content(item, content[1], title);
	$("<span></span>").html(content_v).appendTo(info);
	item.append(info);

	var oper = create_single_item_oper(
		event_modify_content(info, title), 
		event_remove_content(content[0]));
	item.append(oper);

	return $("<li></li>").append(item);
}

function create_rule_modify_head(title)
{
	var head = $("<div></div>");
	head.addClass("rule_modify_item_head");
	head.addClass("rule_head_meta");

	var head_info = $("<div></div>");
	head_info.addClass("info");
	$("<span></span>").addClass("circle_bk")
		.text(title).appendTo(head_info);
	head.append(head_info);

	var head_oper = $("<div></div>");
	head_oper.addClass("oper");
	var oper_link = $("<a></a>");
	oper_link.attr("href", "javascript:;");
	head_oper.append(oper_link);
	head.append(head_oper);

	return { head : head, oper : oper_link };
}

function create_rule_modify_content(wrap, content, type, title)
{
	// 创建头部
	var head = create_rule_modify_head(title);
	head.oper.text("添加" + title);
	head.oper.click(event_insert_content(head.head, type, title));
	wrap.append(head.head);

	// 创建主体
	var body = $("<div></div>");
	body.addClass("rule_modify_item_body");
	var list = $("<ul></ul>");
	list.addClass("rule_modify_ul");

	for(var i = 0; i != content.length; ++i)
		create_single_item(content[i], title).appendTo(list);

	list.children("li").last().css("border-bottom", "0px");
	body.append(list);
	check_ul_border(body);
	wrap.append(body);
}

function conv_time_str(time_type, time_str)
{
	var str;
	var str_arr = time_str.split(",");
	var a1 = str_arr[0].split(":");
	var a2 = str_arr[1].split(":");
	if(time_type == "daily")
	{
		str = time_str.replace(/,/, " ~ ");
	} else if(time_type == "monthly") {
		str = str_arr[0].replace(/:/, ", ") + " ~ "
			+ str_arr[1].replace(/:/, ", ");
	} else {
		var map = [ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ];
		str = map[a1[0]] + ", " + [a1[1], a1[2], a1[3]].join(":") + " ~ "
			+ map[a2[0]] + ", " + [a2[1], a2[2], a2[3]].join(":");
	}

	return str;
}

function create_time_li(head, time_type, time_str)
{
	var item = $("<div></div>");
	var info = $("<div></div>");
	info.addClass("rule_modify_content");
	var str = conv_time_str(time_type, time_str);
	$("<span></span>").text(str).appendTo(info);
	item.append(info);

	var oper = create_single_item_oper(
		event_modify_time(head),
		event_remove_time(head));
	item.append(oper);

	return $("<li></li>").data("time_str", time_str).append(item);
}

function create_rule_modify_time(wrap, time_type, time_str)
{
	// 创建头部
	var head = create_rule_modify_head("回复时间（" + time_type + "）");
	head.oper.text("修改类型");
	head.oper.data("time_type", time_type);
	head.oper.click(event_reset_time(head.head));
	wrap.append(head.head);

	var oper_link = $("<a></a>");
	oper_link.attr("href", "javascript:;");
	oper_link.text("添加时间");
	head.oper.after(oper_link);
	if(time_type == "all")
	{
		time_str = "";
		oper_link.hide();
	}

	// 创建主体
	var body = $("<div></div>");
	body.addClass("rule_modify_item_body");
	var list = $("<ul></ul>");
	list.addClass("rule_modify_ul");

	var content = time_str.split("|");
	for(var i = 0; i != content.length; ++i)
	{
		if(content[i] == "")
			continue;
		create_time_li(head.head, time_type, content[i]).appendTo(list);
	}

	body.append(list);
	list.children("li").last().css("border-bottom", "0px");
	check_ul_border(body);
	oper_link.click(event_add_time(body));
	wrap.append(body);
}

function event_rename_rule(event)
{
	if(event.which != 13)  // Enter
		return;
	var th = $(this);
	var new_rule_name = th.val();
	$.post("../text_reply_oper.php?action=change_rule_name", 
		{ rid : th.data("rid"), 
		  rule_name_new : new_rule_name }, 
		function(data) {
			if(!check_status(data.status))
				return;
			var info = find_wrap(th).find(".rule_head .info");
			info.children(".rule_name").text(new_rule_name);
			info.fadeOut();
			info.fadeIn();
		}, "json");
}

function event_modify_content_solve(content, elem, title)
{
	var meta_id = content.data("meta_id");
	var new_content = elem.children("textarea").val();
	if(new_content.replace(/\s/g, "") == "")
	{
		scan_alert("错误", title + "不能为空");
		return false;
	}

	$.post("../text_reply_oper.php?action=update_content", 
		{ content_index : meta_id, 
		  content : new_content }, 
		function(data) {
			if(!check_status(data.status))
				return;
			content.data("raw_content", new_content);
			var content_v = format_content(
				content.parent(), new_content, title);
			content.fadeOut( function() {
				content.children("span").html(content_v);
				content.fadeIn();
			} );
		}, "json");
	return true;
}

function event_modify_content(content, title)
{
	return function() {
		var elem = $(".modify_dialog").clone();
		elem.children("textarea").val(content.data("raw_content"));
		confirm_dialog(500, "修改" + title, elem, function() {
			return event_modify_content_solve(content, elem, title);
		} );
	};
}

function remove_list_item(li)
{
	while(li && !li.parent().hasClass("rule_modify_ul"))
		li = li.parent();
	var pa = li.parent();
	pa.children("li").last().css("border-bottom", "");
	li.fadeOut( function() {
		li.remove(); 
		check_ul_border(pa.parent());
		pa.children("li").last().css(
			"border-bottom", "0px");
	} );
}

function event_remove_content(meta_id)
{
	return function() {
		var th = $(this);
		scan_confirm("真的要删除吗？", function() {
			$.post("../text_reply_oper.php?action=remove_content", 
				{ content_index : meta_id }, 
				function(data) {
					if(!check_status(data.status))
						return;
					remove_list_item(th);
				}, "json");
		} );
	}
}

function event_insert_content_solve(body, elem, type, title)
{
	var rid = find_wrap(body).data("rid");
	var value = elem.children("textarea").val();
	if(value.replace(/\s/g, "") == "")
	{
		scan_alert("错误", title + "不能为空");
		return false;
	}

	$.post("../text_reply_oper.php?action=insert_" + type,
		{ rid: rid, value: [value] }, 
		function(data) {
			if(!check_status(data.status))
				return;
			// 获取新加入元素的 ID 并显示
			$.post("../text_reply_oper.php?action=get_rule_info", 
				{ rid : rid }, 
				function(data) {
					if(!check_status(data.status))
						return;

					// 获取已经存在元素的 ID
					var existed_elem = body.find(".rule_modify_content");
					var existed_max = 0;
					existed_elem.each( function() {
						existed_max = Math.max(
							existed_max, $(this).data("meta_id"));
					} );

					// 检测新元素 ID
					var new_elem = -1;
					if(type == "key")
						type = "keyword";
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
						scan_alert("错误", 
							"无法找到新插入的关键字，请刷新页面");
					} else {
						var ul = body.find("ul");
						var cont = kr[new_elem];
						ul.children("li").last().css("border-bottom", "");
						var it = create_single_item(cont, title);
						it.hide();
						it.fadeIn();
						it.appendTo(ul);
						ul.children("li").last()
							.css("border-bottom", "0px");
						check_ul_border(body);
					}
				}, "json" );
		}, "json");
	return true;
}

function event_insert_content(head, type, title)
{
	return function() {
		var elem = $(".modify_dialog").clone();
		elem.children("textarea").val("");
		confirm_dialog(500, "添加" + title, elem, function() {
			return event_insert_content_solve(
				head.next(), elem, type, title);
		} );
	};
}

function event_remove_time(head)
{
	return function() {
		var th = $(this);
		var li = $(this);
		while(li && !li.parent().hasClass("rule_modify_ul"))
			li = li.parent();

		var rid = find_wrap(th).data("rid");
		scan_confirm("真的要删除吗？", function() {
			$.post("../text_reply_oper.php?action=remove_reply_time", 
				{ rid : rid, time_str : li.data("time_str") }, 
				function(data) {
					if(!check_status(data.status))
						return;
					remove_list_item(th);
				}, "json");
		} );
	}
}

function modify_time_set_str(elem, type, time_str)
{
	var time_arr = time_str.split(",");
	var a1 = time_arr[0].split(":");
	var a2 = time_arr[1].split(":");

	var base = 0;
	if(type != "daily") ++base;

	elem.find(".dlg_start_time .hour").val(a1[base]);
	elem.find(".dlg_start_time .minute").val(a1[base + 1]);
	elem.find(".dlg_start_time .second").val(a1[base + 2]);
	elem.find(".dlg_end_time .hour").val(a2[base]);
	elem.find(".dlg_end_time .minute").val(a2[base + 1]);
	elem.find(".dlg_end_time .second").val(a2[base + 2]);

	if(type == "monthly")
	{
		elem.find(".dlg_start_time .day").val(a1[0]);
		elem.find(".dlg_end_time .day").val(a2[0]);
	} else if(type == "weekly") {
		elem.find(".dlg_start_time .week").val(a1[0]);
		elem.find(".dlg_end_time .week").val(a2[0]);
	}
}

function event_modify_time(head)
{
	return function() {
		var li = $(this);
		while(li && !li.parent().hasClass("rule_modify_ul"))
			li = li.parent();
		var type = head.find(".oper").children().first().data("time_type");
		var elem = $("." + type + "_time_dialog").clone(true);
		modify_time_set_str(elem, type, li.data("time_str"));
		var rid = find_wrap(head).data("rid");
		confirm_dialog("auto", "修改时间", elem, function() {
			var time_str = get_time_str(type, elem);
			if(time_str === false) return false;
			$.post("../text_reply_oper.php?action=change_reply_time", 
				{ rid : rid, 
				  time_old : li.data("time_str"),
				  time_new : time_str },
				function(data) {
					if(!check_status(data.status))
						return;
					li.data("time_str", time_str);
					var str = conv_time_str(type, time_str);
					var info = li.find(".rule_modify_content span");
					info.fadeOut( function() {
						info.text(str);
						info.fadeIn();
					} );
				}, "json");
			return true;
		} );
	};
}

function event_reset_time(head)
{
	return function() {
		var elem = $(".time_type_dialog").clone(true);
		var th = $(this);
		confirm_dialog("auto", "设置时间类型", elem, function() {
			var type = elem.find("select").val();
			var rid = find_wrap(head).data("rid");
			$.post("../text_reply_oper.php?action=set_reply_time", 
				{ rid : rid, time_type : type, time_str : "" },
				function(data) {
					if(!check_status(data.status))
						return;

					th.data("time_type", type);
					if(type == "all")
						th.next().fadeOut();
					else th.next().fadeIn();
					var title = head.children(".info").children("span");
					title.fadeOut( function() {
						title.text("回复时间（" + type + "）");
						title.fadeIn();
					} );

					head.next().find("li").fadeOut( function() {
						$(this).remove();
						check_ul_border(head.next());
					} );
				}, "json");
		} );

		return true;
	};
}

function complete_time(arr)
{
	for(var i = 0; i != arr.length; ++i)
		if(arr[i] < 10) arr[i] = "0" + arr[i];
	return arr.join(":");
}

function get_time_str(type, elem)
{
	var sh = parseInt(elem.find(".dlg_start_time .hour").val(), 10);
	var sm = parseInt(elem.find(".dlg_start_time .minute").val(), 10);
	var ss = parseInt(elem.find(".dlg_start_time .second").val(), 10);
	var eh = parseInt(elem.find(".dlg_end_time .hour").val(), 10);
	var em = parseInt(elem.find(".dlg_end_time .minute").val(), 10);
	var es = parseInt(elem.find(".dlg_end_time .second").val(), 10);

	if( sh < 0 || sm < 0 || ss < 0 || eh < 0 || em < 0 || es < 0
	 || sh > 24 || eh > 24 || sm > 60 || em > 60 || ss > 60 || es > 60)
	{
		scan_alert("错误", "时间不合法！");
		return false;
	}

	var start = complete_time([sh, sm, ss]);
	var end = complete_time([eh, em, es]);
	if(type == 'weekly')
	{
		var sw = elem.find(".dlg_start_time .week").val();
		var ew = elem.find(".dlg_end_time .week").val();
		start = sw + ":" + start;
		end = ew + ":" + end;
	} else if(type == 'monthly') {
		var sw = parseInt(elem.find(".dlg_start_time .day").val());
		var ew = parseInt(elem.find(".dlg_end_time .day").val());
		if(sw < 1 || ew < 1 || sw > 31 || ew > 31)
		{
			scan_alert("错误", "时间不合法！");
			return false;
		}

		start = sw + ":" + start;
		end = ew + ":" + end;
	}

	return start + "," + end;
}

function event_add_time_solve(body, elem, type)
{
	var time_str = get_time_str(type, elem);
	if(time_str === false) return false;
	var rid = find_wrap(body).data("rid");
	var list = body.find("ul");
	$.post("../text_reply_oper.php?action=insert_reply_time", 
		{ rid : rid, time_str : time_str }, 
		function(data) {
			if(!check_status(data.status))
				return;
			list.children("li").last().css("border-bottom", "");
			var li = create_time_li(body.prev(), type, time_str);
			li.hide();
			li.fadeIn();
			list.append(li);
			list.children("li").last().css("border-bottom", "0px");
			check_ul_border(list.parent());
		}, "json");
	return true;
}

function event_add_time(body)
{
	return function() {
		var time_type = $(this).prev().data("time_type");
		var elem = $("." + time_type + "_time_dialog").clone(true);
		confirm_dialog("auto", "添加时间", elem, function() {
			return event_add_time_solve(body, elem, time_type);
		} );
	};
}

function event_change_passwd(elem)
{
	var old_passwd = elem.children(".passwd_old").val();
	var new_passwd = elem.children(".passwd_new").val();
	var rep_passwd = elem.children(".passwd_new_rep").val();
	if(new_passwd != rep_passwd)
	{
		scan_alert("错误", "两次输入的密码不相同");
		return false;
	}

	$.post("../account.php?action=change_password", 
		{ old_password : $.md5(old_passwd), 
		  new_password : $.md5(new_passwd) }, 
		function(data) {
			if(data.status != SCAN_WX_STATUS_SUCCESS)
				scan_alert("错误", "修改失败");
			scan_alert("通知", "密码修改成功");
		}, "json");
	return true;
}

function event_remove_rule(rid, wrap)
{
	return function() {
		scan_confirm("删除后不可恢复<br />真的要删除规则吗？",  
			function() {
				$.post("../text_reply_oper.php?action=remove",  
					{ rid : rid }, function(data) {
						if(!check_status(data.status))
							return;
						wrap.fadeOut( function() {
							$(this).remove();
						} );
					} );
				return true;
			} );
	};
}

function event_add_rule(elem, rule_num)
{
	var rule_name = elem.children(".dlg_rule_name").val();
	if(rule_name == "")
	{
		scan_alert("错误", "规则名称不能为空");
		return false;
	}

	$.post("../text_reply_oper.php?action=insert", 
		{ rule_name : rule_name, 
		  match_type : elem.children(".dlg_match_type").val() }, 
		function(data) {
			if(!check_status(data.status))
				return;
			$.getJSON("../text_reply_oper.php?action=get_all_rules", 
				function(rules) {
					if(!check_status(rules.status))
						return;

					// 查找新添加规则的 rid
					var rid_pos = 0;
					var current_rid = 0;
					for(var i = 0; i != rules.count; ++i)
					{
						if(parseInt(rules[i].rid) > current_rid)
						{
							current_rid = parseInt(rules[i].rid);
							rid_pos = i;
						}
					}

					create_single_rule(rules.count, 
						rules[rid_pos]).prependTo($("#rules_list"));
				} );
		}, "json");
	return true;
}

function event_register_user(elem)
{
	var username = elem.children(".dlg_user_name").val();
	if(username.replace(/\s/g, "") == "")
	{
		scan_alert("错误", "用户名不能为空");
		return false;
	}

	var password = elem.children(".dlg_password").val();
	$.post("../account.php?action=register", 
		{ username : username, password : $.md5(password) }, 
		function(data) {
			if(!check_status(data.status))
				return;
			scan_alert("通知", "添加用户成功");
		}, "json");

	return true;
}

function confirm_dialog(width, title, elem, callback)
{
	var dialog_config = {
		lock: true,
		esc: false,
		padding: "30px 40px",
		title: title,
		content: elem.get(0),
		btn: {
			ok: {
				val: "确定",
				type: "green",
				click: callback
			}, 
			cancel: {
				val: "取消"
			}
		}
	};

	if(width != "auto")
		dialog_config.width = width;
	$.dialog(dialog_config);
}
