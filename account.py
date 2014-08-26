#!/usr/bin/python3
# -*- coding: utf-8 -*-

import sys
import getpass
import hashlib
import scanwx.database
import scanwx.client.account as account

def print_usage():
	print("usage: account.py [register | list | delete | passwd]")

def change_passwd(db):
	username = input('Username: ')
	sql = 'SELECT * FROM user WHERE username = %s'
	if not db.get_result(sql, [username]):
		print('Sorry, user "%s" not existed' % username)
		return
	password = getpass.getpass('Password: ')
	repeat = getpass.getpass('Repeat password: ')
	if password != repeat:
		print('Sorry, password do not match')
		return
	password = hashlib.md5(password.encode('utf8')).hexdigest()
	password = account.encode_passwd(password)
	sql = 'UPDATE user SET password = %s WHERE username = %s'
	db.query(sql, [password, username])
	db.commit()
	print('Succeeded!')

def register(db):
	username = input('Username: ')
	if db.get_result('SELECT * FROM user WHERE username = %s', [username]):
		print('Sorry, user "%s" existed' % username)
		return
	password = getpass.getpass('Password: ')
	repeat = getpass.getpass('Repeat password: ')
	if password != repeat:
		print('Sorry, password do not match')
		return
	role = input('Role (common or admin): ')
	if role == 'admin':
		role = 'administrator'
	else: role = 'common'
	password = hashlib.md5(password.encode('utf8')).hexdigest()
	password = account.encode_passwd(password)
	db.query('INSERT INTO user (username, password, role) \
		VALUE (%s, %s, %s)', [username, password, role])
	db.commit()
	print('Succeeded!')

def list_user(db):
	print('UID USERNAME           ROLE')
	for user in db.query_dict('SELECT * FROM user'):
		print('%(uid)3d %(username)-18s %(role)s' % user)

def delete_user(db):
	print('\033[1;31mWarning: \033[0mThis action cannot be recovered!')
	username = input('Username: ')
	repeat = input('Check again: ')
	if username != repeat:
		print('Sorry, username do not match')
	sql = 'SELECT uid FROM user WHERE username = %s'
	uid = db.get_result(sql, [username])
	if uid is None:
		print('Sorry, user "%s" not existed' % username)
		return
	db.query('DELETE m FROM reply_meta AS m \
			  INNER JOIN reply_map AS r \
			  ON m.id = r.id WHERE r.uid = %s', [uid])
	db.query('DELETE m FROM record AS m \
			  INNER JOIN reply_map AS r \
			  ON m.rule_id = r.id WHERE r.uid = %s', [uid])
	db.query('DELETE FROM reply_map WHERE uid = %s', [uid])
	db.query('DELETE FROM user WHERE uid = %s', [uid])
	db.commit()
	print('Succeeded!')

def main(action):
	db = scanwx.database.database()
	db.connect()

	if action == 'register':
		register(db)
	elif action == 'list':
		list_user(db)
	elif action == 'delete':
		delete_user(db)
	elif action == 'passwd':
		change_passwd(db)
	else: print_usage()

	db.close()

if __name__ == '__main__':
	if len(sys.argv) != 2:
		print_usage()
	else: main(sys.argv[1])
