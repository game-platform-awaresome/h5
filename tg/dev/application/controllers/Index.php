<?php

class IndexController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{
		$url='http://'.$_SERVER['SERVER_NAME'].'/admin/index/index';
        $this->redirect($url);
	}
}
