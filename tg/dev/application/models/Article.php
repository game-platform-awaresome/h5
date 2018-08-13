<?php

class ArticleModel extends F_Model_Pdo
{
	protected $_table = 'article';
	protected $_primary = 'article_id';
	
	public $_types = array('活动','新闻','公告','视频','攻略','下载','代理公告');
	
	public function getTableLabel()
	{
		return '资讯';
	}
    public function __construct()
    {
        parent::__construct('h5');
    }
	public function getFieldsLabel()
	{
		return array(
			'article_id' => '资讯ID',
			'type' => '分类',
			'game_name' => '游戏名称',
			'weight' => '排序',
		    'cover' => function(&$row){
		        if( empty($row) ) return '封面';
		        return $row['cover'] ? "<a class=\"lightbox\" href=\"{$row['cover']}\"><img style=\"max-width:32px;\" src=\"{$row['cover']}\"></a>" : '';
		    },
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
	        'game_id' => array('游戏ID', 'input', null, ''),
	        'article_id' => array('资讯ID', 'input', null, ''),
	    );
	}
	
	/**
	 * 格式化日期显示
	 * 
	 * @param int $time
	 * @return string
	 */
	public function formatTime($time)
	{
	    $cmp_t = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
	    $tmp = $cmp_t - $time;
	    if( $tmp < 60 ) {
	        return '刚刚';
	    } elseif( $tmp < 3600 ) {
	        return floor($tmp/60).'分钟前';
	    } elseif( $tmp < 86400 ) {
	        return floor($tmp/3600).'小时前';
	    } elseif( $tmp < (86400*30) ) {
	        return floor($tmp/86400).'天前';
	    } elseif( $tmp < (86400*365) ) {
	        return floor($tmp/(86400*30)).'月前';
	    } else {
	        return floor($tmp/(86400*365)).'年前';
	    }
	}
}
