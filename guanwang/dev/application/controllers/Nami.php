<?php

class NamiController extends Yaf_Controller_Abstract
{
	public function contactusAction()
	{
		$m_ad = new AdposModel();
		$assign['banner'] = $m_ad->getByCode('nami_contact_banner', 1);
		
		$this->getView()->assign($assign);
	}
}
