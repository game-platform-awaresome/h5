<?php

class ActivityModel extends F_Model_Pdo
{
	protected $_table = 'activity';
	protected $_primary = 'act_id';
	
	public $_ctls = array(
	    'Roulette' => '转盘摇奖',
	);
	public $_conds = array(
	    'integral' => '积分',
	    'money' => '平台币',
	    'free' => '免费参与',
	);
	public $_periods = array(
	    'unlimited' => '无次数限制',
	    'day' => '每天一次',
	    'week' => '每周一次',
	    'month' => '每月一次',
	);
	
	public function getTableLabel()
	{
		return '活动';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'act_id' => '活动ID',
			'cover' => function(&$row){
		        if( empty($row) ) return '封面';
		        return $row['cover'] ? "<a class=\"lightbox\" href=\"{$row['cover']}\"><img src=\"{$row['cover']}\" style=\"max-width:300px;\"></a>" : '';
		    },
			'title' => '标题',
			'cond' => function(&$row){
			    if(empty($row)) return '参与方式';
			    return $this->_conds[$row['cond']];
			},
			'consume' => '每次消耗',
			'period' => function(&$row){
			    if(empty($row)) return '参与频率';
			    return $this->_periods[$row['period']];
			},
		    'controller' => function(&$row){
		        if(empty($row)) return '活动类型';
		        return $this->_ctls[$row['controller']];
		    },
		    'begin_time' => function(&$row){
		        if( empty($row) ) return '上线时间';
		        return $row['begin_time'] ? date('Y-m-d H:i:s', $row['begin_time']) : '立即';
		    },
		    'end_time' => function(&$row){
		        if( empty($row) ) return '下线时间';
		        return $row['end_time'] ? date('Y-m-d H:i:s', $row['end_time']) : '永不';
		    },
		    'visible' => function(&$row){
		        if( empty($row) ) return '是否可见';
		        return $row['visible'] ? '是' : '-';
		    },
		    'weight' => '排序',
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        //'act_id' => array('活动ID', 'input', null, ''),
	        //'controller' => array('控制器', 'input', null, ''),
	    );
	}
}
