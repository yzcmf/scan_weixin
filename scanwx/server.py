# -*- coding: utf-8 -*-

import hashlib
import time
import xml.dom.minidom

import tornado.web
import scanwx.response.text as response_text
import scanwx.response.pushup as response_pushup
import scanwx.response.fallback as response_fallback
import scanwx.response.script as response_script
import scanwx.response.event as response_event
import scanwx.config as config
import scanwx.parser

class handler(tornado.web.RequestHandler):
	def valid(self):
		try:
			echo_str = self.get_argument('echostr')
		except tornado.web.MissingArgumentError:
			return

		if self.check_signature():
			self.write(echo_str)

	def check_signature(self):
		try:
			signature = self.get_argument('signature')
			timestamp = self.get_argument('timestamp')
			nonce = self.get_argument('nonce')
		except tornado.web.MissingArgumentError:
			return False

		token = config.token
		tmp_arr = [token, timestamp, nonce]
		tmp_arr.sort()
		tmp_str = ''.join(tmp_arr)
		tmp_str = hashlib.sha1(tmp_str.encode('ascii')).hexdigest()
		if tmp_str == signature:
			return True
		return False

	def __reply_text(self, db, data):
		content = data['Content'].strip()
		from_user = data['FromUserName']
		to_user = data['ToUserName']

		if not content:
			ret = response_fallback.response(db, content, from_user)
		else:
			ret = response_script.response(db, content, from_user)
			if ret is None or ret[1] is None:
				ret = response_pushup.response(db, content, from_user)
			if ret is None or ret[1] is None:
				ret = response_text.response(db, content, from_user)
			if ret is None or ret[1] is None or not ret[1].strip():
				ret = response_fallback.response(db, content, from_user)
		if ret is None: ret = (None) * 3
		match_type, reply, info = ret
		if not reply: reply = ''
		# 判断回复类型
		response = scanwx.parser.parse(
			db, from_user, to_user, reply, match_type, info)
		self.write(response)

	def __reply_event(self, db, data):
		from_user = data['FromUserName']
		to_user = data['ToUserName']

		ret = response_event.response(db, data['Event'], from_user)
		if not ret: return
		match_type, reply, info = ret
		if not reply: reply = ''
		response = scanwx.parser.parse(
			db, from_user, to_user, reply, match_type, info)
		self.write(response)

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
		db = self.application.db
		db.connect()
		if data['MsgType'] == 'text':
			self.__reply_text(db, data)
		elif data['MsgType'] == 'event':
			self.__reply_event(db, data)
		db.commit()
		db.close()
