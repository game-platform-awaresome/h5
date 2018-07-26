<?php
/**
 * 保存渠道ID
 */

class ChannelController extends Yaf_Controller_Abstract
{
    public function indexAction()
    {
        $req = $this->getRequest();
        $params = $req->getParams();
        $fwd = $req->get('fwd', '');
        $fwd = $fwd ? urldecode($fwd) : '/game/index.html';
        if( count($params) != 1 ) {
            $this->redirect('/game/index.html');
            return false;
        }
        foreach ($params as $code=>$null)
        {
            break;
        }
        if( strpos($code, '?') ) {
            $code = substr($code, 0, strpos($code, '?'));
        }
        
        if( empty($code) ) {
            $this->redirect('/game/index.html');
            return false;
        }
        $code = preg_replace('/[^a-z0-9]+/', '', $code);
        
        $m_ch = new TgchannelModel();
        $channel = $m_ch->fetch("code='{$code}'", 'channel_id');
        if( empty($channel) ) {
            $this->redirect('/game/index.html');
            return false;
        }
        
        $sess = Yaf_Session::getInstance();
        $sess->set('channel_id', $channel['channel_id']);
        $expires = time() + (86400 * 7);
        setcookie('channel_id', $channel['channel_id'], $expires, '/');
        
        $this->redirect($fwd);
        return false;
    }
}
