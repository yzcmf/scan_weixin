# -*- coding: utf-8 -*-

'基本配置文件'

#数据库服务器地址
server = 'localhost'
#数据库名称
database = 'scan_weixin'
#数据库用户名
username = 'scan_weixin'
#数据库密码
password = '2mLbbSjQveZ5rw9t'
#Token
token = '4efee8b32fdd908988f44bcde6c50998'
#cookie 密钥
cookie_secret = 'ee32a1a78d80ed6507970e114189c77d'

#密码salt
password_salt = '2398fhadjvbqfihef'

#用户身份
user_nologin = -1
user_admin = 0
user_common = 1

#登陆用户允许最长无操作时间（s）
session_keep_time = 600

#返回状态
status_success = 'success'
status_error = '错误！'
status_nologin = '没有登陆'
status_forbidden = '权限不够'
status_rule_not_exist = '规则不存在'
status_rule_exist = '规则已经存在'

#规则时间类型
time_all = 'all'
time_daily = 'daily'
time_weekly = 'weekly'
time_monthly = 'monthly'
time_exact = 'exact'
