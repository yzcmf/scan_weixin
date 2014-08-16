
from tornado.web import MissingArgumentError
import scanwx.client
import scanwx.config as config

def check_time_available(time_type, time_str):
	' 检测时间是否合法 '
	def check(time, reach_zero, limit):
		try:
			time_arr = list(map(int, time.split(':')))
			if len(time_arr) != len(limit):
				return False
			if not reach_zero and time_arr[0] == 0:
				return False
			for a, b in zip(time_arr, limit):
				if a > b: return False
			return True
		except:
			return False

	time_str = time_str.strip()
	if not time_str: return True
	if time_type == config.time_all:
		return True
	reach_zero = True
	if time_type == config.time_daily:
		limit = [24, 60, 60]
	elif time_type == config.time_weekly:
		limit = [6, 24, 60, 60]
	else:
		limit = [31, 24, 60, 60]
		reach_zero = False
	for t in time_str.split('|'):
		j = t.split(',')
		if len(j) != 2:
			return False
		for r in j:
			if not check(r, reach_zero, limit):
				return False
	return True

class handler(scanwx.client.handler):
	def post(self):
		user_info = self.get_current_user_info()
		if user_info is None:
			self.exit_with(config.status_nologin)

		try:
			action = self.get_argument('action')
		except MissingArgumentError:
			self.exit_with(config.status_error)

		# 检测 UID 是否有权限操作
		try:
			uid = self.get_argument('uid')
		except MissingArgumentError:
			uid = user_info['uid']
		if int(uid) == -1: uid = user_info['uid']
		uid = int(uid)

		if uid != user_info['uid'] and user_info['role'] != 'administrator':
			self.exit_with(config.status_forbidden)

		self.db = self.application.db
		self.db.commit()
		if action == 'insert':
			'''
			插入规则

			rule_name  规则的名称
			match_type 规则的类型
			'''
			try:
				rule_name = self.get_argument('rule_name')
				rule_type = self.get_argument('match_type')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			ret = self.__insert_rule(rule_name, rule_type, uid)
			self.exit_with(ret)
		elif action == 'change_rule_name':
			'''
			重命名规则

			rid       规则的ID
			rule_name 规则的新名字
			'''
			try:
				rid = self.get_argument('rid')
				rule_name_new = self.get_argument('rule_name_new')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			ret = self.__rename_rule(int(rid), rule_name_new, uid)
			self.exit_with(ret)
		elif action == 'remove':
			'''
			删除规则

			rid       规则的ID
			'''
			try:
				rid = self.get_argument('rid')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			ret = self.__remove_rule(int(rid), uid)
			self.exit_with(ret)
		elif action == 'get_all_rules':
			'''
			获得所有规则的名称

			uid  用户 ID
			'''
			status, ret = self.__get_all_rules(uid)
			self.exit_with(status, ret)
		elif action == 'get_rule_info':
			'''
			获取规则详细信息

			rid 规则的 ID
			uid 用户的 ID
			'''
			try:
				rid = self.get_argument('rid')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			status, ret = self.__get_rule_detail(int(rid), uid)
			self.exit_with(status, ret)
		elif action in ('remove_content', 'update_content'):
			'''
			更新关键字或回复内容

			content_index  内容的 ID
			content        新的内容

			删除关键字或回复内容
			content_index  内容的 ID
			'''
			try:
				mid = self.get_argument('content_index')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			allow_key = ('reply', 'keyword')
			key_type = self.__get_meta_type(mid)
			if key_type is None or key_type not in allow_key:
				self.exit_with(config.status_forbidden)
			if action == 'update_content':
				try:
					value = self.get_argument('content')
				except MissingArgumentError:
					self.exit_with(config.status_error)
				status = self.__update_meta(mid, value, uid)
			else: status = self.__remove_meta(mid, uid)
			self.exit_with(status)
		elif action in ('insert_reply', 'insert_key'):
			'''
			插入关键字或这回复

			rid    规则的 ID
			value  插入的内容
			uid    用户的 ID
			'''
			try:
				rid = self.get_argument('rid')
				value = self.get_argument('value')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			if self.__get_rule_owner(rid) != uid:
				self.exit_with(config.status_forbidden)
			key_type = 'keyword'
			if action == 'insert_reply':
				key_type = 'reply'
			self.__insert_meta(rid, key_type, value, uid)
			self.exit_with(config.status_success)
		elif action == 'set_reply_time':
			'''
			设置回复时间

			rid       规则的 ID
			time_type 回复时间类型
			time_str  回复时间串
			uid       用户的 ID
			'''
			try:
				rid = self.get_argument('rid')
				time_type = self.get_argument('time_type')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			if time_type != config.time_all:
				try:
					time_str = self.get_argument('time_str')
				except MissingArgumentError:
					self.exit_with(config.status_error)
			else: time_str = ''

			ret = self.__set_rule_time(rid, time_type, time_str, uid)
			self.exit_with(ret)
		elif action == 'insert_reply_time':
			'''
			添加回复时间

			rid       规则的 ID
			time_str  回复时间串
			uid       用户的 ID
			'''
			try:
				rid = self.get_argument('rid')
				time_str = self.get_argument('time_str')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			ret = self.__add_rule_time(rid, time_str, uid)
			self.exit_with(ret)
		elif action == 'remove_reply_time':
			'''
			删除回复时间

			rid       规则的 ID
			time_str  回复时间串
			uid       用户的 ID
			'''
			try:
				rid = self.get_argument('rid')
				time_str = self.get_argument('time_str')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			ret = self.__remove_rule_time(rid, time_str, uid)
			self.exit_with(ret)
		elif action == 'change_reply_time':
			'''
			更新回复时间

			rid       规则的 ID
			time_old  旧的回复时间串
			time_new  新的回复时间串
			uid       用户的 ID
			'''
			try:
				rid = self.get_argument('rid')
				time_new = self.get_argument('time_new')
				time_old = self.get_argument('time_old')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			ret = self.__update_rule_time(rid, time_old, time_new, uid)
			self.exit_with(ret)
		elif action == 'get_rule_record':
			'''
			获取规则记录

			rid 规则 ID
			uid 用户 ID
			'''
			try:
				rid = self.get_argument('rid')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			status, ret = self.__get_rule_record(rid, uid)
			self.exit_with(status, ret)
		elif action == 'clear_rule_record_by_id':
			'''
			根据 ID 删除规则记录

			rid       规则 ID
			record_id 记录 ID
			uid       用户 ID
			'''
			try:
				rid = self.get_argument('rid')
				record_id = self.get_argument('record_id')
			except MissingArgumentError:
				self.exit_with(config.status_error)
			if self.__get_rule_owner(rid) != uid:
				self.exit_with(config.status_forbidden)
			sql = 'DELETE FROM record WHERE rule_id = %s AND id = %s'
			self.db.query(sql, [rid, record_id])
			self.exit_with(config.status_success)
		elif action == 'set_rule_record':
			'''
			设置是否记录消息

			rid             规则的 ID
			record_require  是否记录消息
			'''
			ret = self.__set_meta_public('record_require', 'bool', uid)
			self.exit_with(ret)
		elif action == 'set_match_require':
			'''
			设置匹配次数

			rid             规则的 ID
			match_require   匹配次数
			'''
			ret = self.__set_meta_public('match_require', 'int', uid)
			self.exit_with(ret)
		elif action == 'set_reply_all':
			'''
			设置是否全部回复

			rid         规则的 ID
			reply_all   是否全部回复
			'''
			ret = self.__set_meta_public('reply_all', 'bool', uid)
			self.exit_with(ret)
		else: self.exit_with(config.status_error)

	def __set_meta_public(self, type_str, conv, uid):
		try:
			rid = self.get_argument('rid')
			value = self.get_argument(type_str)
		except MissingArgumentError:
			return config.status_error

		if conv == 'bool':
			if value != '1':
				value = '0'
		elif conv == 'int':
			value = int(value)
		mid = self.__get_meta_id(rid, type_str)
		if mid is None:
			self.__insert_meta(rid, type_str, value, uid)
		else: self.__update_meta(mid, value, uid)
		return config.status_success

	def __insert_rule(self, rule_name, rule_type, uid):
		'''
		添加新的规则

		rule_name 规则名称
		rule_type 规则类型
		uid       用户 ID
		'''
		rule = self.__get_rule_info(rule_name, uid)
		if rule is not None:
			return config.status_rule_exist
		if rule_type not in ('fallback', 'full_match', 'sub_match'):
			return config.status_error

		self.db.query('INSERT INTO reply_map (uid, rule_name, type) \
			VALUES (%s, %s, %s)', [uid, rule_name, rule_type])
		rid = -1
		sql = 'SELECT id FROM reply_map WHERE rule_name = %s AND uid = %s'
		for r in self.db.query_list(sql, [rule_name, uid]):
			if r[0] > rid:
				rid = r[0]
		if rid == -1:
			return config.status_error

		if rule_type != 'fallback':
			self.__insert_meta(rid, 'match_require', 1, uid)
		self.__insert_meta(rid, 'time_type', config.time_all, uid)
		return config.status_success

	def __rename_rule(self, rid, rule_name, uid):
		'''
		重命名规则

		rid       规则的ID
		rule_name 规则的新名字
		uid       用户的ID
		'''
		if self.__get_rule_owner(rid) != uid:
			return config.status_forbidden
		sql = 'UPDATE reply_map SET rule_name = %s WHERE id = %s'
		self.db.query(sql, [rule_name, rid])
		return config.status_success

	def __remove_rule(self, rid, uid):
		'''
		删除规则

		rid       规则的ID
		uid       用户的ID
		'''
		if self.__get_rule_owner(rid) != uid:
			return config.status_forbidden
		self.db.query('DELETE FROM reply_meta WHERE id = %s', [rid])
		self.db.query('DELETE FROM reply_map WHERE id = %s', [rid])
		self.db.query('DELETE FROM record WHERE id = %s', [rid])
		return config.status_success

	def __get_all_rules(self, uid):
		'''
		获得所有规则

		uid  用户 ID
		'''
		sql = 'SELECT * FROM reply_map WHERE uid = %s'
		ret = {}
		count = 0
		for rule in self.db.query_dict(sql, [uid]):
			ret[count] = { 'rid': rule['id'],
				'type': rule['type'], 'name': rule['rule_name'] }
			count += 1
		ret['count'] = len(ret)
		return (config.status_success, ret)

	def __get_rule_detail(self, rid, uid):
		'''
		获得规则详细信息

		rid  规则 ID
		uid  用户 ID
		'''
		if self.__get_rule_owner(rid) != uid:
			return (config.status_forbidden, None)
		ret = {}
		ret['rid'] = rid
		sql = 'SELECT rule_name, type FROM reply_map WHERE id = %s'
		result = list(self.db.get_row_list(sql, [rid]))
		ret['rule_name'] = result[0]
		ret['match_type'] = result[1]
		ret['keyword'] = []
		ret['reply'] = []
		sql = 'SELECT COUNT(id) FROM record WHERE rule_id = %s'
		ret['record_num'] = self.db.get_result(sql, [rid])
		sql = 'SELECT m.reply_key, m.reply_value, m.index_key \
			   FROM reply_meta AS m \
			   INNER JOIN reply_map AS r \
			   ON r.id = m.id \
			   WHERE m.id = %s'
		for key, value, index in self.db.query_list(sql, [rid]):
			if key in ('keyword', 'reply'):
				ret[key].append( [index, value] )
			else: ret[key] = value

		if 'record_require' not in ret:
			ret['record_require'] = 0
		if 'time_type' in ret and 'time_range' in ret:
			time_type = ret['time_type']
			if time_type != config.time_all:
				ret['time_str'] = ret['time_range']
			del ret['time_range']
		return (config.status_success, ret)

	def __update_rule_time(self, rid, time_old, time_new, uid):
		'''
		更新规则回复时间

		rid       规则 ID
		time_old  原先的回复时间串
		time_new  新的回复时间串
		uid       用户 ID
		'''
		if self.__get_rule_owner(rid) != uid:
			return config.status_forbidden

		time_type = self.__get_meta_value(rid, 'time_type')
		time_old = time_old.strip()
		time_new = time_new.strip()
		if not check_time_available(time_type, time_old):
			return config.status_error
		if not check_time_available(time_type, time_new):
			return config.status_error

		tstr = self.__get_meta_value(rid, 'time_range')
		if tstr.find(time_old) == -1:
			return '时间不存在！'
		if tstr.find(time_new) != -1:
			return '时间已经存在！'

		tstr = tstr.split('|')
		tstr.remove(time_old)
		tstr.append(time_new)
		tstr = '|'.join(tstr)
		self.__update_meta(self.__get_meta_id(rid, 'time_range'), tstr, uid)
		return config.status_success

	def __remove_rule_time(self, rid, time_str, uid):
		'''
		删除规则回复时间

		rid       规则 ID
		time_str  回复时间串
		uid       用户 ID
		'''
		if self.__get_rule_owner(rid) != uid:
			return config.status_forbidden

		time_type = self.__get_meta_value(rid, 'time_type')
		time_str = time_str.strip()
		if not check_time_available(time_type, time_str):
			return config.status_error

		tstr = self.__get_meta_value(rid, 'time_range')
		if tstr.find(time_str) == -1:
			return '时间不存在！'
		tstr = tstr.split('|')
		tstr.remove(time_str)
		tstr = '|'.join(tstr)
		self.__update_meta(self.__get_meta_id(rid, 'time_range'), tstr, uid)
		return config.status_success

	def __add_rule_time(self, rid, time_str, uid):
		'''
		添加规则回复时间

		rid       规则 ID
		time_str  回复时间串
		uid       用户 ID
		'''
		if self.__get_rule_owner(rid) != uid:
			return config.status_forbidden

		time_type = self.__get_meta_value(rid, 'time_type')
		time_str = time_str.strip()
		if not check_time_available(time_type, time_str):
			return config.status_error

		tstr = self.__get_meta_value(rid, 'time_range')
		if tstr.find(time_str) != -1:
			return '时间已经存在！'
		if not tstr:
			tstr = []
		else:tstr = tstr.split('|')
		tstr.append(time_str)
		tstr = '|'.join(tstr)
		self.__update_meta(self.__get_meta_id(rid, 'time_range'), tstr, uid)
		return config.status_success

	def __set_rule_time(self, rid, time_type, time_str, uid):
		'''
		设置规则回复时间

		rid       规则 ID
		time_type 回复时间类型
		time_str  回复时间串
		uid       用户 ID
		'''
		if time_type not in (config.time_all, config.time_daily,
			config.time_weekly, config.time_monthly):
			return config.status_error
		if self.__get_rule_owner(rid) != uid:
			return config.status_forbidden
		time_str = time_str.strip()
		if not check_time_available(time_type, time_str):
			return config.status_error

		range_id = self.__get_meta_id(rid, 'time_range')
		type_id = self.__get_meta_id(rid, 'time_type')
		self.__update_meta(type_id, time_type, uid)
		if time_type == config.time_all:
			if range_id is not None:
				self.__remove_meta(range_id, uid)
		else:
			if range_id is not None:
				self.__update_meta(range_id, time_str, uid)
			else: self.__insert_meta(rid, 'time_range', time_str, uid)
		return config.status_success

	def __get_rule_record(self, rid, uid):
		'''
		获取规则记录

		rid 规则 ID
		uid 用户 ID
		'''
		if self.__get_rule_owner(rid) != uid:
			return (config.status_forbidden, None)
		sql = 'SELECT * FROM record WHERE rule_id = %s'
		ret = {}
		for index, content in enumerate(self.db.query_dict(sql, [rid])):
			ret['count'] = index + 1
			ret[index] = {
				'index': content['id'],
				'rid': content['rule_id'],
				'from': content['from_user'],
				'content': content['content'],
				'date': str(content['date'])
			}
		return (config.status_success, ret)

	def __remove_meta(self, mid, uid):
		'''
		删除指定的 meta

		mid    规则的 ID
		uid    用户 ID
		'''
		if self.__get_meta_owner(mid) != uid:
			return config.status_forbidden
		self.db.query('DELETE FROM reply_meta WHERE index_key = %s', [mid])
		return config.status_success

	def __update_meta(self, mid, value, uid):
		'''
		更新指定的 meta

		mid    规则的 ID
		value  meta 的新值
		uid    用户 ID
		'''
		if self.__get_meta_owner(mid) != uid:
			return config.status_forbidden
		sql = 'UPDATE reply_meta SET reply_value = %s WHERE index_key = %s'
		self.db.query(sql, [value, mid])
		return config.status_success

	def __insert_meta(self, rid, key, value, uid):
		'''
		插入信息到指定规则的 meta

		rid    规则的 ID
		key    要插入的键
		value  要插入的值
		uid    用户 ID
		'''
		if self.__get_rule_owner(rid) != uid:
			return config.status_forbidden
		sql = 'INSERT INTO reply_meta (reply_key, \
			reply_value, id) VALUES (%s, %s, %s)'
		self.db.query(sql, [key, str(value), str(rid)])
		return config.status_success

	def __get_rule_owner(self, rid):
		' 获取规则所有者 ID '
		return self.db.get_result('SELECT uid \
			FROM reply_map WHERE id = %s', [rid])

	def __get_meta_owner(self, mid):
		' 获取内容的所有者 ID '
		sql = 'SELECT r.uid FROM reply_map AS r \
			INNER JOIN reply_meta AS m \
			ON m.id = r.id AND m.index_key = %s'
		return self.db.get_result(sql, [mid])

	def __get_meta_id(self, rid, mtype):
		'''
		获取指定规则的 meta id

		rid    规则的 ID
		mtype  meta 的类型
		'''
		sql = 'SELECT index_key FROM reply_meta \
			   WHERE reply_key = %s AND id = %s'
		return self.db.get_result(sql, [mtype, rid])

	def __get_meta_value(self, rid, mtype):
		'''
		获取指定规则的 meta 值

		rid    规则的 ID
		mtype  meta 的类型
		'''
		sql = 'SELECT reply_value FROM reply_meta \
			   WHERE reply_key = %s AND id = %s'
		return self.db.get_result(sql, [mtype, rid])

	def __get_meta_type(self, mid):
		sql = 'SELECT reply_key FROM reply_meta WHERE index_key = %s'
		return self.db.get_result(sql, [mid])

	def __get_rule_info(self, name, uid):
		' 获取规则信息 '
		return self.db.get_row_dict('SELECT * FROM reply_map \
			WHERE uid = %s AND rule_name = %s', [uid, name])
