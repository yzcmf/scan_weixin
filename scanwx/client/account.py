
import hashlib
import time

from tornado.web import MissingArgumentError
import scanwx.client
import scanwx.config as config

class handler(scanwx.client.handler):
	def get(self):
		self.post()

	def post(self):
		try:
			action = self.get_argument('action')
		except MissingArgumentError:
			self.exit_with(config.status_error)

		db = self.application.db
		db.commit()
		if action == 'login':
			self.__login(db)
		elif action == 'logout':
			self.set_secure_cookie('user', '')
			self.set_secure_cookie('login_time', '')
		elif action == 'register':
			self.__register(db)
		elif action == 'get_user_info':
			self.__get_user_info(db)
		elif action == 'change_password':
			self.__change_password(db)
		else: self.exit_with(config.status_error)

	def __login(self, db):
		'''
		用于登陆管理系统

		username 用户名
		password 密码（MD5 散列后的值）
		'''
		try:
			username = self.get_argument('username').strip()
			password = self.get_argument('password').strip()
		except MissingArgumentError:
			self.exit_with(config.status_error)
		password = self.__encode_passwd(password)
		user_info = db.get_row_dict('SELECT * \
			FROM user WHERE username = %s', [username])
		if user_info is None or user_info['password'] != password:
			self.exit_with(config.status_error)
		self.set_secure_cookie('user', str(user_info['uid']))
		self.set_secure_cookie('login_time', str(time.time()))
		self.exit_with(config.status_success)

	def __get_user_info(self, db):
		'''
		用于获取当前登陆用户的信息
		'''
		info = self.get_current_user_info()
		if info is None:
			self.exit_with(config.status_nologin)
		self.exit_with(config.status_success, info)

	def __change_password(self, db):
		'''
		用于修改密码

		username     用户名
		old_password 旧密码（MD5 散列后的值）
		new_password 新密码（MD5 散列后的值）
		'''
		if self.get_current_user() is None:
			self.exit_with(config.status_nologin)
		if not self.is_admin():
			self.exit_with(config.status_forbidden)
		try:
			password_old = self.get_argument('old_password').strip()
			password_new = self.get_argument('new_password').strip()
		except MissingArgumentError:
			self.exit_with(config.status_error)
			return

		try:
			uid = self.get_argument('uid')
		except MissingArgumentError:
			uid = str(self.get_current_user())
		if int(uid) == -1:
			uid = str(self.get_current_user())
		is_admin = self.is_admin()
		if uid != str(self.get_current_user()) and not is_admin:
			self.exit_with(config.status_forbidden)

		password_old = self.__encode_passwd(password_old)
		password_new = self.__encode_passwd(password_new)
		info = db.get_row_dict('SELECT * FROM user WHERE uid = %s', [uid])
		if not is_admin and password_old != info['password']:
			self.exit_with(config.status_forbidden)

		db.query('UPDATE user SET password = %s \
			WHERE uid = %s', [password_new, uid])
		self.exit_with(config.status_success)

	def __register(self, db):
		'''
		用于注册账户（管理员可以）

		username 用户名
		password 密码（MD5 散列后的值）
		'''
		if self.get_current_user() is None:
			self.exit_with(config.status_nologin)
		if not self.is_admin():
			self.exit_with(config.status_forbidden)
		try:
			username = self.get_argument('username').strip()
			password = self.get_argument('password').strip()
		except MissingArgumentError:
			self.exit_with(config.status_error)

		password = self.__encode_passwd(password)
		if db.get_result('SELECT uid FROM user \
			WHERE username = %s', [username]) is not None:
			self.exit_with(config.status_error)

		db.query('INSERT INTO user (username, password, role) \
			VALUES (%s, %s, %s)', [username, password, 'common'])
		self.exit_with(config.status_success)

	def __encode_passwd(self, passwd):
		' 编码密码 '
		passwd = passwd.strip() + config.password_salt
		return hashlib.sha1(passwd.encode('utf8')).hexdigest()
