
import re
import time

_regex_extract = re.compile(r'\[{3}((\s|.)+?)\]{3}')
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

	# 解析回复内容
	if rtype == 'news':
		return (rtype, _parse_news(content))
	elif rtype == 'text':
		return (rtype, content)

def parse(to_user, from_user, content):
	rtype, resp = _parse(content)
	now = int(time.time())
	if rtype == 'text':
		tmpl = _text_template
		response = tmpl % (to_user, from_user, now, resp)
	elif rtype == 'news':
		tmpl = _news_template
		response = tmpl % (to_user, from_user, now, resp[1], resp[0])
	return response
