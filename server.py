#!/usr/bin/python3

import tornado.web
import tornado.ioloop
import server.database
import server.server
import sys

class scan_wx_application(tornado.web.Application):
	def __init__(self):
		self.db = server.database.database()
		self.db.connect()

		handlers = [
			(r'/server', server.server.handler)
		]

		super(scan_wx_application, self).__init__(
			handlers,
			cookie_secret = 'ee32a1a78d80ed6507970e114189c77d')

app = scan_wx_application()
app.listen(int(sys.argv[1]))
tornado.ioloop.IOLoop.instance().start()

