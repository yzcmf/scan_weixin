import urllib.parse
import urllib.request
import json

baiduapi_ak = 'LltX6WkWxZSw4VCUpEL3jn5H'
keywords = ['#天气#', '#weather#', '#tianqi#']

def check(db, content, from_user):
	for keyword in keywords:
		if content.startswith(keyword):
			return True
	return False

def response(db, content, from_user):
	url = 'http://api.map.baidu.com/telematics/v3/weather?'
	url += urllib.parse.urlencode({ 'output': 'json', 'ak': baiduapi_ak })
	content = content[content.find('#', 1) + 1:].strip()
	if not content: city = '闽侯'
	else: city = content
	url += '&' + urllib.parse.urlencode({ 'location': city })

	try:
		with urllib.request.urlopen(url) as page:
			weather = json.loads(page.read().decode('utf-8'))
	except:
		return '在查询天气的时候好像出了点问题 ლ(°Д°ლ)'
	if weather['error'] != 0:
		st = weather['error']
		if st == -3: return '没有找到「%s」的天气 ╮（￣▽￣）╭' % city
		else: return '今天好像不能告诉你天气了、( ¯▽¯；)'
	sep = '--------\n'
	weather = weather['results'][0]
	ret = '「%s」的天气情况：' % weather['currentCity']
	for data, detail in zip(weather['weather_data'], weather['index']):
		ret += data['date'] + '\n'
		ret += data['temperature'] + '\n'
		ret += data['weather'] + ' '
		ret += data['wind'] + '\n'
		ret += detail['des'] + '\n' + sep
	return ret[:-len(sep)]
