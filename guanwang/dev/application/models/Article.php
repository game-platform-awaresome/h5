<?php

class ArticleModel extends F_Model_Pdo
{
	protected $_table = 'article';
	protected $_primary = 'article_id';
	
	public $_types = array('公司介绍','产品介绍','加入我们');
	
	public function getTableLabel()
	{
		return '文档及资料';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'article_id' => '文档ID',
			'type' => '分类',
			'weight' => '排序',
		    'title' => '标题',
		    'add_time' => function(&$row){
		        if( empty($row) ) return '创建时间';
		        return date('Y-m-d H:i:s', $row['add_time']);
		    },
		    'up_time' => function(&$row){
		        if( empty($row) ) return '更新时间';
		        return $row['up_time'] ? date('Y-m-d H:i:s', $row['up_time']) : '-';
		    },
		    'visible' => function(&$row){
		        if( empty($row) ) return '是否可见';
		        return $row['visible'] ? '是' : '否';
		    },
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'type' => array('分类', 'select', $this->_types, null),
	        'article_id' => array('文档ID', 'input', null, ''),
	    );
	}
}
