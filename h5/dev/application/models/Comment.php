<?php

class CommentModel extends F_Model_Pdo
{
	protected $_table = 'commont';
	protected $_primary='comm_id';
	public function getFieldsLabel()
	{
		return array(
			'comm_id' => 'ID',
			'user_id' => '游戏名称',
		    'parent_id' => '上级id',
		    'game_id' => '游戏id',
		    'comm_cont' => '评论',
		    'comm_time' => '时间',
		    'like' => '赞',
		);
	}
    /**
     * 获取玩家的礼包记录
     *
     * @param int $uid
     * @param int $pn
     * @param int $limit
     * @return array
     */
    public function getComment($game_id, $pn = 1, $limit = 6)
    {
        $offset = ($pn - 1) * $limit;
        $pdo = $this->getPdo();
        $stm = $pdo->query("SELECT * FROM comment WHERE game_id='{$game_id}' LIMIT {$offset},{$limit}");
        $logs = array();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        while ($row)
        {
            $logs[] = $row;
            $row = $stm->fetch(PDO::FETCH_ASSOC);
        }
        return $logs;
    }
}
