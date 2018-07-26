<?php

class GameinfoController extends Yaf_Controller_Abstract
{
    private $_menu = array(
        '游戏总览' => array('icon'=>'i_show', 'action'=>'index'),
        '游戏信息' => array(
            array('name'=>'基本信息', 'action'=>'baseinfo'),
            array('name'=>'图片素材', 'action'=>'imginfo'),
            array('name'=>'API接入', 'action'=>'apiinfo'),
            array('name'=>'先行测试', 'action'=>'test'),
            'icon' => 'i_game',
        ),
        '申请上线' => array('icon'=>'i_online', 'action'=>'apply'),
    );
    
    //游戏ID
    private $_gid;
    //开发者登录信息
    private $_dev;
    /**
     * @var DeveloperModel
     */
    private $m_dev;
    /**
     * @var DevgamesModel
     */
    private $m_devgms;
    
    /**
     * 获取游戏ID、开发者登录信息，并检查权限
     */
    public function init()
    {
        $req = $this->getRequest();
        $this->_gid = $req->get('game_id', 0);
        if( $this->_gid < 1 ) {
            $this->redirect('/game/index.html');
            exit;
        }
        
        $this->m_dev = F_Model_Pdo::getInstance('Developer');
        $this->_dev = $this->m_dev->getLogin();
        if( empty($this->_dev) ) {
            $this->redirect('/developer/login.html?fwd=/developer/index.html');
            exit;
        }
        
        $this->m_devgms = new DevgamesModel();
        $has = $this->m_devgms->fetch("dev_id='{$this->_dev['dev_id']}' AND game_id='{$this->_gid}'", 'game_id,name,servers,status,message');
        if( empty($has) || empty($has['game_id']) ) {
            $this->redirect('/game/index.html');
            exit;
        }
        $assign['game_id'] = $this->_gid;
        $assign['game_name'] = $has['name'];
        $assign['servers'] = $has['servers'];
        $assign['status'] = $has['status'];
        $assign['message'] = $has['message'];
        
        $assign['games'] = $this->m_devgms->fetchAll("dev_id='{$this->_dev['dev_id']}'", 1, 50, 'game_id,name', 'game_id DESC');
        $assign['menu'] = $this->_menu;
        $assign['menu_a'] = $req->getActionName();
        
        $this->getView()->assign($assign);
    }
    
    //游戏概况
    public function indexAction()
    {
        $m_game = new GameModel();
        $info = $m_game->fetch("game_id='{$this->_gid}'", 'logo,sign_key');
        
        $v = $this->getView();
        if( $v->status == 9 ) {
            $ymd_b = date('Ymd', strtotime('-30 days'));
            $ymd_e = date('Ymd');
            $m_stat = new StatbygameModel();
            $info['stats'] = $m_stat->fetchAll("game_id='{$this->_gid}' AND ymd BETWEEN {$ymd_b} AND {$ymd_e}", 1, 30,
                'ymd,signon_people,recharge_people,recharge_money', 'ymd ASC');
        }
        
        $v->assign($info);
    }
    
    //基本信息
    public function baseinfoAction()
    {
        $m_game = new GameModel();
        $info = $m_game->fetch("game_id='{$this->_gid}'", 'game_id,name,classic,version,in_short,screen,sign_key,add_time');
        $info['screen'] = $m_game->_screens[$info['screen']];
        $this->getView()->assign('info', $info);
    }
    //编辑基本信息
    public function editinfoAction()
    {
        $req = $this->getRequest();
        $m_game = new GameModel();
        
        if( $req->isPost() && $_POST ) {
            $data = array(
                'classic' => $req->getPost('classic', ''),
                'screen' => $req->getPost('screen', ''),
                'version' => $req->getPost('version', ''),
                'in_short' => $req->getPost('in_short', ''),
            );
            if( ! in_array($data['classic'], $m_game->_classic) ) {
                unset($data['classic']);
            }
            if( ! array_key_exists($data['screen'], $m_game->_screens) ) {
                unset($data['screen']);
            }
            $encoding = mb_internal_encoding();
            mb_internal_encoding('UTF-8');
            $data['version'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['version'], 0, 16));
            $data['in_short'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['in_short'], 0, 255));
            $data['details'] = $req->getPost('details', '');
            mb_internal_encoding($encoding);
            
            $rs = $m_game->update($data, "game_id='{$this->_gid}'");
            //更新状态
            if( $rs && $this->getView()->status < 1 ) {
                if( $data['version'] && $data['in_short'] ) {
                    $this->m_devgms->update(array('status'=>1), "game_id='{$this->_gid}'");
                }
            }
            
            $this->redirect('/gameinfo/baseinfo.html?game_id='.$this->_gid);
            return false;
        } else {
            $assign['info'] = $m_game->fetch("game_id='{$this->_gid}'", 'game_id,name,classic,version,in_short,details,screen,sign_key');
            $assign['classic'] = $m_game->_classic;
            $assign['screens'] = $m_game->_screens;
            $assign['menu_a'] = 'baseinfo';
            $this->getView()->assign($assign);
        }
    }
    
    public function imginfoAction()
    {
        $conds = "game_id='{$this->_gid}'";
        $m_game = new GameModel();
        $info = $m_game->fetch($conds, 'game_id,logo,screenshots');
        $info['screenshots'] = empty($info['screenshots']) ? array() : unserialize($info['screenshots']);
        
        $up_arr = null;
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        if( isset($_FILES['logo']) && $_FILES['logo']['size'] > 0 && $_FILES['logo']['error'] == 0 ) {
            $size = getimagesize($_FILES['logo']['tmp_name']);
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
                //删除原来的LOGO
                if( $info['logo'] ) {
                    $file = explode('?', $info['logo']);
                    $file = str_replace('http://', '', $file[0]);
                    $file = substr($file, strpos($file, '/'));
                    $file = APPLICATION_PATH.'/public'.$file;
                    @unlink($file);
                }
                
                $path = '/game/logo/';
                $path .= "{$this->_gid}.{$ext}";
                $dst = APPLICATION_PATH.'/public'.$path;
                $rs = move_uploaded_file($_FILES['logo']['tmp_name'], $dst);
                if( ! $rs ) {
                    return '不是有效的上传文件，请重新上传！';
                }
                $path .= '?'.time();
                $path = "http://{$domain}{$path}";
                $up_arr['logo'] = $path;
                $info['logo'] = $path;
            }
        }
        if( isset($_FILES['ss']) && count($_FILES['ss']['tmp_name']) > 0 ) {
            foreach ($_FILES['ss']['tmp_name'] as $idx=>$tmp_name)
            {
                $error = $_FILES['ss']['error'][$idx];
                $size = $_FILES['ss']['size'][$idx];
                $idx += 1;
                if( $error == 0 && $size > 0 ) {
                    $img = getimagesize($tmp_name);
                    if( ! $img ) {
                        continue;
                    }
                    $ext = '';
                    switch ($img[2])
                    {
                        case 1: $ext = 'gif'; break;
                        case 2: $ext = 'jpg'; break;
                        case 3: $ext = 'png'; break;
                        default: break;
                    }
                    if( $ext == '' ) {
                        continue;
                    }
                    //删除原来的截图
                    if( ! empty($info['screenshots'][$idx]) ) {
                        $file = explode('?', $info['screenshots'][$idx]);
                        $file = str_replace('http://', '', $file[0]);
                        $file = substr($file, strpos($file, '/'));
                        $file = APPLICATION_PATH.'/public'.$file;
                        @unlink($file);
                    }
                    
                    $path = '/game/screenshots/';
                    $path .= "{$this->_gid}-{$idx}.{$ext}";
                    $dst = APPLICATION_PATH.'/public'.$path;
                    $rs = move_uploaded_file($tmp_name, $dst);
                    if( ! $rs ) {
                        continue;
                    }
                    $path .= '?'.time();
                    $path = "http://{$domain}{$path}";
                    $info['screenshots'][$idx] = $path;
                    $up_arr['screenshots'] = true;
                }
            }
        }
        if( isset($up_arr['screenshots']) ) {
            for($i = 1; $i < 6; $i++)
            {
                if( empty($info['screenshots'][$i]) ) {
                    $info['screenshots'][$i] = '';
                }
            }
            $up_arr['screenshots'] = serialize($info['screenshots']);
        }
        if( $up_arr ) {
            $m_game->update($up_arr, $conds);
        }
        //更新状态
        if( $this->getView()->status < 2 ) {
            if( isset($up_arr['logo']) && isset($up_arr['screenshots']) ) {
                $this->m_devgms->update(array('status'=>2), $conds);
            }
        }
        
        $this->getView()->assign($info);
    }
    
    //API信息
    public function apiinfoAction()
    {
        $m_game = new GameModel();
        $info = $m_game->fetch("game_id='{$this->_gid}'", 'game_id,name AS game_name,login_url,recharge_url,prepay,sign_key');
        
        //API文档链接
        $m_adpos = new AdposModel();
        $info['doc'] = $m_adpos->getByCode('game_api', 2);
        
        $this->getView()->assign($info);
    }
    //编辑API信息
    public function editapiAction()
    {
        $req = $this->getRequest();
        $m_game = new GameModel();
        
        if( $req->isPost() && $_POST ) {
            $data = array(
                'prepay' => $req->getPost('prepay', 0) ? 1 : 0,
                'login_url' => $req->getPost('login_url', ''),
                'recharge_url' => $req->getPost('recharge_url', ''),
            );
            $data['login_url'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['login_url'], 0, 128));
            $data['recharge_url'] = preg_replace('/[\'\"\\\]+/', '', mb_substr($data['recharge_url'], 0, 128));
            
            $rs = $m_game->update($data, "game_id='{$this->_gid}'");
            
            //更新状态
            if( $rs && $this->getView()->status < 3 ) {
                if( $data['login_url'] ) {
                    $this->m_devgms->update(array('status'=>3), "game_id='{$this->_gid}'");
                }
            }
            
            $this->redirect('/gameinfo/apiinfo.html?game_id='.$this->_gid);
            return false;
        } else {
            $info = $m_game->fetch("game_id='{$this->_gid}'", 'game_id,login_url,recharge_url,prepay,sign_key');
            $info['menu_a'] = 'apiinfo';
            
            //API文档链接
            $m_adpos = new AdposModel();
            $info['doc'] = $m_adpos->getByCode('game_api', 2);
            
            $this->getView()->assign($info);
        }
    }
    
    //申请审核
    public function applyAction()
    {
        $req = $this->getRequest();
        $v = $this->getView();
        if( $req->isPost() && in_array($v->status, array(3,5,6,9)) ) {
            $conds = "dev_id='{$this->_dev['dev_id']}'";
            $apply = $req->getPost('apply', '') != '' ? 1 : 0;
            $online = $req->getPost('online', '') != '' ? 1 : 0;
            //$offline = $req->getPost('offline', '') != '' ? 1 : 0;
            
            if( $apply && in_array($v->status, array(3,5)) ) {
                $v->status = 4;
                $v->message = '已提交审核，我们的工作人员一般会在1-3个工作日内处理您的申请，请耐心等待。';
                //更新数量信息
                $this->m_dev->update("check_nums=`check_nums`+1", $conds);
                $this->m_devgms->update(array('status'=>$v->status, 'message'=>$v->message), "game_id='{$this->_gid}'");
            } elseif( $online && $v->status == 6 ) {
                $v->status = 9;
                $v->message = '';
                //上线
                $m_game = new GameModel();
                $m_game->online($this->_gid);
                //更新数量信息
                $this->m_dev->update("on_nums=on_nums+1,off_nums=off_nums-1", $conds);
                $this->m_devgms->update(array('status'=>$v->status, 'message'=>$v->message), "game_id='{$this->_gid}'");
            }/* elseif( $offline && $v->status == 9 ) {
                $v->status = 6;
                $v->message = '';
                //下线
                $m_game = new GameModel();
                $m_game->offline($this->_gid);
                //更新数量信息
                $this->m_dev->update("on_nums=on_nums-1,off_nums=off_nums+1", $conds);
                $this->m_devgms->update(array('status'=>$v->status, 'message'=>$v->message), "game_id='{$this->_gid}'");
            }*/
        }
        
        if( $v->message == '' ) {
            if( $v->status == 4 ) {
                $v->message = '已提交审核，我们的工作人员一般会在1-3个工作日内处理您的申请，请耐心等待。';
            } elseif( $v->status == 9 ) {
                $v->message = '游戏已上线运营，休息一下，准备好数钱...';
            } elseif( $v->status == 6 ) {
                $v->message = '游戏已审核通过，随时可以上线运营。';
            } elseif( $v->status < 3 ) {
                $v->message = '请先完善游戏资料，再提交审核。';
            }
        }
    }
    
    //先行测试
    public function testAction()
    {
        $m_game = new GameModel();
        $info = $m_game->fetch("game_id='{$this->_gid}'", 'game_id,login_url,recharge_url,prepay,sign_key');
        
        //登录地址
        $info['game_url'] = $info['login_url'] ?
            Game_Login::redirect($this->_dev['dev_id'], $this->_dev['username'], $info['game_id'], 0, $info['login_url'], $info['sign_key'])
            : '';
        
        $this->getView()->assign($info);
    }
    //先行支付
    public function prepayAction()
    {
        $req = $this->getRequest();
        $money = $req->getPost('money', 0);
        if( ! $req->isXmlHttpRequest() || $money < 1 ) {
            exit;
        }
        
        $m_game = new GameModel();
        $info = $m_game->fetch("game_id='{$this->_gid}'", 'game_id,name,recharge_url,prepay,sign_key');
        if( empty($info['prepay']) ) {
            exit("《{$info['name']}》已被设置为不能通过平台充值，请在游戏中发起充值。");
        }
        
        $view = $this->getView();
        $m_pay = new PayModel();
        $time = time();
        $data = array(
            'pay_id' => $m_pay->createPayId(),
            'user_id' => $this->_dev['dev_id'],
            'username' => $this->_dev['username'],
            'to_uid' => $this->_dev['dev_id'],
            'to_user' => $this->_dev['username'],
            'game_id' => $this->_gid,
            'game_name' => $view->game_name,
            'server_id' => 0,
            'server_name' => '',
            'money' => $money,
            'deposit' => 0,
            'type' => 'deposit',
            'cp_order' => '',
            'add_time' => $time,
            'pay_time' => $time,
        );
        $rs = $m_pay->insert($data, false);
        if( ! $rs ) {
            exit('订单创建失败，请稍后重试！');
        }
        
        $err = Game_Recharge::notify($info['recharge_url'], $info['sign_key'], $data);
        if( $err ) {
            exit($err);
        }
        $m_pay->update(array('finish_time'=>$time), "pay_id='{$data['pay_id']}'");
        exit('success');
    }
}
