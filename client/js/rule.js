var flush_count = 0;
var global_uid = -1;
$(document).ready( function() {
	$.getJSON("account?action=get_user_info", 
		function(data) {
			if(data.status != SCAN_WX_STATUS_SUCCESS)
				window.open("login.html", "_self");
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
		$.getJSON("account?action=logout",
			{}, function(data) {
				window.open("login.html", "_self");
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
		$.post("oper?action=get_all_rules", 
			{ uid : global_uid },
			function(rules) {
				++flush_count;
				if(!check_status(rules.status))
					return;
				if(flush_count % 2 == 0)
					reflush_rule(rules, $("#rule_wrap"));
				else rtemp = rules;
			}, "json" );
		$("#rule_wrap").fadeOut( function() {
			$(this).empty();
			$(this).fadeIn();
			if(++flush_count % 2 == 0 && rtemp != undefined)
				reflush_rule(rtemp, $("#rule_wrap"));
		} );
	} );

	$.post("oper?action=get_all_rules", 
		{ uid : global_uid },
		function(rules) {
			if(!check_status(rules['status']))
				return;

			$("#rule_tool").fadeIn();

			$(".lw").keypress(limit_input_number);
			$("#waiting_msg").remove();
			reflush_rule(rules, $("#rule_wrap"));
		}, "json" );
} );

function smooth_change_text(elem, text)
{
	elem.fadeOut( function() {
		elem.text(text);
		elem.fadeIn();
	} );
}

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
	$.post("oper?action=get_rule_info", 
		{ rid : rule_info.rid, uid : global_uid },
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

	if(rule.match_type != "fallback" 
	 && rule.match_type != "forward"
	 && rule.match_type != "pushup")
	{
		var record_str = rule.record_require == "1" ? "true" : "false";
		record_str += "，共 " + rule.record_num + " 条";
		create_rule_body_item("记录消息", record_str).appendTo(rule_body);

		if(rule.match_require == undefined)
			rule.match_require = 1;
		create_rule_body_item("需要匹配次数",
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

function create_rule_modify_input(title, text, action)
{
	var rule_name_wrap = $("<div></div>");
	rule_name_wrap.addClass("rule_head_meta");
	var rule_name_area = $("<div></div>");

	$("<label></label>")
		.addClass("rule_modify_detail")
		.addClass("circle_bk")
		.text(title)
		.appendTo(rule_name_area);

	var rule_name_input = generate_span_input(text);
	rule_name_input.children("input").keyup(action);

	rule_name_area.append(rule_name_input);
	rule_name_wrap.append(rule_name_area);
	return rule_name_wrap;
}

function create_rule_modify(wrap, rule)
{
	// 添加修改信息
	var rule_modify = $("<div></div>");
	rule_modify.addClass("rule_modify");
	rule_modify.hide();

	// 规则名区域
	var rule_name_wrap = create_rule_modify_input(
		"规则名称", rule.rule_name, event_rename_rule);
	rule_name_wrap.children().first().css("float", "left");
	rule_name_wrap.children().first().height(39);

	// 添加删除规则按钮
	var rule_remove = $("<div></div>");
	rule_remove.addClass("oper");
	var rule_remove_link = $("<a></a>");
	rule_remove_link.attr("href", "javascript:;");
	rule_remove_link.text("删除规则");
	rule_remove_link.click(event_remove_rule(rule.rid, wrap));
	rule_remove.append(rule_remove_link);
	rule_name_wrap.append(rule_remove);

	rule_modify.append(rule_name_wrap);

	if(rule.match_type != "forward" 
	 && rule.match_type != "pushup"
	 && rule.match_type != "fallback")
	{
		// 匹配次数区域
		var rule_match_req = create_rule_modify_input(
			"需要匹配次数", rule.match_require, event_match_require_set);
		rule_match_req.find("input[type=text]")
			.keypress(limit_input_number);
		rule_modify.append(rule_match_req);

		// 消息记录区域
		create_rule_modify_record(rule_modify, rule.record_require == 1); 
	}

	// 回复时间类型
	create_rule_modify_time(rule_modify, rule.time_type, rule.time_str);

	// 关键字区域
	if(rule.match_type != "fallback")
	{
		create_rule_modify_content(rule_modify, 
			rule.keyword, 'key', "关键词");
	}

	// 回复区域
	if(rule.match_type != "forward")
	{
		create_rule_modify_content(rule_modify, 
			rule.reply, 'reply', "回复");
	}

	rule_modify.children().last().addClass("head_check");
	check_ul_border(rule_modify.children().last());

	if(rule.match_type != "fallback" && rule.match_type != "forward")
	{
		if(rule.reply_all == undefined)
			rule.reply_all = 0;
		var reply_op = rule_modify.children()
			.last().prev().children(".oper");
		var reply_all = $("<input type=\"checkbox\"/>");
		reply_all.css("vertical-align", "middle");
		reply_all.change(event_set_reply_all);
		if(rule.reply_all == 1)
			reply_all.attr("checked", true);
		$("<span></span>").text("回复全部").prependTo(reply_op);
		reply_op.prepend(reply_all);
	}

	wrap.append(rule_modify);
}

function format_content(item, content, title)
{
	var content_v = encode_html(content);
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

	return { head : head, info : head_info, oper : oper_link };
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
	} else if(time_type == "weekly") {
		var map = [ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ];
		str = map[a1[0]] + ", " + [a1[1], a1[2], a1[3]].join(":") + " ~ "
			+ map[a2[0]] + ", " + [a2[1], a2[2], a2[3]].join(":");
	} else {
		var map = [ "Jan", "Feb", "Mar", "Apr", "May", "June", 
					"July", "Aug", "Sept", "Oct", "Nov", "Dec" ];
		var fun = function(v) {
			return map[v[1]] + " " + v[2] + ", " 
					+ [v[3], v[4], v[5]].join(":") + ", " + v[0];
		};

		str = fun(a1) + " ~ " + fun(a2);
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

function create_rule_modify_record(wrap, record_require)
{
	var str = record_require ? "true" : "false";
	var head = create_rule_modify_head("记录消息（" + str + "）");
	if(record_require)
		head.oper.text("取消记录");
	else head.oper.text("设置记录");
	head.oper.data("record_require", record_require);
	head.oper.click(event_set_record(head.head));

	var oper_link = $("<a></a>");
	oper_link.attr("href", "javascript:;");
	oper_link.text("查看记录");
	oper_link.click(event_show_record);
	head.oper.after(oper_link);

	wrap.append(head.head);
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
	$.post("oper?action=change_rule_name", 
		{ rid : find_wrap(th).data("rid"), 
		  rule_name_new : new_rule_name, 
		  uid : global_uid }, 
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

	new_content = new_content.replace(/\n*$/, "");

	$.post("oper?action=update_content", 
		{ content_index : meta_id, 
		  content : new_content,
		  uid : global_uid }, 
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
			$.post("oper?action=remove_content", 
				{ content_index : meta_id, 
				  uid : global_uid }, 
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

	$.post("oper?action=insert_" + type,
		{ rid: rid, value: value, uid: global_uid }, 
		function(data) {
			if(!check_status(data.status))
				return;
			var ul = body.find("ul");
			ul.children("li").last().css("border-bottom", "");
			var it = create_single_item(data.content, title);
			it.hide();
			it.fadeIn();
			it.appendTo(ul);
			ul.children("li").last()
				.css("border-bottom", "0px");
			check_ul_border(body);
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
			$.post("oper?action=remove_reply_time", 
				{ rid : rid, 
				  time_str : li.data("time_str"), 
				  uid : global_uid },
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
	if(type != "daily") 
		base += type != 'exact' ? 1 : 3;

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
	} else if(type == "exact") {
		elem.find(".dlg_start_time .year").val(a1[0]);
		elem.find(".dlg_start_time .month").val(a1[1]);
		elem.find(".dlg_start_time .day").val(a1[2]);
		elem.find(".dlg_end_time .year").val(a2[0]);
		elem.find(".dlg_end_time .month").val(a2[1]);
		elem.find(".dlg_end_time .day").val(a2[2]);
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
			$.post("oper?action=change_reply_time", 
				{ rid : rid, 
				  time_old : li.data("time_str"),
				  time_new : time_str,
				  uid : global_uid },
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
			$.post("oper?action=set_reply_time", 
				{ rid : rid, 
				  time_type : type, 
				  time_str : "",
				  uid : global_uid },
				function(data) {
					if(!check_status(data.status))
						return;

					th.data("time_type", type);
					if(type == "all")
						th.next().fadeOut();
					else th.next().fadeIn();
					var title = head.children(".info").children("span");
					smooth_change_text(title, "回复时间（" + type + "）");

					head.next().find("li").fadeOut( function() {
						$(this).remove();
						check_ul_border(head.next());
					} );
				}, "json");
		} );

		return true;
	};
}

function event_set_record(head)
{
	return function() {
		var record_require = $(this).data("record_require");
		var rid = find_wrap(head).data("rid");
		var th = $(this);
		$.post("oper?action=set_rule_record", 
			{ rid: rid, 
			  record_require: record_require ? "0" : "1",
			  uid: global_uid }, 
			function(data) {
				if(!check_status(data.status))
					return;

				th.data("record_require", !record_require);
				smooth_change_text(th, 
					record_require ? "设置记录" : "取消记录");

				var title = head.children(".info").children("span");
				smooth_change_text(title, "记录消息（" 
					+ (record_require ? "false）" : "true）"));
			}, "json");
	};
}

function event_show_record()
{
	var rid = find_wrap($(this)).data("rid");
	$.post("oper?action=get_rule_record", 
		{ rid : rid, uid : global_uid }, function(data) {
			if(!check_status(data.status))
				return;

			var wrap = $("<div></div>");
			wrap.addClass("show_record_wrap");

			var list = $("<ul></ul>");

			for(var i = data.count - 1; i >= 0; --i)
			{
				var elem = $("<div></div>");
				elem.addClass("show_record_elem");

				$("<span></span>")
					.text(data[i].date)
					.addClass("record_date")
					.appendTo(elem);

				$("<span></span>")
					.text("（" + data[i].from + "）")
					.addClass("record_from")
					.appendTo(elem);

				$("<a></a>")
					.text("×")
					.attr("title", "删除")
					.attr("href", "javascript:;")
					.addClass("close_a")
					.click( function(id, elem) {
						return function() {
							$.post("oper?action="
								+ "clear_rule_record_by_id", 
								{ rid: rid,
								  record_id: id, 
								  uid: global_uid },
								function(data) {
									if(!check_status(data.status))
										return false;
									elem.parent().fadeOut( function() {
										$(this).remove();
									} );
								}, "json");
						};
					}(data[i].index, elem)).appendTo(elem);

				var content = data[i].content;
				var break_str = false;
				if(content.length > 250)
				{
					content = content.slice(0, 250) + " ";
					break_str = true;
				}

				var obj = $("<div></div>")
					.html(encode_html(content))
					.addClass("record_content")
					.appendTo(elem);

				if(break_str)
				{
					var link = $("<a></a>");
					link.data("content", encode_html(data[i].content));
					link.attr("href", "javascript:;");
					link.attr("title", "显示全部");
					link.text("...");
					link.addClass("close_a");
					link.click( function() {
						var content = $(this).data("content");
						$(this).parent().fadeOut( function() {
							$(this).html(content);
							$(this).fadeIn();
						} );
					} );
					obj.append(link);
				}

				$("<li></li>").append(elem).appendTo(list);
			}

			wrap.append(list);

			btn = {
				vote: {
					type: "green",
					val: "分析投票",
					click: function(d) {
						return function() {
							analysis_vote(d);
							return false;
						}
					}(data)
				}
			};

			$.dialog( {
				title: "消息记录",
				padding: "0px",
				content: wrap.get(0),
				lock: true,
				esc: false,
				width: 500,
				height: 400,
				btn: btn } );
		}, "json");
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
	} else if(type == 'exact') {
		var sw = parseInt(elem.find(".dlg_start_time .year").val());
		var ew = parseInt(elem.find(".dlg_end_time .year").val());
		var su = parseInt(elem.find(".dlg_start_time .month").val());
		var eu = parseInt(elem.find(".dlg_end_time .month").val());
		var sv = parseInt(elem.find(".dlg_start_time .day").val());
		var ev = parseInt(elem.find(".dlg_end_time .day").val());
		if(su < 1 || eu < 1 || su > 12 || eu > 12
		 || sv < 1 || ev < 1 || sv > 31 || ev > 31
		 || sw < 2000 || ew < 2000 || sw > 2037 || ew > 2037)
		{
			scan_alert("错误", "时间不合法！");
			return false;
		}

		start = sw + ":" + su + ":" + sv + ":" + start;
		end = ew + ":" + eu + ":" + ev + ":" + end;
	}

	return start + "," + end;
}

function event_add_time_solve(body, elem, type)
{
	var time_str = get_time_str(type, elem);
	if(time_str === false) return false;
	var rid = find_wrap(body).data("rid");
	var list = body.find("ul");
	$.post("oper?action=insert_reply_time", 
		{ rid : rid, time_str : time_str, uid : global_uid }, 
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

	$.post("account?action=change_password", 
		{ old_password : $.md5(old_passwd), 
		  new_password : $.md5(new_passwd), 
		  uid : global_uid }, 
		function(data) {
			if(data.status != SCAN_WX_STATUS_SUCCESS)
			{
				scan_alert("错误", "修改失败");
				return;
			}
			scan_alert("通知", "密码修改成功");
		}, "json");
	return true;
}

function event_remove_rule(rid, wrap)
{
	return function() {
		scan_confirm("删除后不可恢复<br />真的要删除规则吗？",  
			function() {
				$.post("oper?action=remove",  
					{ rid : rid, uid : global_uid }, function(data) {
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

	$.post("oper?action=insert", 
		{ rule_name : rule_name, 
		  match_type : elem.children(".dlg_match_type").val(),
		  uid : global_uid }, 
		function(data) {
			if(!check_status(data.status))
				return;
			var count = $(".rule_wrap_item").length + 1;
			var elem = create_single_rule(count, data);
			elem.prependTo($("#rules_list"));
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
	$.post("account?action=register", 
		{ username : username, password : $.md5(password) }, 
		function(data) {
			if(!check_status(data.status))
				return;
			scan_alert("通知", "添加用户成功");
		}, "json");

	return true;
}

function event_match_require_set(event)
{
	if(event.which != 13) return true;
	var match_require = parseInt($(this).val(), 10);
	if(match_require <= 0)
	{
		scan_alert("错误", "关键字需要匹配的最少次数不能够小于1");
		return true;
	}

	var th = $(this);
	$.post("oper?action=set_match_require", 
		{ rid: find_wrap($(this)).data("rid"), 
		  match_require: match_require, 
		  uid: global_uid }, function(data) {
		 	if(!check_status(data.status))
				return;
			th.fadeOut();
			th.fadeIn();
		}, "json");
	return true;
}

function event_set_reply_all()
{
	var rid = find_wrap($(this)).data("rid");
	var checked = $(this).attr("checked");
	if(checked == undefined)
		checked = 0;
	else if(checked == true || checked == "checked")
		checked = 1;
	var th = $(this);
	$.post("oper?action=set_reply_all", 
		{ rid : rid, 
		  reply_all : checked,
		  uid : global_uid }, function(data) {
			if(!check_status(data.status))
				return;
			th.fadeOut();
			th.next().fadeOut();
			th.fadeIn();
			th.next().fadeIn();
		}, "json");
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
