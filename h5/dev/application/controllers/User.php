<?php

class UserController extends Yaf_Controller_Abstract
{
    private $m_user;
    private $user;
    
    //获取跳转地址
    private function getForward($referer = true)
    {
        $fwd = $this->getRequest()->get('fwd', '');
        $fwd=urldecode($fwd);
//        if( $fwd ) $fwd = urldecode($fwd);
//        if( ! $referer ) return $fwd;
//        if( empty($fwd) && !empty($_SERVER['HTTP_REFERER']) ) {
//            $fwd = $_SERVER['HTTP_REFERER'];
//            /*
//            if( $fwd && strpos($fwd, '?') ) {
//                $query = substr($fwd, strpos($fwd, '?'));
//                parse_str($query, $arr);
//                if( $arr && !empty($arr['fwd']) ) {
//                    $fwd = urldecode($arr['fwd']);
//                }
//            }
//            */
//        }
        return $fwd;
    }
    
    public function init()
    {
        $ip=$this->getIp();
        $host = Yaf_Registry::get('config')->redis->host;
        $port = Yaf_Registry::get('config')->redis->port;
        $conf=array('host'=>$host,'port'=>$port);
        $redis=F_Helper_Redis::getInstance($conf);
        //缓存域名，后面跳转使用
        $redis->set('global_url'.$ip,$_SESSION['HTTP_HOST']);
        Yaf_Registry::set('layout', false);
    }
    
    //个人中心主页
    public function indexAction()
    {
        $ip=$this->getIp();
        $host = Yaf_Registry::get('config')->redis->host;
        $port = Yaf_Registry::get('config')->redis->port;
        $conf=array('host'=>$host,'port'=>$port);
        $redis=F_Helper_Redis::getInstance($conf);
        if($redis->get('back_url'.$ip)){
            $domain=$redis->get('back_url'.$ip);
            $redis->del('back_url'.$ip);
            $this->redirect('http://'.$domain.'/user/index');
        }
        $this->m_user = new UsersModel();
        $this->user = $this->m_user->getLogin();
        
        if( $this->user ) {
            $user = $this->m_user->fetch("user_id='{$this->user['user_id']}'");
            if( $user['app'] == 'wx' && $user['avatar'] ) {
                $user['avatar'] .= '/132';
            }
        } else {
            $user = $this->user;
        }
        //渠道id
        $channel_id=$_SESSION['user']??1;
        $cps_admin=new AdminModel('cps');
        $channel_info=$cps_admin->fetch(['admin_id'=>$channel_id]);
        $user['qq1']=$channel_info['qq1'];
        $user['qq2']=$channel_info['qq2'];
        if($user['qq1']==''){
            //查找管理员的
            $channel_info=$cps_admin->fetch(['admin_id'=>1]);
            $user['qq1']=$channel_info['qq1'];
        }
        if($user['qq2']==''){
            //查找管理员的
            $channel_info=$cps_admin->fetch(['admin_id'=>1]);
            $user['qq2']=$channel_info['qq2'];
        }
        $this->getView()->assign('user', $user);
    }
    
    //注册
	public function registerAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( $this->user ) {
	        $this->redirect('/user/index.html');
	    }
	    
	    $fwd = $this->getForward(false);
	    $fwd = $fwd ? urldecode($fwd) : '/user/index.html';
	    $this->getView()->assign('fwd', $fwd);
	}
	//注册协议
	public function agreementAction()
	{
	    
	}
	//Ajax注册
	public function ajaxregAction()
	{
	    $json = array('msg'=>'success', 'xcode'=>'', 'fwd'=>'');
	    
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( $this->user ) {
	        $json['msg'] = '你已登录，无需注册！';
	        $json['fwd'] = '/user/index.html';
	        exit(json_encode($json));
	    }
	    
	    $req = $this->getRequest();
	    $username = $req->getPost('username', '');
	    $password = $req->getPost('password', '');
	    $xcode = $req->getPost('xcode', '');
//	    $sms_code = $req->getPost('sms_code', 0);
	    
	    $imgcode = new F_Helper_ImgCode();
	    if( ! $imgcode->check($xcode) ) {
	        $json['msg'] = '验证码错误！';
	        $json['xcode'] = 'refresh';
	        exit(json_encode($json));
	    }
	    
	    if( empty($username) || empty($password) ) {
	        $json['msg'] = '用户名与密码不能为空！';
	        exit(json_encode($json));
	    }
	    $pwd_len = strlen($password);
	    if( $pwd_len < 6 || $pwd_len > 16 ) {
	        $json['msg'] = '密码由6-16位字符组成！';
	        exit(json_encode($json));
	    }
	    
	    $re_pwd = $password;
	    if( preg_match('/^(?:13[0-9]|14[57]|15[0-9]|17[0678]|18[0-9])\d{8}$/', $username) ) {
//	        $m_sms_m = new SmscodeMobileModel();
//	        $error = $m_sms_m->check($username, $sms_code);
//	        if( $error ) {
//	            $json['msg'] = $error;
//	            $json['xcode'] = 'refresh';
//	            exit(json_encode($json));
//	        }
	        $mobile = $username;
	        $email = '';
	    } else if( preg_match('/^[\w\-\.]+@[\w\-]+(\.\w+)+$/', $username) ) {
	        $mobile = '';
	        $email = $username;
	    } else {
	        $mobile = '';
	        $email = '';
	    }
	    $uid = $this->m_user->register($username, $password, $re_pwd, $mobile, $email);
	    if( is_string($uid) ) {
	        $json['msg'] = $uid;
	        $json['xcode'] = 'refresh';
	        exit(json_encode($json));
	    }
	    
	    //自动登录
	    $error = $this->m_user->login($username, $password, 0);
	    if( $error ) {
	        $json['msg'] = "注册成功，自动登录失败，点确定后跳转到登录页！";
	        $json['fwd'] = '/user/login.html';
	        exit(json_encode($json));
	    }
	    
	    $json['fwd'] = '/user/index.html';
	    exit(json_encode($json));
	}
	
	//登录界面
	public function loginAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( $this->user ) {
	        $this->redirect('/user/index.html');
	    }
	    
	    $assign['err'] = $this->getRequest()->getParam('errmsg', '');
	    $assign['fwd'] = $this->getForward(false);
	    
	    $this->getView()->assign($assign);
	}
	//Ajax登录
	public function ajaxloginAction()
	{
	    $json = array('msg'=>'success', 'xcode'=>'false', 'fwd'=>'');
	    
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( $this->user ) {
	        exit(json_encode($json));
	    }
	    
	    $req = $this->getRequest();
	    $username = $req->getPost('username', '');
	    $password = $req->getPost('password', '');
	    $username = preg_replace('#[\'\"\%\#\*\?\\\]+#', '', substr($username, 0, 32));
	    if( empty($username) || empty($password) ) {
	        $json['msg'] = '请输入用户名及密码！';
	        exit(json_encode($json));
	    }
	    /*
	    //判断登录次数决定是否使用验证码
	    $user = $this->m_user->fetch("username='{$username}'", 'user_id,login_day,login_times');
	    if( empty($user) ) {
	        $json['msg'] = '此账户还未注册，是否现在去注册？';
	        $json['fwd'] = '/user/register.html?username='.$username;
	        exit(json_encode($json));
	    }

	    $today = date('Ymd');
	    if( $user['login_day'] == $today ) {
	        if( $user['login_times'] >= 3 ) {
	            $xcode = $req->getPost('xcode', '');
	            $xcode = preg_replace('#\D+#', '', substr($xcode, 0, 6));
	            $scode = Yaf_Session::getInstance()->get('xcode');
	            if( $xcode != $scode ) {
	                $json['msg'] = '验证码错误！';
	                $json['xcode'] = 'refresh';
	                exit(json_encode($json));
	            }
	        }
	    }
	    */
	    $remember = $req->getPost('remember', 0);
	    $error = $this->m_user->login($username, $password, $remember);
	    if( $error ) {
	        /*$times = $user['login_day'] == $today ? $user['login_times'] + 1 : 1;
	        $this->m_user->update(array('login_day'=>$today, 'login_times'=>$times), "username='{$username}'");
	        if( $times >= 3 ) {
	            $json['xcode'] = 'true';
	        }*/
	        
	        $json['msg'] = $error;
	        exit(json_encode($json));
	    }
	    
	    exit(json_encode($json));
	}
	
	public function logoutAction()
	{
	    $this->m_user = new UsersModel();
	    $this->m_user->logout();
	    $fwd = $this->getForward();
	    if( $fwd ) {
	        header("Location: {$fwd}");
	        return false;
	    }
	}
	
	//修改资料
	public function editAction()
	{
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        $this->redirect('/user/login.html?fwd=/user/edit.html');
	    }
	    
	    $req = $this->getRequest();
	    if( $req->isXmlHttpRequest() ) {
	        $nickname = $req->getPost('nickname', '');
	        $sex = $req->getPost('sex', 0);
	        $signature = $req->getPost('signature', '');
	        
	        $up_str = array();
	        if( $nickname ) {
	            $nickname = addslashes(mb_substr($nickname, 0, 16, 'UTF-8'));
	            $up_str[] = "nickname='{$nickname}'";
	        }
	        if( $sex == 1 || $sex == 2 ) {
	            $up_str[] = "sex='{$sex}'";
	        }
	        if( $signature ) {
	            $signature = addslashes(mb_substr($signature, 0, 32, 'UTF-8'));
	            $up_str[] = "signature='{$signature}'";
	        }
	        if( $up_str ) {
	            $up_str = implode(',', $up_str);
	            $m_user->update($up_str, "user_id='{$user['user_id']}'");
	        }
	        
	        $json = array('msg'=>'个人资料更新成功！', 'fwd'=>'/user/index.html');
	        exit(json_encode($json));
	        
	    } else {
	        $user = $m_user->fetch("user_id='{$user['user_id']}'", 'user_id,app,avatar,username,nickname,sex,signature');
	        if( $user['app'] == 'wx' && $user['avatar'] ) {
	            $user['avatar'] .= '/96';
	        }
	        $this->getView()->assign('user', $user);
	    }
	}
	
	//忘记密码
	public function forgetAction()
	{
	    $conf = Yaf_Application::app()->getConfig();
	    $this->getView()->assign('qq_group', $conf->service->qq_group);
	}
	//重置密码
	public function resetAction()
	{
	    $req = $this->getRequest();
	    if( ! $req->isPost() ) {
	        exit;
	    }
	    
	    $username = $req->getPost('username', '');
	    $xcode = $req->getPost('xcode', '');
	    $sms_code = $req->getPost('sms_code', 0);
	    $em_code = $req->getPost('em_code', 0);
	    
	    $conf = Yaf_Application::app()->getConfig();
	    $assign = array(
	        'username' => $username,
	        'token' => '',
	        'error' => '',
	        'qq_group' => $conf->service->qq_group,
	    );
	    
	    if( empty($username) || empty($xcode) || ($sms_code == 0 && $em_code == 0) ) {
	        $assign['error'] = '参数不能为空！';
	        $this->getView()->assign($assign);
	        return true;
	    }
	    
	    $ic = new F_Helper_ImgCode();
	    $rs = $ic->check($xcode);
	    if( ! $rs ) {
	        $assign['error'] = '图片验证码不正确！';
	        $this->getView()->assign($assign);
	        return true;
	    }
	    
	    if( preg_match('/^(?:13[0-9]|14[57]|15[0-9]|17[0678]|18[0-9])\d{8}$/', $username) ) {
	        $m_sms_m = new SmscodeMobileModel();
	        $err = $m_sms_m->check($username, $sms_code);
	    } else {
	        $m_xc_em = new XcodeEmailModel();
	        $err = $m_xc_em->check($username, $em_code);
	    }
	    if( $err != '' ) {
	        $assign['error'] = $err;
	        $this->getView()->assign($assign);
	        return true;
	    }
	    
	    $assign['token'] = md5(uniqid(mt_rand()));
	    $m_user = new UsersModel();
	    $m_user->update(array('access_token'=>$assign['token']), "username='{$username}'");
	    $this->getView()->assign($assign);
	}
	//Ajax保存新密码
	public function ajaxresetAction()
	{
	    $req = $this->getRequest();
	    if( ! $req->isXmlHttpRequest() ) {
	        exit;
	    }
	    
	    $username = $req->getPost('username', '');
	    $username = preg_replace('/[\'\"\\\]+/', '', $username);
	    $token = $req->getPost('token', '');
	    $token = preg_replace('/[^a-f0-9]+/', '', $token);
	    if( strlen($token) != 32 ) {
	        exit;
	    }
	    
	    $m_user = new UsersModel();
	    $has = $m_user->fetch("username='{$username}' AND access_token='{$token}'", 'user_id');
	    if( empty($has) ) {
	        exit(json_encode(array('msg'=>'错误的令牌，即将返回找回密码页面！','fwd'=>'/user/forget.html')));
	    }
	    
	    $newpwd = $req->getPost('newpwd', '');
	    $re_pwd = $req->getPost('re_pwd', '');
	    $len = strlen($newpwd);
	    if( $len < 6 || $len > 16 ) {
	        exit(json_encode(array('msg'=>'密码由6-16位字符组成！','fwd'=>'')));
	    }
	    if( strcmp($newpwd, $re_pwd) != 0 ) {
	        exit(json_encode(array('msg'=>'确认密码与新密码不符！','fwd'=>'')));
	    }
	    
	    $err = $m_user->edit($username, '', $newpwd, '', '', true);
	    if( $err == '' ) {
	        exit(json_encode(array('msg'=>'密码已被重置，请使用新密码登录！','fwd'=>'/user/login.html?fwd=/user/index.html')));
	    } else {
	        exit(json_encode(array('msg'=>$err,'fwd'=>'')));
	    }
	}
	
	//修改密码
	public function passwordAction()
	{
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        $this->redirect('/user/login.html?fwd=/user/password.html');
	    }
	    
	    $req = $this->getRequest();
	    if( $req->isXmlHttpRequest() ) {
	        $oldpwd = $req->getPost('oldpwd', '');
	        $newpwd = $req->getPost('newpwd', '');
	        $re_pwd = $req->getPost('re_pwd', '');
	        
	        if( $oldpwd && $newpwd && strcmp($re_pwd, $newpwd) == 0 ) {
	            $new_len = strlen($newpwd);
	            if( $new_len < 6 || $new_len > 16 ) {
	                exit(json_encode(array('msg'=>'密码由6-16位字符组成！','fwd'=>'')));
	            }
	            
	            $err = $m_user->edit($user['username'], $oldpwd, $newpwd);
	            if( $err ) {
	                exit(json_encode(array('msg'=>$err,'fwd'=>'')));
	            } else {
	                $m_user->logout();
	                exit(json_encode(array('msg'=>'密码修改成功，请重新登录！', 'fwd'=>'/user/login.html?fwd=/user/edit.html')));
	            }
	        }
	        
	        exit(json_encode(array('msg'=>'参数错误！', 'fwd'=>'')));
	    } else {
	        $conf = Yaf_Application::app()->getConfig();
	        $this->getView()->assign('qq_group', $conf->service->qq_group);
	    }
	}
	
	//修改头像
	public function avatarAction()
	{
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        $this->redirect('/user/login.html?fwd=/user/avatar.html');
	    }
	    
	    $req = $this->getRequest();
	    if( $req->isXmlHttpRequest() ) {
	        $avatar = $req->getPost('avatar', '');
	        $avatar = str_replace(' ', '+', $avatar);
	        $avatar = substr($avatar, strpos($avatar, ',')+1);
	        $avatar = base64_decode($avatar);
	        $im = imagecreatefromstring($avatar);
	        if( $im == false ) {
	            exit(json_encode(array('msg'=>'不支持的图片类型！', 'fwd'=>'')));
	        }
	        
	        $uid_len = strlen($user['user_id']);
	        if( $uid_len < 4 ) {
	            $uid_s = str_repeat('0', 4 - $uid_len).$user['user_id'];
	        } else {
	            $uid_s = substr($user['user_id'], -4);
	        }
	        
	        $path = '/avatar/';
	        $path .= substr($uid_s, 0, 2);
	        $dst = APPLICATION_PATH.'/public';
	        $tmp = $dst.$path;
	        if( ! file_exists($tmp) ) {
	            mkdir($tmp, 0755);
	        }
	        $path .= '/'.substr($uid_s, 2);
	        $tmp = $dst.$path;
	        if( ! file_exists($tmp) ) {
	            mkdir($tmp, 0755);
	        }
	        
	        $path .= '/'.$user['user_id'].'.jpg';
	        $dst .= $path;
	        $rs = imagejpeg($im, $dst);
	        if( ! $rs ) {
	            exit(json_encode(array('msg'=>'头像保存失败，请稍后重试！', 'fwd'=>'')));
	        }
	        
	        $path .= '?'.time();
	        $m_user->update(array('avatar'=>$path), "user_id='{$user['user_id']}'");
	        
	        exit(json_encode(array('msg'=>'新头像已保存。', 'fwd'=>'/user/edit.html')));
	    }
	}
	
	//最近在玩
	public function gamesAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( empty($this->user) ) {
	        $this->redirect('/user/login.html?fwd=/user/games.html');
	    }
	    
	    $games = $this->m_user->getPlayGames($this->user['user_id'], 1, 12);
	    $this->getView()->assign(array('user'=>$this->user, 'games'=>$games));
	}
	//游戏收藏
	public function favoritesAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( empty($this->user) ) {
	        $this->redirect('/user/login.html?fwd=/user/favorites.html');
	    }
	    
	    $games = $this->m_user->getFavorites($this->user['user_id'], 1, 12);
	    $this->getView()->assign(array('user'=>$this->user, 'games'=>$games));
	}
	
	//删除收藏
	public function delfavoriteAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( empty($this->user) ) {
	        exit('未登录，不能删除收藏！');
	    }
	    $gid = $this->getRequest()->get('game_id', 0);
	    if( $gid < 1 ) {
	        exit('请指定你要删除的收藏！');
	    }
	    
	    $this->m_user->delFavorite($this->user['user_id'], $gid);
	    exit('success');
	}
	//添加收藏
	public function addfavoriteAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( empty($this->user) ) {
	        exit('还未登录，不能收藏游戏！');
	    }
	    $gid = $this->getRequest()->get('game_id', 0);
	    if( $gid < 1 ) {
	        exit('你要收藏的游戏不存在！');
	    }
	    
	    $m_game = new GameModel();
	    $game = $m_game->fetch("game_id='{$gid}'", 'game_id');
	    if( empty($game) ) {
	        exit('你要收藏的游戏不存在！');
	    }
	    
	    $this->m_user->addFavorite($this->user['user_id'], $gid);
	    exit('success');
	}
	
	//充值记录
	public function paylogsAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( empty($this->user) ) {
	        $this->redirect('/user/login.html?fwd=/user/paylogs.html');
	    }
	    
	    $m_adpos = new AdposModel();
	    $banner = $m_adpos->getByCode('pay_center_banner', 1);
	    $this->getView()->assign('banner', $banner);
	}
	//充值记录列表
	public function paylistAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( empty($this->user) ) {
	        exit;
	    }
	    
	    $req = $this->getRequest();
	    $pn = $req->get('pn', 0);
	    $limit = $req->get('limit', 0);
	    
	    if( $pn < 1 || $limit < 1 ) {
	        exit;
	    }
	    
	    $m_pay = new PayModel();
	    $logs = $m_pay->fetchAll("user_id='{$this->user['user_id']}' and pay_time > 0", $pn, $limit, 'pay_id,to_user,game_id,game_name,money,add_time', 'add_time DESC');
	    
	    $this->getView()->assign('logs', $logs);
	}
	//充值详情
	public function payinfoAction()
	{
	    $this->m_user = new UsersModel();
	    $this->user = $this->m_user->getLogin();
	    if( empty($this->user) ) {
	        $fwd = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/user/paylogs.html';
	        $this->redirect('/user/login.html?fwd='.$fwd);
	    }
	    
	    $pay_id = $this->getRequest()->get('pay_id', '');
	    $pay_id = preg_replace('/\D+/', '', $pay_id);
	    if( empty($pay_id) ) {
	        $this->redirect('/user/paylogs.html');
	    }
	    $m_pay = new PayModel();
	    $pay = $m_pay->fetch("pay_id='{$pay_id}'");
	    
	    $m_adpos = new AdposModel();
	    $banner = $m_adpos->getByCode('pay_center_banner', 1);
	    
	    $this->getView()->assign(array('pay'=>$pay, 'types'=>$m_pay->_types, 'banner'=>$banner));
	}
	
	//礼包记录
	public function giftlogsAction()
	{
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        $this->redirect('/user/login.html?fwd=/user/giftlogs.html');
	        return false;
	    }
	    
	    $m_adpos = new AdposModel();
	    $banner = $m_adpos->getByCode('gift_logs_banner', 1);
	    $this->getView()->assign('banner', $banner);
	}
	//礼包记录列表
	public function giftlistAction()
	{
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        exit;
	    }
	    
	    $req = $this->getRequest();
	    $pn = $req->get('pn', 0);
	    $limit = $req->get('limit', 0);
	    
	    if( $pn < 1 || $limit < 1 ) {
	        exit;
	    }
	    
	    $logs = $m_user->giftLogs($user['user_id'], $pn, $limit);
	    $this->getView()->assign('logs', $logs);
	}
	//礼包详情
	public function giftinfoAction()
	{
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        $fwd = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/user/giftlogs.html';
	        $this->redirect('/user/login.html?fwd='.$fwd);
	        return false;
	    }
	    
	    $log_id = $this->getRequest()->get('log_id', '');
	    $log_id = preg_replace('/\D+/', '', $log_id);
	    if( empty($log_id) ) {
	        $this->redirect('/user/giftlogs.html');
	        return false;
	    }
	    
	    $log = $m_user->fetchBySql("SELECT * FROM user_cdkey WHERE log_id='{$log_id}' AND user_id='{$user['user_id']}' LIMIT 1");
	    if( empty($log) ) {
	        $this->redirect('/user/giftlogs.html');
	        return false;
	    }
	    
	    $m_gift = new GiftbagModel();
	    $gift = $m_gift->get($log['gift_id']);
	    $log = array_merge($log, $gift);
	    
	    $m_adpos = new AdposModel();
	    $banner = $m_adpos->getByCode('gift_logs_banner', 1);
	    
	    $this->getView()->assign(array('log'=>$log, 'banner'=>$banner));
	}
	
	//微信二维码
	public function weixinAction()
	{
	    
	}
	
	//防沉迷资料
	public function fcmAction()
	{
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        $this->redirect('/user/login.html?fwd=/user/fcm.html');
	        return false;
	    }
	    
	    $req = $this->getRequest();
	    if( $req->isXmlHttpRequest() ) {
	        $idno = $req->getPost('idno', '');
	        $name = $req->getPost('name', '');
	        $json = array('msg'=>'', 'fwd'=>'', 'notice'=>'');
	        $err = $m_user->fcm($idno, $name);
	        if( $err ) {
	            $json['msg'] = $err;
	            $notice = Yaf_Registry::get('fcm_notice');
	            if( $notice ) {
	                $json['notice'] = $notice;
	            }
	        } else {
	            $json['msg'] = '防沉迷信息已保存成功！';
	            $json['fwd'] = '/user/index.html';
	        }
	        exit(json_encode($json));
	    } else {
	        $info = $m_user->fetch("user_id='{$user['user_id']}'", 'identity,realname,fcm_status');
	        $conf = Yaf_Application::app()->getConfig();
	        $info['qq_group'] = $conf->service->qq_group;
	        
	        $m_log = new UserfcmlogModel();
	        $has = $m_log->fetch("user_id='{$user['user_id']}'");
	        $info['notice'] = '';
	        if( $has ) {
	            $info['notice'] = '您本周还可修改一次！';
	        }
	        
	        $this->getView()->assign($info);
	    }
	}
	
	//QQ登录
	public function qqloginAction()
	{
	    $fwd = $this->getForward(false);
	    
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( $user ) {
	        $this->redirect('/user/index.html');
	        return false;
	    }
	    
	    $conf = Yaf_Application::app()->getConfig();
	    $appid = $conf->qq->appid;
	    $domain_old =isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        if($domain_old!='h5.zyttx.com'){
            //缓存域名,登录成功后跳转
            $ip=$this->getIp();
            $host = Yaf_Registry::get('config')->redis->host;
            $port = Yaf_Registry::get('config')->redis->port;
            $conf=array('host'=>$host,'port'=>$port);
            $redis=F_Helper_Redis::getInstance($conf);
            $redis->set('back_url'.$ip,$_SERVER['HTTP_HOST']);
            $redis->set('back_url_query'.$ip,$_REQUEST['fwd']);
        }
	    $domain = 'h5.zyttx.com';
	    $callback = "http://{$domain}/user/qqcallback.html";
	    if( $fwd ) {
	        $callback .= '?fwd='.urlencode($fwd);
	    }
	    $callback = urlencode($callback);
	    $state = uniqid('dxj', true);
	    Yaf_Session::getInstance()->set('qq_state', $state);
	    
	    $url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id={$appid}&redirect_uri={$callback}&state={$state}";
	    $this->redirect($url);
	    return false;
	}
	//QQ登录回调
	public function qqcallbackAction()
	{
	    $fwd = $this->getForward(false);
	    $fwd = empty($fwd) ? '/user/index.html' : urldecode($fwd);
	    $req = $this->getRequest();
	    $sess = Yaf_Session::getInstance();
	    $cancle = $req->get('usercancel', 0);
	    $msg = $req->get('msg', '');
	    $state = $req->get('state', '');
//	    if( strcmp($state, $sess->qq_state) != 0 ) {
//	        $this->redirect('/');
//	        return false;
//	    }
	    $sess->del('qq_state');
	    
	    if( $cancle || $msg != '' ) {
	        print_r($_GET);exit;
	        return true;
	    }
	    
	    $code = $req->get('code', '');
	    $arr = array(
	        'openid'=>'', 'nickname'=>'', 'sex'=>0,
	        'avatar'=>'', 'access_token'=>'', 'expires'=>''
	    );
	    $time = time();
	    
	    //获取access_token
	    $conf = Yaf_Application::app()->getConfig();
	    $appid = $conf->qq->appid;
	    $appkey = $conf->qq->appkey;
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	    $callback = urlencode("http://{$domain}/user/qqcallback.html");
	    
	    //$log = new F_Helper_Log();
	    //$log->debug("qq_login:\r\n");
	    
	    $url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id={$appid}&client_secret={$appkey}&code={$code}&redirect_uri={$callback}";
	    $curl = new F_Helper_Curl();
        $rs = $curl->request($url);
        parse_str($rs, $arr1);
        //$log->debug($rs."\r\n".var_export($arr1,true)."\r\n");
        $arr['access_token'] = $arr1['access_token'];
        $arr['expires'] = $arr1['expires_in'] + $time;
	    
	    //获取open_id
    	$url = "https://graph.qq.com/oauth2.0/me?access_token={$arr['access_token']}";
        $rs = $curl->request($url);
        //$log->debug($rs."\r\n");
        if( preg_match('/openid\":\"([^\"]+)\"/', $rs, $arr2) ) {
            $arr['openid'] = $arr2[1];
        } else {
            exit($rs);
        }
        
        //判断用户是否已经使用QQ登录过平台
        $m_user = new UsersModel();
        $user = $m_user->fetch("app='qq' AND openid='{$arr['openid']}'", 'user_id,username,nickname,email,expires');
        if( $user && $user['expires'] > $time ) {
            $err = $m_user->oAuthLogin('qq', $arr);
            if( $err ) {
                //$log->debug($err."\r\n");
                $this->forward('user', 'login', array('errmsg'=>$err));
                return false;
            } else {
                $this->redirect($fwd);
                return false;
            }
        }
	    
	    //获取用户信息
        $url = "https://graph.qq.com/user/get_user_info?access_token={$arr['access_token']}&oauth_consumer_key={$appid}&openid={$arr['openid']}";
        $rs = $curl->request($url);
        //$log->debug($rs."\r\n");
        $arr3 = json_decode($rs, true);
        if( ! $arr3 ) {
            $this->forward('user', 'login', array('errmsg'=>'QQ登录失败！'));
            return false;
        }
        if( $arr3['ret'] != 0 ) {
            $this->forward('user', 'login', array('errmsg'=>$arr3['msg']));
            return false;
        }
	    
        $arr['nickname'] = $arr3['nickname'];
        $arr['sex'] = $arr3['gender'] != '男' ? 2 : 1;
        if( $arr3['figureurl_qq_2'] ) {
            $arr['avatar'] = $arr3['figureurl_qq_2'];
        } elseif( $arr3['figureurl_2'] ) {
            $arr['avatar'] = $arr3['figureurl_2'];
        } else {
            $arr['avatar'] = $arr3['figureurl_qq_1'];
        }
        
        $err = $m_user->oAuthLogin('qq', $arr);
        if( $err ) {
            $this->forward('user', 'login', array('errmsg'=>$err));
        } else {
            $this->redirect($fwd);
        }
        
        /*
        {
        "ret":0,
        "msg":"",
        "nickname":"Peter",
        "figureurl":"http://qzapp.qlogo.cn/qzapp/111111/942FEA70050EEAFBD4DCE2C1FC775E56/30",
        "figureurl_1":"http://qzapp.qlogo.cn/qzapp/111111/942FEA70050EEAFBD4DCE2C1FC775E56/50",
        "figureurl_2":"http://qzapp.qlogo.cn/qzapp/111111/942FEA70050EEAFBD4DCE2C1FC775E56/100",
        "figureurl_qq_1":"http://q.qlogo.cn/qqapp/100312990/DE1931D5330620DBD07FB4A5422917B6/40",
        "figureurl_qq_2":"http://q.qlogo.cn/qqapp/100312990/DE1931D5330620DBD07FB4A5422917B6/100",
        "gender":"男",
        "is_yellow_vip":"1",
        "vip":"1",
        "yellow_vip_level":"7",
        "level":"7",
        "is_yellow_year_vip":"1"
        }
        */
        //需要注意，不是所有的用户都拥有QQ的100x100的头像，但40x40像素则是一定会有。
        return false;
	}
	
	//微信登录
	public function wxloginAction()
	{
	    $fwd = $this->getForward(false);
	    
	    $conf = Yaf_Application::app()->getConfig();
	    $appid = $conf->wx->appid;
	    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	    $callback = "http://{$domain}/user/wxcallback.html";
	    if( $fwd ) {
	        $callback .= '?fwd='.urlencode($fwd);
	    }
	    $callback = urlencode($callback);
	    $state = uniqid('dxj', true);
	    Yaf_Session::getInstance()->set('wx_state', $state);
	    
	    $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";
	    $this->redirect($url);
	    return false;
	}
	
	//微信登录回调
	public function wxcallbackAction()
	{
	    $fwd = $this->getForward(false);
	    $fwd = empty($fwd) ? '/user/index.html' : urldecode($fwd);
	    
	    $req = $this->getRequest();
	    $sess = Yaf_Session::getInstance();
	    $code = $req->get('code', '');
	    $state = $req->get('state', '');
	    if( strcmp($state, $sess->wx_state) != 0 ) {
	        $this->redirect('/');
	        return false;
	    }
	    $sess->del('wx_state');
	    
	    $conf = Yaf_Application::app()->getConfig();
	    $appid = $conf->wx->appid;
	    $appkey = $conf->wx->appkey;
	    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	    //$log = new F_Helper_Log();
	    //$log->debug("wx_login:\r\n");
	    
	    //获取access_token
	    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appkey}&code={$code}&grant_type=authorization_code";
	    $curl = new F_Helper_Curl();
	    $rs = $curl->request($url);
	    $arr1 = json_decode($rs, true);
	    if( ! $arr1 || isset($arr1['errmsg']) ) {
	        //$log->debug($rs.$url."\r\n");
	        $params = array();
	        if( $arr1['errcode'] == -1 ) {
	            $params['errmsg'] = '微信服务器繁忙，请稍后再试！';
	        }
	        $this->forward('user', 'login', $params);
	        return false;
	    }
	    
	    /*{
	        "access_token":"ACCESS_TOKEN",
	        "expires_in":7200,
	        "refresh_token":"REFRESH_TOKEN",
	        "openid":"OPENID",
	        "scope":"SCOPE",
	        "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
	    }*/
	    
	    //判断用户是否已经使用微信登录过平台
	    $m_user = new UsersModel();
	    $time = time();
	    $user = $m_user->fetch("app='wx' AND openid='{$arr1['openid']}'", 'user_id,username,nickname,email,expires');
	    if( $user && $user['expires'] > $time ) {
	        $arr1['expires'] = $arr1['expires_in'] + $time;
	        $err = $m_user->oAuthLogin('wx', $arr1);
	        if( $err ) {
	            //$log->debug($err."\r\n");
    	        $this->forward('user', 'login', array('errmsg'=>$err));
    	        return false;
	        } else {
	            $this->redirect($fwd);
	            return false;
	        }
	    }
	    
	    //获取用户信息
	    $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$arr1['access_token']}&openid={$arr1['openid']}&lang=zh_CN";
	    $rs = $curl->request($url);
	    $arr2 = json_decode($rs, true);
	    if( ! $arr2 || isset($arr2['errmsg']) ) {
	        //$log->debug($rs.$url."\r\n");
	        $this->redirect('/user/login.html');
	        return false;
	    }
	    
	    $arr2['access_token'] = $arr1['access_token'];
	    $arr2['expires'] = $arr1['expires_in'] + $time;
	    $arr2['avatar'] = preg_replace('/\/\d{1,3}$/', '', $arr2['headimgurl']);
	    $err = $m_user->oAuthLogin('wx', $arr2);
	    if( $err ) {
	        //$log->debug($err."\r\n");
	        $this->forward('user', 'login', array('errmsg'=>$err));
	    } else {
	        $this->redirect($fwd);
	    }
	    
	    /*{
           "openid":" OPENID",
           " nickname": NICKNAME,
           "sex":"1",
           "province":"PROVINCE"
           "city":"CITY",
           "country":"COUNTRY",
            "headimgurl": "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46", 
        	"privilege":[
        	"PRIVILEGE1"
        	"PRIVILEGE2"
            ],
            "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
        }*/
	    //头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像
	    
	    return false;
	}

    //不同环境下获取真实的IP
    function getIp(){
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if(getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else $ip = "Unknow";
        return $ip;
    }
}
