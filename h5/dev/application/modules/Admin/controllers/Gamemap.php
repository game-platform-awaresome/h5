<?php

class GamemapController extends F_Controller_Backend
{
    protected function beforeEdit(&$info)
    {
        $this->_view->assign('channels', $this->_model->_channels);
        
        $m_game = new GameModel();
        $games = $m_game->fetchAll('', 1, 500, 'game_id,name', 'weight ASC');
        $this->_view->assign('games', $games);
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
}
