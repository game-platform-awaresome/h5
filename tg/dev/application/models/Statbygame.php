<?php

class StatbygameModel extends F_Model_Pdo
{
	protected $_table = 'stat_by_game';
	protected $_primary = array('ymd','game_id','admin_id');
//    public function __construct()
//    {
//        parent::__construct('h5');
//    }
	public function getTableLabel()
	{
	    return '游戏统计';
	}

    public function getFieldsLabel()
    {
        return array(
            'ymd' => '统计日期',
            'game_name' => '游戏名称',
            'signon_times' => '登录次数',
            'signon_people' => '登录人数',
            'recharge_times' => '充值次数',
            'recharge_people' => '充值人数',
            'recharge_money' => function(&$row){
                if(empty($row)) return '充值金额';
                return number_format($row['recharge_money']).'￥';
            },
        );
    }

    public function getFieldsSearch()
    {
        return array(
            'ymd_begin' => array('开始日期', 'datepicker', null, ''),
            'ymd_end' => array('结束日期', 'datepicker', null, ''),
            'game_name' => array('游戏名称', 'input', null, ''),
        );
    }
}
