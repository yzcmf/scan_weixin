var SCAN_WX_STATUS_SUCCESS = "success";

function check_status(status)
{
	if(status == SCAN_WX_STATUS_SUCCESS)
		return true;
	alert("请先登陆！");
	return false;
}
