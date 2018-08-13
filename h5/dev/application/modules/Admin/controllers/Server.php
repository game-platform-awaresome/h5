<?php

class ServerController extends F_Controller_Backend
{
    public function init()
    {
        parent::init();
//        $this->_view->assign('labels', $this->_model->_labels);
//        $this->_view->assign('corner', $this->_model->_corner);
//        $this->_view->assign('channels', $this->_model->_channels);
//        $this->_view->assign('load_types', $this->_model->_load_types);
    }
    
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['orderby'] = 'start_time ASC';
        $params['op'] = F_Helper_Html::Op_Delete;
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

    /**
     * 导入区服
     */
    public function importAction(){
        Yaf_Dispatcher::getInstance()->autoRender(FALSE);
        if($_FILES){
            $csv=new F_Helper_Csv();
            $handle = fopen($_FILES['file']['tmp_name'], 'r');
            $data=$csv->input_csv($handle);
            unset($data[0]);
            $gameModel=new GameModel();
//            var_dump($data);die;
            $inserts=array();
            foreach ($data as $key=>$value){
                //1.根据名字查到对应的游戏
                $game=$gameModel->fetch(['name'=>trim($value[0])]);
                if(!$game){
                    throw new Exception("第".$key.'行,游戏名：'.$value[0].'匹配不到对应的游戏!');
                }
                $insert=array();
                $insert['game_id']=$game['game_id'];
                $insert['game_name']=$game['name'];
                $insert['name']=$value[1];
                $insert['add_time']=date('Y-m-d H:i:s');
                $insert['visible']=1;
                $insert['start_time']=$value[3];
                $insert['number']=$value[2];
                $inserts[]=$insert;
            }
            //插入数据
            $server=new ServerModel();
            foreach ($inserts as $key=>$value){
                $server->insert($value);
            }
            echo '导入成功!';
        }
    }
}
