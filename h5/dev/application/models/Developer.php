<?php

class DeveloperModel extends F_Model_Pdo
{
    protected $_table = 'developer';
    protected $_primary = 'dev_id';
	
	public $_status = array(
	    0 => '新用户',
	    1 => '申请认证',
	    2 => '拒绝申请',
	    3 => '冻结账户',
	    9 => '认证通过',
	);
	public $_froze_code = 3; //冻结状态码
	
	public function __construct()
	{
	    parent::__construct('h5_open');
	}
}
