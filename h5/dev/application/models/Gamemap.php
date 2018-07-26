<?php

class GamemapModel extends F_Model_Pdo
{
	protected $_table = 'game_map';
	protected $_primary='map_id';
	
	//渠道
	public $_channels = array(
	    'egret' => '白鹭',
	);
	
	public function getTableLabel()
	{
		return '渠道游戏映射';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'map_id' => 'ID',
			'channel' => function(&$row){
			    if( empty($row) ) return '合作渠道';
			    return $this->_channels[$row['channel']];
			},
			'chnl_gid' => '渠道游戏ID',
		    'chnl_sid' => '渠道区/服ID',
		    'game_id' => '久乐游戏ID',
		    'server_id' => '久乐区/服ID',
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'channel' => array('合作渠道', 'select', $this->_channels, null),
	    );
	}
}
