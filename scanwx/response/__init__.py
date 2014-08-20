import time
import random
import scanwx.config as config

def get_meta(db, rid, meta):
	return db.get_result(
		"SELECT reply_value FROM reply_meta \
		 WHERE reply_key = %s \
		   AND id = %s", [meta, rid])

def check_meta(db, rid, meta):
	return get_meta(db, rid, meta) == '1'

def check_uid(db, content):
	sql = "SELECT uid, user_value \
		   FROM user_meta \
		   WHERE user_key = 'keyword'"
	candidate = []
	for row in db.query_list(sql):
		if content.find(row[1]) == 0:
			candidate.append(row[0])

	if not candidate: return None
	return random.choice(candidate)

def time_in_range(time_type, time_str):
	now = time.localtime()
	t1 = list(map(int, time_str[0].split(':')))
	t2 = list(map(int, time_str[1].split(':')))
	ts = list(now)
	te = list(now)
	if time_type == config.time_daily:
		ts[3:6] = t1
		te[3:6] = t2
		loop = 60 * 60 * 24
	elif time_type == config.time_weekly:
		ws = (t1[0] + 6) % 7 #数据中第一天是星期天
		we = (t2[0] + 6) % 7
		week = now[6]
		if ws > we: we += 7
		if ws < week < we or ws < week + 7 < we:
			return True
		ts[3:6] = t1[1:]
		te[3:6] = t2[1:]
		if ws == week:
			if ws != we: te[3:6] = [23, 59, 59]
		elif we == week or we == week + 7:
			if ws != we: ts[3:6] = [0, 0, 0]
		else: return False
		loop = 60 * 60 * 24
	elif time_type == config.time_exact:
		loop = 0
		ts = t1 + [-1] * 3
		te = t2 + [-1] * 3
	else:
		ts[-3:] = [-1] * 3
		te[-3:] = [-1] * 3
		ts[2:6] = t1
		te[2:6] = t2
		days = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
		year = now[0]
		if year % 4 == 0 and year % 100 or year % 400 == 0:
			days[1] += 1
		loop = days[now[1] - 1] * 60 * 60 * 24

	ts = time.mktime(tuple(ts))
	te = time.mktime(tuple(te))
	now = time.mktime(now)
	if ts > te: te += loop
	return ts <= now <= te or ts <= now + loop <= te

def check_time(db, rid):
	time_type = get_meta(db, rid, 'time_type')
	if time_type == config.time_all:
		return True
	time_arr = get_meta(db, rid, 'time_range').split('|')
	for t in time_arr:
		if time_in_range(time_type, t.split(',')):
			return True
	return False

def record_message(db, rid, from_user, content):
	if not check_meta(db, rid, 'record_require'):
		return
	db.query("INSERT INTO record \
			  (rule_id, from_user, content) \
	   VALUES (%s, %s, %s)", [rid, from_user, content])
	db.commit()

