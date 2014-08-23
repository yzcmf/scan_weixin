# -*- coding: utf-8 -*-

import re
import time

_regex_extract = re.compile(r'\[{3}((\s|.)+?)\]{3}')
_regex_substitute_extract = re.compile(r'\{{3}((\s|.)+?)\}{3}')
_regex_parse = re.compile(r'([a-z]+)="(.*?)"')
_text_template = '''
			<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			<FuncFlag>0</FuncFlag>
			</xml>
			'''

_news_template = '''
			<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<ArticleCount>%s</ArticleCount>
			<Articles>%s</Articles>
			</xml>
			'''

def _parse_argument(content):
	result = {}
	content = content.lstrip()
	while True:
		arg = _regex_parse.match(content)
		if not arg: return result
		result[arg.group(1)] = arg.group(2)
		content = content[arg.end():].lstrip()

def _parse_news(content):
	result = []
	start_pos = 0
	command = _regex_extract.match(content[start_pos:])
	while command:
		args = _parse_argument(command.group(1))
		if 'url' not in args or 'pic' not in args:
			start_pos += command.end()
		else:
			if result:
				t = content[:start_pos + command.start()]
				result[-1]['content'] = t
			if 'title' not in args:
				args['title'] = ''
			result.append(args)
			content = content[start_pos + command.end():].lstrip()
			start_pos = 0
		command = _regex_extract.search(content[start_pos:])
	if not result: return ('', 0)
	result[-1]['content'] = content
	templ = '''
		<item>
		<Title><![CDATA[%(title)s]]></Title>
		<Description><![CDATA[%(content)s]]></Description>
		<PicUrl><![CDATA[%(pic)s]]></PicUrl>
		<Url><![CDATA[%(url)s]]></Url>
		</item>
			'''
	ret = ''
	for r in result:
		ret += templ % r
	return (ret, len(result))

def _regex_sub_func(groups):
	def oper(matchobj):
		content = matchobj.group(1).strip()
		if int(content) >= len(groups):
			return matchobj.group(0)
		return groups[int(content)]
	return oper

def _regex_substitute(db, content, match_type, group):
	if not group:
		groups = []
	elif match_type == 'regex_match':
		groups = (group[0].group(0),) + group[0].groups()
	else:
		sql = 'SELECT reply_value FROM reply_meta \
			   WHERE index_key IN (%s)' % ','.join(map(str, group))
		groups = []
		for data in db.query_list(sql):
			groups.append(data[0])

	return _regex_substitute_extract.sub(_regex_sub_func(groups), content)

def _parse(content):
	raw_content = content

	# 检查回复类型
	command = _regex_extract.match(content)
	if not command:
		return ('text', raw_content)
	rtype = command.group(1)
	if rtype not in ('news', 'text'):
		return ('text', raw_content)
	content = content[command.end():].lstrip()
	return (rtype, content)

def parse(db, to_user, from_user, content, match_type, info):
	rtype, resp = _parse(content)
	resp = _regex_substitute(db, resp, match_type, info)
	now = int(time.time())
	if rtype == 'text':
		tmpl = _text_template
		response = tmpl % (to_user, from_user, now, resp)
	elif rtype == 'news':
		tmpl = _news_template
		resp = _parse_news(resp)
		response = tmpl % (to_user, from_user, now, resp[1], resp[0])
	return response
