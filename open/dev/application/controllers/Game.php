<?php

class GameController extends Yaf_Controller_Abstract
{
    //获取跳转地址
    private function getForward($referer = true)
    {
        $fwd = $this->getRequest()->get('fwd', '');
        if( $fwd ) $fwd = urldecode($fwd);
        if( ! $referer ) return $fwd;
        if( empty($fwd) && !empty($_SERVER['HTTP_REFERER']) ) {
            $fwd = $_SERVER['HTTP_REFERER'];
        }
        return $fwd;
    }
    
    private function _init()
    {
        $assign['menu_c'] = 'game';
        $assign['menu_a'] = $this->getRequest()->getActionName();
        $this->getView()->assign($assign);
        Yaf_Registry::set('layout', false);
    }
    //格式化菜单，显示游戏数量
    private function formatMenu(&$menu, &$dev)
    {
        $menu['game']['list']['online']['name'] .= "({$dev['on_nums']})";
        $menu['game']['list']['offline']['name'] .= "({$dev['off_nums']})";
        $menu['game']['list']['checking']['name'] .= "({$dev['check_nums']})";
    }
    
    //开发者概览
    public function indexAction()
    {
        $m_dev = F_Model_Pdo::getInstance('Developer');
        $login = $m_dev->getLogin();
        if( empty($login) ) {
            $this->redirect('/developer/login.html?fwd=/game/index.html');
            return false;
        }
        $this->_init();
        
        //统计当月流水，暂时没有统计当月的活跃用户数
        $ymd_b = date('Ym01');
        $ymd = date('Ymd', strtotime('-1 day'));
        //$ymd_e = date('Ymd', strtotime(date('Y-m-01', strtotime('+1 month')).' -1 day'));
        $m_bydev = new StatbydevModel();
        $stat = $m_bydev->fetch("ymd BETWEEN {$ymd_b} AND {$ymd} AND dev_id={$login['dev_id']}", 'SUM(recharge_money) AS money');
        $assign['month_money'] = empty($stat) ? 0 : (int)$stat['money'];
        
        //获取开发者最近半月的统计情况
        $ymd_b = date('Ymd', strtotime('-15 days'));
        $logs = $m_bydev->fetchAll("ymd BETWEEN {$ymd_b} AND {$ymd} AND dev_id={$login['dev_id']}", 1, 15,
            'ymd,signon_people,recharge_money', 'ymd ASC');
        foreach ($logs as &$row)
        {
            $row['ymd'] = substr($row['ymd'], 4, 2).'-'.substr($row['ymd'], 6);
        }
        $assign['logs'] = $logs;
        
        //昨天流水及活跃用户数
        $stat = $m_bydev->fetch("ymd=$ymd AND dev_id={$login['dev_id']}", 'recharge_money,signon_people');
        if( empty($stat) ) {
            $assign['yestoday_money'] = 0;
            $assign['yestoday_people'] = 0;
        } else {
            $assign['yestoday_money'] = (int)$stat['recharge_money'];
            $assign['yestoday_people'] = (int)$stat['signon_people'];
        }
        
        $assign['dev'] = $m_dev->fetch("dev_id='{$login['dev_id']}'", 'trade_money,on_nums,off_nums,check_nums');
        $assign['dev'] = array_merge($login, $assign['dev']);
        $assign['menu'] = $m_dev->_menu;
        $this->formatMenu($assign['menu'], $assign['dev']);
        
        $this->getView()->assign($assign);
    }
    
    //创建游戏
    public function createAction()
    {
        $m_dev = F_Model_Pdo::getInstance('Developer');
        $login = $m_dev->getLogin();
        if( empty($login) ) {
            $this->redirect('/developer/login.html?fwd=/game/create.html');
            return false;
        }
        
        $m_game = new GameModel();
        $assign['classic'] = $m_game->_classic;
        $assign['game_types'] = $m_game->_game_types;

        $assign['dev'] = $m_dev->fetch("dev_id='{$login['dev_id']}'", 'contact,mobile,qq,wx,status,message');
        if( $assign['dev']['status'] != 9 && empty($assign['dev']['message']) ) {
            switch ($assign['dev']['status'])
            {
                case 3: $assign['dev']['message'] = '您的账号已被冻结。'; break;
                case 2: $assign['dev']['message'] = '开发者账号审核申请未通过，如有疑问请联系客服帮您处理。'; break;
                default: $assign['dev']['message'] = '请先完善您的资料，审核通过后即可创建游戏，审核工作一般在1-3个工作日内完成。'; break;
            }
        }
        $assign['dev'] = array_merge($login, $assign['dev']);
        
        $this->getView()->assign($assign);
    }
    //创建游戏
    public function addAction()
    {
        $req = $this->getRequest();
        if( ! $req->isPost() ) {
            exit;
        }
        
        $m_dev = F_Model_Pdo::getInstance('Developer');
        $login = $m_dev->getLogin();
        if( empty($login) ) {
            $this->redirect('/developer/login.html?fwd=/game/create.html');
            return false;
        }
        $dev = $m_dev->fetch("dev_id='{$login['dev_id']}'", 'status,message');
        if( $dev['status'] != 9 ) {
            $this->redirect('/game/index.html');
            return false;
        }
        
        $name = $req->getPost('name', '');
        $type = $req->getPost('type', '');
        $game_type = $req->getPost('game_type', '');
        if( empty($name) || empty($type) ) {
            $this->forward('game', 'create');
            return false;
        }
        $m_game = new GameModel();
        if( ! in_array($type, $m_game->_classic) ) {
            $this->forward('game', 'create');
            return false;
        }
        if( ! in_array($game_type, $m_game->_game_types) ) {
            $this->forward('game', 'create');
            return false;
        }
        $name = preg_replace('/[\'\"\\\]+/', '', mb_substr($name, 0, 16, 'UTF-8'));
        $sign_key = md5(uniqid(mt_rand()));
        $data = array(
            'name' => $name,
            'type' => '推荐',
            'classic' => $type,
            'game_type'=>$game_type,
            'tag' => '',
            'logo' => '',
            'version' => '',
            'in_short' => '',
            'login_url' => '',
            'recharge_url' => '',
            'sign_key' => $sign_key,
            'visible' => 0,
            'dev_id' => $login['dev_id'],
        );
        $game_id = $m_game->insert($data);
        if( $game_id == false ) {
            $this->forward('game', 'create');
            return false;
        }
        
        $m_d_gm = new DevgamesModel();
        $ins_arr = array(
            'dev_id' => $login['dev_id'],
            'game_id' => $game_id,
            'name' => $name,
            'servers' => 0,
            'status' => 0,
            'message' => '',
        );
        $m_d_gm->insert($ins_arr, false);
        
        $m_dev->update('off_nums=off_nums+1', array('dev_id'=>$login['dev_id']));
        
        $assign['dev'] = $login;
        $assign['game_id'] = $game_id;
        $assign['name'] = $name;
        $assign['key'] = $sign_key;
        
        $this->getView()->assign($assign);
    }
    
	//Ajax获取游戏列表
	public function listAction()
	{
	    $req = $this->getRequest();
	    $attr = $req->get('attr', '');
	    $pn = $req->get('pn', 1);
	    $attr = preg_replace('#[^a-z]+#', '', $attr);
	    if( ! in_array($attr, array('support', 'new', 'grade')) ) {
	        exit;
	    }
	    
	    $m_game = new GameModel();
	    $games = $m_game->getListByAttr($attr, $pn, 10);
	    $this->getView()->assign('games', $games);
	}
	
	public function entryAction()
	{
	    $m_dev = F_Model_Pdo::getInstance('Developer');
	    $login = $m_dev->getLogin();
	    if( empty($login) ) {
	        $this->redirect('/developer/login.html?fwd=/game/index.html');
	        return false;
	    }
	    
	    $req = $this->getRequest();
	    $game_id = $req->get('game_id', 0);
	    if( $game_id ) {
	        //进行状态验证，以免非测试状态的游戏也在请求充值
	        $m_devgms = new DevgamesModel();
	        $devgms = $m_devgms->fetch("game_id='{$game_id}'", 'status');
	        if( empty($devgms) ) {
	            return false;
	        }
	        if( $devgms['status'] >= 6 ) {
	            return false;
	        }
	        
	        $m_game = new GameModel();
	        $params = $m_game->fetch("game_id='{$game_id}' AND dev_id='{$login['dev_id']}'", 'name AS game_name,login_url,sign_key,load_type');
	        if( $params ) {
	            $params['url'] = Game_Login::redirect($login['dev_id'], $login['username'], $game_id, 0, $params['login_url'], $params['sign_key']);
	            unset($params['login_url'], $params['sign_key']);
	        }
	    } else {
	        $params = $req->getParams();
	    }
	    if( count($params) != 3 || empty($params['game_name']) || empty($params['url']) || empty($params['load_type']) ) {
	        $this->redirect('/game/index.html');
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
    
    private function gamelist($status)
    {
        switch ($status)
        {
            case 9:
                $fwd = '/game/online.html';
                $num = 'on_nums';
                break;
            case 4:
                $fwd = '/game/checking.html';
                $num = 'check_nums';
                break;
            default:
                $fwd = '/game/offline.html';
                $num = 'off_nums';
                break;
        }
        
        $m_dev = F_Model_Pdo::getInstance('Developer');
        $login = $m_dev->getLogin();
        if( empty($login) ) {
            $this->redirect('/developer/login.html?fwd='.$fwd);
            return false;
        }
        $this->_init();
        $assign['dev'] = $m_dev->fetch("dev_id='{$login['dev_id']}'", 'on_nums,off_nums,check_nums');
        $assign['dev'] = array_merge($login, $assign['dev']);
        $assign['menu'] = $m_dev->_menu;
        $this->formatMenu($assign['menu'], $assign['dev']);
        
        $req = $this->getRequest();
        $pn = $req->get('pn', 1);
        $pn = $pn < 1 ? 1 : $pn;
        $limit = 9;
        
        $m_devgms = new DevgamesModel();
        $assign['games'] = $m_devgms->getGamesByStatus($login['dev_id'], $status, $pn, $limit);
        
        $total = $m_dev->fetch("dev_id='{$login['dev_id']}'", $num);
        $total = ceil($total[$num]/$limit);
        $assign['pager'] = $total > 0 ? new Game_Pager($total) : '';
        
        $this->getView()->assign($assign);
    }
    
    public function onlineAction()
    {
        $this->gamelist(9);
    }
    
    public function offlineAction()
    {
        $this->gamelist(6);
    }
    
    public function checkingAction()
    {
        $this->gamelist(4);
    }

    /**
     * 删除渠道分包
     */
    public function deleteChannelApkAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        $game_id=$_GET['game_id'];
        shell_exec("
        cd /www/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id};
        rm -rf *.apk;
         > /dev/null 2>&1 &");
        $this->redirect('/admin/game/index');
    }
}
