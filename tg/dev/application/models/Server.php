<?php

class ServerModel extends F_Model_Pdo
{
	protected $_table = 'server';
	protected $_primary='server_id';
    public function __construct()
    {
        parent::__construct('h5');
    }
//	public $_corner = array(
//	    'normal' => '-',
//	    'recommend' => '推荐',
//	    'new' => '新服',
//	);
//	public $_labels = array(
//	    'normal' => '-',
//	    'hot' => '人气',
//	    'new' => '上新',
//	);
	//渠道
//	public $_channels = array(
//	    'self' => '-',
//	    'egret' => '白鹭',
//	);
	
//	public $_load_types = array(
//	    'iframe' => 'iframe标签',
//	    'object' => 'object标签',
//	    'redirect' => '跳转（域名会变，慎用）',
//	);
	
//	public function getTableLabel()
//	{
//		return '游戏-区/服';
//	}
	
	public function getFieldsLabel()
	{
		return array(
			'server_id' => 'ID',
			'game_name' => '游戏名称',
		    'name' => '区/服名称',
		    'start_time' => '开服时间',
		    'number' => '编号',
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'game_id' => array('游戏ID', 'input', null, ''),
	    );
	}
}
