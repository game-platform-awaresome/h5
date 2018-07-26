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
    
    protected function beforeUpdate($id, &$info)
    {
        $info['up_time'] = time();
        if( empty($id) ) {
            $info['add_time'] = $info['up_time'];
        }
    }
    
    protected function beforeDelete($id)
    {
        $conds = "article_id='{$id}'";
        $item = $this->_model->fetch($conds, 'cover,content');
        if( empty($item) ) {
            return '咨询不存在，可能已经被删除了。';
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
    
    public function cacheAction()
    {
        $arr = array();
        foreach ($this->_model->_types as $tp)
        {
            $arr[$tp] = $this->_model->fetchAll("type='{$tp}' AND visible=1", 1, 20, 'article_id,title', 'weight ASC,article_id ASC');
        }
        $data = '<?php';
        $data .= "\r\n\r\nreturn ";
        $data .= var_export($arr, true);
        $data .= ";\r\n";
        
        $file = APPLICATION_PATH.'/application/cache/article/catalog.php';
        file_put_contents($file, $data);
        
        $this->forward('admin', 'message', 'success', array('msg'=>'缓存已更新！'));
        return false;
    }
}
