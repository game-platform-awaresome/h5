<?php

class CommentController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['conditions'] = 'is_check=0';
        $params['orderby'] = 'comm_time DESC';
        return $params;
    }
    public function editAction(){
        Yaf_Dispatcher::getInstance()->disableView();
        $id=$_GET['comm_id'];
        $m_comment=new CommentModel();
        $m_comment->update(['is_check'=>1],['comm_id'=>$id]);
        $this->redirect("/admin/{$this->_ctl}/list?{$this->_query}");
    }
}
