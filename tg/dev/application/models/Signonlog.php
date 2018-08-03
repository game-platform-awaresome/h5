<?php

class SignonlogModel extends F_Model_Pdo
{
	protected $_table = 'signon_log';
	protected $_primary = '';
    public function __construct()
    {
        parent::__construct('h5');
    }
	public function getTableLabel()
	{
	    return '登录日志';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'user_id' => '用户ID',
	        'game_id' => '游戏ID',
	        'ip' => '登录IP',
	        'time' => function(&$row){
	            if(empty($row)) return '登录时间';
	            return date('Y-m-d H:i:s', $row['time']);
	        },
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'user_id' => array('用户ID', 'input', null, ''),
	        'game_id' => array('游戏ID', 'input', null, ''),
	        'time' => array('登录日期', 'datepicker', '{dateFmt:\'yyyy-MM-dd\'}', ''),
	    );
	}
}
