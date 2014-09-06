# -*- coding: utf8 -*-

import urllib.parse
import urllib.request
import json

youdao_keyfrom = 'fzyzscanweixin'
youdao_key = '1011449812'

youdaoapi_url = 'http://fanyi.youdao.com/openapi.do?keyfrom=%s&key=%s&type=data&doctype=json&version=1.1&' % (youdao_keyfrom, youdao_key)

error_code_map = { 20: '你要翻译的东西太长了QAQ',
					30: '好像不能进行有效的翻译TAT',
					40: '这是什么语言我好像不太认识',
					50: '现在好像不能进行翻译',
					60: '翻译没有结果' }

def check(db, content, from_user):
	if content.startswith('@') or content.startswith('＠'):
		return content[1:]
	return False

def response(db, content, from_user, word):
	url = youdaoapi_url + urllib.parse.urlencode({ 'q': word })
	try:
		with urllib.request.urlopen(url) as page:
			result = json.loads(page.read().decode('utf-8'))
	except:
		return '在翻译的时候好像出了点问题 ლ(°Д°ლ)'

	if result['errorCode'] != 0:
		try: return error_code_map[result['errorCode']]
		except: return error_code_map[50]

	ret = result['query']

	if 'basic' in result:
		ret += ' - ' + ' '.join(result['translation']) + '\n'
		flag = False
		if 'us-phonetic' in result['basic']:
			ret += '[美]' + result['basic']['us-phonetic'] + ' '
			flag = True
		if 'uk-phonetic' in result['basic']:
			ret += '[英]' + result['basic']['uk-phonetic']
			flag = True
		if not flag and 'phonetic' in result['basic']:
			ret += result['basic']['phonetic']

		ret += '\n[详细释义]\n'
		ret += '\n'.join(result['basic']['explains']) + '\n'
	else: ret += '\n' + ' '.join(result['translation'])
	return ret
