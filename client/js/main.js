var SCAN_WX_STATUS_SUCCESS = "success";

function check_status(status)
{
	if(status == SCAN_WX_STATUS_SUCCESS)
		return true;
	alert("错误: " + status);
	return false;
}
