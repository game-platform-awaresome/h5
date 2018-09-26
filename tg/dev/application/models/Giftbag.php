<?php

class GiftbagModel extends F_Model_Pdo
{
	protected $_table = 'giftbag';
	protected $_primary='gift_id';
    public function __construct()
    {
        parent::__construct('h5');
    }
	public $_types = array(
	    'limited' => '限量礼包',
	    'infinity' => '统一礼包码',
	);
	public $_desc = array(
	    'limited' => '每人限领1个CDKEY！',
	    'infinity' => '礼包数量无限，每人限领1个CDKEY！',
	);
	
	public function getTableLabel()
	{
		return '渠道游戏礼包';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'gift_id' => '礼包ID',
			'game_id' => '游戏ID',
		    'game_name' => '游戏名称',
		    'name' => '礼包标题',
		    'type' => function(&$row){
		        if( empty($row) ) return '礼包类型';
		        return $this->_types[$row['type']];
		    },
		    'nums' =>  function(&$row){
                if( empty($row) ) return '可领数量';
                if($row['type']=='limited'){
                    return 20;
                }else{
		            return '不限';
                }
            },
		    'add_time' => '发布时间',
		    'used' => function(&$row){
			    $m_tggifbag=new TggiftbagModel();
			   $tggifbag=$m_tggifbag->fetch(['tg_channel'=>$_SESSION['admin_id'],'giftbag_id'=>$row['gift_id']],'id');
			    if($tggifbag['id']){
                        return '已领取';
                }elseif(($row['nums']-$row['used'])<50 && $row['type']=='limited'){
//			            return $row['nums']-$row['used'];
			            return '礼包码不足';
                }else{
			            return '未领取';
                }
		    },
		);
	}
	
	public function getFieldsPadding()
	{
	    return array(
	        function(&$row){
	            if( empty($row) ) return '查看礼包码';
	            return "<a href=\"javascript:void(0);\" class='open_detail' data-id=\"{$row['gift_id']}\" >查看</a>";
	        },
	        function(&$row){
	            if( empty($row) ) return '领取';
                $m_tggifbag=new TggiftbagModel();
                $tggifbag=$m_tggifbag->fetch(['tg_channel'=>$_SESSION['admin_id'],'giftbag_id'=>$row['gift_id']],'id');
                if($tggifbag['id']){
                    return '';
                }elseif(($row['nums']-$row['used'])<50 && $row['type']=='limited'){
                    return '';
                }else{
                    return "<a href=\"/admin/giftbag/get?gift_id={$row['gift_id']}\">领取</a>";
                }
	        },
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'gift_id' => array('礼包ID', 'input', null, ''),
	        'game_id' => array('游戏ID', 'input', null, ''),
	        'game_name' => array('游戏名字', 'input', null, ''),
	    );
	}
}
