
import time
import json

import tornado.web
import scanwx.config as config

class handler(tornado.web.RequestHandler):
	def get_current_user(self):
		' 获取当前登陆用户的 UID '
		login_time = self.get_secure_cookie('login_time')
		if not login_time:
			return None
		if time.time() - float(login_time) > config.session_keep_time:
			self.set_secure_cookie('user', '')
			self.set_secure_cookie('login_time', '')
			return None
		self.set_secure_cookie('login_time', str(time.time()))
		return int(self.get_secure_cookie('user'))

	def get_current_user_info(self):
		' 获取当前登陆用户的信息 '
		db = self.application.db
		uid = self.get_current_user()
		if uid is None: return None
		info = db.get_row_dict('SELECT username, uid, role \
			FROM user WHERE uid = %s', [uid])
		return info

	def is_admin(self):
		' 查看当前用户是否是管理员 '
		info = self.get_current_user_info()
		if info is None: return False
		if info['role'] == 'administrator':
			return True
		return False

	def exit_with(self, status, args = None):
		if args is None:
			args = {}
		args['status'] = status
		self.set_header('Content-Type', 'application/json')
		self.write(json.dumps(args))
		self.finish()
		raise tornado.web.Finish()

	def on_finish(self):
		self.db.commit()
		self.db.close()

	def prepare(self):
		self.db = self.application.db
		self.db.connect()

	def get(self):
		if self.get_current_user() is None:
			self.redirect('/client/login.html')
		else: self.redirect('/client/rule.html')
