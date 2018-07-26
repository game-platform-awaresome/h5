<?php

class EgretController extends Yaf_Controller_Abstract
{
    public function payAction()
    {
        $req = $this->getRequest();
        $egret = new Game_Channel_Egret();
        $pay = $egret->pay($req);
        
        if( is_string($pay) ) {
            exit($pay);
        }
        
        $subject = $pay['subject'];
        unset($pay['subject']);
        
        if( isset($pay['chnl_gid']) ) {
            $m_gmap = new GamemapModel();
            $map = $m_gmap->fetch("channel='{$pay['channel']}' AND chnl_gid='{$pay['chnl_gid']}'", 'game_id,server_id');
            if( empty($map) ) {
                exit('Game map not set.');
            }
            
            $pay['game_id'] = $map['game_id'];
            $pay['server_id'] = $map['server_id'];
            unset($pay['chnl_gid']);
        }
        
        if( $pay['server_id'] ) {
            $m_server = new ServerModel();
            $server = $m_server->fetch("server_id='{$m_server}'", 'game_name,name');
            $pay['game_name'] = $server['game_name'];
            $pay['server_name'] = $server['name'];
        } else {
            $m_game = new GameModel();
            $game = $m_game->fetch("game_id='{$pay['game_id']}'", 'name');
            $pay['game_name'] = $game['name'];
            $pay['server_name'] = '';
        }
        
        $pay['type'] = 'iapppay';
        $m_pay = new PayModel();
        $pay['pay_id'] = $m_pay->createPayId();
        $rs = $m_pay->insert($pay, false);
        if( ! $rs ) {
            //$log = new F_Helper_Log();
            //$log->debug(var_export($pay, true)."\r\n");
            exit('Server error, please refresh this page.');
        }
        
        //判断用户是否有足够的平台币
        $m_user = new UsersModel();
        $user = $m_user->fetch("user_id='{$pay['user_id']}'", 'money');
        if( $user && $user['money'] >= $pay['money'] ) {
            $this->forward('api', 'gotop', array('pay'=>$pay, 'subject'=>$subject, 'deposit'=>$user['money'], 'return'=>$pay['cp_return']));
            return false;
        }
        
        if( $subject == '' ) {
            $conf = Yaf_Application::app()->getConfig();
            if( $pay['server_id'] ) {
                $subject = "充值到《{$pay['game_name']}，{$pay['server_name']}》 - {$conf['application']['sitename']}";
            } else {
                $subject = "充值到《{$pay['game_name']}》 - {$conf['application']['sitename']}";
            }
        }
        $body = '';
        
        $iapppay = new Pay_Iapppay_Mobile();
        $url = $iapppay->redirect($pay['pay_id'], $pay['money'], $subject, $body, $pay['username']);
        $this->getView()->assign('url', $url);
    }
}
