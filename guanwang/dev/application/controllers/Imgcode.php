<?php

class ImgcodeController extends Yaf_Controller_Abstract
{
    public function init()
    {
        Yaf_Registry::set('layout', false);
    }
    
    //用户模块验证码
	public function developerAction()
	{
		$ic = new F_Helper_ImgCode();
		$ic->width = 104;
		$ic->height = 34;
		$ic->w_pad = 12;
		$ic->h_pad = 6;
		$ic->bg_color = 'f9f7f8';
		$ic->ft_color = '466f09';
		$ic->ft_size = 20;
		$ic->create();
		return false;
	}
}
