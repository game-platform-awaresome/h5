<?php
/**
 * @name AdminController
 * @desc 管理员控制器
 */
class AdminController extends Yaf_Controller_Abstract
{
	private $m_admin;
	private $a_info;
	private $_pn;
	
	public function init()
	{
		$this->m_admin = new AdminModel();
		$this->_pn = $this->getRequest()->get('pn', 1);
		$this->getView()->assign('pn', $this->_pn);
	}
	
	public function listAction()
	{
	    $req = $this->getRequest();
	    $h_html = new F_Helper_Html($req, $this->m_admin);
	    $html = $h_html->DataList('', 16);
	    $this->getView()->assign('html', $html);
	}
	
	public function editAction()
	{
		$req = $this->getRequest();
		$id = $req->getQuery('admin_id', 0);
		$info = null;
		if( $id ) {
		    $info = $this->m_admin->fetch('admin_id='.$id);
		    $this->getView()->assign('title', '编辑管理员');
		} else {
		    $this->getView()->assign('title', '添加管理员');
		}
		$this->getView()->assign('info', $info);
	}
	
	public function updateAction()
	{
	    $req = $this->getRequest();
	    $info = $_POST['info'];
	    $id = intval($info['admin_id']);
	    unset($info['admin_id']);
	    if( $id && empty($info['password']) ) {
	        unset($info['password']);
	    } else {
	        $info['password'] = md5(trim($info['password']));
	    }
	    
	    if( $id ) {
    	    $this->m_admin->update($info, 'admin_id='.$id);
    	    $this->redirect('/admin/admin/list?pn='.$this->_pn);
	    } else {
	        $info['add_by'] = Yaf_Session::getInstance()->get('username');
	        $info['add_ip'] = $_SERVER['REMOTE_ADDR'];
	        $ins_id = $this->m_admin->insert($info);
	        if( $ins_id ) {
	            $this->forward('admin', 'message', 'success', array('msg'=>'添加成功！'));
	        } else {
	            $this->forward('admin', 'message', 'error', array('msg'=>'添加失败，请重试！'));
	        }
	    }
	    return false;
	}
	
	public function deleteAction()
	{
	    $req = $this->getRequest();
	    $id = $req->get('admin_id', 0);
	    if( $id ) {
	        $this->m_admin->delete('admin_id='.$id);
	    }
	    $ajax = $req->getQuery('ajax', 0);
	    if( $req->isXmlHttpRequest() && $ajax ) {
	        exit('1');
	    }
	    $this->redirect('/admin/admin/list?pn='.$this->_pn);
	}
}
