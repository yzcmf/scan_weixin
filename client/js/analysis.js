
function analysis_vote_solve(data, limit)
{
	var map = [];
	var size = [];
	for(var i = data.count - 1; i >= 0; --i)
	{
		var from = data[i].from;
		if(!map[from] || size[from] < limit)
		{
			if(map[from] == undefined)
			{
				map[from] = [];
				size[from] = 0;
			}

			map[from][data[i].content] = 1;
			++size[from];
		}
	}

	reverse_map = [];
	for(key in map)
	{
		for(value in map[key])
		{
			if(reverse_map[value] == undefined)
				reverse_map[value] = 0;
			++reverse_map[value];
		}
	}

	reverse_map.sort( function(a, b) { return b - a; } );
	
	wrap = $("<div></div>")
	wrap.addClass("show_record_wrap");

	table = $("<table></table>");
	table.addClass("analytics");
	title = $("<tr></tr>");
	$("<th></th>").css("width", "375px").text("投票选项").appendTo(title);
	$("<th></th>").css("width", "75px").text("总票数").appendTo(title);
	title.appendTo(table);
	for(key in reverse_map)
	{
		row = $("<tr></tr>");
		$("<td></td>").text(key).appendTo(row);
		$("<td></td>").text(reverse_map[key]).appendTo(row);
		row.appendTo(table);
	}

	table.find("tr").addClass("light");
	table.find("tr").last().removeClass("light");
	wrap.append(table);

	$.dialog( {
		title: "投票分析结果",
		padding: "0px",
		content: wrap.get(0),
		lock: true,
		esc: false,
		width: 500,
		height: 400
	} );
}

function analysis_vote(content)
{
	var wrap = $("<div></div>");
	$("<p></p>").text("请输入每个人最多可以投的票数").appendTo(wrap);
	var input = $("<input type=\"text\"/>");
	input.val("1");
	input.click(limit_input_number);
	input.appendTo(wrap);

	$.dialog( {
		content: wrap.get(0),
		lock: true,
		esc: false,
		btn: {
			ok: {
				val: "确定",
				type: "green",
				click: function() {
					analysis_vote_solve(content, 
						parseInt(input.val(), 10));
					return true;
				}
			},
			cancel: {
				val: "取消"
			}
		}
	} );
}
