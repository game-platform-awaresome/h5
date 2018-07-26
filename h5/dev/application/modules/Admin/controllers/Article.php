<?php

class ArticleController extends F_Controller_Backend
{
    public function init()
    {
        parent::init();
        $this->_view->assign('types', $this->_model->_types);
    }
    
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['orderby'] = 'weight ASC,article_id DESC';
        return $params;
    }
    
    protected function beforeEdit(&$info)
    {
        $m_game = new GameModel();
        $games = $m_game->fetchAll('', 1, 500, 'game_id,name', 'weight DESC');
        $this->_view->assign('games', $games);
    }
    
    protected function beforeUpdate($id, &$info)
    {
        $info['up_time'] = time();
        if( empty($id) ) {
            $info['add_time'] = $info['up_time'];
        }
    }
    
    protected function afterUpdate($id, &$info)
    {
        if( $_FILES['cover']['size'] > 0 && $_FILES['cover']['error'] == 0 ) {
            $img = getimagesize($_FILES['cover']['tmp_name']);
            if( ! $img ) {
                return '上传文件不是有效的图片文件！';
            }
            $ext = '';
            switch ($img[2])
            {
                case 1: $ext = 'gif'; break;
                case 2: $ext = 'jpg'; break;
                case 3: $ext = 'png'; break;
                default: return '不支持的图片文件格式！';
            }
            
            if( $info['cover'] ) {
                $info['cover'] = explode('?', $info['cover']);
                $info['cover'] = $info['cover'][0];
                $file = APPLICATION_PATH.'/public'.$info['cover'];
                if( file_exists($file) ) {
                    unlink($file);
                }
            }
            
            $path = '/game/article/';
            $path .= "{$id}.{$ext}";
            $dst = APPLICATION_PATH.'/public'.$path;
            $rs = move_uploaded_file($_FILES['cover']['tmp_name'], $dst);
            if( ! $rs ) {
                return '不是有效的上传文件，请重新上传！';
            }
            $path .= '?'.time();
            $data['cover'] = $path;
            
            $primary = $this->_model->getPrimary();
            $this->_model->update($data, array($primary=>$id));
        }
    }
    
    protected function beforeDelete($id)
    {
        $conds = "article_id='{$id}'";
        $item = $this->_model->fetch($conds, 'cover,content');
        if( empty($item) ) {
            return '咨询不存在，可能已经被删除了。';
        }
        if( $item['cover'] ) {
            $item['cover'] = explode('?', $item['cover']);
            $item['cover'] = $item['cover'][0];
            $file = APPLICATION_PATH."/public{$item['cover']}";
            $rs = @unlink($file);
            if( ! $rs ) {
                return '删除咨询封面时发生错误！';
            }
        }
        
        if( $item['content'] ) {
            $times = preg_match_all('#<img.*?src=\"([^\"]+)\"#i', $item['content'], $matches);
            if( $times ) {
                foreach ($matches[1] as $file)
                {
                    $file = APPLICATION_PATH."/public{$file}";
                    @unlink($file);
                }
            }
        }
        return '';
    }
}
