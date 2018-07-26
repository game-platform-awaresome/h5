<?php

class GroupcontrollerModel extends F_Model_Pdo
{
	protected $_table = 'group_controller';
	protected $_primary = array('group_id', 'controller');
	
	public function getTableLabel()
	{
		return '权限控制器隐射';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'group_id' => '组ID',
			'controller' => '控制器',
		);
	}
}
