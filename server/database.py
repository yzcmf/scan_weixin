import pymysql
import server.config as config

class database:
	def connect(self):
		'连接数据库'
		try:
			self.__conn = pymysql.connect(
				host = config.server,
				user = config.username,
				passwd = config.password,
				db = config.database,
				charset = 'utf8')
		except:
			raise RuntimeError('无法连接数据库')

	def close(self):
		'关闭数据库连接'
		if self.__conn:
			self.__conn.close()

	def query_list(self, sql, args = None):
		'以列表方式生成结果'
		for i in self.__query(sql, pymysql.cursors.Cursor, args):
			yield i

	def query_dict(self, sql, args = None):
		'以字典方式生成结果'
		for i in self.__query(sql, pymysql.cursors.DictCursor, args):
			yield i

	def query(self, sql, args = None):
		'查询数据不返回信息'
		for i in self.__query(sql, pymysql.cursors.Cursor, args):
			pass

	def commit(self):
		'提交缓存内容'
		self.__conn.commit()

	def get_row_list(self, sql, args = None):
		'以列表方式获取查询结果的第一个行'
		for i in self.query_list(sql, args):
			return i
		return None

	def get_row_dict(self, sql, args = None):
		'以字典方式获取查询结果的第一个行'
		for i in self.query_dict(sql, args):
			return i
		return None

	def get_result(self, sql, args = None):
		'获取查询结果的第一个元素'
		result = self.get_row_list(sql, args)
		if not result: return None
		return result[0]

	def __query(self, sql, curtype, args):
		'查询数据'
		self.__check()
		cur = self.__conn.cursor(cursor = curtype)
		cur.execute(sql, args)
		for data in cur:
			yield data
		cur.close()

	def __check(self):
		'检测数据库是否连接'
		if not self.__conn:
			raise RuntimeError('数据库连接错误')
