<?php

class PasswordController extends Yaf_Controller_Abstract
{
    public function forgetAction()
    {
        $m_dev = new DeveloperModel();
        $login = $m_dev->getLogin();
        if( $login ) {
            $this->redirect('/developer/index.html');
            return false;
        }
    }
    
    public function resetAction()
    {
        $req = $this->getRequest();
        $email = $req->get('email', '');
        $xcode = $req->get('xcode', '');
        $xcode && $xcode = preg_replace('/[^0-9a-f]+/', '', $xcode);
        if( strlen($xcode) != 32 ) {
            $assign['error'] = '链接被篡改！';
        } elseif( ! preg_match('/^[\w\-\.]+@[\w\-]+(\.\w+)+$/', $email) ) {
            $assign['error'] = '登录邮箱格式不正确！';
        }
        
        if( $req->isXmlHttpRequest() && $email && $xcode ) {
            if( isset($assign['error']) ) {
                $assign['result'] = 'error';
                exit(json_encode($assign));
            }
            
            $assign['result'] = 'error';
            $newpwd = $req->get('newpwd', '');
            $re_pwd = $req->get('re_pwd', '');
            $newlen = strlen($newpwd);
            if( $newlen < 6 || $newlen > 16 ) {
                $assign['error'] = '密码由6-16位字母、数字或字符组成！';
                exit(json_encode($assign));
            }
            if( strcmp($newpwd, $re_pwd) != 0 ) {
                $assign['error'] = '确认密码与密码不符！';
                exit(json_encode($assign));
            }
            
            $m_dev = new DeveloperModel();
            $dev = $m_dev->fetch("username='{$email}'", 'dev_id');
            if( empty($dev) ) {
                $assign['error'] = '登录邮箱不存在，请核实！';
                exit(json_encode($assign));
            }
            
            $newpwd = md5($newpwd);
            $rs = $m_dev->update("`password`='{$newpwd}'", "dev_id='{$dev['dev_id']}'");
            if( $rs ) {
                $assign['result'] = 'success';
                exit(json_encode($assign));
            } else {
                $assign['error'] = '密码重置失败，请重试！';
                exit(json_encode($assign));
            }
            
            return false;
        }
        
        $assign['email'] = $email;
        $assign['xcode'] = $xcode;
        $this->getView()->assign($assign);
    }
}
