<?php

class GroupmenuModel extends F_Model_Pdo
{
	protected $_table = 'group_menu';
	protected $_primary = array('group_id', 'menu_id');
	
	public function getTableLabel()
	{
		return '分组权限';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'group_id' => '组ID',
			'menu_id' => '菜单ID',
		);
	}
}
