<?php
/**
 * @name Bootstrap
 * @author pc201303241817\administrator
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract {

	private $_config;
	
    public function _initConfig() {
		//把配置保存起来
		$this->_config = Yaf_Application::app()->getConfig();
		Yaf_Registry::set('config', $this->_config);
		
		date_default_timezone_set($this->_config['timezone']);
		
		Url::$suffix = $this->_config['application']['url_suffix'];
		
		$sess = Yaf_Session::getInstance();
		$sess->start();
	}
	
	public function _initPlugin(Yaf_Dispatcher $dispatcher) {
		//注册通用插件 
		$cp = new CommonPlugin();
		$dispatcher->registerPlugin($cp);
	}
    /*
    public function _initView(Yaf_Dispatcher $dispatcher){
		//在这里注册自己的view控制器，例如smarty,firekylin
	}
	*/
}
