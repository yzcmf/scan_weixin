
import hashlib
import time
import xml.dom.minidom

import tornado.web
import server.response.text as response_text
import server.response.fallback as response_fallback
import server.response.script as response_script
import server.config as config

class handler(tornado.web.RequestHandler):
	def valid(self):
		echo_str = self.get_argument('echostr')
		if self.check_signature():
			self.write(echo_str)

	def check_signature(self):
		signature = self.get_argument('signature')
		timestamp = self.get_argument('timestamp')
		nonce = self.get_argument('nonce')
		if not nonce or not timestamp or not signature:
			return False

		token = config.token
		tmp_arr = [token, timestamp, nonce]
		tmp_arr.sort()
		tmp_str = ''.join(tmp_arr)
		tmp_str = hashlib.sha1(tmp_str.encode('ascii')).hexdigest()
		if tmp_str == signature:
			return True
		return False

	def get(self):
		self.valid()

	def post(self):
		if not self.check_signature():
			return
		raw_content = self.request.body.decode('utf-8')
		xmldom = xml.dom.minidom.parseString(raw_content)
		data = {}
		for d in xmldom.documentElement.childNodes:
			if not d.childNodes: continue
			data[d.nodeName] = d.childNodes[0].nodeValue
		content = data['Content'].strip()
		from_user = data['FromUserName']
		to_user = data['ToUserName']

		text_template = "<xml> \
						<ToUserName><![CDATA[%s]]></ToUserName> \
						<FromUserName><![CDATA[%s]]></FromUserName> \
						<CreateTime>%s</CreateTime> \
						<MsgType><![CDATA[%s]]></MsgType> \
						<Content><![CDATA[%s]]></Content> \
						<FuncFlag>0</FuncFlag> \
						</xml>"

		db = self.application.db
		if not content:
			reply = response_fallback.response(db, content, from_user)
		else:
			reply = response_script.response(db, content, from_user)
			if reply is None:
				reply = response_text.response(db, content, from_user)
			if reply is None or not reply.strip():
				reply = response_fallback.response(db, content, from_user)

		if not reply: reply = ''
		response = text_template % (from_user,
			to_user, int(time.time()), 'text', reply)
		self.write(response)
