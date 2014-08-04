<?php

// 数据库服务地址 
define('SCAN_WX_SERVER', 'localhost');
// 数据库名称
define('SCAN_WX_DATABASE', 'scan_weixin');
// 数据库用户名
define('SCAN_WX_USERNAME', 'scan_weixin');
// 数据库密码
define('SCAN_WX_PASSWORD', '');
// Token
define("SCAN_WX_TOKEN", "");

// 密码 salt
define('SCAN_WX_PASSWORD_SALT', '');

// 用户身份
define('SCAN_WX_USER_NOLOGIN', -1);
define('SCAN_WX_USER_ADMIN', 0);
define('SCAN_WX_USER_COMMON', 1);

// 登陆用户最长允许的无操作时间（秒）
define('SCAN_WX_SESSION_KEEP_TIME', 600);

// 返回状态
define('SCAN_WX_STATUS_SUCCESS', 'success');
define('SCAN_WX_STATUS_ERROR', '错误！');
define('SCAN_WX_STATUS_NOLOGIN', '请先登陆');
define('SCAN_WX_STATUS_FORBIDDEN', '权限不够');
define('SCAN_WX_STATUS_RULE_NOT_EXIST', '规则或者内容不存在');
define('SCAN_WX_STATUS_RULE_EXIST', '规则或者内容已经存在');

// 自动回复时间类型
define('SCAN_WX_TIME_DAILY', 'daily');
define('SCAN_WX_TIME_WEEKLY', 'weekly');
define('SCAN_WX_TIME_MONTHLY', 'monthly');
define('SCAN_WX_TIME_ALL', 'all');

?>
