# -*- coding: utf-8 -*-

import scanwx.client

class handler(scanwx.client.handler):
	def get(self):
		uid = self.get_current_user()
		if uid is not None:
			self.redirect('/client/rule.html')
		else:
			self.set_header('Content-Type', 'text/html')
			self.render('../../client/login.html')

