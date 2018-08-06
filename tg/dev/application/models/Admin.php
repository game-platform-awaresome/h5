<?php

class AdminModel extends F_Model_Pdo
{
	protected $_table = 'admin';
	protected $_primary='admin_id';
	
	private $_ck_ui_name = '_a_';
	
	private $_tmp_m_g;
	private $_tmp_group = array();
	
	public function getTableLabel()
	{
		return '渠道';
	}
	
	public function getFieldsLabel()
	{
	    $this->_tmp_m_g = F_Model_Pdo::getInstance('Admingroup');
		return array(
			'admin_id' => '渠道ID',
			'username' => '渠道账号',
			'nickname' => '渠道名称',
		    'add_by' => '添加人',
			'add_ip' => '添加IP',
		    'add_time' => '添加时间',
		    'parent_id' => '上级渠道',
            'parent_id' => function(&$row){
                if( empty($row) ) return '上级渠道';
                $parent_name=$this->fetch(['admin_id'=>$row['parent_id']],'nickname');
                return $parent_name['nickname']??'无';
            },
//		    'status' => function(&$row){
//		        if( empty($row) ) return '账号类型';
//		        switch ($row['status'])
//		        {
//		            case 'super': return '超级管理员';
//		            case 'normal': return '普通管理员';
//		            case 'disabled': return '被禁用';
//		        }
//		    },
//		    'group_id' => function(&$row){
//		        if( empty($row) ) return '权限组';
//		        if( $row['group_id'] == 0 ) return '-';
//		        if( array_key_exists($row['group_id'], $this->_tmp_group) ) return $this->_tmp_group[$row['group_id']];
//		        $tmp = $this->_tmp_m_g->fetch("group_id='{$row['group_id']}'", 'name');
//		        $this->_tmp_group[$row['group_id']] = $tmp['name'];
//		        return $tmp['name'];
//		    },
		);
	}
//    public function getFieldsPadding()
//    {
//        return array(
//            'link' => function(&$row){
//                if(empty($row)) return '推广链接';
//                $url = 'http://www.namiyx.com/channel/index/'.$row['code'];
//                if( $row['fwd'] ) {
//                    $fwd = str_replace(array('http://www.namiyx.com', 'http://namiyx.com', 'www.namiyx.com', 'namiyx.com'), '', trim($row['fwd']));
//                    $url .= '?fwd=';
//                    $url .= strpos($row['fwd'], '?') ? urlencode($fwd) : $fwd;
//                }
//                return $url;
//            },
//        );
//    }
	/**
	 * 管理员登录
	 *
	 * @param string $username
	 * @param string $password
	 * @param number $remember
	 * @return string
	 */
	public function login($username, $password, $remember = 0)
	{
		if( empty($username) || empty($password) ) {
			return 'Empty username or password.';
		}
		if( strlen($password) != 32 ) {
			$password = md5($password);
		}
		$conds = array('username'=>$username);
		$user = $this->fetch($conds);
		if( empty($user) || strcmp($password, $user['password']) != 0 ) {
			return '用户名或密码错误。';
		}
		if( $user['status'] == 'disabled' ){
			return '你没有访问权限。';
		}
        $tg_channel=$user['admin_id'];
        $admin=new AdminModel();
        $channel_ids=$admin->fetchAll(['parent_id'=>$user['admin_id']],1,20000,'admin_id');
        foreach ($channel_ids as $k=>$v){
            $channel_ids[$k]=(int)$channel_ids[$k]['admin_id'];
        }

		$s = Yaf_Session::getInstance();
		$s->set('admin_id', $user['admin_id']);
		$s->set('admin_name', $user['username']);
		$s->set('admin_status', $user['status']);
		$s->set('admin_group', $user['group_id']);
		$s->set('channel_ids', $channel_ids);//渠道
        $s->set('channel_ids_condition','('.implode(',',$channel_ids).')');

		if( $remember ) {
			$time = time() + 864000;
			$info = "{$user['admin_id']}\t{$user['username']}\t{$user['status']}\t{$user['group_id']}\t{$time}";
			$info = F_Helper_Mcrypt::Encrypt($info);
			setcookie($this->_ck_ui_name, $info, $time, '/');
		}
		return '';
	}
	
	public function logout()
	{
		$s = Yaf_Session::getInstance();
		$s->del('admin_id');
		$s->del('admin_name');
		$s->del('admin_status');
		$s->del('admin_group');
		
		if( isset($_COOKIE[$this->_ck_ui_name]) ) {
			setcookie($this->_ck_ui_name, '', 1, '/');
		}
	}
	
	/**
	 * 获取管理员的登录信息，并检查管理员的权限
	 * 
	 * @return null|array
	 */
	public function getLogin()
	{
		$s = Yaf_Session::getInstance();
		$user_id = $s->get('admin_id');
		$s_status = $s->get('admin_status');
		if( $user_id ) {
			if( $s_status == 'disabled' ) {
				return null;
			}
			return array(
				'admin_id' => $user_id,
				'username' => $s->get('admin_name'),
				'status' => $s->get('admin_status'),
			    'group_id' => $s->get('admin_group'),
			);
		}
		if( empty($_COOKIE[$this->_ck_ui_name]) ) {
			return null;
		}
		$info = F_Helper_Mcrypt::Decrypt($_COOKIE[$this->_ck_ui_name]);
		$info = explode("\t", $info);
		if( count($info) != 5 ) {
			setcookie($this->_ck_ui_name, '', 1, '/');
			return null;
		}
		list($user_id, $username, $status, $group_id, $time) = $info;
		if( (int)$time > time() ) {
			if( $status == 'disabled' ) {
				return null;
			}
			$s->set('admin_id', $user_id);
			$s->set('admin_name', $username);
			$s->set('admin_status', $status);
			$s->set('admin_group', $group_id);
			return array(
				'admin_id' => $user_id,
				'username' => $username,
				'status' => $status,
			    'group_id' => $group_id,
			);
		}
		setcookie($this->_ck_ui_name, '', 1, '/');
		return null;
	}
}