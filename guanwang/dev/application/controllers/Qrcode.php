<?php

class QrcodeController extends Yaf_Controller_Abstract
{
    //生成游戏登录地址等的二维码，便于手机扫描
	public function gameAction()
	{
	    $req = $this->getRequest();
	    $addr = $req->get('addr', '');
	    if( empty($addr) ) exit;
	    
	    if( strpos($addr, 'http') === false ) {
	        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	        $addr = "http://{$domain}{$addr}";
	    }
	    
	    include APPLICATION_PATH.'/application/library/phpqrcode/phpqrcode.php';
	    QRcode::png($addr);
	    exit;
	}
}
