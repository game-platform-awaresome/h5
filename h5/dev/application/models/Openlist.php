<?php

class OpenlistModel extends F_Model_Pdo
{
	protected $_table = 'open_list';
	protected $_primary = 'id';
	
	public function getTableLabel()
	{
	    return '开服列表';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'id' => 'ID',
	        'game_name' => '游戏名称',
	        'server_name' => '区/服名称',
	        'open_time' => function(&$row){
	            if(empty($row)) return '开服时间';
	            return date('Y-m-d H:i', $row['open_time']);
	        },
	    );
	}
}
