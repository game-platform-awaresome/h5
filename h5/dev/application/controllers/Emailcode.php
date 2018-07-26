<?php
//邮箱验证码
class EmailcodeController extends Yaf_Controller_Abstract
{
    private $email;
    private $xcode;
    
    public function init()
    {
        $req = $this->getRequest();
        $this->xcode = $req->getPost('xcode', '');
        $this->email = $req->getPost('email', '');
        $ip = ip2long($_SERVER['REMOTE_ADDR']);
        
        if( $this->xcode == '' ) exit;
        if( $ip === false ) exit;
        
        $this->email = substr($this->email, 0, 32);
        if( ! preg_match('/^[\w\-\.]+@[\w\-]+(\.\w+)+$/', $this->email) ) exit;
        
        $ic = new F_Helper_ImgCode();
        if( ! $ic->check($this->xcode) ) {
            $json['msg'] = '图片验证码不正确，请重新输入！';
            $json['xcode'] = 'refresh';
            exit(json_encode($json));
        }
    }
    
    /*
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
	*/
	
	//用户找回密码
	public function forgetAction()
	{
	    $json = array(
	        'msg' => 'success',
	        'xcode' => '',
	    );
	    
	    $m_user = new UsersModel();
	    $has = $m_user->fetch("username='{$this->email}'", 'user_id');
	    if( empty($has) ) {
	        $json['msg'] = '您输入的账号不存在，请核对！';
	        exit(json_encode($json));
	    }
	    
	    $subject = "【久乐游戏】密码找回";
	    $message = '亲爱的久乐用户，您刚申请了密码找回验证，验证码为：{xcode}。请尽快使用该验证码进行密码重置。
<br><br>小纳温馨提示：如非本人操作，请修改您的密码，并关注账号安全。被骗于亲信，被盗于轻心。时刻关注账号安全、防范于未然。';
	    $m_xc_em = new XcodeEmailModel();
	    $em_code = $m_xc_em->send($this->email, $subject, $message);
	    if( is_string($em_code) ) {
	        $json['msg'] = $em_code;
	        $json['xcode'] = 'refresh';
	    }
	    exit(json_encode($json));
	}
}
