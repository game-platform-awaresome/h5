<?php

class AdminmenuModel extends F_Model_Pdo
{
	protected $_table = 'admin_menu';
	protected $_primary='menu_id';
	
	public function getTableLabel()
	{
		return '菜单列表';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'menu_id' => '菜单ID',
			'parent_id' => '菜单父ID',
			'controller' => '控制器',
			'action' => '方法',
			'name' => '菜单名称',
			'display' => '显示顺序',
		    'visible' => function(&$row){
		        if( empty($row) ) return '是否显示';
		        if( $row['visible'] ) return '是';
		        else return '-';
		    },
		    'super' => function(&$row){
		        if( empty($row) ) return '超级菜单';
		        if( $row['super'] ) return '<font color="red">是</font>';
		        else return '-';
		    },
		);
	}
	public function getListCallback()
	{
	    return array(
	        'op_edit' => function(&$row){
	            if( $row['super'] ) {
	                return false;
	            }
	            return true;
	        },
	        'op_delete' => function(&$row){
	            if( $row['super'] ) {
	                return false;
	            }
	            return true;
	        }
	    );
	}
	
	/**
	 * 获取用户菜单
	 * 
	 * @param number $user_id
	 * @return array
	 */
	public function get($user_id = 0)
	{
		$menu = array();
		$first = $this->fetchAll(array('parent_id'=>0, 'visible'=>1), 1, 50, '*', 'display asc');
		$i = 0;
		foreach ($first as $row)
		{
			$menu[$i]['nav'] = $row;
			$menu[$i]['items'] = $this->fetchAll(array('parent_id'=>$row['menu_id'], 'visible'=>1), 1, 50, '*', 'display asc');
			++$i;
		}
		return $menu;
	}
}
