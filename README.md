scan_weixin
===========

一个微信平台，提供简单的回复和用户管理，可支持多用户使用

目前提供的回复类型有：完全匹配、部分匹配、正则表达式匹配

支持按时间回复、记录用户发来的消息

***

在使用前请先配置 scanwx/config.py 填写数据库用户、密码、微信 Token 等信息

然后将 scan_weixin.sql 导入数据库，使用根目录下的 account.py 注册 root 用户

如果有使用天气查询功能需要百度地图API的AK号

***

本平台用 Python3 编写，需要以来的包有 tornado 以及 pymysql

可以使用 nginx 做反向代理，配置参考 scan_weixin.conf

启动服务请运行 `python3 server.py port`

另外，根目录下的 proxy.php 是转发用的，可以用它向真正的服务器转发微信请求
