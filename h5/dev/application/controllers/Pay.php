<?php

class PayController extends Yaf_Controller_Abstract
{
    private $m_pay;
    private $m_user;
    private $user;
    private $pay;
    private $sess;
    
    private $pay_id;
    
    private function initPayInfo()
    {
        $action = $this->getRequest()->getActionName();
        if( $action == 'checkout' ) {
            $pay_id = $this->getRequest()->get('pay_id', '');
            $pay_id = preg_replace('/\D+/', '', $pay_id);
            if( strlen($pay_id) == 16 ) {
                $this->pay = $this->m_pay->fetch("pay_id='{$pay_id}'", 'pay_id,user_id,username,to_uid,to_user,game_id,game_name,server_id,server_name,money,deposit,type,cp_order,cp_return,channel,extra');
                if( $this->pay ) {
                    $this->pay_id = $pay_id;
                    return $this->pay;
                }
            }
        }
        
        $this->pay = $this->sess->get('pay_info');
        if( empty($this->pay) || $action == 'index' ) {
            $this->pay = array(
                'pay_id' => $this->m_pay->createPayId(),
                'user_id' => $this->user['user_id'],
                'username' => $this->user['username'],
                'to_uid' => $this->user['user_id'],
                'to_user' => $this->user['username'],
                'game_id' => 0,
                'game_name' => '',
                'server_id' => 0,
                'server_name' => '',
                'money' => 0,
                'deposit' => 0,
                'type' => '',
                'cp_order' => '',
                'tg_channel' => $this->user['channel_id'],
            );
            $this->sess->set('pay_info', $this->pay);
        }
        return $this->pay;
    }
    
    private function setPayInfo($arr)
    {
        $this->pay = array_merge($this->pay, $arr);
        $this->sess->set('pay_info', $this->pay);
    }
    private function unsetPayInfo()
    {
        $this->pay = null;
        $this->sess->set('pay_info', null);
    }
    
    public function init()
    {
        Yaf_Registry::set('layout', false);
        $this->sess = Yaf_Session::getInstance();
        
        $this->m_user = new UsersModel();
        $this->user = $this->m_user->getLogin();
        $action = $this->getRequest()->getActionName();
        if( empty($this->user) && $action != 'result' && $action != 'checkout' ) {
            $fwd = isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : '/pay/index.html';
            $this->redirect('/user/login.html?fwd='.$fwd);
            return false;
        }
        $this->m_pay = new PayModel();
        
        $this->initPayInfo();
        /*if( empty($this->pay['to_user']) ) {
            $this->pay['to_user'] = $this->user['username'];
            $this->sess->set('pay_info', $this->pay);
        }*/
        $this->getView()->assign('pay', $this->pay);
    }
    
    //选择充值到平台还是游戏
    public function indexAction()
    {
        
    }
    
    //充值到平台
	public function depositAction()
	{
	    $to_user = $this->getRequest()->get('to_user', '');
	    $set_arr = array();
	    if( $to_user ) {
	        $to_user = preg_replace('/[^\w@\-\.]+/', '', $to_user);
	        $user = $this->m_user->fetch("username='{$to_user}'", 'user_id');
	        if( empty($user) ) {
	            $this->redirect('/pay/index.html');
	        }
	        $set_arr = array('to_uid'=>$user['user_id'], 'to_user'=>$to_user);
	    }
	    
	    $set_arr = array_merge($set_arr, array('game_id'=>0, 'game_name'=>'', 'server_id'=>0, 'server_name'=>''));
	    $this->setPayInfo($set_arr);
	    
	    $conf = Yaf_Application::app()->getConfig();
	    $this->getView()->assign(array('parities'=>$conf->application->parities, 'pay'=>$this->pay));
	}

	//选择要充值的游戏
	public function gameAction()
	{
	    $to_user = $this->getRequest()->get('to_user', '');
	    if( $to_user ) {
    	    $to_user = preg_replace('/[^\w@\-\.]+/', '', $to_user);
    	    $user = $this->m_user->fetch("username='{$to_user}'", 'user_id');
    	    if( empty($user) ) {
    	        $this->redirect('/pay/index.html');
    	    }
    	    $this->setPayInfo(array('to_uid'=>$user['user_id'], 'to_user'=>$to_user));
	    }
	    
	    //最近在玩
	    $assign['play'] = $this->m_user->getPlayGames($this->user['user_id'], 1, 6);
	    if( $assign['play'] ) {
	        $last = $assign['play'][0]['game_id'];
	        $m_search = new GameSearchModel();
	        $initial = $m_search->fetch("game_id='{$last}'", 'initial');
	        $assign['init_key'] = $initial['initial'];
	    } else {
	        $assign['init_key'] = '';
	    }
	    
	    $assign['initial'] = include APPLICATION_PATH.'/application/cache/game/initial.php';
	    //过滤掉白鹭的游戏
	    foreach ($assign['initial'] as $k1=>&$v)
	    {
	        foreach ($v as $k2=>&$v2)
	        {
	            if( $v2['prepay'] == 0 || $v2['visible'] == 0 ) {
	                unset($v[$k2]);
	            }
	        }
	        if( empty($v) ) {
	            unset($assign['initial'][$k1]);
	        }
	    }
	    
	    $this->getView()->assign($assign);
	}
	
	//选择要充值的区/服
	public function serverAction()
	{
	    $game_id = $this->getRequest()->get('game_id', 0);
	    if( $game_id < 1 ) {
	        $this->redirect('/pay/game.html');
	    }
	    
	    $m_game = new GameModel();
	    $game = $m_game->fetch("game_id='{$game_id}' AND visible=1", 'game_id,name,recharge_url,sign_key');
	    if( empty($game) ) {
	        $this->redirect('/pay/game.html');
	    }
	    
	    $this->setPayInfo(array('game_id'=>$game_id, 'game_name'=>$game['name']));
	    
	    if( $game['recharge_url'] && $game['sign_key'] ) {
	        $this->redirect('/pay/numstype.html?server_id=0&game_id='.$game_id);
	    }
	    
	    $assign['game'] = $game;
	    $assign['play'] = $this->m_user->getPlayServers($this->user['user_id'], $game_id, 1, 1);
	    
	    $m_server = new ServerModel();
	    $assign['server'] = $m_server->fetchAll("game_id='{$game_id}' AND visible=1", 1, 100, 'server_id,name,corner,label', 'weight DESC');
	    $this->getView()->assign($assign);
	}
	
	//选择充值金额及支付方式
	public function numstypeAction()
	{
	    $req = $this->getRequest();
	    $game_id = $req->get('game_id', 0);
	    $server_id = $req->get('server_id', 0);
	    $server_name = '';
	    
	    if( $game_id < 1 || $this->pay['game_id'] < 1 ) {
	        $this->redirect('/pay/game.html');
	    }
	    
	    if( $server_id ) {
	        $m_server = new ServerModel();
	        $server = $m_server->fetch("server_id='{$server_id}'", 'server_id,name');
	        if( $server )
	            $server_name = $server['name'];
	        else
	            $this->redirect("/pay/server.html?game_id={$game_id}");
	    }
	    
	    $this->setPayInfo(array('server_id'=>$server_id, 'server_name'=>$server_name));
	    
	    $user = $this->m_user->fetch("user_id='{$this->pay['user_id']}'", 'money');
	    $deposit = $user['money'];
	    
	    $conf = Yaf_Application::app()->getConfig();
	    $this->getView()->assign(array('parities'=>$conf->application->parities, 'deposit'=>$deposit));
	}
	
	//跳转到第三方支付网站
	public function checkoutAction()
	{
	    $req = $this->getRequest();
	    $money = $req->get('money', 0);
	    $type = $req->get('type', '');
	    
	    $ck_arr = $this->m_pay->_types;
	    $ck_arr['deposit'] = '平台币';
	    if( ($type && ! array_key_exists($type, $ck_arr)) || ($money < 1 && $this->pay['money'] < 1) ) {
	        $this->redirect('/pay/index.html');
	        return false;
	    }
	    
	    $set = null;
	    if( $type ) {
	        $set['type'] = $type;
	    }
	    if( $money > 0 ) {
	        if( $money > 60000 ) {
	            $money = 60000;
	        }
	        $set['money'] = $money;
	    } elseif( $this->pay['money'] > 60000 ) {
	        $set['money'] = 60000;
	    }
	    if( $set ) {
	        $this->setPayInfo($set);
	    }
	    $time = time();
	    
	    if( empty($this->pay_id) ) {
	        $this->pay['trade_no'] = '';
	        $this->pay['add_time'] = $time;
	        $rs = $this->m_pay->insert($this->pay, false);
	        if( ! $rs ) {
	            //$log = new F_Helper_Log();
	            //$log->debug(var_export($this->pay,true)."\r\n");
	            //$log->debug($this->m_pay->error()."\r\n");
	            $this->forward('pay', 'result', array('status'=>'failed', 'message'=>'订单生成失败，请重试！', 'pay'=>$this->pay));
	            $this->setPayInfo(array('pay_id'=>$this->m_pay->createPayId()));
	            return false;
	        }
	    }
	    
	    /*if( $this->pay['game_id'] && $this->pay['type'] == 'deposit' ) {
	        $this->pay['type'] = 'iapppay';
	    }*/
	    
	    //平台币支付
	    if( $this->pay['type'] == 'deposit' ) {
	        $user = $this->m_user->fetch("user_id='{$this->pay['user_id']}'", 'money');
	        $deposit = $user['money'];
	        if( $deposit < $this->pay['money'] ) {
	            $this->forward('pay', 'result', array('status'=>'failed', 'message'=>'平台币不足，无法完成充值！', 'pay'=>$this->pay));
	            return false;
	        }
	        
	        $up_arr = array(
	            'deposit' => $deposit - $this->pay['money'],
	            'pay_time' => $time,
	        );
	        
	        //事务处理
	        $pdo = $this->m_pay->getPdo();
	        $pdo->beginTransaction();
	        $rs1 = $this->m_user->changeMoney($this->pay['user_id'], -$this->pay['money']);
	        $rs2 = $this->m_pay->update($up_arr, "pay_id='{$this->pay['pay_id']}'");
	        if( $rs1 && $rs2 ) {
	            $pdo->commit();
	        } else {
	            $pdo->rollBack();
	        }
	        
	        if( $rs1 && $rs2 ) {
	            //充值到游戏
	            if( $this->pay['server_id'] ) {
	                $m_server = new ServerModel();
	                $server = $m_server->fetch("server_id='{$this->pay['server_id']}'", 'login_url,recharge_url,sign_key,load_type');
	                $login_url = $server['login_url'];
	                $recharge_url = $server['recharge_url'];
	                $sign_key = $server['sign_key'];
	                $load_type = $server['load_type'];
	            } else {
	                $m_game = new GameModel();
	                $game = $m_game->fetch("game_id='{$this->pay['game_id']}'", 'login_url,recharge_url,sign_key,load_type');
	                $login_url = $game['login_url'];
	                $recharge_url = $game['recharge_url'];
	                $sign_key = $game['sign_key'];
	                $load_type = $game['load_type'];
	            }
	            
	            //白鹭不支持平台发起的充值，但在游戏中发起的充值可以使用平台币来支付
	            if( $this->pay['channel'] == 'egret' && $this->pay['cp_return'] ) {
	                $egret = new Game_Channel_Egret();
	                $rs = $egret->notify($recharge_url, $sign_key, $this->pay);
	            } else {
	                $rs = Game_Recharge::notify($recharge_url, $sign_key, $this->pay);
	            }
	            
	            if( $rs == '' ) {
	                $this->m_pay->update(array('finish_time'=>$time), "pay_id='{$this->pay['pay_id']}'");
	                
	                $status = 'success';
	                $message = '您的充值已成功到账！';
	            } else {
	                $status = 'failed';
	                $message = $rs;
	            }
	            
	            if( $this->pay['cp_return'] == 1 ) {
	                $url = Game_Login::redirect($this->pay['to_uid'], $this->pay['to_user'], $this->pay['game_id'], $this->pay['server_id'], $login_url, $sign_key);
	                $this->forward('game', 'entry', array('game_name'=>$this->pay['game_name'], 'url'=>$url, 'load_type'=>$load_type));
	                return false;
	            } elseif( $this->pay['cp_return'] ) {
	                //跳回白鹭游戏
	                if( $this->pay['channel'] == 'egret' ) {
	                    $this->forward('game', 'entry', array('game_name'=>$this->pay['game_name'], 'url'=>$this->pay['cp_return'], 'load_type'=>$load_type));
	                    return false;
	                }
	                $url = $this->pay['cp_return'];
	                $game_name = $this->pay['game_name'];
	                unset($this->pay['cp_return'], $this->pay['channel']);
	                unset($this->pay['game_name'], $this->pay['server_name']);
	                unset($this->pay['deposit'], $this->pay['type']);
	                
	                $comma = '';
	                if( strpos($url, '?') !== false ) {
	                    $url = trim($url, '&');
	                    $comma = '&';
	                } else {
	                    $comma = '?';
	                }
	                
	                $this->pay['time'] = time();
	                ksort($this->pay);
	                $this->pay['sign'] = md5(implode('', $this->pay).$sign_key);
	                
	                $this->pay['username'] = urlencode($this->pay['username']);
	                $this->pay['to_user'] = urlencode($this->pay['to_user']);
	                $query = '';
	                foreach ($this->pay as $k=>$v)
	                {
	                    $url .= "{$comma}{$k}={$v}";
	                    $comma = '&';
	                }
	                
	                $this->forward('game', 'entry', array('game_name'=>$game_name, 'url'=>$url, 'load_type'=>$load_type));
	                return false;
	            }
	            
	            $this->forward('pay', 'result', array('status'=>$status, 'message'=>$message, 'pay'=>$this->pay));
	        } else {
	            if( $this->pay['cp_return'] ) {
	                if( $this->pay['server_id'] ) {
	                    $m_server = new ServerModel();
	                    $server = $m_server->fetch("server_id='{$this->pay['server_id']}'", 'load_type');
	                    $load_type = $server['load_type'];
	                } else {
	                    $m_game = new GameModel();
	                    $game = $m_game->fetch("game_id='{$this->pay['game_id']}'", 'load_type');
	                    $load_type = $game['load_type'];
	                }
	                $this->forward('game', 'entry', array('game_name'=>$this->pay['game_name'], 'url'=>$this->pay['cp_return'], 'load_type'=>$load_type));
	            } else {
	                $this->forward('pay', 'result', array('status'=>'failed', 'message'=>'充值失败，请重试！', 'pay'=>$this->pay));
	            }
	        }
	        $this->unsetPayInfo();
	        return false;
	    }
	    
	    $conf = Yaf_Application::app()->getConfig();
	    if( $this->pay['game_id'] == 0 ) {
    	    $subject = "充值到《{$conf['application']['sitename']}》";
	    } else {
	        if( $this->pay['server_id'] ) {
	            $subject = "充值到《{$this->pay['game_name']}，{$this->pay['server_name']}》 - {$conf['application']['sitename']}";
	        } else {
	            $subject = "充值到《{$this->pay['game_name']}》 - {$conf['application']['sitename']}";
	        }
	    }
	    $body = '';
	    
//	    if( $this->pay['type'] == 'iapppay' ) {
//	        $class = new Pay_Iapppay_Mobile();
//	        $url = $class->redirect($this->pay['pay_id'], $this->pay['money'], $subject, $body, $this->pay['to_user']);
//	        header("Location: {$url}");
//            throw new Exception('非法操作');
//	    } elseif( $this->pay['type'] == 'alipay' ) {
//	        $class = new Pay_Alipay_Mobile();
//	        $class->redirect($this->pay['pay_id'], $this->pay['money'], $subject, $body);
//	    } elseif( $this->pay['type'] == 'wxpay' ) {
//
//	    }
        $class = new Pay_Pigpay_Mobile();
        $class->redirect($this->pay);
	    $this->unsetPayInfo();
	    return false;
	}
	
	//前台返回
	public function resultAction()
	{
	    $req = $this->getRequest();
	    $status = $req->getParam('status', '');
	    $message = $req->getParam('message', '');
	    if( $status && $message ) {
	        $m_adpos = new AdposModel();
	        $banner = $m_adpos->getByCode('pay_center_banner', 1);
	        
	        $this->getView()->assign(array('status'=>$status, 'message'=>$message, 'banner'=>$banner));
	        return true;
	    }
	    
	    //iapppay返回
	    if( isset($_GET['transdata']) ) {
	        $class = new Pay_Iapppay_Mobile();
	        $rs = $class->result();
	    }
	    
	    //处理来自游戏的直充，直充需要返回到之前的地址
	    if( $rs ) {
	        $pay = $this->m_pay->fetch("pay_id='{$rs['pay_id']}'", 'pay_id,user_id,username,to_uid,to_user,game_id,server_id,game_name,money,cp_order,cp_return,channel,extra');
	    }
	    if( isset($pay) && $pay['channel'] == 'egret' ) {
	        if( $pay['server_id'] ) {
	            $m_server = new ServerModel();
	            $server = $m_server->fetch("server_id='{$pay['server_id']}'", 'load_type');
	            $load_type = $server['load_type'];
	        } else {
	            $m_game = new GameModel();
	            $game = $m_game->fetch("game_id='{$pay['game_id']}'", 'load_type');
	            $load_type = $game['load_type'];
	        }
	        $this->forward('game', 'entry', array('game_name'=>$pay['game_name'], 'url'=>$pay['cp_return'], 'load_type'=>$load_type));
            return false;
	    }
	    if( isset($pay) && $pay['cp_return'] ) {
	        if( $pay['server_id'] ) {
	            $m_server = new ServerModel();
	            $server = $m_server->fetch("server_id='{$pay['server_id']}'", 'login_url,sign_key,load_type');
	            $login_url = $server['login_url'];
	            $sign_key = $server['sign_key'];
	            $load_type = $server['load_type'];
	        } else {
	            $m_game = new GameModel();
	            $game = $m_game->fetch("game_id='{$pay['game_id']}'", 'login_url,sign_key,load_type');
	            $login_url = $game['login_url'];
	            $sign_key = $game['sign_key'];
	            $load_type = $game['load_type'];
	        }
	        
	        if( $pay['cp_return'] == 1 ) {
	            $url = Game_Login::redirect($pay['user_id'], $pay['username'], $pay['game_id'], $pay['server_id'], $login_url, $sign_key);
	            $this->forward('game', 'entry', array('game_name'=>$pay['game_name'], 'url'=>$url, 'load_type'=>$load_type));
	            return false;
	        } elseif( ! $rs['result'] ) {
	            $this->forward('game', 'entry', array('game_name'=>$pay['game_name'], 'url'=>$pay['cp_return'], 'load_type'=>$load_type));
	            return false;
	        } else {
	            //如果回跳地址已经包含了验证信息
	            if( preg_match('/[&\?]sign=[^&]+/', $pay['cp_return']) ) {
	                $this->forward('game', 'entry', array('game_name'=>$pay['game_name'], 'url'=>$pay['cp_return'], 'load_type'=>$load_type));
	                return false;
	            }
	            
	            $url = $pay['cp_return'];
	            $game_name = $pay['game_name'];
	            unset($pay['cp_return'], $pay['channel'], $pay['game_name']);
	            
	            $comma = '';
	            if( strpos($url, '?') !== false ) {
	                $url = trim($url, '&');
	                $comma = '&';
	            } else {
	                $comma = '?';
	            }
	            
	            $pay['time'] = time();
	            ksort($pay);
	            $pay['sign'] = md5(implode('', $pay).$sign_key);
	            
	            $pay['username'] = urlencode($pay['username']);
	            $pay['to_user'] = urlencode($pay['to_user']);
	            $query = '';
	            foreach ($pay as $k=>$v)
	            {
	                $url .= "{$comma}{$k}={$v}";
	                $comma = '&';
	            }
	            
	            $this->forward('game', 'entry', array('game_name'=>$game_name, 'url'=>$url, 'load_type'=>$load_type));
	            return false;
	        }
	    }
	    
	    if( is_array($rs) && $rs['result'] ) {
	        $status = 'success';
	        $message = '您的充值已成功到账！';
	    } else {
	        $status = 'failed';
	        $message = '充值失败，请重试！';
	    }
	    
	    $m_adpos = new AdposModel();
	    $banner = $m_adpos->getByCode('pay_center_banner', 1);
	    
	    $this->getView()->assign(array('status'=>$status, 'message'=>$message, 'banner'=>$banner));
	}
}
