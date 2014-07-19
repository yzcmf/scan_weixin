<?php

include_once('config.php');

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


	/* @brief 插入或更新一条文本自动回复
	   @param $rule_name  规则的名字，用于索引
	   @param $keyword    关键字数组
	   @param $reply_info 回复信息数组
	   @param $match_require 最少匹配次数
	   @param $is_full_match 表示是否完全匹配关键字 */
	public function insert_text_reply(
		$rule_name, 
		$keyword, 
		$reply_info,
		$match_require,
		$is_full_match = false
	) {
		$rule_type = $is_full_match ? 'full_match' : 'sub_match';
		$rule_name = trim($rule_name);

		// 检测规则是否存在
		$escape_str = $this->escape_sql_string($rule_name);
		if($this->get_rule_info($escape_str))
			return SCAN_WX_STATUS_RULE_EXIST;

		// 规则不存在就插入规则
		$sql = "INSERT INTO `reply_map` 
				(uid, rule_name, type) 
				VALUES 
				($uid, '$escape_str', '$rule_type')";
		$this->db->query($sql);

		// 获得规则 ID
		$id = $this->get_rule_id($rule_name);

		// 插入关键字
		foreach($keyword as $v)
			$this->insert_meta($id, 'keyword', trim($v));
		// 插入回复信息
		foreach($reply_info as $v)
			$this->insert_meta($id, 'reply', $v);
		// 设置回复时段类型（全部时段）
		$this->insert_meta($id, 'time_type', SCAN_WX_TIME_ALL);
		// 设置最少匹配次数
		$this->insert_meta($id, 'match_require', intval($match_require));
		return SCAN_WX_STATUS_SUCCESS;
	}

	/* @brief 设置文本回复的时间
	   @param $rule_name  规则的名字，用于索引 
	   @param $time_type  时间类型 
	   @param $time       时间数组（某一点到指定时刻的秒数） */
	public function set_text_reply_time(
		$rule_name, $time_type, $time, $uid = -1)
	{
		// 获得规则 ID
		$row = $this->get_rule_info($rule_name, $uid);
		if(!$row) return SCAN_WX_RULE_NOT_EXIST;
		$uid = $this->uid;
		if($row['uid'] != $uid && $this->user_role != SCAN_WX_USER_ADMIN)
			return SCAN_WX_STATUS_FORBIDDEN;
		$id = $row['id'];
		
		switch($time_type)
		{
		case SCAN_WX_TIME_ALL:
			$this->remove_meta($id, 'time_range');
			$this->update_meta($id, 'time_type', SCAN_WX_TIME_ALL);
			break;
		default:
			$this->update_meta($id, 'time_type', $time_type);
			$this->update_meta($id, 'time_range', 
				scan_time_to_str($time_type, $time));
			break;
		}

		return SCAN_WX_STATUS_SUCCESS;
	}

	/* @brief 删除一条文本自动回复
	   @param $rule_name  规则的名字，用于索引 */
	public function remove_text_reply($rule_name, $uid = -1) 
	{
		$row = $this->get_rule_info($rule_name, $uid);
		if(!$row) return SCAN_WX_STATUS_RULE_NOT_EXIST;
		$id = $row['id'];
		if($row['uid'] == $this->uid || $this->user_role == SCAN_WX_USER_ADMIN)
		{
			$this->db->query("DELETE FROM `reply_meta` WHERE `id` = $id");
			$this->db->query("DELETE FROM `reply_map` WHERE `id` = $id");
			return SCAN_WX_STATUS_SUCCESS;
		}

		return SCAN_WX_STATUS_FORBIDDEN;
	}

	/* @brief 删除一条文本自动回复内容或关键字
	   @param $rule_name  规则的名字，用于索引 
	   @param $keyword    要删除的关键数组
	   @param $content    要删除的回复数组 */
	public function remove_text_reply_content(
		$rule_name, $keyword, $content, $uid = -1)
	{
		$row = $this->get_rule_info($rule_name, $uid);
		if(!$row) return false;
		$id = $row['id'];
		if($row['uid'] == $this->uid || $this->user_role == SCAN_WX_USER_ADMIN)
		{
			foreach($keyword as $v)
				$this->remove_meta($id, 'keyword', $v);
			foreach($content as $v)
				$this->remove_meta($id, 'reply', $v);
			return true;
		}
		return false;
	}

	/* @brief 获取一条规则
	   @param $rule_name  规则的名字，用于索引 */
	public function get_text_reply($rule_name, $uid = -1)
	{
		$row = $this->get_rule_info($rule_name, $uid);
		if(!$row) return false;
		$id = $row['id'];
		$sql = "SELECT m.reply_key, m.reply_value
			    FROM `reply_meta` AS m
				INNER JOIN `reply_map` AS r
				ON r.id = m.id
				WHERE m.id = $id";
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
				if(array_key_exists($key, $ret))
					array_push($ret[$key], $value);
				else $ret[$key] = array($value);
				break;
			default:
				$ret[$key] = $value;
				break;
			}
		}
		$ret['time_type'] = $time_type;
		if($time_type != SCAN_WX_TIME_ALL)
			$ret['time_range'] = scan_wx_time_to_array($time_type, $time_range);
		$ret['rule_owner'] = $this->get_user_name();
		$result->free();
		return $ret;
	}

	/* @brief 插入一条文本自动回复内容或关键字
	   @param $rule_name  规则的名字，用于索引 
	   @param $keyword    要插入的关键数组
	   @param $content    要插入回复数组 */
	public function insert_text_reply_content(
		$rule_name, $keyword, $content)
	{
		$row = $this->get_rule_info($rule_name);
		if(!$row) return false;
		$id = $row['id'];
		if($row['uid'] == $this->uid || $this->user_role == SCAN_WX_USER_ADMIN)
		{
			foreach($keyword as $v)
				$this->insert_meta($id, 'keyword', $v);
			foreach($content as $v)
				$this->insert_meta($id, 'reply', $v);
			return true;
		}
		return false;
	}

	/* @brief 在 reply_meta 删除信息 */
	private function remove_meta($id, $key, $value = null)
	{
		$value = $this->escape_sql_string($value);
		if($value != null)
			$sql = "DELETE FROM `reply_meta` WHERE `id` = $id AND `reply_key` = '$key' AND `reply_value` = '$value'";
		else $sql = "DELETE FROM `reply_meta` WHERE `id` = $id AND `reply_key` = '$key'";
		$this->db->query($sql);
	}

	/* @brief 插入信息到 reply_meta */
	private function insert_meta($id, $key, $value)
	{
		$value = $this->escape_sql_string($value);
		$sql = "SELECT * FROM `reply_meta` WHERE `id` = $id AND `reply_key` = '$key' AND `reply_value` = '$value'";
		if(!$this->get_first_row($sql))
		{
			$sql = "INSERT INTO `reply_meta` (reply_key, reply_value, id) VALUES ('$key', '$value', '$id')";
			$this->db->query($sql);
		}
	}

	/* @brief 更新 reply_meta */
	private function update_meta($id, $key, $value)
	{
		$value = $this->escape_sql_string($value);
		$sql = "SELECT * FROM `reply_meta` 
			    WHERE `id` = $id 
				  AND `reply_key` = '$key'";
		if(!$this->get_first_row($sql))
		{
			$sql = "INSERT INTO `reply_meta` 
				    (reply_key, reply_value, id) 
					VALUES
					('$key', '$value', '$id')";
			$this->db->query($sql);
		} else {
			$sql = "UPDATE `reply_meta` 
				    SET `reply_value` = '$value' 
					WHERE `reply_key` = '$key'
					  AND `id` = $id";
			$this->db->query($sql);
		}
	}

	/* @brief 获得规则的信息
	   @param $rule_name 规则的名字 */
	private function get_rule_info($rule_name, $uid = -1)
	{
		$rule_name = trim($rule_name);
		$escape_str = $this->escape_sql_string($rule_name);
		if($uid == -1) $uid = $this->uid;
		if($this->uid != $uid && $this->get_user_info()['role'] != SCAN_WX_USER_ADMIN) 
			return false;
		$sql = "SELECT * FROM `reply_map`
			    WHERE `rule_name`='$escape_str'
				  AND `uid` = $uid";
		$row = $this->get_first_row($sql);
		return $row;
	}

	/* @brief 获得规则的 ID
	   @param $rule_name 规则的名字 */
	private function get_rule_id($rule_name)
	{
		$row = $this->get_rule_info($rule_name);
		if($row) return $row['id'];
		return -1;
	}

	/* @brief 查询数据库并获得第一格内容 */
	public function get_result($sql)
	{
		$result = $this->db->query($sql);
		$row = $result->fetch_row();
		$result->free();
		return $row[0];
	}

	/* @brief 查询数据库并获得第一行内容 */
	public function get_first_row($sql)
	{
		$result = $this->db->query($sql);
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
