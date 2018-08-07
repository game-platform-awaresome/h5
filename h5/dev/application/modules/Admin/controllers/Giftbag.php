<?php

class GiftbagController extends F_Controller_Backend
{
    private $_file_data;
    
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['orderby'] = 'weight ASC';
        return $params;
    }
    
    protected function beforeEdit(&$info)
    {
        $this->_view->assign('types', $this->_model->_types);
        $this->_view->assign('desc', json_encode($this->_model->_desc));
        
        $m_game = new GameModel();
        $games = $m_game->fetchAll('', 1, 500, 'game_id,name', 'weight ASC');
        $this->_view->assign('games', $games);
        
        if( $info && $info['content'] ) {
            $info['content'] = unserialize($info['content']);
        }
    }
    
    public function serverAction()
    {
        $game_id = $this->getRequest()->get('game_id', 0);
        if( empty($game_id) ) {
            exit('[]');
        }
        
        $m_server = new ServerModel();
        $server = $m_server->fetchAll(array('game_id'=>$game_id), 1, 100, 'server_id,name', 'weight ASC');
        exit(json_encode($server));
    }
    
    protected function beforeUpdate($id, &$info)
    {
        $req = $this->getRequest();
        if( empty($id) && $info['nums'] > 0 ) {
            set_time_limit(600);
        }
        if( $id ) {
//            unset($info['type'], $info['nums']);
            unset($info['type']);
        }
        
        //判断文件是否被导入过
        if( isset($_FILES['cdkey_file']) && $_FILES['cdkey_file']['size'] > 0 && $_FILES['cdkey_file']['error'] == 0 ) {
            $this->_file_data = file($_FILES['cdkey_file']['tmp_name']);
            
            foreach ($this->_file_data as $cdkey)
            {
                if( trim($cdkey) != '' ) {
                    $cdkey = trim($cdkey);
                    break;
                }
            }
            
            $m_cdkey = new GiftbagcdkeyModel();
            $has = $m_cdkey->fetch("cdkey='{$cdkey}'");
            if( $has ) {
                return '此文件已经导入了，请不要重复导入！';
            }
        }
        
        $content = array();
        foreach ($_POST['content'] as $item)
        {
            if( trim($item['name']) != '' ) {
                $content[] = $item;
            }
        }
        $info['content'] = serialize($content);
        $info['begin_time'] = strtotime($info['begin_time']);
        $info['end_time'] = strtotime($info['end_time']);
    }
    
    protected function afterUpdate($id, &$info)
    {
        if( $this->_file_data ) {
            $m_cdkey = new GiftbagcdkeyModel();
            $nums = 0;
            foreach ($this->_file_data as $cdkey)
            {
                $cdkey = trim($cdkey);
                if( empty($cdkey) ) {
                    continue;
                }
                $ins_id = $m_cdkey->insert(array(
                    'cdkey' => $cdkey,
                    'gift_id' => $id,
                    'user_id' => '0',
                ), false);
                if( $ins_id ) {
                    ++$nums;
                }
            }
            
            $gift = $this->_model->fetch("gift_id='{$id}'", 'nums');
            
            $this->_model->update(array(
//                'type' => 'limited',
//                'nums' => intval($gift['nums']) + $nums,
            ), "gift_id='{$id}'");
            
            return ;
        }
        
        if( empty($info['nums']) ) {
            return ;
        }
        
        $m_cdkey = new GiftbagcdkeyModel();
        for ($nums = $info['nums']; $nums > 0; --$nums)
        {
            $ins_id = $m_cdkey->insert(array(
                'cdkey' => $m_cdkey->createCdkey(),
                'gift_id' => $id,
                'user_id' => '0',
            ), false);
            if( ! $ins_id ) {
                ++$nums;
                usleep(1000);
            }
        }
    }
    
    public function beforeDelete($id)
    {
        $m_cdkey = new GiftbagcdkeyModel();
        $rs = $m_cdkey->delete("gift_id='{$id}'");
        if( $rs ) {
            return '';
        } else {
            return '礼包兑换码删除失败，请重试！';
        }
    }
}
