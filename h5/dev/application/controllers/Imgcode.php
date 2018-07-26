<?php

class ImgcodeController extends Yaf_Controller_Abstract
{
    public function init()
    {
        Yaf_Registry::set('layout', false);
    }
    
    //用户模块验证码
	public function userAction()
	{
		$ic = new F_Helper_ImgCode();
		$ic->create();
		return false;
	}
}
