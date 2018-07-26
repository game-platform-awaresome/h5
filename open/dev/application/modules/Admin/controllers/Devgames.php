<?php

class DevgamesController extends F_Controller_Backend
{
    private $_user_id = 2048235813;
    private $_username = 'admin';
    
    public function init()
    {
        parent::init();
        $this->_view->assign('op_stt', $this->_model->_op_stt);
    }
    
    protected function beforeList()
    {
        $params = parent::beforeList();
        if( empty($params['conditions']) ) {
            $params['conditions'] = 'status BETWEEN 4 AND 9';
        } else {
            if( strpos($params['conditions'], 'status') === false ) {
                $params['conditions'] .= ' AND status BETWEEN 4 AND 9';
            }
        }
        $params['orderby'] = 'game_id ASC';
        $params['op'] = F_Helper_Html::Op_Edit;
        return $params;
    }
    
    protected function beforeEdit(&$info)
    {
        if( $info['status'] == 4 ) {
            $m_game = F_Model_Pdo::getInstance('Game');
            $game = $m_game->fetch("game_id='{$info['game_id']}'", 'login_url,recharge_url,prepay,sign_key');
            if( empty($game['login_url']) ) {
                $info['login'] = '还未设置登录接口地址！';
            } else {
                $url = Game_Login::redirect($this->_user_id, $this->_username, $info['game_id'], 0, $game['login_url'], $game['sign_key']);
                $info['login'] = '<a target="_blank" href="/admin/devgames/entry.html?game_id=';
                $info['login'] .= $info['game_id'].'">';
                $info['login'] .= '<img src="/qrcode/game.html?addr=';
                $info['login'] .= urlencode($url).'"/>&nbsp;点击进入游戏或使用手机扫描进行测试';
                $info['login'] .= '</a>';
            }
            if( empty($game['prepay']) ) {
                $info['recharge'] = '游戏不支持平台发起充值，请进入游戏后在游戏内发起充值。';
            } elseif( empty($game['recharge_url']) ) {
                $info['recharge'] = '游戏未设置充值接口地址！';
            } else {
                $info['recharge'] = '<input type="text" class="text-input small-input" style="width:85px !important" id="money" placeholder="请输入充值金额，最小为1元" value="1" maxlength="2">';
                $info['recharge'] .= '&nbsp;<input type="button" value="充入游戏" id="recharge" class="button">';
            }
        }
    }
    
    protected function beforeUpdate($id, &$info)
    {
        //游戏状态切换时，更新相关游戏的数量
        //只允许修改以下状态码：4，5，6，9
        $old = $this->_model->fetch("game_id={$id}", 'dev_id,status');
        if( $old['status'] != $info['status'] && in_array($info['status'], array(4,5,6,9)) ) {
            $on = '';
            $off = '';
            $ck = '';
            $up_str = '';
            if( in_array($old['status'], array(3,5)) ) {
                if( $info['status'] == 4 ) {
                    $ck = 'check_nums=check_nums+1';
                }
            } elseif( $old['status'] == 4 ) {
                if( in_array($info['status'], array(5,6)) ) {
                    $ck = 'check_nums=check_nums-1';
                }
            } elseif( $old['status'] == 6 ) {
                if( $info['status'] == 9 ) {
                    $on = 'on_nums=on_nums+1';
                    $off = 'off_nums=off_nums-1';
                    $up_str = 'visible=1';
                }
            } elseif( $old['status'] == 9 ) {
                if( in_array($info['status'], array(5,6)) ) {
                    $on = 'on_nums=on_nums-1';
                    $off = 'off_nums=off_nums+1';
                    $up_str = 'visible=0';
                }
            }
            $data = null;
            if( $on ) $data['on_nums'] = $on;
            if( $off ) $data['off_nums'] = $off;
            if( $ck ) $data['check_nums'] = $ck;
            if( $data ) {
                $m_dev = new DeveloperModel();
                $rs = $m_dev->update(implode(',', $data), "dev_id={$old['dev_id']}");
                if( ! $rs ) {
                    return '更新游戏数量时失败，请重试！';
                }
            } else {
                unset($info['status']); //不更新状态码
            }
            if( $up_str ) {
                $m_game = F_Model_Pdo::getInstance('Game');
                $pdo = $m_game->getPdo();
                $m_game->update($up_str, "game_id={$id}");
                $pdo->exec("UPDATE game_search SET {$up_str} WHERE game_id={$id}");
            }
        } else {
            unset($info['status']); //不更新状态码
        }
        return '';
    }
    
    public function deleteAction()
    {
        exit;
    }
    
    //登录测试
    public function entryAction()
    {
        Yaf_Registry::set('layout', false);
        
        $req = $this->getRequest();
        $game_id = $req->get('game_id', 0);
        if( $game_id ) {
            $devgms = $this->_model->fetch("game_id='{$game_id}'", 'status');
            if( $devgms['status'] != 4 ) {
                return false;
            }
            
            $m_game = new GameModel();
            $params = $m_game->fetch("game_id='{$game_id}'", 'name AS game_name,login_url,sign_key,load_type');
            if( $params ) {
                $params['url'] = Game_Login::redirect($this->_user_id, $this->_username, $game_id, 0, $params['login_url'], $params['sign_key']);
                unset($params['login_url'], $params['sign_key']);
            }
        } else {
            $params = array();
        }
        if( count($params) != 3 || empty($params['game_name']) || empty($params['url']) || empty($params['load_type']) ) {
            return false;
        }
        $params['mobile'] = $this->isMobile();
        $this->getView()->assign($params);
    }
    
    private function isMobile()
    {
        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
         
        $mobilebrowser =array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
            'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
            'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
            'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
            'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
            'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
            'benq', 'haier', '^lct', '320x320', '240x320', '176x220');
        $mobilebrowser[] = 'pad';
        $mobilebrowser[] = 'gt-p1000';
        foreach ($mobilebrowser as $mb)
        {
            if( strpos($useragent, $mb) !== false ) {
                return true;
            }
        }
        return false;
    }
    
    //支付测试
    public function prepayAction()
    {
        $req = $this->getRequest();
        $game_id = $req->get('game_id', 0);
        $money = $req->getPost('money', 0);
        if( ! $req->isXmlHttpRequest() || $money < 1 || $game_id < 1 ) {
            exit;
        }
        
        $devgms = $this->_model->fetch("game_id='{$game_id}'", 'status');
        if( $devgms['status'] != 4 ) {
            exit('游戏不在测试状态，无法进行充值操作。');
        }
        
        $m_game = new GameModel();
        $info = $m_game->fetch("game_id='{$game_id}'", 'game_id,name,recharge_url,prepay,sign_key');
        if( empty($info['prepay']) ) {
            exit("《{$info['name']}》已被设置为不能通过平台充值，请在游戏中发起充值。");
        }
        
        $m_pay = new PayModel();
        $time = time();
        $data = array(
            'pay_id' => $m_pay->createPayId(),
            'user_id' => $this->_user_id,
            'username' => $this->_username,
            'to_uid' => $this->_user_id,
            'to_user' => $this->_username,
            'game_id' => $game_id,
            'game_name' => $info['name'],
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
