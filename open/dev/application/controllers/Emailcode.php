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
	
	//用户找回密码
	public function forgetAction()
	{
	    $json = array(
	        'msg' => 'success',
	        'xcode' => '',
	    );
	    
	    $m_dev = new DeveloperModel();
	    $has = $m_dev->fetch("username='{$this->email}'", 'dev_id');
	    if( empty($has) ) {
	        $json['msg'] = '您输入的登录邮箱不存在，请核对！';
	        $json['xcode'] = 'refresh';
	        exit(json_encode($json));
	    }
	    
	    $domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	    $url = "http://{$domain}/password/reset.html?email={$this->email}&xcode={xcode}";
	    $subject = "【久乐游戏开发者】密码找回";
	    $message = '<div>亲爱的久乐游戏开发者，您刚申请了密码找回验证，点击下面的链接重置您的密码，如果不能点击请将链接复制到浏览器中打开。</div>';
	    $message .= "<div><a target=\"_blank\" href=\"{$url}\">{$url}</a></div>";
	    $message .= '<div>&nbsp;</div><div>&nbsp;</div>';
	    $message .= '<div>小纳温馨提示：如非本人操作，请修改您的密码，并关注账号安全。被骗于亲信，被盗于轻心。时刻关注账号安全、防范于未然。</div>';
	    $m_xc_em = new XcodeEmailModel();
	    $em_code = $m_xc_em->send($this->email, $subject, $message);
	    if( preg_match('/[^0-9a-f]+/', $em_code) ) {
	        $json['msg'] = $em_code;
	        $json['xcode'] = 'refresh';
	    }
	    exit(json_encode($json));
	}
}
