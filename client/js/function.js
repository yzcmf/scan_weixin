var SCAN_WX_STATUS_SUCCESS = "success";

function scan_alert(title, content)
{
	$.dialog( {
		lock: true,
		width: 250,
		content: content,
		title: title,
		btn: {
			cancel: {
				val: "确定",
				type: "green"
			}
		}
	} );
}

function scan_confirm(content, allow, deny)
{
	$.dialog( {
		lock: true,
		content: content,
		btn: {
			ok: {
				val: "确定",
				type: "green",
				click: function() {
					if(allow != undefined)
						allow();
					return true;
				}
			},
			cancel: {
				val: "取消",
				click: function() {
					if(deny != undefined)
						deny();
					return true;
				}
			}
		}
	} );
}

function check_status(status)
{
	if(status == SCAN_WX_STATUS_SUCCESS)
		return true;
	scan_alert("错误", status);
	return false;
}

function map_reply_type(type)
{
	switch(type)
	{
	case "sub_match":
		return "部分匹配";
	case "full_match":
		return "完全匹配";
	case "regex_match":
		return "正则表达式";
	case "forward":
		return "forward";
	case "pushup":
		return "公告";
	default:
		return "fallback";
	}
}

function encode_html(content)
{
	return content
		 .replace(/&/g, "&amp")
		 .replace(/</g, "&lt;")
		 .replace(/>/g, "&gt;")
		 .replace(/ /g, "&nbsp;")
		 .replace(/\t/g, "&nbsp;&nbsp;&nbsp;&nbsp;")
		 .replace(/\n/g, "<br />");
}

function limit_input_number(e)
{
	if(!e.which || e.which == 8) return true;
	if(e.which < 48 || e.which > 57)
		return false;
	return true;
} 

