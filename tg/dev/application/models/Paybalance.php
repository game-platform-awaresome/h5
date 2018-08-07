<?php

class PaybalanceModel extends F_Model_Pdo
{
	protected $_table = 'pay_balance';
	protected $_primary='id';
	public function getTableLabel()
	{
		return '渠道结算';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'id' => '编号',
            'start_time' =>function($row){
                if(empty($row['start_time']))return '开始时间';
                return date('Y-m-d H:i:s',$row['start_time']);
            },
		    'end_time' =>function($row){
		        if(empty($row['end_time']))return '结束时间';
		        return date('Y-m-d H:i:s',$row['end_time']);
            },
		    'admin_id' =>function($row){
                if(empty($row['admin_id']))return '渠道信息';
                return '所有代理及本渠道';
            },
		    'add_time' => '申请时间',
		    'total_stream' => '流水总计',
			'total_balance' => '结算总计',
			'status' => function($row){
		        if(empty($row['status']))return '申请';
		         switch ($row['status']){
                     case 1:
                         return "<a class=\"lightbox\" href=\"{$row['id']}\">申请结算</a>";
                         break;
                     case 2:
                         return "<a class=\"lightbox\" href=\"{$row['id']}\">正在结算中</a>";
                         break;
                     case 3:
                         return "<a class=\"lightbox\" href=\"{$row['id']}\">已经结算完毕</a>";
                         break;
                     default;
                     break;
                 }
            },
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
	    );
	}
}
