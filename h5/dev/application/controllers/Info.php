<?php

class InfoController extends Yaf_Controller_Abstract
{
    public function init()
    {
        Yaf_Registry::set('layout', false);
    }
    
	public function indexAction()
	{
	    $assign['type'] = $this->getRequest()->get('type', '');
	    $assign['type'] = preg_replace('/[\'\"\\\]+/', '', $assign['type']);
	    
	    $m_adpos = new AdposModel();
	    $assign['banner'] = $m_adpos->getByCode('article_center_banner', 3);
		
		$this->getView()->assign($assign);
	}
	
	public function listAction()
	{
	    $req = $this->getRequest();
	    if( ! $req->isPost() ) {
	        exit;
	    }
	    
	    $type = $req->getPost('type', '');
	    $type = preg_replace('/[\'\"\\\]+/', '', $type);
	    $pn = $req->getPost('pn', 1);
	    $limit = $req->getPost('limit', 6);
	    
	    $m_article = new ArticleModel();
	    if( $type == '综合' ) {
	        $conds = 'visible=1';
	    } else if( in_array($type, $m_article->_types) ) {
	        $conds = "type='{$type}' AND visible=1";
	    } else {
	        exit;
	    }
        $conds .= " and type!='代理公告'";//过滤公告
        $list = $m_article->fetchAll($conds, $pn, $limit, 'article_id,cover,title,up_time', 'weight ASC,article_id DESC');
	    foreach ($list as &$row)
	    {
	        $row['up_time'] = $m_article->formatTime($row['up_time']);
	    }
	    $v = $this->getView();
	    $v->assign('list', $list);
	    $v->assign('type', $type);
	}
	
	public function detailAction()
	{
	    $req = $this->getRequest();
	    $type = $req->get('type', '');
	    $type = preg_replace('/[\'\"\\\]+/', '', $type);
	    $article_id = $req->get('article_id', 0);
	    if( $article_id < 1 ) {
	        $this->redirect('/info/index.html');
	        return false;
	    }
	    $assign['type'] = $type;
	    
	    $m_article = new ArticleModel();
	    $assign['info'] = $m_article->fetch("article_id='{$article_id}' AND visible=1");
	    if( empty($assign['info']) ) {
	        $this->redirect('/info/index.html');
	        return false;
	    }
	    
	    //$m_adpos = new AdposModel();
	    //$assign['banner'] = $m_adpos->getByCode('article_detail_banner', 1);
	    
	    $this->getView()->assign($assign);
	}
}
