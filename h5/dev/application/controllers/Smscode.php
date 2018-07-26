<?php
//短信验证码
class SmscodeController extends Yaf_Controller_Abstract
{
    private $mobile;
    private $xcode;
    private $ip;
    
    public function init()
    {
        $req = $this->getRequest();
        $this->xcode = $req->getPost('xcode', '');
        $this->mobile = $req->getPost('mobile', '');
        $this->ip = ip2long($_SERVER['REMOTE_ADDR']);
        
        if( $this->xcode == '' ) exit;
        if( $this->ip === false ) exit;
        
        $this->mobile = substr($this->mobile, 0, 11);
        if( ! preg_match('/^(?:13[0-9]|14[57]|15[0-9]|17[0678]|18[0-9])\d{8}$/', $this->mobile) ) exit;
        
        $ic = new F_Helper_ImgCode();
        if( ! $ic->check($this->xcode) ) {
            $json['msg'] = '图片验证码不正确，请重新输入！';
            $json['xcode'] = 'refresh';
            exit(json_encode($json));
        }
    }
    
    //用户注册验证码
	public function registerAction()
	{
	    $json = array(
	        'msg' => 'success',
	        'xcode' => '',
	    );
		
		$message = '您正通过手机号注册【久乐游戏】，短信验证码：{xcode}，请勿告知他人。';
		$m_sms_m = new SmscodeMobileModel();
		$sms_code = $m_sms_m->send($this->mobile, $this->ip, $message);
		if( is_string($sms_code) ) {
		    $json['msg'] = $sms_code;
		    $json['xcode'] = 'refresh';
		}
		exit(json_encode($json));
	}
	
	//用户找回密码
	public function forgetAction()
	{
	    $json = array(
	        'msg' => 'success',
	        'xcode' => '',
	    );
	    
	    $m_user = new UsersModel();
	    $has = $m_user->fetch("username='{$this->mobile}'", 'user_id');
	    if( empty($has) ) {
	        $json['msg'] = '您输入的账号不存在，请核对！';
	        exit(json_encode($json));
	    }
	    
	    $message = '【久乐游戏】您找回密码的验证码为：{xcode}，祝您游戏愉快。';
	    $m_sms_m = new SmscodeMobileModel();
	    $sms_code = $m_sms_m->send($this->mobile, $this->ip, $message);
	    if( is_string($sms_code) ) {
	        $json['msg'] = $sms_code;
	        $json['xcode'] = 'refresh';
	    }
	    exit(json_encode($json));
	}
}
