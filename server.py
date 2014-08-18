#!/usr/bin/python3

import tornado.web
import tornado.ioloop
import tornado.httpserver
import scanwx.database
import scanwx.server
import scanwx.client
import scanwx.client.account
import scanwx.client.oper
import scanwx.client.login
import scanwx.client.rule
import scanwx.config as config
import sys

class scan_wx_application(tornado.web.Application):
	def __init__(self):
		self.db = scanwx.database.database()
		handlers = [
			(r'/server', scanwx.server.handler),
			(r'/client/account', scanwx.client.account.handler),
			(r'/client/oper', scanwx.client.oper.handler),
			(r'/client/?', scanwx.client.handler),
			(r'/client/login.html', scanwx.client.login.handler),
			(r'/client/rule.html', scanwx.client.rule.handler),
			(r'/client/(.*\.(js|css|png))',
				tornado.web.StaticFileHandler,
				{ 'path': './client' })
		]

		settings = dict(
			cookie_secret = config.cookie_secret,
			compress_response = { 'gzip': 6 },
			gzip = True
		)

		super(scan_wx_application, self).__init__(handlers, **settings)

if __name__ == '__main__':
	app = scan_wx_application()
	port = int(sys.argv[1])
	app.listen(app)
	tornado.ioloop.IOLoop.instance().start()

