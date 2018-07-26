<?php

class StatbypayModel extends F_Model_Pdo
{
	protected $_table = 'stat_by_pay';
	protected $_primary = array('ymd','type');
	
	public $_types = array(
	    'iapppay' => '爱贝云计费',
	    'alipay' => '支付宝',
	    'wxpay' => '微信',
	    'deposit' => '余额',
	);
	
	public function getTableLabel()
	{
	    return '充值方式统计';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'ymd' => '统计日期',
	        'type' => function(&$row){
	            if( empty($row) ) return '充值类型';
	            return $this->_types[$row['type']];
	        },
	        'recharge_times' => '充值次数',
	        'recharge_people' => '充值人数',
	        'recharge_money' => '充值金额',
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'ymd_begin' => array('开始日期', 'datepicker', null, ''),
	        'ymd_end' => array('结束日期', 'datepicker', null, ''),
	        'type' => array('充值类型', 'select', $this->_types, null),
	    );
	}
}
