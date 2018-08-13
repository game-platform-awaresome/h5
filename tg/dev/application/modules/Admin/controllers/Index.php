<?php
/**
 * @name IndexController
 * @desc 默认控制器
 */
class IndexController extends Yaf_Controller_Abstract
{
	private $m_admin;
	private $a_info;
	
	public function init()
	{
		Yaf_Registry::set('layout', 0);
		
		$this->m_admin = new AdminModel();
		$this->a_info = $this->m_admin->getLogin();
	}
	
	public function indexAction()
	{
		if( $this->a_info ) {
			$this->forward('main');
			return false;
		}
		$error = Yaf_Registry::get('error');
		if( $error ) {
			$this->getView()->assign('error', $error);
		}
	}
	
	public function loginAction()
	{
		$req = $this->getRequest();
		$u = substr($req->getPost('username', ''), 0, 16);
		$p = substr($req->getPost('password', ''), 0, 31);
		$r = $req->getPost('remember', 0);
		
		$error = $this->m_admin->login($u, $p, $r, 1);
		if( $error != '' ) {
			Yaf_Registry::set('error', $error);
			$this->forward('index');
		} else {
			$this->redirect('/admin/index/main');
		}
		return false;
	}
	
	public function logoutAction()
	{
		$this->m_admin->logout();
		$this->forward('index');
		return false;
	}
	
	public function mainAction()
	{
		if( empty($this->a_info) ) {
			$this->forward('index');
			return false;
		}
	}
	
	public function leftAction()
	{
		if( empty($this->a_info) ) {
			return false;
		}
		$v = $this->getView();
		$v->assign('username', $this->a_info['username']);
		
		if( $this->a_info['status'] == 'disabled' ) {
		    $menu = array();
		} else {
		    if( $this->a_info['status'] == 'super' ) {
		        $group_id = 0;
		    } elseif( $this->a_info['group_id'] > 0 ) {
		        $group_id = $this->a_info['group_id'];
		    } else {
		        $menu = array();
		    }
		}
		
		if( isset($group_id) ) {
		    $m_am = new AdminmenuModel();
		    $menu = $m_am->get($group_id);
		}
		$v->assign('menu', $menu);
	}
	
	public function rightAction()
	{
		Yaf_Registry::set('layout', 1);

		if( empty($this->a_info) ) {
			return false;
		}
		$user=new UsersModel();
        $s = Yaf_Session::getInstance();
        $channel_ids_condition=$s->get('channel_ids_condition');
        $date=date("Ymd");
        $count=$user->fetchAll("tg_channel in {$channel_ids_condition} and login_day={$date}", 1, 200000, 'user_id');
        $count=count($count);
        $this->getView()->assign('count',$count);

        //公告列表
        $m_article=new ArticleModel();
        $articles=$m_article->fetchAll(['type'=>'代理公告']);
        $this->getView()->assign('articles',$articles);
//		$mem = new Memcache();
//		$mem->addServer('127.0.0.1');
//		$stat = $mem->getStats();
//		$stat['used_percent'] = floor(($stat['bytes'] / $stat['limit_maxbytes']) * 100).'%';
//		if( $stat['bytes'] > 1048576 ) {
//		    $stat['bytes'] = round($stat['bytes'] / 1048576).'M';
//		} elseif( $stat['bytes'] > 1024 ) {
//		    $stat['bytes'] = round($stat['bytes'] / 1024).'K';
//		} else {
//		    $stat['bytes'] .= 'B';
//		}
//		$stat['limit_maxbytes'] = ($stat['limit_maxbytes'] / 1048576).'M';
//		$this->getView()->assign('stat', $stat);
	}
}
