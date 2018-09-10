<?php

class UsersModel extends F_Model_Pdo
{
    protected $_table = 'user';
    protected $_primary = 'user_id';
    public function __construct()
    {
        parent::__construct('h5');
    }
    //private $_fcm_url = 'http://fcm.namiyx.com/';
    public $_nciic = false; //是否去NCIIC验证身份信息
    public $_fcm_status = array(
        -2 => '身份证号不存在',
        -1 => '信息不一致',
        0 => '等待验证',
        1 => '信息一致',
    );
    
    /**
     * 用户COOKIE名称
     * 
     * @var string
     */
	private $_ck_ui_name = 'u_auth';
	
	public function getTableLabel()
	{
	    return '用户信息';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'user_id' => '用户ID',
	        'username' =>
            function(&$row){
                if( empty($row) ) return '用户名';
                if($row['player_channel']>0){
                    return '(玩家推广)'.$row['username'];
                }else{
                    return $row['username'];
                }
            },
            'player_channel'=>
                function(&$row){
                    if( empty($row) ) return '玩家推广id';
                    if($row['player_channel']>0){
                        return  $row['player_channel'];
                    }else{
                        return '无';
                    }
                },
	        'tg_channel' =>function($row){
                if( empty($row) ) return '渠道id';
                return $row['tg_channel'];
            },
//	        'email' => '邮箱',
	        'money' => function(&$row){
	            if( empty($row) ) return '余额';
	            return number_format($row['money']).'￥';
	        },
//	        'identity' => '身份证号',
//	        'realname' => '真实姓名',
//	        'fcm_status' => function(&$row){
//	            if( empty($row) ) return '身份认证';
//	            return $this->_fcm_status[$row['fcm_status']];
//	        },
	        'reg_time' => function(&$row){
	            if( empty($row) ) return '注册时间';
	            return date('Y-m-d H:i:s', $row['reg_time']);
	        },
//	        'check_status' => function(&$row){
//	            if( empty($row) ) return '验证状态';
//	            $ck = $row['check_status'] & 1 ? '手机' : '';
//	            $ck .= $row['check_status'] & 2 ? '邮箱' : '';
//	            return $ck;
//	        },
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'user_id' => array('用户id', 'input', null, ''),
	        'username' => array('用户名', 'input', null, ''),
	        'reg_begin' => array('开始日期', 'datepicker', null, ''),
	        'reg_end' => array('结束日期', 'datepicker', null, ''),
	    );
	}
	
	/**
	 * 格式化用户名
	 * 
	 * @param string $username
	 * @return string
	 */
	public function usernameFormat($username)
	{
	    if( strlen($username) > 7 ) {
	        return substr($username, 0, 5).'..';
	    }
	    return $username;
	}
	
	/**
	 * 用户登录
	 *
	 * @param string $username
	 * @param string $password
	 * @param number $remember
	 * @param bool $isuid
	 * @return string
	 */
	public function login($username, $password, $remember = 0, $isuid = false)
	{
		if( empty($username) || empty($password) ) {
			return '用户名或密码不能为空。';
		}
		$username = preg_replace('/[\'\"\\\]+/', '', $username);
		$time = time();

//		require_once APPLICATION_PATH . '/config.inc.php';
//		require_once APPLICATION_PATH.'/uc_client/client.php';
		
//		list($u_id, $u_name, $u_pass, $u_email) = uc_user_login($username, $password, $isuid);
//		if( $u_id == -1 ) {
//		    return '用户不存在，或已被冻结。';
//		} elseif( $u_id == -2 ) {
//		    return '密码错误。';
//		} elseif( $u_id < 1 ) {
//		    return '服务器错误，请重试！';
//		}
		$has = $this->fetch(['username'=>$username,'password'=>md5($password)], 'user_id,username,nickname,email,login_times,tg_channel');
		$u_id=$has['user_id'];
		if( empty($has) ) {
//		    $sess = Yaf_Session::getInstance();
//		    $channel_id = $sess->get('channel_id');
//		    if( empty($channel_id) ) {
//		        $channel_id = isset($_COOKIE['channel_id']) ? $_COOKIE['channel_id'] : 0;
//		    }
//		    $this->insert(array(
////		        'user_id' => $u_id,
//		        'username' => $username,
//		        'nickname' => '',
//		        'email' => '',
//		        'reg_time' => $time,
//		        'login_day' => date('Ymd'),
//		        'login_times' => 1,
//		        'tg_channel' => $channel_id,
//		    ), false);
//		    $nickname = '';
            return '用户名或密码错误';
		} else {
		    $nickname = $has['nickname'];
		    $channel_id = $has['tg_channel'];
		    $this->update(array('login_day'=>date('Ymd'), 'login_times'=>$has['login_times']+1), "username='{$username}'");
		}
		
		//记录登录日志
		$m_log = new SignonlogModel();
		$m_log->insert(array(
		    'user_id' => $u_id,
		    'game_id' => 0,
		    'ip' => $_SERVER['REMOTE_ADDR'],
		    'time' => $time,
		), false);
		
		$s = Yaf_Session::getInstance();
		$s->set('user_id', $u_id);
		$s->set('username', $username);
		$s->set('nickname', $nickname);
		$s->set('email', $has['email']);
		$s->set('channel_id', $channel_id);
		
		if( $remember ) {
            //始终使用自动登录功能
            $time = $time + 86400 * 7;
            $info = "{$u_id}\t{$username}\t{$nickname}\t{$password}\t{$has['email']}\t{$channel_id}";
            $info = F_Helper_Mcrypt::authcode($info, 'ENCODE');
            $domain = Yaf_Registry::get('config')->cookie->domain;
            setcookie($this->_ck_ui_name, $info, $time, '/', $domain);
		}
		return '';
	}
	
	/**
	 * 第三方平台登录
	 * 
	 * @param string $app
	 * @param array $info array(openid, nickname, sex, avatar, access_token, expires)
	 * @return string $errstr
	 */
	public function oAuthLogin($app, $info)
    {
//        require_once APPLICATION_PATH . '/config.inc.php';
//        require_once APPLICATION_PATH . '/uc_client/client.php';
        $time = time();
        
        $user = $this->fetch("app='{$app}' AND openid='{$info['openid']}'", 'user_id,username,nickname,`password`,email,expires,login_times,tg_channel');
        if( empty($user) ) {
            $info['nickname'] = trim(str_replace(array("'", '"', '\\'), '', $info['nickname']));
            if( $info['nickname'] == '' ) {
                $username = "{$app}_".substr(md5($info['openid']), 8, 8);
            } else {
                $username = "{$app}_{$info['nickname']}";
            }
            $check = uc_user_checkname($username);
            if( $check != 1 ) {
                $username = "{$app}_".substr(md5($info['openid']), 0, 8);
                $check = uc_user_checkname($username);
                if( $check != 1 ) {
                    $username = "{$app}_".uniqid();
                }
            }
            
            $nickname = $info['nickname'];
            $password = substr(md5("{$info['openid']}#{$app}"), 8, 16);
            $email = '';
            $uid = uc_user_register($username, $password, '');
            if( $uid < 1 ) {
                switch ($uid)
                {
                    case -3: //用户名已经存在
                        $user = uc_get_user($username);
                        $uid = $user[0];
                        break;
                    default: return $uid.' 用户数据同步失败，请重试！';
                }
            }
            
            $sess = Yaf_Session::getInstance();
            $channel_id = $sess->get('channel_id');
            if( empty($channel_id) ) {
                $channel_id = isset($_COOKIE['channel_id']) ? $_COOKIE['channel_id'] : 0;
            }
            
            if( strlen($info['openid']) > 32 ) {
                $info['openid'] = md5($info['openid']);
            }
            $rs = $this->insert(array(
                'user_id' => $uid,
                'app' => $app,
                'openid' => $info['openid'],
                'password' => $password,
                'access_token' => $info['access_token'],
                'expires' => $info['expires'],
                'username' => $username,
                'nickname' => $info['nickname'],
                'avatar' => $info['avatar'],
                'mobile' => '',
                'email' => '',
                'sex' => $info['sex'],
                'reg_time' => $time,
                'login_day' => date('Ymd'),
                'login_times' => 1,
                'tg_channel' => $channel_id,
            ), false);
            if( ! $rs ) {
                return '用户数据保存失败，请重试！';
            }
        } else {
            $uid = $user['user_id'];
            $username = $user['username'];
            $password = $user['password'];
            $email = $user['email'];
            $channel_id = $user['tg_channel'];
            
            if( $user['expires'] < $time ) {
                if( strlen($info['openid']) > 32 ) {
                    $info['openid'] = md5($info['openid']);
                }
                $this->update(array(
                    'access_token' => $info['access_token'],
                    'expires' => $info['expires'],
                    'nickname' => $info['nickname'],
                    'avatar' => $info['avatar'],
                    'sex' => $info['sex'],
                    'login_day' => date('Ymd'),
                    'login_times' => $user['login_times'] + 1,
                ), "app='{$app}' AND openid='{$info['openid']}'");
                
                $nickname = $info['nickname'];
            } else {
                $nickname = $user['nickname'];
            }
        }
        
        //记录登录日志
        $m_log = new SignonlogModel();
        $m_log->insert(array(
            'user_id' => $uid,
            'game_id' => 0,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => $time,
        ), false);
        
        $s = Yaf_Session::getInstance();
        $s->set('user_id', $uid);
        $s->set('username', $username);
        $s->set('nickname', $nickname);
        $s->set('email', $email);
        $s->set('channel_id', $channel_id);
        
        //第三方登录始终使用自动登录功能
        $time = $time + 86400 * 7;
        $info = "{$uid}\t{$username}\t{$nickname}\t{$password}\t{$email}\t{$channel_id}";
        $info = F_Helper_Mcrypt::authcode($info, 'ENCODE', Yaf_Registry::get('config')->mcrypt->key, $time);
        $domain = Yaf_Registry::get('config')->cookie->domain;
        setcookie($this->_ck_ui_name, $info, $time, '/', $domain);
        
        return '';
	}
	
	public function logout()
	{
		$s = Yaf_Session::getInstance();
		$s->del('user_id');
		$s->del('username');
		$s->del('nickname');
		$s->del('email');
		
		if( isset($_COOKIE[$this->_ck_ui_name]) ) {
		    $domain = Yaf_Registry::get('config')->cookie->domain;
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
		$user_id = $s->get('user_id');
		if( $user_id ) {
			return array(
				'user_id' => $user_id,
				'username' => $s->get('username'),
			    'nickname' => $s->get('nickname'),
				'email' => $s->get('email'),
			    'channel_id' => $s->get('channel_id'),
			);
		}
		if( empty($_COOKIE[$this->_ck_ui_name]) ) {
			return null;
		}
		$info = F_Helper_Mcrypt::authcode($_COOKIE[$this->_ck_ui_name],'DECODE');
		$info = explode("\t", $info);
		if( count($info) != 6 ) {
		    $domain = Yaf_Registry::get('config')->cookie->domain;
			setcookie($this->_ck_ui_name, '', 1, '/', $domain);
			return null;
		}
		list($user_id, $username, $nickname, $password, $email, $channel_id) = $info;
		if( $user_id > 0 ) {
		    //再次登录，避免被冻结的用户继续操作
		    $err = $this->login($username, $password);
		    if( empty($err) ) {
		        $s->set('user_id', $user_id);
    			$s->set('username', $username);
    			$s->set('nickname', $nickname);
    			$s->set('email', $email);
    			$s->set('channel_id', $channel_id);
    			return array(
    				'user_id' => $user_id,
    				'username' => $username,
    			    'nickname' => $nickname,
    				'email' => $email,
    			    'channel_id' => $channel_id,
    			);
		    }
		}
		$domain = Yaf_Registry::get('config')->cookie->domain;
		setcookie($this->_ck_ui_name, '', 1, '/', $domain);
		return null;
	}
	
	/**
	 * 用户注册
	 * 
	 * @param string $username
	 * @param string $password
	 * @param string $re_pwd
	 * @param string $mobile
	 * @param string $email
	 * @return mixed
	 */
	public function register($username, $password, $re_pwd, $mobile = '', $email = '')
	{
	    $rs = $this->checkUsername($username, false);
	    if( $rs ) {
	        return $rs;
	    }
	    
	    if( strcmp($password, $re_pwd) != 0 ) {
	        return '确认密码与密码不符。';
	    }
	    
//	    require_once APPLICATION_PATH . '/config.inc.php';
//	    require_once APPLICATION_PATH.'/uc_client/client.php';
	    
//	    $uid = uc_user_register($username, $password, $email);
//	    if( $uid > 0 ) {
	        $sess = Yaf_Session::getInstance();
	        $channel_id = $sess->get('channel_id');
	        if( empty($channel_id) ) {
	            $channel_id = isset($_COOKIE['channel_id']) ? $_COOKIE['channel_id'] : 0;
	        }
	        try {
                $rs = $this->insert(array(
//	            'user_id' => $uid,
                    'username' => $username,
                    'password' => md5($password),
                    'nickname' => '',
                    'mobile' => $mobile,
                    'email' => $email,
                    'reg_time' => time(),
                    'check_status' => $mobile ? 1 : 0,
                    'tg_channel' => $channel_id,
                ), false);
                $uid = $rs;
                return (int)$uid;
            }catch(mysqli_sql_exception $exception){
	            return $exception->getMessage();
            }
//	    }
	    
//	    switch ($uid)
//	    {
//	        case -1: return '用户名不合法';
//	        case -2: return '包含不允许注册的词语';
//	        case -3: return '用户名已经存在';
//	        case -4: return 'Email 格式有误';
//	        case -5: return 'Email 不允许注册';
//	        case -6: return '该 Email 已经被注册';
//	    }
//	    return '注册失败，请重试！';
	}
	
	/**
	 * 更新用户账户
	 * 
	 * @param string $username
	 * @param string $oldpwd
	 * @param string $newpwd
	 * @param string $mobile
	 * @param string $email
	 * @param bool $ignore
	 * @return string
	 */
	public function edit($username, $oldpw, $newpw, $mobile= '', $email = '', $ignore = false)
	{
//	    require_once APPLICATION_PATH . '/config.inc.php';
//	    require_once APPLICATION_PATH . '/uc_client/client.php';
	    
	    $rs = uc_user_edit($username, $oldpw, $newpw, $email, $ignore);
	    
	    switch ($rs)
	    {
	        case -1: return '旧密码不正确';
	        case -4: return 'Email 格式有误';
	        case -5: return 'Email 不允许注册';
	        case -6: return '该 Email 已经被注册';
	        case -8: return '该用户受保护无权限更改';
	    }
	    
	    $up_arr = null;
	    if( $mobile ) {
	        $up_arr['mobile'] = $mobile;
	    }
	    if( $email ) {
	        $up_arr['email'] = $email;
	    }
	    if( $up_arr ) {
	        $s = Yaf_Session::getInstance();
	        $user_id = $s->get('user_id');
	        $this->update($up_arr, "user_id='{$user_id}'");
	    }
	    
	    return '';
	}
	
	/**
	 * 检查用户名
	 * 
	 * @param string $username
	 * @param bool $uc_ckeck
	 * @return string
	 */
	public function checkUsername($username, $uc_ckeck = true)
	{

	    $len = strlen($username);
	    if( $len < 3 || $len > 32 ) {
	        return '用户账号由4-16位字母或数字组成，邮箱最长32位。';
	    }
	    //判断用名是否存在
        if($this->fetch(['username'=>$username],['user_id'])){
            return '该用户名已被占用';
        }
//	    if( $len == 11 && is_numeric($username) ) {
//	        if( ! preg_match('/^(?:13[0-9]|14[57]|15[0-9]|17[0678]|18[0-9])\d{8}$/', $username) ) {
//	            return '手机号格式不正确。';
//	        }
//	    }
//	    if( strpos($username, '@') ) {
//	        if( ! preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $username) ) {
//	            return '邮箱格式不正确。';
//	        }
//	        $email = $username;
//	    }
	    //第三方账号
//	    if( strpos($username, '#') ) {
//
//	    } elseif( $len > 17 ) {
//	        return '普通账号为4-16位。';
//	    } elseif( preg_match('#[\'\"\%\*\&\^\$\(\)\{\}\[\]\\\]#', $username) ) {
//	        return '用户名含有非法字符。';
//	    }
//
//	    if( $uc_ckeck ) {
//	        require APPLICATION_PATH.'/config.inc.php';
//	        require APPLICATION_PATH.'/uc_client/client.php';
//
//	        $rs = uc_user_checkname($username);
//	        switch ($rs)
//	        {
//	            case 1: return '';
//	            case -1: return '用户名不合法';
//	            case -2: return '包含要允许注册的词语';
//	            case -3: return '用户名已经存在';
//	        }
//	    }
	    
	    return '';
	}
	
	/**
	 * 检查Email
	 * 
	 * @param string $email
	 * @return string
	 */
	public function checkEmail($email)
	{
//	    require APPLICATION_PATH . '/config.inc.php';
//	    require APPLICATION_PATH.'/uc_client/client.php';
//
//	    $rs = uc_user_checkemail($email);
//	    switch ($rs)
//	    {
//	        case -4: return 'Email 格式有误';
//	        case -5: return 'Email 不允许注册';
//	        case -6: return '该 Email 已经被注册';
//	    }
	    
	    return '';
	}
	
	/**
	 * 防沉迷认证
	 * 
	 * @param string $idno
	 * @param string $name
	 * @param int $min
	 * @param int $max
	 * @return string $error
	 */
	public function fcm($idno, $name, $min = 2, $max = 12)
	{
	    if( ! F_Helper_Idcard::isIdcard($idno) ) {
	        return '请输入有效的第二代18位身份证号码！';
	    }
	    $idno{17} = strtoupper($idno{17});
	    if( ! F_Helper_Idcard::isRealname($name, $min, $max) ) {
	        return "请输入{$min}-{$max}位的真实姓名！";
	    }
	    $s = Yaf_Session::getInstance();
	    $uid = $s->get('user_id');
	    $conds = "user_id='{$uid}'";
	    $fcm = $this->fetch($conds, 'identity,realname,fcm_status');
	    if( $fcm['fcm_status'] == 1 ) {
	        return '';
	    }
	    
	    $m_log = new UserfcmlogModel();
	    $log = $m_log->fetch($conds);
	    $week = date('W');
	    if( $log && $log['week'] == $week ) {
	        return '防沉迷信息一周只能验证一次，请下周再来！';
	    }
	    
	    if( empty($fcm['identity']) || empty($fcm['realname']) || strcmp($fcm['identity'], $idno) != 0 || $fcm['realname'] != $name ) {
	        $status = 0;
	        if( $this->_nciic ) {
	            $nciic = new Nciic_Services();
	            $rs = $nciic->checkIdName($idno, $name);
	            if( is_string($rs) ) {
	                return $rs;
	            } else {
	                foreach ($rs as $row)
	                {
	                    $status = $row['status'];
	                }
	            }
	        }
	        if( $log ) {
	            $m_log->update(array('day'=>date('Ymd'), 'week'=>$week), $conds);
	        } else {
	            $m_log->insert(array('user_id'=>$uid, 'day'=>date('Ymd'), 'week'=>$week), false);
	        }
	        if( $status == -2 ) {
	            Yaf_Registry::set('fcm_notice', '您本周还可修改一次！');
	            return '身份证号码不存在！';
	        } elseif( $status == -1 ) {
	            Yaf_Registry::set('fcm_notice', '您本周还可修改一次！');
	            return '身份证号码与姓名不一致！';
	        }
	        $this->update(array('identity'=>$idno, 'realname'=>$name, 'fcm_status'=>$status), $conds);
	    }
	    return '';
	}
	
	/**
	 * 获取好友总数
	 * 
	 * @param int $direct
	 * @return int
	 */
	public function friendsTotal($direct = 0)
	{
	    $s = Yaf_Session::getInstance();
	    $uid = $s->get('user_id');
	    
//	    require APPLICATION_PATH . '/config.inc.php';
//	    require APPLICATION_PATH . '/uc_client/client.php';
	    return uc_friend_totalnum($uid, $direct);
	}
	
	/**
	 * 获取好友列表
	 * 
	 * @param int $total
	 * @param int $page
	 * @param int $pagesize
	 * @param int $direct
	 * @return array
	 */
	public function friends($total, $page = 1, $pagesize = 10, $direct = 0)
	{
	    $s = Yaf_Session::getInstance();
	    $uid = $s->get('user_id');
//
//	    require APPLICATION_PATH . '/config.inc.php';
//	    require APPLICATION_PATH . '/uc_client/client.php';
	    return uc_friend_ls($uid, $page, $pagesize, $total, $direct);
	}
	
	/**
	 * 添加好友
	 * 
	 * @param int $fid
	 * @return bool
	 */
	public function addFriend($fid)
	{
	    $s = Yaf_Session::getInstance();
	    $uid = $s->get('user_id');
//	    require APPLICATION_PATH . '/config.inc.php';
//	    require APPLICATION_PATH . '/uc_client/client.php';
	    return uc_friend_add($uid, $fid);
	}
	
	/**
	 * 删除好友
	 * 
	 * @param int $fid
	 * @return bool
	 */
	public function delFriend($fid)
	{
	    $s = Yaf_Session::getInstance();
	    $uid = $s->get('user_id');
//	    require APPLICATION_PATH . '/config.inc.php';
//	    require APPLICATION_PATH . '/uc_client/client.php';
	    return uc_friend_delete($uid, $fid);
	}
	
	/**
	 * 修改用户的余额，正为增加、负为减少
	 * 
	 * @param int $uid
	 * @param int $money
	 * @return mixed
	 */
	public function changeMoney($uid, $money)
	{
	    return $this->getPdo()->exec("UPDATE `user` SET `money`=`money`+({$money}) WHERE `user_id`='{$uid}'");
	}
	/**
	 * 修改用户的积分与金钱，正为增加、负为减少，0则不修改
	 *
	 * @param int $uid
	 * @param int $integral
	 * @param int $money
	 * @return mixed
	 */
	public function changeIntegralMoney($uid, $integral = 0, $money = 0)
	{
	    $comma = '';
	    $up_str = '';
	    if( $integral != 0 ) {
	        $up_str .= "`integral`=`integral`+({$integral})";
	        $comma = ',';
	    }
	    if( $money != 0 ) {
	        $up_str .= "{$comma}`money`=`money`+({$money})";
	    }
	    if( $up_str == '' ) return 0;
	    return $this->getPdo()->exec("UPDATE `user` SET {$up_str} WHERE `user_id`='{$uid}'");
	}
	
	/**
	 * 获取用户最近在玩的游戏
	 * 
	 * @param int $uid
	 * @param int $pn
	 * @param int $limit
	 * @return array
	 */
	public function getPlayGames($uid, $pn = 1, $limit = 3)
	{
	    $offset = ($pn - 1) * $limit;
	    return $this->fetchAllBySql("SELECT g.game_id,g.name,g.logo,g.corner,g.label,g.giftbag FROM user_games AS u 
	        LEFT JOIN game AS g ON u.game_id=g.game_id WHERE u.user_id='{$uid}' AND g.game_id IS NOT NULL 
	        ORDER BY u.last_play DESC LIMIT {$offset},{$limit}");
	}
	/**
	 * 获取用户最近登录的区/服
	 *
	 * @param int $uid
	 * @param int $gid
	 * @param int $pn
	 * @param int $limit
	 * @return array
	 */
	public function getPlayServers($uid, $gid, $pn = 1, $limit = 4)
	{
	    $offset = ($pn - 1) * $limit;
	    return $this->fetchAllBySql("SELECT s.server_id,s.name,s.corner,s.label FROM user_servers AS u
	        LEFT JOIN server AS s ON u.server_id=s.server_id WHERE u.user_id='{$uid}' AND u.game_id='{$gid}' AND s.server_id IS NOT NULL 
	        ORDER BY u.last_play DESC LIMIT {$offset},{$limit}");
	}
	/**
	 * 加入游戏到在玩列表
	 * 
	 * @param int $uid
	 * @param int $gid
	 */
	public function addPlayGame($uid, $gid)
	{
	    $time = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
	    $has = $this->fetchBySql("SELECT * FROM user_games WHERE user_id='{$uid}' AND game_id='{$gid}'");
	    $pdo = $this->getPdo();
	    if( $has ) {
	        $pdo->exec("UPDATE user_games SET last_play={$time} WHERE user_id='{$uid}' AND game_id='{$gid}'");
	    } else {
	        $pdo->exec("INSERT INTO user_games(user_id,game_id,last_play) VALUES({$uid},{$gid},{$time})");
	    }
	    
	    $pdo->exec("UPDATE `game` SET `play_times`=`play_times`+1 WHERE game_id='{$gid}'");
	    
	    //记录登录日志
	    $m_log = new SignonlogModel();
	    $m_log->insert(array(
	        'user_id' => $uid,
	        'game_id' => $gid,
	        'ip' => $_SERVER['REMOTE_ADDR'],
	        'time' => $time,
	    ), false);
	}
	/**
	 * 加入区/服到最近登录
	 * 
	 * @param int $uid
	 * @param int $gid
	 * @param int $sid
	 */
	public function addPlayServer($uid, $gid, $sid)
	{
	    $time = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
	    $pdo = $this->getPdo();
	    
	    $has = $this->fetchBySql("SELECT * FROM user_games WHERE user_id='{$uid}' AND game_id='{$gid}'");
	    if( $has ) {
	        $pdo->exec("UPDATE user_games SET last_play={$time} WHERE user_id='{$uid}' AND game_id='{$gid}'");
	    } else {
	        $pdo->exec("INSERT INTO user_games(user_id,game_id,last_play) VALUES({$uid},{$gid},{$time})");
	    }
	    
	    $has = $this->fetchBySql("SELECT * FROM user_servers WHERE user_id='{$uid}' AND server_id='{$sid}'");
	    if( $has ) {
	        $pdo->exec("UPDATE user_servers SET last_play={$time} WHERE user_id='{$uid}' AND server_id='{$sid}'");
	    } else {
	        $pdo->exec("INSERT INTO user_servers(user_id,game_id,server_id,last_play) VALUES({$uid},{$gid},{$sid},{$time})");
	    }
	    
	    $pdo->exec("UPDATE `game` SET `play_times`=`play_times`+1 WHERE game_id='{$gid}'");
	    
	    //记录登录日志
	    $m_log = new SignonlogModel();
	    $m_log->insert(array(
	        'user_id' => $uid,
	        'game_id' => $gid,
	        'ip' => $_SERVER['REMOTE_ADDR'],
	        'time' => $time,
	    ), false);
	}
	
	/**
	 * 获取玩家的收藏列表
	 * 
	 * @param int $uid
	 * @param int $pn
	 * @param int $limit
	 * @return array
	 */
	public function getFavorites($uid, $pn = 1, $limit = 3)
	{
	    $offset = ($pn - 1) * $limit;
	    return $this->fetchAllBySql("SELECT g.game_id,g.name,g.logo,g.corner,g.label,g.giftbag FROM user_favorites AS f
	        LEFT JOIN game AS g ON f.game_id=g.game_id WHERE f.user_id='{$uid}' AND g.game_id IS NOT NULL 
	        ORDER BY f.add_time DESC LIMIT {$offset},{$limit}");
	}
	/**
	 * 加入游戏到收藏夹
	 *
	 * @param int $uid
	 * @param int $gid
	 */
	public function addFavorite($uid, $gid)
	{
	    $has = $this->fetchBySql("SELECT add_time FROM user_favorites WHERE user_id='{$uid}' AND game_id='{$gid}'");
	    if( empty($has) ) {
	        $time = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
	        $this->getPdo()->exec("INSERT INTO user_favorites(user_id,game_id,add_time) VALUES({$uid},{$gid},{$time})");
	    }
	}
	/**
	 * 从收藏夹移除一个游戏
	 * 
	 * @param int $uid
	 * @param int $gid
	 * @return int
	 */
	public function delFavorite($uid, $gid)
	{
	    return $this->getPdo()->exec("DELETE FROM user_favorites WHERE user_id='{$uid}' AND game_id='{$gid}'");
	}
	/**
	 * 判断游戏是否已被收藏
	 * 
	 * @param int $uid
	 * @param int $gid
	 * @return bool
	 */
	public function isFavorited($uid, $gid)
	{
	    $has = $this->fetchBySql("SELECT add_time FROM user_favorites WHERE user_id='{$uid}' AND game_id='{$gid}'");
	    return $has ? true : false;
	}
	
	/**
	 * 获取玩家的礼包记录
	 * 
	 * @param int $uid
	 * @param int $pn
	 * @param int $limit
	 * @return array
	 */
	public function giftLogs($uid, $pn = 1, $limit = 6)
	{
	    $offset = ($pn - 1) * $limit;
	    $pdo = $this->getPdo();
	    $stm = $pdo->query("SELECT * FROM user_cdkey WHERE user_id='{$uid}' LIMIT {$offset},{$limit}");
	    $logs = array();
	    $row = $stm->fetch(PDO::FETCH_ASSOC);
	    while ($row)
	    {
	        $logs[] = $row;
	        $row = $stm->fetch(PDO::FETCH_ASSOC);
	    }
	    return $logs;
	}
}
