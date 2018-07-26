<?php

class DocumentsController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{
		$m_ad = new AdposModel();
		$assign['banner'] = $m_ad->getByCode('doc_banner', 1);
		
		$catalog = include APPLICATION_PATH.'/application/cache/article/catalog.php';
		$first = null;
		$assign['index_1'] = 0;
		$assign['index_2'] = 0;
		$req = $this->getRequest();
		$id = $req->get('id', 0);
		$idx_1 = 0;
		foreach ($catalog as $tp=>&$row)
		{
		    foreach ($row as $idx_2=>&$tmp)
		    {
		        if( $first == null ) {
		            $first = $tmp['article_id'];
		            $assign['index_1'] = $idx_1;
		            $assign['index_2'] = $idx_2;
		        }
		        if( $id == 0 ) {
    		        break;
		        }
		        if( $id == $tmp['article_id'] ) {
		            $first = $id;
		            $assign['index_1'] = $idx_1;
		            $assign['index_2'] = $idx_2;
		            break;
		        }
		    }
		    ++$idx_1;
		}
		$assign['catalog'] = $catalog;
		
		if( $first ) {
		    $m_article = new ArticleModel();
		    $assign['first'] = $m_article->fetch("article_id='{$first}'", 'article_id,title,content,up_time');
		} else {
		    $assign['first'] = $first;
		}
		
		$this->getView()->assign($assign);
	}
	
	public function detailsAction()
	{
	    $req = $this->getRequest();
	    if( ! $req->isXmlHttpRequest() ) exit;
	    
	    $id = $req->get('id', 0);
	    if( $id < 1 ) exit;
	    
	    $m_article = new ArticleModel();
	    $assign['article'] = $m_article->fetch("article_id='{$id}'", 'article_id,title,content,up_time');
	    
	    $this->getView()->assign($assign);
	}
}
