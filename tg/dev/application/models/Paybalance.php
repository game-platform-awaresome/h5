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
                if(empty($row['admin_id']))return '渠道id-姓名-支付宝';
                if($row['admin_id']==$_SESSION['admin_id']){
                    return '所有代理及本渠道';
                }else{
                    $admin=new AdminModel();
                    $info=$admin->fetch(['admin_id'=>$row['admin_id']],'nickname,pay_number');
                    return $row['admin_id'].'-'.$info['nickname'].'-'.$info['pay_number'];
                }
            },
		    'add_time' =>function($row) {
                if (empty($row['add_time'])) return '申请时间';
                return date('Y-m-d H:i:s', $row['add_time']);
            },
		    'total_stream' => '流水总计',
			'total_balance' => '结算总计',
			'status' => function($row){
		        if(empty($row['status']))return '申请';
		         switch ($row['status']){
                     case 1:
                         if($row['total_balance']<100){
                             return '累计结算金额大于100方可申请结算';
                         }
                         return "<a href=\"applybalance?id={$row['id']}&type=apply\">申请结算</a>";
                         break;
                     case 2:
                         return "<a href=\"javascrpit:void(0);\">正在结算中</a>";
                         break;
                     case 3:
                         return "<a  href=\"javascrpit:void(0);\">已经结算完毕</a>";
                         break;
                     default;
                     break;
                 }
            },

		);
	}
    public function getFieldsPadding()
    {
        if($_SESSION['admin_id']==1) {
            return array(
                function (&$row) {
                    if (empty($row)) return '操作';
                    if($row['status']==2){
                        return "<a href=\"applybalance?id={$row['id']}&type=complete\">结算完毕</a>";
                    }else{
                        return '';
                    }
                }
            );
        }else{
            return array();
        }
    }
	public function getFieldsSearch()
	{
	    return array(
	    );
	}
}
