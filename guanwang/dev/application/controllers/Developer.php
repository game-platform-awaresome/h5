<?php

class DeveloperController extends Yaf_Controller_Abstract
{
    //获取跳转地址
    private function getForward($referer = true)
    {
        $fwd = $this->getRequest()->get('fwd', '');
        if( $fwd ) $fwd = urldecode($fwd);
        if( ! $referer ) return $fwd;
        if( empty($fwd) && !empty($_SERVER['HTTP_REFERER']) ) {
            $fwd = $_SERVER['HTTP_REFERER'];
        }
        return $fwd;
    }
    
    public function init()
    {
        $assign['menu_c'] = 'developer';
        $assign['menu_a'] = $this->getRequest()->getActionName();
        $this->getView()->assign($assign);
        Yaf_Registry::set('layout', false);
    }
    
    //格式化菜单，显示游戏数量
    private function formatMenu(&$menu, &$dev)
    {
        $menu['game']['list']['online']['name'] .= "({$dev['on_nums']})";
        $menu['game']['list']['offline']['name'] .= "({$dev['off_nums']})";
        $menu['game']['list']['checking']['name'] .= "({$dev['check_nums']})";
    }
    
    //个人中心主页
    public function indexAction()
    {
        $m_dev = F_Model_Pdo::getInstance('Developer');
	    $login = $m_dev->getLogin();
	    if( empty($login) ) {
	        $this->redirect('/developer/login.html');
	        return false;
	    }
        
	    $assign['dev'] = $m_dev->fetch("dev_id='{$login['dev_id']}'", 'on_nums,off_nums,check_nums,trade_money,status,message');
        $assign['dev'] = array_merge($login, $assign['dev']);
        
        if( $assign['dev']['status'] != 9 && empty($assign['dev']['message']) ) {
            switch ($assign['dev']['status'])
            {
                case 3: $assign['dev']['message'] = '您的账号已被冻结。'; break;
                case 2: $assign['dev']['message'] = '开发者账号审核申请未通过，如有疑问请联系客服帮您处理。'; break;
                default: $assign['dev']['message'] = '请先完善您的资料，审核通过后即可创建游戏，审核工作一般在1-3个工作日内完成。'; break;
            }
        }
        
        $assign['menu'] = $m_dev->_menu;
        $this->formatMenu($assign['menu'], $assign['dev']);
        
        $this->getView()->assign($assign);
    }
    
    //注册
	public function registerAction()
	{
	    $m_dev = new DeveloperModel();
	    $dev = $m_dev->getLogin();
	    if( $dev ) {
	        $this->redirect('/developer/index.html');
	    }
	    
	    $fwd = $this->getForward(false);
	    $assign['fwd'] = $fwd ? urldecode($fwd) : '/developer/index.html';
	    
	    $assign['action'] = 'register';
	    $this->getView()->assign($assign);
	}
	//Ajax注册
	public function ajaxregAction()
	{
	    $json = array('msg'=>'success', 'xcode'=>'', 'fwd'=>'');
	    
	    $m_dev = new DeveloperModel();
	    $dev = $m_dev->getLogin();
	    if( $dev ) {
	        $json['msg'] = '你已登录，无需注册！';
	        $json['fwd'] = '/developer/index.html';
	        exit(json_encode($json));
	    }
	    
	    $req = $this->getRequest();
	    $username = $req->getPost('username', '');
	    $password = $req->getPost('password', '');
	    $re_pwd = $req->getPost('re_pwd', '');
	    $xcode = $req->getPost('xcode', '');
	    
	    $imgcode = new F_Helper_ImgCode();
	    if( ! $imgcode->check($xcode) ) {
	        $json['msg'] = '验证码错误！';
	        $json['xcode'] = 'refresh';
	        exit(json_encode($json));
	    }
	    
	    if( empty($username) || empty($password) ) {
	        $json['msg'] = '账号与密码不能为空！';
	        exit(json_encode($json));
	    }
	    if( ! preg_match('/^[\w\-\.]+@[\w\-]+(\.\w+)+$/', $username) ) {
	        $json['msg'] = '请输入一个邮箱地址作为开发者账号！';
	        exit(json_encode($json));
	    }
	    $pwd_len = strlen($password);
	    if( $pwd_len < 6 || $pwd_len > 16 ) {
	        $json['msg'] = '密码由6-16位字母、数字与字符组成！';
	        exit(json_encode($json));
	    }
	    
	    $uid = $m_dev->register($username, $password, $re_pwd);
	    if( is_string($uid) ) {
	        $json['msg'] = $uid;
	        $json['xcode'] = 'refresh';
	        exit(json_encode($json));
	    }
	    
	    $json['fwd'] = '/developer/index.html';
	    exit(json_encode($json));
	}
	
	//登录界面
	public function loginAction()
	{
	    $m_dev = new DeveloperModel();
	    $dev = $m_dev->getLogin();
	    if( $dev ) {
	        $this->redirect('/developer/index.html');
	    }
	    
	    $assign['err'] = $this->getRequest()->getParam('errmsg', '');
	    $assign['fwd'] = $this->getForward(false);
	    
	    $assign['action'] = 'login';
	    $this->getView()->assign($assign);
	}
	//Ajax登录
	public function ajaxloginAction()
	{
	    $req = $this->getRequest();
	    if( ! $req->isXmlHttpRequest() || ! $req->isPost() ) exit;
	    
	    $json = array('msg'=>'success', 'xcode'=>'false', 'fwd'=>'');
	    
	    $m_dev = new DeveloperModel();
	    $dev = $m_dev->getLogin();
	    if( $dev ) {
	        exit(json_encode($json));
	    }
	    
	    $username = $req->getPost('username', '');
	    $password = $req->getPost('password', '');
	    $xcode = $req->getPost('xcode', '');
	    $username = preg_replace('#[\'\"\%\#\*\?\\\]+#', '', substr($username, 0, 32));
	    if( empty($username) || empty($password) ) {
	        $json['msg'] = '请输入用户名及密码！';
	        exit(json_encode($json));
	    }
	    
	    $imgcode = new F_Helper_ImgCode();
	    if( ! $imgcode->check($xcode) ) {
	        $json['msg'] = '验证码错误！';
	        $json['xcode'] = 'refresh';
	        exit(json_encode($json));
	    }
	    
	    $remember = $req->getPost('remember', 0);
	    $error = $m_dev->login($username, $password, $remember);
	    if( $error ) {
	        $json['msg'] = $error;
	        exit(json_encode($json));
	    }
	    
	    exit(json_encode($json));
	}
	
	public function logoutAction()
	{
	    $m_dev = new DeveloperModel();
	    $m_dev->logout();
	    $fwd = $this->getForward();
	    if( $fwd ) {
	        header("Location: {$fwd}");
	        return false;
	    }
	}
	
	public function applyAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $login = $m_dev->getLogin();
	    if( empty($login) ) {
	        exit;
	    }
	    
	    $req = $this->getRequest();
	    $apply = $req->getPost('apply', 0);
	    if( ! $req->isXmlHttpRequest() || $apply != 1 ) {
	        exit;
	    }
	    
	    $dev = $m_dev->fetch("dev_id='{$login['dev_id']}'", 'status');
	    if( $dev['status'] == 9 ) {
	        exit('<a href="/game/create.html" class="btn_green">创建游戏</a>');
	    } elseif( $dev['status'] == 3 ) {
	        exit('<span style="color:red;font-size:16px">您的账号已被冻结。</span>');
	    } else{
	        $message = '您的审核申请已经提交，请耐心等待工作人员的审核，审核周期一般1-3个工作日。';
	        if( $dev['status'] != 1 ) {
	            $m_dev->update(array('status'=>1,'message'=>$message), "dev_id='{$login['dev_id']}'");
	        }
	        exit("<span style=\"color:green;font-size:16px\">{$message}</span>");
	    }
	}
	
	public function baseinfoAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $login = $m_dev->getLogin();
	    if( empty($login) ) {
	        $this->redirect('/developer/login.html?fwd=/developer/baseinfo.html');
	        return false;
	    }
	    
	    $assign['dev'] = $m_dev->fetch("dev_id='{$login['dev_id']}'");
	    $assign['dev']['scale'] = $m_dev->_scales[$assign['dev']['scale']];
	    $assign['menu'] = $m_dev->_menu;
	    $this->formatMenu($assign['menu'], $assign['dev']);
	    
	    $this->getView()->assign($assign);
	}
	
	//修改资料
	public function editinfoAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $dev = $m_dev->getLogin();
	    if( empty($dev) ) {
	        $this->redirect('/developer/login.html?fwd=/developer/editinfo.html');
	    }
	    
	    $req = $this->getRequest();
	    if( $req->isPost() && $_POST ) {
	        $data = array(
	            'type' => $req->getPost('type', ''),
	            'scale' => $req->getPost('scale', 0),
	            'province' => $req->getPost('province', ''),
	            'city' => $req->getPost('city', ''),
	            'county' => $req->getPost('county', ''),
	            'addr' => $req->getPost('addr', ''),
	            'contact' => $req->getPost('contact', ''),
	            'mobile' => $req->getPost('mobile', ''),
	            'email' => $req->getPost('email', ''),
	            'qq' => $req->getPost('qq', ''),
	            'wx' => $req->getPost('wx', ''),
	        );
	        if( ! in_array($data['type'], array('company', 'person')) ) {
	            $data['type'] = 'company';
	        }
	        
	        $encode = mb_internal_encoding();
	        mb_internal_encoding('UTF-8');
	        if( $data['type'] == 'company' ) {
	            $data['com_short'] = $req->getPost('com_short', '');
	            $data['company'] = $req->getPost('company', '');
//	            $data['org_code'] = $req->getPost('org_code', '');
	            $data['license'] = $req->getPost('license', '');
	            $data['tel'] = $req->getPost('tel', '');
	            
	            $data['com_short'] && $data['com_short'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['com_short'], 0, 6));
	            $data['company'] && $data['company'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['company'], 0, 32));
//	            $data['org_code'] && $data['org_code'] = preg_replace('/[^a-zA-Z\d\-]+/', '', substr($data['org_code'], 0, 10));
	            $data['license'] && $data['license'] = preg_replace('/[^a-zA-Z\d\-]+/', '', substr($data['license'], 0, 18));
	            $data['tel'] && $data['tel'] = preg_replace('/[^\d\-\s]+/', '', substr($data['tel'], 0, 13));
	        }
	        
	        $data['province'] && $data['province'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['province'], 0, 16));
	        $data['city'] && $data['city'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['city'], 0, 16));
	        $data['county'] && $data['county'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['county'], 0, 16));
	        $data['addr'] && $data['addr'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['addr'], 0, 96));
	        $data['contact'] && $data['contact'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['contact'], 0, 8));
	        $data['mobile'] && $data['mobile'] = preg_replace('/\D+/', '', substr($data['mobile'], 0, 11));
	        $data['email'] && $data['email'] = preg_replace('/[\'\"\\\]+/', '', substr($data['email'], 0, 32));
	        $data['qq'] && $data['qq'] = preg_replace('/\D+/', '', substr($data['qq'], 0, 12));
	        $data['wx'] && $data['wx'] = preg_replace('/[\'\"\\\]+/', '', substr($data['wx'], 0, 32));
	        mb_internal_encoding($encode);
	        
	        $m_dev->update($data, "dev_id='{$dev['dev_id']}'");
	        $this->redirect('/developer/baseinfo.html');
	        return false;
	    } else {
	        $assign['dev'] = $m_dev->fetch("dev_id='{$dev['dev_id']}'");
	        $assign['scales'] = $m_dev->_scales;
	        
	        $assign['menu'] = $m_dev->_menu;
	        $assign['menu_a'] = 'baseinfo';
	        $this->formatMenu($assign['menu'], $assign['dev']);
	        
	        $this->getView()->assign($assign);
	    }
	}
	
	//证件信息
	public function papersAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $dev = $m_dev->getLogin();
	    if( empty($dev) ) {
	        $this->redirect('/developer/login.html?fwd=/developer/papers.html');
	    }
	    
	    $assign['dev'] = $m_dev->fetch("dev_id='{$dev['dev_id']}'", 'type,org_img,lic_img,on_nums,off_nums,check_nums');
	    $assign['dev'] = array_merge($dev, $assign['dev']);
	    $assign['menu'] = $m_dev->_menu;
	    $this->formatMenu($assign['menu'], $assign['dev']);
	    
	    $this->getView()->assign($assign);
	}
	//证件上传
	public function uploadAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $dev = $m_dev->getLogin();
	    if( empty($dev) ) {
	        $this->redirect('/developer/login.html?fwd=/developer/upload.html');
	    }
	    
	    $req = $this->getRequest();
	    if( $req->isPost() && $_FILES ) {
	        $img = $m_dev->fetch("dev_id='{$dev['dev_id']}'", 'org_img,lic_img');
	        $data = null;
	        
	        $mod = $dev['dev_id'] % 1000;
	        $len = strlen($mod);
	        if( $len < 3 ) {
	            $mod = str_repeat('0', 3 - $len).$mod;
	        }
	        
	        if( $_FILES['org_img']['error'] == 0 && $_FILES['org_img']['size'] > 0 && is_uploaded_file($_FILES['org_img']['tmp_name']) ) {
	            $size = getimagesize($_FILES['org_img']['tmp_name']);
	            $ext = '';
	            if( $size ) {
    	            switch ($size[2])
    	            {
    	                case 1: $ext = 'gif'; break;
    	                case 2: $ext = 'jpg'; break;
    	                case 3: $ext = 'png'; break;
    	            }
	            }
	            if( $ext ) {
	                if( $img['org_img'] ) {
	                    $file = explode('?', $img['org_img']);
	                    $file = APPLICATION_PATH.'/public'.$file[0];
	                    @unlink($file);
	                }
	                
	                $path = '/dev/'.$mod;
	                $dst = APPLICATION_PATH.'/public'.$path;
	                if( ! file_exists($dst) ) {
	                    mkdir($dst);
	                }
	                $path .= "/{$dev['dev_id']}-org.{$ext}";
	                $dst = APPLICATION_PATH.'/public'.$path;
	                $rs = move_uploaded_file($_FILES['org_img']['tmp_name'], $dst);
	                if( $rs ) {
	                    $path .= '?'.time();
	                    $data['org_img'] = $path;
	                }
	            }
	        }
	        if( $_FILES['lic_img']['error'] == 0 && $_FILES['lic_img']['size'] > 0 && is_uploaded_file($_FILES['lic_img']['tmp_name']) ) {
	            $size = getimagesize($_FILES['lic_img']['tmp_name']);
	            $ext = '';
	            if( $size ) {
    	            switch ($size[2])
    	            {
    	                case 1: $ext = 'gif'; break;
    	                case 2: $ext = 'jpg'; break;
    	                case 3: $ext = 'png'; break;
    	            }
	            }
	            if( $ext ) {
	                if( $img['lic_img'] ) {
	                    $file = explode('?', $img['lic_img']);
	                    $file = APPLICATION_PATH.'/public'.$file[0];
	                    @unlink($file);
	                }
	                 
	                $path = '/dev/'.$mod;
	                $dst = APPLICATION_PATH.'/public'.$path;
	                if( ! file_exists($dst) ) {
	                    mkdir($dst);
	                }
	                $path .= "/{$dev['dev_id']}-lic.{$ext}";
	                $dst = APPLICATION_PATH.'/public'.$path;
	                $rs = move_uploaded_file($_FILES['lic_img']['tmp_name'], $dst);
	                if( $rs ) {
	                    $path .= '?'.time();
	                    $data['lic_img'] = $path;
	                }
	            }
	        }
	        
	        if( $data ) {
	            $m_dev->update($data, "dev_id='{$dev['dev_id']}'");
	        }
	        $this->redirect('/developer/papers.html');
	        return false;
	    } else {
	        $assign['dev'] = $m_dev->fetch("dev_id='{$dev['dev_id']}'", 'type,org_img,lic_img,on_nums,off_nums,check_nums');
	        $assign['dev'] = array_merge($dev, $assign['dev']);
	        
	        $assign['menu'] = $m_dev->_menu;
	        $assign['menu_a'] = 'papers';
	        $this->formatMenu($assign['menu'], $assign['dev']);
	        
	        $this->getView()->assign($assign);
	    }
	}
	
	//银行账号信息
	public function bankinfoAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $dev = $m_dev->getLogin();
	    if( empty($dev) ) {
	        $this->redirect('/developer/login.html?fwd=/developer/bankinfo.html');
	        return false;
	    }
	    
	    $assign['dev'] = $m_dev->fetch("dev_id='{$dev['dev_id']}'", 'bank_name,bank_open,bank_user,bank_no,bank_check,bank_ck_mobile,on_nums,off_nums,check_nums');
	    $assign['dev'] = array_merge($dev, $assign['dev']);
	    $assign['menu'] = $m_dev->_menu;
	    $this->formatMenu($assign['menu'], $assign['dev']);
	    
	    $this->getView()->assign($assign);
	}
	
	//修改银行信息
	public function editbankAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $dev = $m_dev->getLogin();
	    if( empty($dev) ) {
	        $this->redirect('/developer/login.html?fwd=/developer/editbank.html');
	    }
	    
	    $req = $this->getRequest();
	    if( $req->isPost() && $_POST ) {
	        $data = array(
	            'bank_name' => $req->getPost('bank_name', ''),
	            'bank_open' => $req->getPost('bank_open', ''),
	            'bank_user' => $req->getPost('bank_user', ''),
	            'bank_no' => $req->getPost('bank_no', ''),
	            'bank_check' => $req->getPost('bank_check', ''),
	            'bank_ck_mobile' => $req->getPost('bank_ck_mobile', ''),
	        );
	        
	        $encode = mb_internal_encoding();
	        mb_internal_encoding('UTF-8');
	        $data['bank_name'] && $data['bank_name'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['bank_name'], 0, 16));
	        $data['bank_open'] && $data['bank_open'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['bank_open'], 0, 24));
	        $data['bank_user'] && $data['bank_user'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['bank_user'], 0, 24));
	        $data['bank_no'] && $data['bank_no'] = preg_replace('/\D+/', '', substr($data['bank_no'], 0, 24));
	        $data['bank_check'] && $data['bank_check'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['bank_check'], 0, 8));
	        $data['bank_ck_mobile'] && $data['bank_ck_mobile'] = preg_replace('/\D+/', '', substr($data['bank_ck_mobile'], 0, 11));
	        mb_internal_encoding($encode);
	        
	        $m_dev->update($data, "dev_id='{$dev['dev_id']}'");
	        $this->redirect('/developer/bankinfo.html');
	        return false;
	    } else {
	        $assign['dev'] = $m_dev->fetch("dev_id='{$dev['dev_id']}'", 'bank_name,bank_open,bank_user,bank_no,bank_check,bank_ck_mobile,on_nums,off_nums,check_nums');
	        $assign['dev'] = array_merge($dev, $assign['dev']);
	        $assign['banks'] = $m_dev->_banks;
	        $assign['menu'] = $m_dev->_menu;
	        $assign['menu_a'] = 'bankinfo';
	        $this->formatMenu($assign['menu'], $assign['dev']);
	        
	        $this->getView()->assign($assign);
	    }
	}
	
	//修改密码
	public function passwordAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $dev = $m_dev->getLogin();
	    if( empty($dev) ) {
	        $this->redirect('/developer/login.html?fwd=/developer/password.html');
	    }
	    
	    $req = $this->getRequest();
	    if( $req->isXmlHttpRequest() && $_POST ) {
	        $oldpwd = $req->getPost('oldpwd', '');
	        $newpwd = $req->getPost('newpwd', '');
	        $re_pwd = $req->getPost('re_pwd', '');
	        
	        if( $oldpwd && $newpwd && strcmp($re_pwd, $newpwd) == 0 ) {
	            $new_len = strlen($newpwd);
	            if( $new_len < 6 || $new_len > 16 ) {
	                exit(json_encode(array('msg'=>'密码由6-16位字符组成！','fwd'=>'')));
	            }
	            
	            $has = $m_dev->fetch("dev_id='{$dev['dev_id']}'", 'password');
	            if( empty($has) || strcmp($has['password'], $oldpwd) != 0 ) {
	                exit(json_encode(array('msg'=>'旧密码不正确！', 'fwd'=>'')));
	            }
	            
	            $pwd = md5($newpwd);
	            $rs = $m_dev->update("`password`='{$pwd}'", "dev_id='{$dev['dev_id']}'");
	            if( $rs ) {
	                $m_dev->logout();
	                exit(json_encode(array('msg'=>'密码修改成功，请重新登录！', 'fwd'=>'/developer/login.html?fwd=/developer/index.html')));
	            } else {
	                exit(json_encode(array('msg'=>'密码修改失败，请重试！','fwd'=>'')));
	            }
	        }
	        
	        exit(json_encode(array('msg'=>'参数错误！', 'fwd'=>'')));
	    } else {
	        $assign['dev'] = $m_dev->fetch("dev_id='{$dev['dev_id']}'", 'on_nums,off_nums,check_nums');
	        $assign['dev'] = array_merge($dev, $assign['dev']);
	        $assign['banks'] = $m_dev->_banks;
	        $assign['menu'] = $m_dev->_menu;
	        $this->formatMenu($assign['menu'], $assign['dev']);
	        
	        $this->getView()->assign($assign);
	    }
	}
}
