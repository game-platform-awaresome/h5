<?php

class ServerController extends F_Controller_Backend
{
    public function init()
    {
        parent::init();
        $this->_view->assign('labels', $this->_model->_labels);
        $this->_view->assign('corner', $this->_model->_corner);
        $this->_view->assign('channels', $this->_model->_channels);
        $this->_view->assign('load_types', $this->_model->_load_types);
    }
    
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['orderby'] = 'weight DESC,server_id DESC';
        return $params;
    }
    
    protected function beforeEdit(&$info)
    {
        if( $info && $info['game_id'] ) {
            $game_id = $info['game_id'];
        } else {
            $search = $this->getRequest()->getQuery('search', array());
            $game_id = empty($search['game_id']) ? 0 : $search['game_id'];
        }
        
        $m_game = new GameModel();
        $types = $m_game->_types;
        $games = '{';
        $comma = '';
        $game_type = '';
        foreach ($types as $tp)
        {
            $games .= "{$comma}\"{$tp}\":{";
            $cond = "type='{$tp}'";
            $rows = $m_game->fetchAll($cond, 1, 100, 'game_id,name', 'weight ASC');
            $comma = '';
            foreach ($rows as $item)
            {
                $games .= "{$comma}\"{$item['game_id']}\":\"{$item['name']}\"";
                $comma = ',';
                if( $item['game_id'] == $game_id ) {
                    $game_type = $tp;
                }
            }
            $games .= '}';
            $comma = ',';
        }
        $games .= '}';
        if( $game_type == '' ) {
            $game_type = $types[0];
        }
        
        $this->_view->assign('types', $types);
        $this->_view->assign('games', $games);
        $this->_view->assign('game_id', $game_id);
        $this->_view->assign('game_type', $game_type);
    }
}
