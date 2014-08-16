
import scanwx.client

class handler(scanwx.client.handler):
	def get(self):
		uid = self.get_current_user()
		if uid is None:
			self.redirect('/client/login.html')
		else:
			self.set_header('Content-Type', 'text/html')
			self.render('../../client/rule.html')

