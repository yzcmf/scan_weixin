var SCAN_WX_STATUS_SUCCESS = "success";
var SCAN_WX_STATUS_NOLOGIN = "nologin";

function scan_alert(title, content)
{
	$.dialog( {
		lock: true,
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
	default:
		return "fallback";
	}
}
