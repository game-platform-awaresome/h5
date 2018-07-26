<?php
/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @see http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author pc201303241817\administrator
 */
class ErrorController extends Yaf_Controller_Abstract
{
    public function init()
    {
        Yaf_Registry::set('layout', false);
    }

	//从2.1开始, errorAction支持直接通过参数获取异常
	public function errorAction($exception) {
	    if( $_SERVER['REMOTE_ADDR'] != '127.0.0.1' ) {
	        //header('HTTP/1.1 404 Not Found');
            //header("status: 404 Not Found");
	        $this->getView()->assign('sitename', Yaf_Registry::get('config')->application->sitename);
	        return true;
	    }
	    
	    exit($exception->getMessage());
	    
		//$this->_view->setScriptPath(APPLICATION_PATH.'/application/views/');
		//1. assign to view engine
		//$this->getView()->assign("exception", $exception);
		//5. render by Yaf 
	}
}
