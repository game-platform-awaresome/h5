<?php

class TgstatisticsModel extends F_Model_Pdo
{
	protected $_table = 'tg_statistics';
	protected $_primary = 'stat_id';
	public function getTableLabel()
	{
	    return '渠道统计';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'ymd' => function(&$row){
                if( empty($row) ) return '统计日期';
                return date('Y-m-d H:i:s', $row['ymd']);
            },
	        'channel' => function(&$row){
	            if(empty($row)) return '推广渠道';
	            $model = F_Model_Pdo::getInstance('Tgchannel');
	            $tmp = $model->getWithTmpCache($row['channel']);
	            return $tmp ? $tmp['name'] : '-';
	        },
	        'pv' => '访问量',
	        'ip' => '独立IP数',
	        'reg_people' => '注册人数',
	        'pay_times' => '付费次数',
	        'pay_people' => '付费人数',
	        'pay_money' => function(&$row){
	            if(empty($row)) return '付费金额';
	            return number_format($row['pay_money']).'￥';
	        },
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'ymd_begin' => array('开始日期', 'datepicker', null, ''),
	        'ymd_end' => array('结束日期', 'datepicker', null, ''),
	        'channel' => array('渠道ID', 'input', null, ''),
	    );
	}
}
