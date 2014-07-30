<?php

include_once('config.php');
include_once('function.php');

session_start();
$wx = new scan_wx_database();

class scan_wx_database
{
	// mysql 数据库连接
	private $db;
	private $uid;
	private $user_role;

	// 初始化 mysql 数据库
	function __construct()
	{
		$this->db = new mysqli(SCAN_WX_SERVER, SCAN_WX_USERNAME, 
				SCAN_WX_PASSWORD, SCAN_WX_DATABASE);
		if($this->db->connect_errno)
			die('Cannot connect to database: ' . $this->db->connect_error);

		// 获取登陆的用户信息
		if(isset($_SESSION['uid']) && time() - $_SESSION['time'] < SCAN_WX_SESSION_KEEP_TIME)
		{
			$this->uid = $_SESSION['uid'];
			$_SESSION['time'] = time();
			$uid = $this->uid;
			$result = $this->get_result("SELECT `role` FROM `user` WHERE `uid` = '$uid'");
			switch($result)
			{
			case 'administrator':
				$this->user_role = SCAN_WX_USER_ADMIN;
				break;
			case 'common':
				$this->user_role = SCAN_WX_USER_COMMON;
				break;
			default:
				$this->uid = -1;
				$this->user_role = SCAN_WX_USER_NOLOGIN;
				session_destroy();
				break;
			}
		} else {
			session_destroy();
			$this->uid = -1;
			$this->login_role = SCAN_WX_USER_NOLOGIN;
		}
	}

	function __destruct()
	{
		$this->db->close();
	}

	// @brief 判断是否登陆
	public function is_login()
	{
		return $this->uid != -1;
	}

	// @beief 获取登陆用户的信息（uid 和 role）
	public function get_user_info()
	{
		return array( 'uid' => $this->uid, 'role' => $this->user_role);
	}

	// @beief 获取登陆用户的名称
	public function get_user_name()
	{
		$uid = $this->uid;
		if($uid == -1) return "nologin";
		$sql = "SELECT `username` FROM `user` WHERE `uid` = $uid";
		return $this->get_result($sql);
	}

	// @brief 判断 uid 是否可以执行
	public function check_uid($uid)
	{
		if(!$this->is_login())
			return false;
		if($this->user_role == SCAN_WX_USER_ADMIN)
			return true;
		return $this->uid == $uid;
	}


	/* @brief 插入或更新一条文本自动回复
	   @param $rule_name  规则的名字，用于索引
	   @param $keyword    关键字数组
	   @param $reply_info 回复信息数组
	   @param $match_require 最少匹配次数
	   @param $match_type 匹配的类型 */
	public function insert_text_reply(
		$rule_name, $keyword, $reply_info,
		$match_require, $match_type, $uid = -1)
	{
		// Check UID
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) 
			return SCAN_WX_STATUS_FORBIDDEN;

		$rule_name = trim($rule_name);
		// Check rule
		$rule = $this->get_rule_info($rule_name, $uid);
		if($rule !== false)
			return SCAN_WX_STATUS_RULE_EXIST;

		// Check $match_type
		if($match_type != 'fallback' && $match_type != 'sub_match' && $match_type != 'full_match')
			return SCAN_WX_STATUS_ERROR;

		// Insert rule
		$escape_str = $this->escape_sql_string($rule_name);
		$sql = "INSERT INTO `reply_map` 
				(uid, rule_name, type) 
		 VALUES ($uid, '$escape_str', '$rule_type')";
		$this->query($sql);

		// Get rule info
		$rule = $this->get_rule_info($rule_name);
		if($rule === false)
			return SCAN_WX_STATUS_ERROR;
		$rid = $rule['id'];

		// Insert keywords
		foreach($keyword as $v)
			$this->insert_meta($rid, 'keyword', trim($v), $uid);
		// Insert reply info
		foreach($reply_info as $v)
			$this->insert_meta($rid, 'reply', $v, $uid);
		// Set reply time
		$this->insert_meta($rid, 'time_type', SCAN_WX_TIME_ALL,  $uid);
		// Set match time(s)
		$this->insert_meta($rid, 'match_require', intval($match_require), $uid);
		return SCAN_WX_STATUS_SUCCESS;
	}

	/* @brief 设置文本回复的时间
	   @param $rule_name  规则的名字，用于索引 
	   @param $time_type  时间类型 
	   @param $time       时间数组（某一点到指定时刻的秒数） */
	public function set_text_reply_time(
		$rule_name, $time_type, $time, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) 
			return SCAN_WX_STATUS_FORBIDDEN;
		$rule = $this->get_rule_info($rule_name, $uid);
		if($rule === false)
			return SCAN_WX_STATUS_RULE_NOT_EXIST;

		$rid = $rule['id'];
		switch($time_type)
		{
		case SCAN_WX_TIME_ALL:
			$this->remove_meta($this->get_meta_id($rid, 'time_range'));
			$this->update_meta($this->get_meta_id($rid, 'time_type'), SCAN_WX_TIME_ALL);
			break;
		default:
			$this->update_meta($this->get_meta_id($rid, 'time_type'), $time_type);
			$this->update_meta($this->get_meta_id($rid, 'time_range'),
				scan_array_to_time($time_type, $time));
			break;
		}

		return SCAN_WX_STATUS_SUCCESS;
	}

	/* @brief 删除一条文本自动回复
	   @param $rule_name  规则的名字，用于索引 */
	public function remove_text_reply($rule_name, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) 
			return SCAN_WX_STATUS_FORBIDDEN;
		$rule = $this->get_rule_info($rule_name, $uid);
		if($rule === false)
			return SCAN_WX_STATUS_RULE_NOT_EXIST;

		$rid = $rule['id'];
		$this->query("DELETE FROM `reply_meta` WHERE `id` = $rid");
		$this->query("DELETE FROM `reply_map` WHERE `id` = $rid");
		return SCAN_WX_STATUS_SUCCESS;
	}

	/* @brief 更新一条文本自动回复内容或关键字
	   @param $content_index 要更新内容的索引 
	   @param $value         新的值 */
	public function update_text_reply_content(
		$content_index, $value, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) 
			return SCAN_WX_STATUS_FORBIDDEN;
		$content_index = intval($content_index, 10);
		if($this->get_meta_owner($content_index) != $uid)
			return SCAN_WX_STATUS_FORBIDDEN;
		$sql = "SELECT `reply_key` FROM `reply_meta`
				WHERE `index_key` = $content_index";
		$meta_type = $this->get_result($sql);
		if($meta_type != 'reply' && $meta_type != 'keyword')
			return SCAN_WX_STATUS_ERROR;
		$this->update_meta($content_index, $value);
		return SCAN_WX_STATUS_SUCCESS;
	}


	/* @brief 删除一条文本自动回复内容或关键字
	   @param $content_index 要删除内容的索引 */
	public function remove_text_reply_content(
		$content_index, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) 
			return SCAN_WX_STATUS_FORBIDDEN;

		$content_index = intval($content_index, 10);
		if($this->get_meta_owner($content_index) != $uid)
			return SCAN_WX_STATUS_FORBIDDEN;
		$this->remove_meta($content_index, $uid);
		return SCAN_WX_STATUS_SUCCESS;
	}

	/* @brief 插入一条文本自动回复内容或关键字
	   @param $rule_name  规则的名字，用于索引 
	   @param $keyword    要插入的关键数组
	   @param $content    要插入回复数组 */
	public function insert_text_reply_content(
		$rule_name, $keyword, $content, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) 
			return SCAN_WX_STATUS_FORBIDDEN;
		$rule = $this->get_rule_info($rule_name, $uid);
		if($rule === false)
			return SCAN_WX_STATUS_RULE_NOT_EXIST;

		$rid = $rule['id'];
		foreach($keyword as $k)
			$this->insert_meta($rid, 'keyword', $k, $uid);
		foreach($content as $v)
			$this->insert_meta($rid, 'reply', $v, $uid);

		return SCAN_WX_STATUS_SUCCESS;
	}

	/* @brief 获取一条规则
	   @param $rule_name  规则的名字，用于索引 */
	public function get_text_reply($rule_name, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) 
			return SCAN_WX_STATUS_FORBIDDEN;
		$rule = $this->get_rule_info($rule_name, $uid);
		if($rule === false)
			return SCAN_WX_STATUS_RULE_NOT_EXIST;
		$rid = $rule['id'];
		$sql = "SELECT m.reply_key, m.reply_value, m.index_key
			    FROM `reply_meta` AS m
				INNER JOIN `reply_map` AS r
				ON r.id = m.id
				WHERE m.id = $rid";
		$ret = array();
		$result = $this->query($sql);
		while($row = $result->fetch_row())
		{
			$key = $row[0];
			$value = $row[1];
			switch($key)
			{
			case 'time_range':
				$time_range = $value;
				break;
			case 'time_type':
				$time_type = $value;
				break;
			case 'keyword': case 'reply':
				if(!array_key_exists($key, $ret))
					$ret[$key] = array();
				// $meta_id, $value
				array_push($ret[$key], array($row[2], $value));
				break;
			default:
				$ret[$key] = $value;
				break;
			}
		}
		$ret['time_type'] = $time_type;
		if($time_type != SCAN_WX_TIME_ALL)
		{
			$ret['time_range'] = scan_time_to_array($time_type, $time_range);
			$ret['time_str'] = $time_range;
		}
		$ret['rule_owner'] = $this->get_user_name();
		$result->free();
		return $ret;
	}

	/* @brief 获得所有规则名称 */
	public function get_all_rules($uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) return false;
		$sql = "SELECT * FROM `reply_map`
				WHERE `uid` = $uid";
		$result = $this->query($sql);
		$ret = array();
		while($row = $result->fetch_array())
		{
			array_push($ret, array(
				'type' => $row['type'], 
				'name' => $row['rule_name']));
		}
		$result->free();
		return $ret;
	}

	private function get_meta_id($rid, $name)
	{
		$rid = intval($rid, 10);
		$name = $this->escape_sql_string($name);
		return $this->get_result(
			"SELECT `index_key`
			 FROM `reply_meta` 
			 WHERE `id` = $rid
			   AND `reply_key` = '$name'");
	}

	/* @brief 在 reply_meta 删除信息 */
	private function remove_meta($index, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) return false;
		if($this->get_meta_owner($index) != $uid)
			return false;
		$index = intval($index, 10);
		$this->query("DELETE FROM `reply_meta`
				      WHERE `index_key` = $index");
		return true;
	}

	/* @brief 插入信息到 reply_meta 
	   @param $id    规则的 ID
	   @param $key   新的 meta key
	   @param $value 新的 meta value */ 
	private function insert_meta($id, $key, $value, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) return false;
		$id = intval($id, 10);
		$key = $this->escape_sql_string($key);
		$value = $this->escape_sql_string($value);
		$this->query("INSERT INTO `reply_meta`
				      (reply_key, reply_value, id)
			   VALUES ('$key', '$value', '$id')");
		return true;
	}

	/* @brief 更新 reply_meta 
	   @param $index meta 的索引
	   @param $value 新的 meta 值 */
	private function update_meta($index, $value, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) return false;
	//	if($this->get_meta_owner($index) != $uid)
	//		return false;
		$value = $this->escape_sql_string($value);
		$index = intval($index, 10);
		$this->query("UPDATE `reply_meta`
				      SET `reply_value` = '$value'
					  WHERE `index_key` = $index");
		return true;
	}

	/* @brief 获得规则所有者
	   @param $index rule 的索引 */
	private function get_rule_owner($index)
	{
		$index = intval($index, 10);
		return $this->get_result(
			"SELECT uid FROM reply_map
			 WHERE id = $index");
	}

	/* @brief 获得 reply_meta 所有者
	   @param $index meta 的索引 */
	private function get_meta_owner($index)
	{
		$index = intval($index, 10);
		return $this->get_result(
			"SELECT r.uid FROM reply_map AS r
			 INNER JOIN reply_meta AS m
			 ON m.id = r.id 
			 AND m.index_key = $index");
	}

	/* @brief 获得规则的信息
	   @param $rule_name 规则的名字 */
	private function get_rule_info($rule_name, $uid = -1)
	{
		$uid = intval($uid, 10);
		if($uid == -1) $uid = $this->uid;
		if(!$this->check_uid($uid)) return false;
		$rule_name = $this->escape_sql_string($rule_name);
		return $this->get_first_row("SELECT * FROM `reply_map`
			    WHERE `uid` = $uid AND `rule_name` = '$rule_name'");
	}

	/* @brief 查询数据库并获得第一格内容 */
	public function get_result($sql)
	{
		$result = $this->query($sql);
		$row = $result->fetch_row();
		$result->free();
		return $row[0];
	}

	/* @brief 查询数据库并获得第一行内容 */
	public function get_first_row($sql)
	{
		$result = $this->query($sql);
		if($result->num_rows == 0)
			return false;
		$row = $result->fetch_array();
		$result->free();
		return $row;
	}

	/* @brief 清除SQL字符串特殊字符 */
	public function escape_sql_string($sql)
	{
		return addcslashes($sql, "'`\\");
	}

	public function query($sql)
	{
		return $this->db->query($sql);
	}
};

?>
