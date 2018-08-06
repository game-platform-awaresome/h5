<?php

class TgchannelModel extends F_Model_Pdo
{
	protected $_table = 'channel';
	protected $_primary = 'channel_id';

	private $_tmp_cache = array();


	public function getTableLabel()
	{
	    return '推广渠道';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'channel_id' => '渠道ID',
	        'nickname' => '渠道名称',
	        'code' => '渠道代码',
	        'fwd' => '跳转地址',
	        'admin' => '所属管理员',
	        'add_time' => function(&$row){
	            if(empty($row)) return '添加时间';
	            return substr($row['add_time'], 0 ,10);
	        },
	    );
	}
	
	public function getFieldsPadding()
	{
	    return array(
	        'link' => function(&$row){
	            if(empty($row)) return '推广链接';
	            $url = 'http://www.namiyx.com/channel/index/'.$row['code'];
	            if( $row['fwd'] ) {
	                $fwd = str_replace(array('http://www.namiyx.com', 'http://namiyx.com', 'www.namiyx.com', 'namiyx.com'), '', trim($row['fwd']));
	                $url .= '?fwd=';
	                $url .= strpos($row['fwd'], '?') ? urlencode($fwd) : $fwd;
	            }
	            return $url;
	        },
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'code' => array('推广代码', 'input', null, ''),
	    );
	}
	
	/**
	 * 获取渠道信息
	 * 
	 * @param int $channel_id
	 * @param string $selects
	 * @return array
	 */
	public function getWithTmpCache($channel_id, $selects = 'name')
	{
	    if( array_key_exists($channel_id, $this->_tmp_cache) ) {
	        return $this->_tmp_cache[$channel_id];
	    }
	    $this->_tmp_cache[$channel_id] = $this->fetch("channel_id='{$channel_id}'", $selects);
	    return $this->_tmp_cache[$channel_id];
	}
}
