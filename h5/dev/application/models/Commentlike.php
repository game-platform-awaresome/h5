<?php

class CommentlikeModel extends F_Model_Pdo
{
	protected $_table = 'comment_like';
	protected $_primary='id';
//    public function __construct()
//    {
//        parent::__construct('h5_open');
//    }
	public function getFieldsLabel()
	{
		return array(
			'id' => 'ID',
			'comm_id' => '评论ID',
			'user_id' => '用户id',
		);
	}
}
