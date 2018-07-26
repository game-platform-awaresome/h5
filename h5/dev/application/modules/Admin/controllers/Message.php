<?php
/**
 * @name MessageController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 */
class MessageController extends Yaf_Controller_Abstract
{
    
    public function init()
    {
        $v = $this->getView();
        $req = $this->getRequest();
        
        $v->assign('msg', $req->getParam('msg', ''));
        $v->assign('url', $req->getParam('url', '-1'));
        $v->assign('time', $req->getParam('time', 2));
    }
    
	public function successAction()
	{
	    
	}
	
	public function warningAction()
	{
	    
	}
	
	public function errorAction()
	{
	    
	}
}
