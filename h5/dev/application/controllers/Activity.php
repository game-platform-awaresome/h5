<?php

class ActivityController extends Yaf_Controller_Abstract
{
    public function init()
    {
        Yaf_Registry::set('layout', false);
    }
    
	public function indexAction()
	{
		$m_adpos = new AdposModel();
		$activity = $m_adpos->getByCode('game_index_activity', 5);
		
		$this->getView()->assign('activity', $activity);
	}
	
	public function detailAction()
	{
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    
	    $req = $this->getRequest();
	    $act_id = $req->get('act_id', 0);
	    $m_act = new ActivityModel();
	    $act = $m_act->fetch("act_id='{$act_id}'");
	    if( empty($act) ) {
	        $this->redirect('/activity/index.html');
	        return false;
	    }
	    $act['settings'] = empty($act['settings']) ? array() : unserialize($act['settings']);
	    
	    $v = $this->getView();
	    $tpl = $this->getViewpath().'/activity/';
	    $tpl .= $act['controller'].'.phtml';
	    $v->assign($act);
	    $v->assign('user', $user);
	    $v->display($tpl);
	    return false;
	}
	
	public function lotteryAction()
	{
	    
	}
	
	//发放激活码
	public function sendoutAction()
	{
	    $req = $this->getRequest();
	    if( ! $req->isPost() ) {
	        exit;
	    }
	    $gift_id = $req->getPost('gift_id', 0);
	    if( $gift_id < 1 ) {
	        exit;
	    }
	    
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        exit(json_encode(array('msg'=>'还未登录！','cdkey'=>'')));
	    }
	    
	    $cdkey = '';
	    $m_gift = new GiftbagModel();
	    $error = $m_gift->sendout($user['user_id'], $gift_id, $cdkey);
	    if( $error == '' ) {
	        exit(json_encode(array('msg'=>'领取成功，双击激活码可复制！','cdkey'=>$cdkey)));
	    } else {
	        exit(json_encode(array('msg'=>$error,'cdkey'=>'')));
	    }
	}
	
	//礼包中心
	public function giftbagAction()
	{
	    
	}
	//礼包列表
	public function giftlistAction()
	{
	    $req = $this->getRequest();
	    if( ! $req->isPost() ) {
	        exit;
	    }
	    
	    $pn = $req->getPost('pn', 0);
	    $limit = $req->getPost('limit', 0);
	    if( $pn < 1 || $limit < 1 ) {
	        exit;
	    }
	    
	    $time = time();
	    $offset = ($pn - 1) * $limit;
	    $m_gift = new GiftbagModel();
	    $list = $m_gift->fetchAllBySql("SELECT gb.gift_id,gb.name,gb.type,gb.nums,gb.used, gm.name AS game_name,gm.logo FROM giftbag AS gb 
	        LEFT JOIN game AS gm ON gb.game_id=gm.game_id 
	        WHERE (gb.begin_time=0 OR gb.begin_time<{$time}) AND (gb.end_time=0 OR gb.end_time>{$time}) AND gm.visible=1 
	       ORDER BY gb.weight ASC LIMIT {$offset},{$limit}");
	    if( empty($list) ) {
	        exit;
	    }
	    foreach ($list as $k=>&$row)
	    {
	        if( $row['type'] == 'limited' ) {
	            $row['store'] = $row['nums'] - $row['used'];
	        } else {
	            $row['store'] = '不限';
	        }
	    }
	    if( count($list) >= $limit ) {
	        $m_adpos = new AdposModel();
	        $gap = $m_adpos->getByCode('activity_giftbag_gap', 1);
	    } else {
	        $gap = null;
	    }
	    
	    $this->getView()->assign(array('list'=>$list, 'pn'=>$pn, 'gap'=>$gap));
	}
}
