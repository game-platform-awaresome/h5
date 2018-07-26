<?php

class SignrewardController extends Yaf_Controller_Abstract
{
    public function init()
    {
        Yaf_Registry::set('layout', false);
    }
    
	public function indexAction()
	{
		$assign['today'] = (int)date('j');
		$assign['days'] = array();
		
		//处理上月日期
		$day_1 = date('Y-m-01');
		$time_1 = strtotime($day_1);
		$wkday_1 = (int)date('w', $time_1);
		if( $wkday_1 != 0 ) {
		    $first = (int)date('j', strtotime("$day_1 -{$wkday_1} days"));
		    for (; $wkday_1>0; --$wkday_1)
		    {
		        $assign['days'][] = array('p', $first++, false);
		    }
		}
		
		//处理用户的签到记录
		$m_user = new UsersModel();
		$user = $m_user->getLogin();
		$assign['user'] = $user;
		if( empty($user) ) {
		    $logs = array();
		} else {
		    $m_book = new SignbookModel();
		    $logs = $m_book->get($user['user_id']);
		}
		if( $logs ) {
		    $last = count($logs) - 1;
		    $assign['serial_days'] = $logs[$last]['serial_days'];
		    $assign['total_days'] = $logs[$last]['total_days'];
		} else {
		    $assign['serial_days'] = 0;
		    $assign['total_days'] = 0;
		}
		$assign['logs'] = array_reverse($logs);
		$logs = array();
		foreach ($assign['logs'] as &$row)
		{
		    $logs[substr($row['ymd'], 6, 2)] = 1;
		}
		
		//处理当月日期
		$time_L = strtotime('last day of');
		$day_L = (int)date('j', $time_L);
		for ($first = 1; $day_L>0; --$day_L, ++$first)
		{
		    $assign['days'][] = array('c', $first, array_key_exists($first, $logs));
		}
		//处理下月日期
		$wkday_L = (int)date('w', $time_L);
		if( $wkday_L != 6 ) {
		    $wkday_L = 6 - $wkday_L;
		    for ($first = 1; $wkday_L>0; --$wkday_L)
		    {
		        $assign['days'][] = array('n', $first++, false);
		    }
		}
		
		$this->getView()->assign($assign);
	}
	
	//签到
	public function signAction()
	{
	    $req = $this->getRequest();
	    if( ! $req->isXmlHttpRequest() ) {
	        exit;
	    }
	    
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        exit(json_encode(array(false, '请先登录，再来签到！')));
	    }
	    
	    $m_book = new SignbookModel();
	    $rs = $m_book->sign($user['user_id']);
	    exit(json_encode($rs));
	}
	
	public function rulesAction()
	{
	    
	}
}
