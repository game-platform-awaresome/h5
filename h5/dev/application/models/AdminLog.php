<?php

class AdminlogModel extends F_Model_Pdo
{
	protected $_table = 'admin_log';
	protected $_primary = 'log_id';
	
	public function getTableLabel()
	{
		return '管理员操作日志';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'log_id' => '日志ID',
			'admin' => '管理员',
			'content' => '操作内容',
			'op_time' => '操作时间',
		);
	}
	
	public function getFieldsSearch()
	{

	    return array(
	        'admin' => array('操作管理员', 'input', null, ''),
	        'content' => array('操作内容', 'input', null, ''),
	        'ymd' => array('操作日期', 'datepicker', '{dateFmt:\'yyyyMMdd\'}', ''),
	    );
	}
}
