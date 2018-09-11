<?php

class AdinstanceModel extends F_Model_Pdo
{
	protected $_table = 'ad_instance';
	protected $_primary='ad_id';
	
	public $_target = array(
	    '_self' => '本页面',
	    '_blank' => '新页面',
	    '_top' => '顶层页面',
	);
	
	public function getTableLabel()
	{
		return '广告';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'ad_id' => '广告ID',
			'pos_id' => '广告位ID',
			'name' => '广告位名称',
		    'image' => function(&$row){
		        if( empty($row) ) return '广告图片';
		        return $row['image'] ? "<a class=\"lightbox\" href=\"{$row['image']}\"><img src=\"{$row['image']}\" style=\"max-width:300px;\"></a>" : '';
		    },
		    'subject' => '广告标题',
		    'url' => '广告链接',
		    'target' => function(&$row){
		        if(empty($row)) return '打开方式';
		        return $this->_target[$row['target']];
		    },
		    'add_time' => '添加时间',
		    'on_time' => function(&$row){
		        if( empty($row) ) return '上线时间';
		        return $row['on_time'] ? date('Y-m-d H:i:s', $row['on_time']) : '立即';
		    },
		    'off_time' => function(&$row){
		        if( empty($row) ) return '下线时间';
		        return $row['off_time'] ? date('Y-m-d H:i:s', $row['off_time']) : '永不';
		    },
		    'visible' => function(&$row){
		        if( empty($row) ) return '是否可见';
		        return $row['visible'] ? '是' : '否';
		    },
		    'display' => '显示顺序',
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'ad_id' => array('广告ID', 'input', null, ''),
	        'pos_id' => array('广告位ID', 'input', null, ''),
	    );
	}
}
