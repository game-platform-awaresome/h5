<?php

class IndexController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{
		$this->forward('game', 'index');
		return false;
	}
}
