<?php

class AdmingroupModel extends F_Model_Pdo
{
	protected $_table = 'admin_group';
	protected $_primary='group_id';
	
	public function getTableLabel()
	{
		return '权限组';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'group_id' => '组ID',
			'parent_id' => '父ID',
		    'child_no' => '子权限数',
			'name' => '组名称',
		    'weight' => '排序',
		);
	}
	
	/**
	 * 递归获取权限分组
	 * 
	 * @param int $parent_id
	 * @return array
	 */
	public function getAllGroups($parent_id = 0)
	{
	    $data = $this->fetchAll("parent_id={$parent_id}", 1, 50, '*', 'weight ASC,group_id ASC');
	    foreach ($data as $k=>$v)
	    {
	        if( $v['child_no'] > 0 ) {
	            $data[$k]['lists'] = $this->getAllGroups($v['group_id']);
	        }
	    }
	    return $data;
	}
}
