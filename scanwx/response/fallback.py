import random
from scanwx.response import check_time

def response(db, content, from_user, uid = 1):
	uid = int(uid)
	sql = "SELECT r.id, m.reply_value   \
		   FROM reply_meta AS m         \
		   INNER JOIN reply_map AS r    \
		   ON m.id = r.id               \
		   WHERE r.type = 'fallback'    \
		     AND r.uid = %d             \
		     AND m.reply_key = 'reply'"
	candidate = []
	for row in db.query_list(sql % uid):
		if check_time(db, row[0]):
			candidate.append(row[1])

	if not candidate: return None
	return ('fallback', random.choice(candidate), None)
