<?php

class IndexController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{
		$m_ad = new AdposModel();
		$assign['banner'] = $m_ad->getByCode('index_banner', 5);
		$assign['cases'] = $m_ad->getByCode('index_cases', 7);
		$assign['games'] = $m_ad->getByCode('index_games', 24);
		
		$this->getView()->assign($assign);
	}
}
