<?php

class StatisticsModel extends F_Model_Pdo
{
	protected $_table = 'statistics';
	protected $_primary = 'ymd';
	
	public function getTableLabel()
	{
	    return '平台统计';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'ymd' => '统计日期',
	        'deposit' => function(&$row){
	            if(empty($row)) return '平台币存量';
	            return number_format($row['deposit']).'￥';
	        },
	        'new_payer' => '新增付费用户',
	        'reg_people' => '新增登录人数',
	        'signon_times' => '登录次数',
	        'signon_people' => '登录人数',
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
	    );
	}
}
