<?php

class DevgamesModel extends F_Model_Pdo
{
	protected $_table = 'dev_games';
	protected $_primary = 'game_id';
	
	public $_status = array(
	    0 => '新建游戏',
	    1 => '基础信息',
	    2 => '素材信息',
	    3 => 'API信息',
	    4 => '提交审核',
	    5 => '审核未通过',
	    6 => '审核通过',
	    9 => '上线运营',
	);
	public $_op_stt = array(
	    4 => '提交审核',
	    5 => '审核未通过',
	    6 => '审核通过',
	    9 => '上线运营',
	);
	
	public function getTableLabel()
	{
		return '开发者游戏';
	}
	
	public function getFieldsLabel()
	{
	    return array(
	        'dev_id' => '开发者ID',
	        'game_id' => '游戏ID',
	        'name' => '游戏名称',
	        'servers' => '区服数量',
	        'status' => function(&$row){
	            if( empty($row) ) return '状态';
	            return $this->_status[$row['status']];
	        },
	        'message' => '信息',
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'dev_id' => array('开发者ID', 'input', null, ''),
	        'game_id' => array('游戏ID', 'input', null, ''),
	        'status' => array('状态筛选', 'select', $this->_op_stt, ''),
	    );
	}
	
	/**
	 * 根据游戏状态获取游戏列表
	 * 
	 * @param int $dev_id
	 * @param int $status
	 * @param int $pn
	 * @param int $limit
	 * @return array
	 */
	public function getGamesByStatus($dev_id, $status, $pn = 1, $limit = 9)
	{
	    $conds = 'dev_id='.$dev_id;
	    switch ($status)
	    {
	        case 4: $conds .= ' AND status=4'; break;
	        case 9: $conds .= ' AND status=9'; break;
	        default: $conds .= ' AND status<9'; break;
	    }
	    $conds .= ' ORDER BY game_id DESC';
	    $offset = ($pn - 1) * $limit;
	    $conds .= " LIMIT {$offset},{$limit}";
	    $gids = $this->fetch($conds, 'GROUP_CONCAT(game_id) AS gids');
	    $gids = "game_id IN({$gids['gids']})";
	    
	    $m_game = F_Model_Pdo::getInstance('Game');
	    return $m_game->fetchAll($gids, $pn, $limit, 'game_id,name,classic,logo,IF(recharge_url,0,1) AS isfree', 'game_id DESC');
	}
}
