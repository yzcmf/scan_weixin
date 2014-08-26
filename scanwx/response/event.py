# -*- coding: utf-8 -*-
import random

from scanwx.response import check_time
from scanwx.response import check_meta

def response(db, content, from_user):
	if content not in ('subscribe', 'unsubscribe'):
		return None
	sql = 'SELECT id FROM reply_map WHERE rule_name = %s AND uid = 1'

	candidate = []
	for row in db.query_list(sql, [content]):
		if check_time(db, row[0]):
			candidate.append(row[0])
	if not candidate: return None

	rid = random.choice(candidate)
	sql = 'SELECT reply_value FROM reply_meta \
		   WHERE reply_key = \'reply\' AND id = %s'
	reply = []
	for row in db.query_list(sql, [rid]):
		reply.append(row[0])

	if check_meta(db, rid, 'reply_all'):
		ret = '\n'.join(reply)
	else: ret = random.choice(reply)
	return ('event', ret, None)

