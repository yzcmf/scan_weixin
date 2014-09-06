# -*- coding: utf8 -*-

import random
from scanwx.response import *

def response(db, content, from_user):
	sql = "SELECT m.id FROM reply_meta AS m \
		   INNER JOIN reply_map AS r ON r.id = m.id \
		   WHERE r.type = 'pushup' AND r.uid = 1 \
		     AND m.reply_key = 'keyword' AND m.reply_value = %s"
	ans = [i[0] for i in db.query_list(sql, [content])]
	if not ans: return None
	rid = random.choice(ans)

	sql = "SELECT r.uid, m.id FROM reply_meta AS m \
		   INNER JOIN reply_map AS r ON r.id = m.id \
		   WHERE r.type = 'pushup' AND m.reply_key = 'keyword' \
		     AND m.reply_value = %s"
	infos = db.query_list(sql, [content])
	reply_root = []
	replys = []
	for uid, rid in infos:
		if not check_time(db, rid):
			continue
		if uid == 1: contain = reply_root
		else: contain = replys

		sql = "SELECT reply_value FROM reply_meta \
			   WHERE id = %s AND reply_key = 'reply'"
		candidate = [i[0] for i in db.query_list(sql, [rid])]
		if candidate and not check_meta(db, rid, 'reply_all'):
			candidate = [random.choice(candidate)]
		contain.extend(candidate)

	resp = []
	if len(reply_root) > 10:
		resp = reply_root[0] + random.sample(reply_root[1:], 9)
	else:
		resp = reply_root
		resp.extend(random.sample(replys, min(len(replys), 10 - len(resp))))
	return ('pushup', '\n'.join(resp), None)
