<?php

class GiftbagModel extends F_Model_Pdo
{
	protected $_table = 'giftbag';
	protected $_primary='gift_id';
	
	public $_types = array(
	    'limited' => '限量礼包',
	    'onlyone' => '限领礼包',
	    'infinity' => '无限礼包',
	);
	public $_desc = array(
	    'limited' => '每人限领1个CDKEY！',
	    'onlyone' => '每人限领1个CDKEY！',
	    'infinity' => '礼包数量无限，每人限领1个CDKEY！',
	);
	
	public function getTableLabel()
	{
		return '游戏礼包';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'gift_id' => '礼包ID',
		    'name' => '礼包名称',
		    'game_name' => '游戏名称',
			'server_name' => '区/服名称',
		    'type' => function(&$row){
		        if( empty($row) ) return '礼包类型';
		        return $this->_types[$row['type']];
		    },
		    'nums' => '生成数量',
		    'used' => '领取数量',
		    'add_time' => '生成时间',
		    'begin_time' => function(&$row){
		        if( empty($row) ) return '有效期开始时间';
		        return $row['begin_time'] ? date('Y-m-d H:i:s', $row['begin_time']) : '立即';
		    },
		    'end_time' => function(&$row){
		        if( empty($row) ) return '有效期结束时间';
		        return $row['end_time'] ? date('Y-m-d H:i:s', $row['end_time']) : '永不';
		    },
		);
	}
	
	public function getFieldsPadding()
	{
	    return array(
	        function(&$row){
	            if( empty($row) ) return '兑换码列表';
	            return "<a href=\"/admin/giftbagcdkey/list?search[gift_id]={$row['gift_id']}\">查看</a>";
	        },
	        function(&$row){
	            if( empty($row) ) return '导出兑换码';
	            return "<a href=\"/admin/giftbagcdkey/export?gift_id={$row['gift_id']}\">导出</a>";
	        },
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'gift_id' => array('礼包ID', 'input', null, ''),
	        'game_id' => array('游戏ID', 'input', null, ''),
	        'server_id' => array('区/服ID', 'input', null, ''),
	    );
	}
	
	/**
	 * 获取一个礼包的详细信息
	 */
	public function get($gift_id)
	{
	    $gift = $this->fetch("gift_id='{$gift_id}'");
	    
	    if( $gift['content'] ) {
	        $gift['content'] = unserialize($gift['content']);
	    } else {
	        $gift['content'] = array();
	    }
	    $gift['rule'] = $this->_desc[$gift['type']];
	    if( $gift['type'] == 'limited' ) {
	        $gift['rule'] = str_replace('{nums}', $gift['nums'], $gift['rule']);
	    }
	    return $gift;
	}
	
	/**
	 * 获取用户的礼包领取记录
	 * 不判断礼包的类型，始终按多个礼包的格式返回二维数组
	 * 
	 * @param int $uid
	 * @param int $gid
	 * @return array
	 */
	public function userGiftLog($uid, $gid)
	{
	    $pdo = $this->getPdo();
	    $stm = $pdo->query("SELECT * FROM user_cdkey WHERE user_id='{$uid}' AND gift_id='{$gid}'");
	    $logs = array();
	    $row = $stm->fetch(PDO::FETCH_ASSOC);
	    while ($row)
	    {
	        $logs[] = $row;
	        $row = $stm->fetch(PDO::FETCH_ASSOC);
	    }
	    return $logs;
	}
	
	/**
	 * 发放礼包
	 * 
	 * @param int $uid
	 * @param int $gid
	 * @param string &$cdkey
	 * @return string $error
	 */
	public function sendout($uid, $gid, &$cdkey)
	{
	    $gift = $this->fetch("gift_id='$gid'", 'name,type,nums,used,begin_time,end_time');
	    if( empty($gift) ) {
	        return '礼包不存在！';
	    }
	    
	    $time = time();
	    if( $gift['begin_time'] > 0 && $gift['begin_time'] > $time ) {
	        return '活动还未开始！';
	    }
	    if( $gift['end_time'] > 0 && $gift['end_time'] < $time  ) {
	        return '活动已经结束，下次要快哦！';
	    }
	    
	    if( $gift['type'] == 'limited' && $gift['used'] >= $gift['nums'] ) {
	        return '激活码已被领完！';
	    }
	    
	    $pdo = $this->getPdo();
	    if( $gift['type'] != 'infinity' ) {
	        $stm = $pdo->query("SELECT log_id FROM user_cdkey WHERE user_id='{$uid}' AND gift_id='{$gid}' LIMIT 1");
	        $log = $stm->fetch(PDO::FETCH_ASSOC);
	        if( $log ) {
	            return '这个礼包你已经领取过了！';
	        }
	    }
	    
//	    if( $gift['type'] == 'limited' ) {
	        $stm = $pdo->query("SELECT cdkey FROM giftbag_cdkey WHERE gift_id='{$gid}' AND user_id=0  AND get_time=0 LIMIT 1");
	        $tmp = $stm->fetch(PDO::FETCH_ASSOC);
	        if( empty($tmp) ) {
	            return '激活码已全部送出！';
	        }
	        
	        $cdkey = $tmp['cdkey'];
	        if($gift['type']=='infinity'){
                $rs=1;
            }else{
                $rs = $pdo->exec("UPDATE giftbag_cdkey SET user_id='{$uid}',get_time='{$time}' WHERE cdkey='{$cdkey}'");
            }
	        if( $rs != 1 ) {
	            return '激活码领取失败，请重试！';
	        }
	        
	        $pdo->exec("UPDATE giftbag SET used=used+1 WHERE gift_id='{$gid}'");
	        $pdo->exec("INSERT INTO user_cdkey(user_id,gift_id,gift_name,cdkey,get_time) VALUES($uid,$gid,'{$gift['name']}','{$cdkey}',$time)");
//	    } else {
//	        $m_cdkey = new GiftbagcdkeyModel();
//	        $cdkey = $m_cdkey->createCdkey();
//
//	        $rs = $m_cdkey->insert(array(
//	            'cdkey' => $cdkey,
//	            'gift_id' => $gid,
//	            'user_id' => $uid,
//	            'get_time' => $time,
//	        ), false);
//	        if( ! $rs ) {
//	            return '激活码领取失败，请重试！';
//	        }
//
//	        $pdo->exec("UPDATE giftbag SET nums=nums+1,used=used+1 WHERE gift_id='{$gid}'");
//	        $pdo->exec("INSERT INTO user_cdkey(user_id,gift_id,gift_name,cdkey,get_time) VALUES($uid,$gid,'{$gift['name']}','{$cdkey}',$time)");
//	    }
	    
	    return '';
	}
}
