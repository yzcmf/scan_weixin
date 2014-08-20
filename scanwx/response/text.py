import random
import re

import scanwx.response.fallback as fallback
from scanwx.response import *

def full_match(db, content, from_user, uid):
	sql = "SELECT DISTINCT(m.id),        \
		          m.index_key            \
		   FROM reply_meta AS m          \
		   INNER JOIN reply_map AS r     \
		   ON r.id = m.id                \
		   WHERE r.type = 'full_match'   \
		     AND m.reply_key = 'keyword' \
		     AND m.reply_value = %s"
	if uid != -1: sql += " AND r.uid = " + str(int(uid))

	candidate = []
	for row in db.query_list(sql, [content]):
		if check_time(db, row[0]):
			candidate.append((row[0], row[1]))
	if not candidate: return None
	rid, meta_id = random.choice(candidate)
	record_message(db, rid, from_user, content)
	reply = []
	sql = "SELECT reply_value FROM reply_meta \
		WHERE id = %s AND reply_key = 'reply'"
	for row in db.query_list(sql, [rid]):
		reply.append(row[0])
	if check_meta(db, rid, 'reply_all'):
		ret = '\n'.join(reply)
	else: ret = random.choice(reply)
	return ('full_match', ret, meta_id)

def rule_match(db, match_type, match_func, content, from_user, uid):
	sql = "SELECT m.reply_value, m.id, m.index_key \
		   FROM reply_meta AS m       \
		   INNER JOIN reply_map AS r  \
		   ON r.id = m.id             \
		   WHERE r.type = '%s' \
		     AND m.reply_key = '%s'"
	if uid != -1: sql += " AND r.uid = " + str(int(uid))
	#记录每一条规则需要匹配的关键字个数
	match_require = {}
	for row in db.query_list(sql % (match_type, 'match_require')):
		rid = int(row[1])
		match_require[rid] = int(row[0])

	#查询关键字
	match_record = {}
	rule_check_time = {}
	for row in db.query_list(sql % (match_type, 'keyword')):
		rid = int(row[1])

		status, info = match_func(content, row[0], row[2])
		if status == True:
			if not rid in rule_check_time:
				rule_check_time[rid] = check_time(db, rid)
			if rule_check_time[rid]:
				if rid in match_record:
					match_record[rid].append(info)
				else: match_record[rid] = [info]

	#删除匹配次数不够的规则
	candidate = []
	for k, v in match_record.items():
		if not k in match_require:
			r = 1
		else: r = match_require[k]
		if len(v) >= r or r == -1:
			candidate.append(k)

	#选择规则及回复
	if not candidate: return None
	ret = []
	for rid in candidate:
		sql = "SELECT m.reply_value  \
			   FROM reply_meta AS m  \
			   WHERE m.reply_key = 'reply' \
			     AND m.id = " + str(int(rid))
		for row in db.query_list(sql):
			ret.append((rid, row[0]))

	if not ret: return None
	rule = random.choice(ret)
	rid = rule[0]
	record_message(db, rid, from_user, content)
	if check_meta(db, rid, 'reply_all'):
		reply = []
		sql = "SELECT reply_value FROM reply_meta \
			WHERE id = %s AND reply_key = 'reply'"
		for row in db.query_list(sql, [rid]):
			reply.append(row[0])
		ret = '\n'.join(reply)
	else: ret = rule[1]
	return (match_type, ret, match_record[rid])

def sub_match_test(content, value, meta_id):
	if content.find(value) != -1:
		return (True, meta_id)
	return (False, None)

def regex_match_test(content, pattern, meta_id):
	try:
		ret = re.search(pattern, content)
		if ret is None:
			return (False, None)
		return (True, ret)
	except:
		return (False, None)

def response(db, content, from_user):
	uid = check_uid(db, content)
	if uid is None: uid = -1
	# 检测全匹配
	result = full_match(db, content, from_user, uid)
	if result and result[1]: return result
	# 检测部分匹配
	result = rule_match(db, 'sub_match',
		sub_match_test, content, from_user, uid)
	if result and result[1]: return result
	# 检测正则表达式规则
	result = rule_match(db, 'regex_match',
		regex_match_test, content, from_user, uid)
	if result and result[1]: return result
	# 检测 fallback
	if uid != -1:
		return fallback.response(db, content, from_user, uid)
	return None
