import random
import server.response.fallback as fallback
from server.response import *

def full_match(db, content, from_user, uid):
	sql = "SELECT DISTINCT(m.id)         \
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
			candidate.append(row[0])
	if not candidate: return None
	rid = random.choice(candidate)
	record_message(db, rid, from_user, content)
	reply = []
	for row in db.query_list("SELECT reply_value FROM reply_meta \
		WHERE id = %s AND reply_key = 'reply'", [rid]):
		reply.append(row[0])
	if check_meta(db, rid, 'reply_all'):
		return '\n'.join(reply)
	return random.choice(reply)

def sub_match(db, content, from_user, uid):
	sql = "SELECT m.reply_value, m.id \
		   FROM reply_meta AS m       \
		   INNER JOIN reply_map AS r  \
		   ON r.id = m.id             \
		   WHERE r.type = 'sub_match' \
		     AND m.reply_key = '%s'"
	if uid != -1: sql += " AND r.uid = " + str(int(uid))
	#记录每一条规则需要匹配的关键字个数
	match_require = {}
	for row in db.query_list(sql % 'match_require'):
		rid = int(row[1])
		match_require[rid] = int(row[0])

	#查询关键字
	match_record = {}
	keyword_count = {}
	rule_check_time = {}
	for row in db.query_list(sql % 'keyword'):
		rid = int(row[1])
		if rid in keyword_count:
			keyword_count[rid] += 1
		else: keyword_count[rid] = 1

		if content.find(row[0]) != -1:
			if not rid in rule_check_time:
				rule_check_time[rid] = check_time(db, rid)
			if rule_check_time[rid]:
				if rid in match_record:
					match_record[rid] += 1
				else: match_record[rid] = 1

	#删除匹配次数不够的规则
	candidate = []
	for k, v in match_record.items():
		if not k in match_record:
			r = 1
		else: r = match_record[k]
		if v >= r or r == -1:
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
		for row in db.query_list("SELECT reply_value FROM reply_meta \
			WHERE id = %s AND reply_key = 'reply'", [rid]):
			reply.append(row[0])
		return '\n'.join(reply)
	return rule[1]

def response(db, content, from_user):
	uid = check_uid(db, content)
	if uid is None: uid = -1
	# 检测全匹配
	result = full_match(db, content, from_user, uid)
	if result: return result
	# 检测部分匹配
	result = sub_match(db, content, from_user, uid)
	if result: return result
	# 检测 fallback
	if uid != -1:
		return fallback.response(db, content, from_user, uid)
	return None
