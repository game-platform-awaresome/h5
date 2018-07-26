<?php
/**
 * @name F_Controller_Backend
 * @desc 后台通用控制器
 */
class F_Controller_Backend extends Yaf_Controller_Abstract
{
    /**
     * @var F_Model_Pdo
     */
	protected $_model;
	/**
	 * @var Yaf_View_Interface
	 */
	protected $_view;
	
	protected $_ctl;
	protected $_primary;
	protected $_pn;
	protected $_query;
	
	/**
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see Yaf_Controller_Abstract::init()
	 */
	public function init()
	{
	    $this->_ctl = str_replace('Controller', '', get_class($this));
	    $this->_model = $this->_ctl.'Model';
		$this->_model = new $this->_model();
		$this->_ctl = strtolower($this->_ctl);
		$this->_primary = $this->_model->getPrimary();

		$primary = $this->_model->getPrimary();
		if( is_array($primary) ) {
		    $this->_query = $_SERVER['QUERY_STRING'];
		    foreach ($primary as $_pmy)
		    {
		        $this->_query = preg_replace("#{$_pmy}=[^&]+&?#", '', $this->_query);
		    }
		} else {
		    $this->_query = preg_replace("#{$primary}=[^&]+&?#", '', $_SERVER['QUERY_STRING']);
		}
		$this->_query = trim($this->_query, '&');
		$this->_pn = $this->getRequest()->get('pn', 1);
		
		$this->_view = $this->getView();
		$this->_view->assign('controller', $this->_ctl);
		$this->_view->assign('primary', $this->_primary);
		$this->_view->assign('query', $this->_query);
		$this->_view->assign('pn', $this->_pn);
	}
	
	/**
	 * 默认列表页面
	 */
	public function listAction()
	{
	    $params = $this->beforeList();
	    $conditions = isset($params['conditions']) ? $params['conditions'] : '';
	    $limit = isset($params['limit']) ? $params['limit'] : 16;
	    $orderby = isset($params['orderby']) ? $params['orderby'] : null;
	    $op = isset($params['op']) ? $params['op'] : F_Helper_Html::Op_ED;
	    $perpage = isset($params['perpage']) ? $params['perpage'] : null;
	    
	    $req = $this->getRequest();
	    $h_html = new F_Helper_Html($req, $this->_model);
	    $html = $h_html->DataList($conditions, $limit, $orderby, $op, $perpage);
	    $this->_view->assign('html', $html);
	}
	
	/**
	 * 默认添加或修改页面
	 */
	public function editAction()
	{
		$req = $this->getRequest();
	    $primary = $this->_model->getPrimary();
	    $id = $req->get($primary, '');
	    $info = null;
	    if( $id ) {
	        $info = $this->_model->fetch(array($primary=>$id));
	        $this->_view->assign('title', '编辑'.$this->_model->getTableLabel());
	    } else {
	        $this->_view->assign('title', '添加'.$this->_model->getTableLabel());
	    }
	    $this->beforeEdit($info);
	    $this->_view->assign('info', $info);
	}
	
	/**
	 * 默认添加或修改动作
	 * 
	 * @return bool false
	 */
	public function updateAction()
	{
	    $req = $this->getRequest();
	    $info = $req->getPost('info', array());
	    $primary = $this->_model->getPrimary();
	    $id = $info[$primary];
	    unset($info[$primary]);
	    
	    $errstr = $this->beforeUpdate($id, $info);
	    if( $errstr ) {
	        $this->forward('admin', 'message', 'error', array('msg'=>$errstr, 'time'=>4));
	        return false;
	    }
	    
	    if( $id ) {
    	    $this->_model->update($info, "{$primary}='{$id}'");
    	    $this->afterUpdate($id, $info);
    	    $this->redirect("/admin/{$this->_ctl}/list?{$this->_query}");
	    } else {
	        $ins_id = $this->_model->insert($info);
	        $this->afterUpdate($ins_id, $info);
	        if( $ins_id ) {
	            $this->forward('admin', 'message', 'success', array('msg'=>'添加成功！'));
	        } else {
	            $this->forward('admin', 'message', 'error', array('msg'=>'添加失败，请重试！'));
	        }
	    }
	    return false;
	}
	
	/**
	 * 默认删除动作
	 */
	public function deleteAction()
	{
	    $primary = $this->_model->getPrimary();
	    $req = $this->getRequest();
	    $id = $req->get($primary, '');
	    $ajax = $req->get('ajax', 0);
	    $errstr = $this->beforeDelete($id);
	    if( $errstr ) {
	        if( $req->isXmlHttpRequest() && $ajax ) {
	            exit($errstr);
	        } else {
	            $this->forward('admin', 'message', 'error', array('msg'=>$errstr));
	            return false;
	        }
	    }
	    if( $id ) {
	        $this->_model->delete("{$primary}='{$id}'");
	    }
	    if( $req->isXmlHttpRequest() && $ajax ) {
	        exit('1');
	    }
	    $this->redirect("/admin/{$this->_ctl}/list?{$this->_query}");
	}
	
	/**
	 * 处理列表的查询条件
	 * 
	 * @return array $params
	 * array(
	 *     'conditions' => '',
	 *     'limit' => 16,
	 *     'orderby' => null,
	 *     'op' => F_Helper_Html::Op_ED,
	 *     'perpage' => null,
	 * )
	 */
	protected function beforeList()
	{
	    $conds = '';
	    $search = $this->getRequest()->getQuery('search', array());
	    if( $search ) {
	        $cmm = '';
	        foreach ($search as $k=>$v)
	        {
	            if( empty($v) ) {
	                continue;
	            }
	            if( $k == 'username' ) {
	                $m_dev = new DeveloperModel();
	                $dev = $m_dev->fetch("username='{$v}'", 'dev_id');
	                if( $dev ) {
	                    $k = 'dev_id';
	                    $v = $dev['dev_id'];
	                } else {
	                    continue;
	                }
	            }
	            $conds .= "{$cmm}{$k}='{$v}'";
	            $cmm = ' AND ';
	        }
	    }
	    return array(
	        'conditions' => $conds,
	    );
	}
	
	/**
	 * 处理添加或修改页面的前提操作
	 * 
	 * @param mixed &$info
	 */
	protected function beforeEdit(&$info)
	{
	    
	}
	
	/**
	 * 添加或修改前的回调函数
	 *
	 * @param mixed $id
	 * @param array &$info
	 * @return string $errstr
	 */
	protected function beforeUpdate($id, &$info)
	{
	    return '';
	}
	
	/**
	 * 添加或修改后的回调函数
	 *
	 * @param mixed $id
	 * @param array &$info
	 */
	protected function afterUpdate($id, &$info)
	{
	    
	}
	
	/**
	 * 删除前的回调函数
	 * 
	 * @param mixed $id
	 * @return string $errstr
	 */
	protected function beforeDelete($id)
	{
	    
	}
}
