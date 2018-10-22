<?php

class CommentModel extends F_Model_Pdo
{
	protected $_table = 'comment';
	protected $_primary='comm_id';
//    public function __construct()
//    {
//        parent::__construct('h5_open');
//    }
	public function getFieldsLabel()
	{
		return array(
			'comm_id' => 'ID',
			'user_id' => '游戏名称',
		    'parent_id' => '上级id',
		    'game_id' => '游戏id',
		    'comm_cont' => '评论',
		    'comm_time' => '时间',
		    'like_num' => '赞',
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
        $stm = $pdo->query("SELECT * FROM comment WHERE game_id='{$game_id}' and parent_id = 0 LIMIT {$offset},{$limit}");
        $logs = array();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        while ($row)
        {
            $logs[] = $row;
            $row = $stm->fetch(PDO::FETCH_ASSOC);
        }
        foreach ($logs as $key =>&$value){
            $value['children']=$this->fetchAll(['parent_id'=>$value['comm_id']]);
        }
        return $logs;
    }
}
