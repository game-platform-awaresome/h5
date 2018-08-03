<?php

class ServerModel extends F_Model_Pdo
{
	protected $_table = 'server';
	protected $_primary='server_id';
    public function __construct()
    {
        parent::__construct('h5');
    }
	public $_corner = array(
	    'normal' => '-',
	    'recommend' => '推荐',
	    'new' => '新服',
	);
	public $_labels = array(
	    'normal' => '-',
	    'hot' => '人气',
	    'new' => '上新',
	);
	//渠道
	public $_channels = array(
	    'self' => '-',
	    'egret' => '白鹭',
	);
	
	public $_load_types = array(
	    'iframe' => 'iframe标签',
	    'object' => 'object标签',
	    'redirect' => '跳转（域名会变，慎用）',
	);
	
	public function getTableLabel()
	{
		return '游戏-区/服';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'server_id' => '区/服ID',
			'game_name' => '游戏',
		    'name' => '区/服名称',
		    'corner' => function(&$row){
		        if( empty($row) ) return '角标';
		        return $this->_corner[$row['corner']];
		    },
		    'label' => function(&$row){
		        if( empty($row) ) return '标注';
		        return $this->_labels[$row['label']];
		    },
		    'weight' => '排序',
		    'login_url' => '登录地址',
		    'recharge_url' => '充值地址',
		    'add_time' => function(&$row){
		        if( empty($row) ) return '添加时间';
		        return substr($row['add_time'], 0, 10);
		    },
		    'visible' => function(&$row){
		        if( empty($row) ) return '是否可见';
		        return $row['visible'] ? '是' : '否';
		    },
		    'channel' => function(&$row){
		        if( empty($row) ) return '合作渠道';
		        return $this->_channels[$row['channel']];
		    },
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'game_id' => array('游戏ID', 'input', null, ''),
	    );
	}
}
