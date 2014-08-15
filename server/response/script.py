import os

# 遍历所有脚本文件并导入
scripts = []
for root, dirs, files in os.walk('response/scripts'):
	for fn in files:
		name, ext = os.path.splitext(fn)
		if ext != '.py' or name.startswith('_'):
			continue
		exec('import response.scripts.%s' % name)
		exec('scripts.append(response.scripts.%s)' % name)

def response(db, content, from_user):
	for script in scripts:
		if script.check(db, content, from_user):
			result = script.response(db, content, from_user)
			if result is not None:
				return result
	return None
