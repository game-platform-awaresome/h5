<?php

class AdminfeedbackModel extends F_Model_Pdo
{
	protected $_table = 'admin_feedback';
	protected $_primary = 'fb_id';
	
	public function getTableLabel()
	{
		return '后台使用反馈';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'fb_id' => '反馈ID',
		    'type' => '反馈类型',
			'username' => '反馈人',
			'weight' => '权重',
		    'title' => '标题',
		    'add_time' => '反馈时间',
		    'deal_time' => function(&$row){
		        if( empty($row) ) return '处理时间';
		        return $row['deal_time'] ? date('Y-m-d H:i:s', $row['deal_time']) : '-';
		    },
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'type' => array('分类', 'select', $this->_types, null),
	        'game_id' => array('游戏ID', 'input', null, ''),
	        'article_id' => array('资讯ID', 'input', null, ''),
	    );
	}
}
