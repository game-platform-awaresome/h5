<?php

class DeveloperModel extends F_Model_Pdo
{
    protected $_table = 'developer';
    protected $_primary = 'dev_id';
    
	private $_ck_ui_name = 'dev_auth'; //Cookie键名
	private $_sess_name = 'developer'; //Session键名
	
	public $_status = array(
	    0 => '新用户',
	    1 => '申请认证',
	    2 => '拒绝申请',
	    3 => '冻结账户',
	    9 => '认证通过',
	);
	public $_froze_code = 3; //冻结状态码
	
	public $_scales = array(
	    0 => '',
	    1 => '10人以下',
	    2 => '10-30人',
	    3 => '30-50人',
	    4 => '50-100人',
	    5 => '100-200人',
	    6 => '200人以上',
	);
	
	public $_banks = array(
	    '中国工商银行',
	    '招商银行',
	    '中国光大银行',
	    '中信银行',
	    '中国建设银行',
	    '中国银行',
	    '中国民生银行',
	    '中国邮政储蓄银行',
	    '中国农业银行',
	    '兴业银行',
	    '平安银行',
	    '浦发银行',
	    '交通银行',
	    '华夏银行',
	    '广发银行',
	    '其他银行',
	);
	
	public $_menu = array(
	    'developer' => array(
	        'name' => '账号中心',
	        'icon' => 'i_user',
	        'list' => array(
	            'index' => array('name' => '账号概述', 'icon' => 'i_summarize'),
	            'baseinfo' => array('name' => '基本信息', 'icon' => 'i_edit'),
	            'papers' => array('name' => '证件上传', 'icon' => 'i_papers'),
	            'bankinfo' => array('name' => '银行账号', 'icon' => 'i_bank'),
	            'password' => array('name' => '修改密码', 'icon' => 'i_password'),
	        ),
	    ),
	    'game' => array(
	        'name' => '游戏管理',
	        'icon' => 'i_game',
	        'list' => array(
	            'index' => array('name' => '开发者概览', 'icon' => 'i_earth4'),
	            'online' => array('name' => '已上线的', 'icon' => 'i_hasOnline'),
	            'checking' => array('name' => '审核中的', 'icon' => 'i_review'),
	            'offline' => array('name' => '未上线的', 'icon' => 'i_notOnline'),
	        ),
	    ),
	);
	
	public function getTableLabel()
	{
	    return '开发者';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'dev_id' => '开发者ID',
	        'username' => '用户名',
	        'com_short' => '公司名称',
	        'org_code' => '组织机构代码',
	        'license' => '营业证号',
	        'province' => '省/自治区',
	        'city' => '市/州',
	        'contact' => '联系人',
	        'mobile' => '联系电话',
	        'reg_time' => '注册时间',
	        'on_nums' => '已上线游戏',
	        'off_nums' => '未上线游戏',
	        'trade_money' => '游戏总流水',
	        'status' => function(&$row){
	            if( empty($row) ) return '账户状态';
	            $ck = $this->_status[$row['status']];
	            if( $row['status'] == 2 || $row['status'] == $this->_froze_code ) {
	                $ck = "<font color=\"red\">{$ck}</font>";
	            } elseif( $row['status'] == 9 ) {
	                $ck = "<font color=\"green\">{$ck}</font>";
	            }
	            return $ck;
	        },
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'username' => array('用户名', 'input', null, ''),
	        'reg_begin' => array('开始日期', 'datepicker', null, ''),
	        'reg_end' => array('结束日期', 'datepicker', null, ''),
	    );
	}
	
	/**
	 * 用户登录
	 *
	 * @param string $username
	 * @param string $password
	 * @param number $remember
	 * @return string
	 */
	public function login($username, $password, $remember = 0)
	{
		if( empty($username) || empty($password) ) {
			return '用户名或密码不能为空。';
		}
		$username = preg_replace('/[\'\"\\\]+/', '', $username);
		$time = time();
		
		$dev = $this->fetch("username='{$username}'", 'dev_id,username,password,status,message');
		if( empty($dev) ) {
		    return '账号不存在。';
		}
		if( $dev['status'] == $this->_froze_code ) {
		    return $dev['message'] ? $dev['message'] : '账号被冻结。';
		}
		if( strlen($password) != 32 ) $password = md5($password);
		if( strcmp($password, $dev['password']) != 0 ) {
		    return '密码错误。';
		}
		unset($dev['password']);
		
		$s = Yaf_Session::getInstance();
		$s->set($this->_sess_name, $dev);
		
		if( $remember ) {
			$time = $time + 86400 * 7;
			$conf = Yaf_Application::app()->getConfig();
			$info = "{$dev['dev_id']}\t{$dev['username']}\t{$password}";
			$info = F_Helper_Mcrypt::authcode($info, 'ENCODE', $conf->mcrypt->key, $time);
			$domain = $conf->cookie->domain;
			setcookie($this->_ck_ui_name, $info, $time, '/', $domain);
		}
		return '';
	}
	
	public function logout()
	{
		$s = Yaf_Session::getInstance();
		$s->del($this->_sess_name);
		
		if( isset($_COOKIE[$this->_ck_ui_name]) ) {
		    $conf = Yaf_Application::app()->getConfig();
		    $domain = $conf->cookie->domain;
			setcookie($this->_ck_ui_name, '', 1, '/', $domain);
		}
	}
	
	/**
	 * 获取用户的登录信息
	 * 
	 * @return null|array
	 */
	public function getLogin()
	{
		$s = Yaf_Session::getInstance();
		$dev = $s->get($this->_sess_name);
		if( $dev ) {
			return $dev;
		}
		if( empty($_COOKIE[$this->_ck_ui_name]) ) {
			return null;
		}
		$info = F_Helper_Mcrypt::authcode($_COOKIE[$this->_ck_ui_name]);
		$info = explode("\t", $info);
		if( count($info) != 3 ) {
		    $conf = Yaf_Application::app()->getConfig();
		    $domain = $conf->cookie->domain;
			setcookie($this->_ck_ui_name, '', 1, '/', $domain);
			return null;
		}
		list($dev_id, $username, $password) = $info;
		if( $dev_id > 0 ) {
		    //再次登录，避免被冻结的用户继续操作
		    $err = $this->login($username, $password);
		    if( empty($err) ) {
    			return $s->get($this->_sess_name);
		    }
		}
		$conf = Yaf_Application::app()->getConfig();
	    $domain = $conf->cookie->domain;
		setcookie($this->_ck_ui_name, '', 1, '/', $domain);
		return null;
	}
	
	/**
	 * 用户注册
	 * 
	 * @param string $username
	 * @param string $password
	 * @param string $re_pwd
	 * @return mixed
	 */
	public function register($username, $password, $re_pwd)
	{
	    $rs = $this->checkUsername($username, false);
	    if( $rs ) {
	        return $rs;
	    }
	    
	    $len = strlen($password);
	    if( $len < 6 || $len > 20 ) {
	        return '密码长度为6-16位。';
	    }
	    if( strcmp($password, $re_pwd) != 0 ) {
	        return '确认密码与密码不符。';
	    }
	    $password = md5($password);
	    
	    $has = $this->fetch("username='{$username}'", 'dev_id');
	    if( $has ) {
	        return '账号已被注册，请更换。';
	    }
	    
	    $dev = array(
	        'username' => $username,
	        'password' => $password,
	        'email' => $username,
	        'status' => 0,
	        'message' => '',
	    );
	    $dev_id = $this->insert($dev);
	    
	    //自动登录
	    if( (int)$dev_id > 0 ) {
	        $sess = Yaf_Session::getInstance();
	        unset($dev['password'], $dev['email']);
	        $dev['dev_id'] = $dev_id;
	        $sess->set($this->_sess_name, $dev);
	        return (int)$dev_id;
	    }
	    
	    return '注册失败，请重试！';
	}
	
	/**
	 * 检查用户名
	 * 
	 * @param string $username
	 * @return string
	 */
	public function checkUsername($username)
	{
	    $len = strlen($username);
	    if( $len > 32 ) {
	        return '开发者账号为一个长度不超过32位的电子邮箱。';
	    }
        if( ! preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $username) ) {
            return '请输入一个电子邮箱作为开发者账号。';
        }
	    
	    return '';
	}
	
	/**
	 * 格式化用户名
	 * 
	 * @param string $username
	 * @return string
	 */
	public function formatUsername($username)
	{
	    $username = preg_replace('/@.*(\.[a-zA-Z]+)$/', '@*\\1', $username);
	    $pos = strpos($username, '@');
	    if( $pos > 4 ) {
	        $username = substr_replace($username, '*', 4, $pos - 4);
	    }
	    return $username;
	}
}
