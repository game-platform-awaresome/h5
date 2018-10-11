<?php

class GameroleModel extends F_Model_Pdo
{
	protected $_table = 'game_role';
	protected $_primary = 'id';
    public function getTableLabel()
    {
        return '角色信息';
    }
    public function getFieldsLabel()
    {
        return array(
            'game_id' => '游戏id',
            'server_id' => '区服id',
            'role_id' => '角色id',
            'role_name' => '角色名称',
            'user_id' => '用户id',
            'username' => '用戶名',
            'tg_channel' => '渠道id',
        );
    }
    public function getFieldsSearch()
    {
        return array(
            'role_id' => array('角色id	', 'input', null, ''),
            'role_name' => array('角色名称', 'input', null, ''),
            'user_id' => array('用户id', 'input', null, ''),
            'username' => array('用户名称', 'input', null, ''),
        );
    }
}
