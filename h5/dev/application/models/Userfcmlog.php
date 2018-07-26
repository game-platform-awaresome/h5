<?php

class UserfcmlogModel extends F_Model_Pdo
{
	protected $_table = 'user_fcm_log';
	protected $_primary = 'user_id';
	
	public function getTableLabel()
	{
	    return '防沉迷验证日志';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'user_id' => '用户ID',
	        'day' => '验证日期',
	        'week' => '验证周',
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'user_id' => array('用户ID', 'input', null, ''),
	    );
	}
}
