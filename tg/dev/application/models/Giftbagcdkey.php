<?php

class GiftbagcdkeyModel extends F_Model_Pdo
{
	protected $_table = 'giftbag_cdkey';
	protected $_primary='cdkey';
    public function __construct()
    {
        parent::__construct('h5');
    }
	public function getTableLabel()
	{
		return '礼包兑换码';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'cdkey' => '兑换码',
			'gift_id' => '礼包ID',
		    'user_id' => '用户ID（领取人）',
		    'get_time' => function(&$row){
		        if( empty($row) ) return '领取时间';
		        return $row['get_time'] ? date('Y-m-d H:i:s', $row['get_time']) : '-';
		    },
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'cdkey' => array('兑换码', 'input', null, ''),
	        'gift_id' => array('礼包ID', 'input', null, ''),
	    );
	}
	
	/**
	 * 生成16位礼包兑换码
	 * 
	 * @param bool $numeric
	 * @return string $cdkey
	 */
	public function createCdkey($numeric = false)
	{
	    if( $numeric ) {
	        list($usec, $sec) = explode(' ', microtime());
	        $usec = str_replace('0.', '', $usec);
	        $cdkey = substr($sec, 3);
	        $len = strlen($usec);
	        $pad = 100;
	        if( $len < 6 ) {
	            $pad *= pow(10, 6 - $len);
	        } else {
	            $len = 6;
	        }
	        $cdkey .= substr($usec, 0, $len);
	        $cdkey .= mt_rand($pad, $pad*10-1);
	        return $cdkey;
	    }
	    
	    $cdkey = strtoupper(substr(uniqid(), 3));
	    $arr = array(1,2,3,4,5,6,7,8,9,'A','B','C','D','E','F','G','H','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z');
	    shuffle($arr);
	    $max = count($arr) - 1;
	    for($i = 0; $i < 6; ++$i)
	    {
	        $k = mt_rand(0, $max);
	        $cdkey .= $arr[$k];
	    }
	    return $cdkey;
	}
}
