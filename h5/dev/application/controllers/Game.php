<?php

class GameController extends Yaf_Controller_Abstract
{
    //获取跳转地址
    private function getForward()
    {
        $fwd = $this->getRequest()->get('fwd', '');
        if( $fwd ) $fwd = urldecode($fwd);
        if( empty($fwd) && !empty($_SERVER['HTTP_REFERER']) ) {
            $fwd = $_SERVER['HTTP_REFERER'];
            /*
            if( $fwd && strpos($fwd, '?') ) {
                $query = substr($fwd, strpos($fwd, '?') + 1);
                parse_str($query, $arr);
                if( $arr && !empty($arr['fwd']) ) {
                    $fwd = urldecode($arr['fwd']);
                }
            }
            */
        }
        return $fwd;
    }
    
    public function init()
    {
        $ip=$this->getIp();
        $host = Yaf_Registry::get('config')->redis->host;
        $port = Yaf_Registry::get('config')->redis->port;
        $conf=array('host'=>$host,'port'=>$port);
        $redis=F_Helper_Redis::getInstance($conf);
        //缓存域名，后面跳转使用
        $redis->set('global_url'.$ip,$_SERVER['HTTP_HOST']);
        $f_url=new F_Helper_Url();
        $tg_channel=$f_url->getUrlSign();
        $this->getView()->assign('tg_channel',$tg_channel);
        Yaf_Registry::set('layout', false);
    }
    
    //游戏中心
    public function indexAction()
    {
        $m_adpos = new AdposModel();
        $assign['banner'] = $m_adpos->getByCode('game_index_banner', 3);
        //$assign['ad_info'] = $m_adpos->getByCode('game_index_article', 1);
        //今日特别推荐
        //$assign['special'] = $m_adpos->getByCode('game_index_special', 2);
        //快捷通道下方
        $assign['shortcut'] = $m_adpos->getByCode('game_shortcut_banner', 1);
        //火爆新游
//        $assign['hotnew'] = $m_adpos->getByCode('game_index_hotnew', 10);
        //最新活动
        $assign['activity'] = $m_adpos->getByCode('game_index_activity', 2, false);
        
        $m_user = new UsersModel();
        $user = $m_user->getLogin();
        /*
        if( $user ) {
            $user['username'] = $m_user->usernameFormat($user['username']);
            
            $assign['play'] = $m_user->getPlayGames($user['user_id'], 1, 3);
            if( empty($assign['play']) ) {
                $assign['recommend'] = $m_adpos->getByCode('game_index_recommend', 3);
            }
        } else {
            $assign['recommend'] = $m_adpos->getByCode('game_index_recommend', 3);
        }
        */
        $assign['user'] = $user;
        
        $m_game = new GameModel();
        $assign['games'] = $m_game->getTopByType();
        $assign['new_games'] = $m_game->getListByAttr('new',1,5);
        /*
        //今日推荐
        $file = APPLICATION_PATH.'/application/cache/game/recommend.php';
        if( file_exists($file) ) {
            $gids = include $file;
            $assign['today'] = $m_game->fetchAll("game_id IN({$gids}) AND visible=1", 1, 20, 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times', 'weight ASC');
            foreach ($assign['today'] as &$row)
            {
                $row['grade'] = $m_game->gradeHtml($row['grade']);
                $row['support'] = $m_game->supportFormat($row['support'] + $row['play_times']);
            }
        } else {
            $assign['today'] = array();
        }
        */
        
        //$m_article = new ArticleModel();
        //$assign['info'] = $m_article->fetchAll("type='综合' AND visible=1", 1, 2, 'article_id,cover,title', 'weight ASC,article_id DESC');
        
        $this->getView()->assign($assign);
    }
    
    //新游戏中心
    public function centerAction()
    {
        $m_game = new GameModel();
        $assign['types'] = $m_game->_types;
        $assign['classic'] = $m_game->_classic;
        
        $this->getView()->assign($assign);
    }
    //分类列表
    public function typeAction()
    {
        $this->getView()->assign('tc', $this->getRequest()->get('tc', ''));
    }
    //获取type,classic列表
    public function tcAction()
    {
        $req = $this->getRequest();
        $tc = $req->get('tc', '');
        $tc = preg_replace('/[\%\*\'\"\\\]+/', '', $tc);
        $od = $req->get('order', 0);
        $pn = $req->get('pn', 1);
        $limit = $req->get('limit', 6);
        
        switch ($od)
        {
            case 1: $order = 'game_id DESC'; break;
            case 2: $order = 'play_times DESC'; break;
            case 3: $order = 'grade DESC,weight ASC'; break;
            default: $order = 'weight ASC'; break;
        }
        
        $map = array(
            '精品推荐' => '推荐',
            '独家首发' => '独家',
            'BT游戏' => 'BT版',
            '满V游戏' => '满V版',
            'GM游戏' => 'GM版',
        );
        
        $selects = 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times,game_type';
        $m_game = new GameModel();
        $games = array();
        if( $tc == '全部分类' ) {
            $games = $m_game->fetchAll('visible=1', $pn, $limit, $selects, $order);
        } elseif( array_key_exists($tc, $map) ) {
            $tc = $map[$tc];
            $games = $m_game->fetchAll("visible=1 AND type='{$tc}'", $pn, $limit, $selects, $order);
        } elseif( in_array($tc, $m_game->_classic) ) {
            $games = $m_game->fetchAll("visible=1 AND classic='{$tc}'", $pn, $limit, $selects, $order);
        }elseif(in_array($tc,$m_game->_game_type)){
            $games = $m_game->fetchAll("visible=1 AND game_type='{$tc}'", $pn, $limit, $selects, $order);
        }
        foreach ($games as &$row)
        {
            $row['grade'] = $m_game->gradeHtml($row['grade']);
            $row['support'] = $m_game->supportFormat($row['support'] + $row['play_times']);
        }
        
        $this->getView()->assign('games', $games);
    }
    
    //旧的游戏中心
    private function _centerAction()
    {
        $m_adpos = new AdposModel();
        $assign['ad_2col'] = $m_adpos->getByCode('game_center_2col', 2);
        
        $m_game = new GameModel();
        $assign['types'] = $m_game->_special_all;
        
        $types = $m_game->_types;
        foreach ($types as $v)
        {
            $assign['types'][$v] = $v;
        }
        
        $special = $m_game->_special;
        foreach ($special as $k=>$v)
        {
            $assign['types'][$k] = $v;
        }
        
        $initial = include APPLICATION_PATH.'/application/cache/game/initial.php';
        foreach ($initial as $k=>&$v)
        {
            $assign['types'][$k] = $k;
        }
        
        $this->getView()->assign($assign);
    }
    //Ajax获取游戏列表
    public function pullAction()
    {
        $req = $this->getRequest();
        if( ! $req->isXmlHttpRequest() ) exit;
        
        $type = $req->getPost('type', '');
        $pn = $req->getPost('pn', 1);
        $limit = $req->getPost('limit', 10);
        $type = preg_replace('#[\'\"\-\*\%\?\\\]+#', '', $type);
        if( $type == '' ) exit;
        
        $m_game = new GameModel();
        $games = $m_game->getListBySpecial($type, $pn, $limit);
        if( empty($games) ) exit;
        
        $this->getView()->assign('games', $games);
    }
    //Ajax搜索
    public function findAction()
    {
        $search = $this->getRequest()->get('search', '');
        $search = preg_replace('/[\'\"\?`~\!\$\%\^\*\(\)\[\]\{\}\-\+\\\]+/', '', substr($search, 0, 16));
        if( empty($search) ) {
            exit;
        }
        
        $m_game = new GameModel();
        $games = $m_game->search($search);
        
        $this->getView()->assign(array('games'=>$games, 'search'=>$search));
    }
    
    //排行榜
	public function topAction()
	{
		//$m_game = new GameModel();
		//$assign['types'] = $m_game->getCountByType();
		
		//获取人气排行
		//$assign['support'] = $m_game->getListByAttr('support');
		
		$m_user = new UsersModel();
		$assign['user'] = $m_user->getLogin();
		
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
	
	//详情
	public function detailAction()
	{
	    $game_id = $this->getRequest()->get('game_id', 0);
	    if( $game_id < 1 ) {
	        $this->forward('error', 'error');
	        return false;
	    }
	    
	    $m_game = new GameModel();
	    $game = $m_game->fetch("game_id='{$game_id}'");
	    if( empty($game) ) {
	        $this->forward('error', 'error');
	        return false;
	    }
	    $game['grade'] = $m_game->gradeHtml($game['grade']);
	    $game['support'] = $m_game->supportFormat($game['support'] + $game['play_times']);
	    
	    $game['screenshots'] = empty($game['screenshots']) ? array() : unserialize($game['screenshots']);
	    $assign['game'] = $game;
	    
	    $m_user = new UsersModel();
	    $assign['user'] = $m_user->getLogin();
	    if( $assign['user'] ) {
	        $assign['favorited'] = $m_user->isFavorited($assign['user']['user_id'], $game_id);
	    } else {
	        $assign['favorited'] = false;
	    }
	    
	    $this->getView()->assign($assign);
	}
	
	//进入游戏
	public function playAction()
	{
        $ip=$this->getIp();
        $host = Yaf_Registry::get('config')->redis->host;
        $port = Yaf_Registry::get('config')->redis->port;
        $conf=array('host'=>$host,'port'=>$port);
        $redis=F_Helper_Redis::getInstance($conf);
        if($redis->get('back_url'.$ip)){
            $domain=$redis->get('back_url'.$ip);
            $fwd=$redis->get('back_url_query'.$ip);
            $redis->del('back_url'.$ip);
            $redis->del('back_url_query'.$ip);
            $this->redirect('http://'.$domain.$fwd);
        }
	    $game_id = $this->getRequest()->get('game_id', 0);
	    $server_id = $this->getRequest()->get('server_id', 0);
	    $m_user = new UsersModel();
	    $user = $m_user->getLogin();
	    if( empty($user) ) {
	        //缓存渠道id
            if(!$_SESSION['user']) {
                $url=new F_Helper_Url();
                $g=$url->getUrlSign();
                $_SESSION['user'] = $g;
            }
	        $this->redirect('/user/login.html?fwd='.urlencode("/game/play.html?game_id={$game_id}&server_id={$server_id}"));
	        return false;
	    }
	    
	    if( $server_id > 0 ) {
	        $m_server = new ServerModel();
	        $server = $m_server->fetch("server_id='{$server_id}' AND visible=1", 'game_id,server_id,game_name,name,login_url,sign_key,channel,load_type');
	        if( empty($server) ) {
	            $this->redirect('/game/index.html');
	        }
	        if( $server['channel'] == 'egret' ) {
	            $user_merge = $m_user->fetch("user_id='{$user['user_id']}'", 'avatar,sex');
	            $user_merge = array_merge($user, $user_merge);
	            $egret = new Game_Channel_Egret();
	            $url = $egret->login($user_merge, $server);
	            $this->forward('game', 'entry', array('game_name'=>$server['game_name'], 'url'=>$url, 'load_type'=>$server['load_type']));
	            return false;
	        }
	        if( $server['login_url'] && $server['sign_key'] ) {
	            $m_user->addPlayServer($user['user_id'], $server['game_id'], $server_id);
	            $url = Game_Login::redirect($user['user_id'], $user['username'], $server['game_id'], $server_id, $server['login_url'], $server['sign_key']);
	            $this->forward('game', 'entry', array('game_name'=>$server['game_name'], 'url'=>$url, 'load_type'=>$server['load_type']));
	            return false;
	        }
	        if( $server['login_url'] ) {
	            $m_user->addPlayServer($user['user_id'], $server['game_id'], $server_id);
	            header("Location: {$server['login_url']}");
	            return false;
	        }
	        throw new Yaf_Exception("游戏区/服（id={$server_id}）未设置登录地址及校验码！");
	    }
	    
	    if( $game_id < 1 ) {
	        $this->redirect('/game/index.html');
	    }
	    $m_game = new GameModel();
	    $game = $m_game->fetch("game_id='{$game_id}'", 'game_id,name,logo,login_url,sign_key,channel,load_type');
	    if( empty($game) ) {
	        $this->redirect('/game/index.html');
	        return false;
	    }
	    
	    if( $game['channel'] == 'egret' ) {
	        $user_merge = $m_user->fetch("user_id='{$user['user_id']}'", 'avatar,sex');
	        $user_merge = array_merge($user, $user_merge);
	        $game['server_id'] = 0;
	        $egret = new Game_Channel_Egret();
	        $url = $egret->login($user_merge, $game);
	        $this->forward('game', 'entry', array('game_name'=>$game['name'], 'url'=>$url, 'load_type'=>$game['load_type']));
	        return false;
	    }
	    if( $game['login_url'] && $game['sign_key'] ) {
	        $m_user->addPlayGame($user['user_id'], $game_id);
	        $url = Game_Login::redirect($user['user_id'], $user['username'], $game_id, 0, $game['login_url'], $game['sign_key']);
	        $this->forward('game', 'entry', array('game_name'=>$game['name'], 'url'=>$url, 'load_type'=>$game['load_type']));
	        return false;
	    }
	    
	    if( ! isset($m_server) ) {
    	    $m_server = new ServerModel();
	    }
	    $list = $m_server->fetchAll("game_id='{$game_id}' AND visible=1", 1, 100, 'server_id,name,corner,label', 'weight DESC');
	    if( empty($list) ) {
	        throw new Yaf_Exception("游戏（id={$game_id}）未设置登录地址及校验码，也没有添加区/服！");
	    }
	    
	    $play = $m_user->getPlayServers($user['user_id'], $game_id);
	    
	    $this->getView()->assign(array('user'=>$user ,'game'=>$game, 'list'=>$list, 'play'=>$play));
	}
	
	public function entryAction()
	{
	    $req = $this->getRequest();
	    $params = $req->getParams();
	    if( count($params) != 3 || empty($params['game_name']) || empty($params['url']) || empty($params['load_type']) ) {
	        $this->redirect('/game/index.html');
	        return false;
	    }
	    
	    if( $params['load_type'] == 'redirect' ) {
	        $this->redirect($params['url']);
	        return false;
	    }
	    
	    $this->getView()->assign($params);
	}
	
	//搜索
	public function searchAction()
	{
	    $search = $this->getRequest()->get('search', '');
	    $search = preg_replace('/[\'\"\?`~\!\$\%\^\*\(\)\[\]\{\}\-\+\\\]+/', '', substr($search, 0, 16));
	    if( empty($search) ) {
	        $this->redirect('/game/index.html');
	    }
	    
	    $m_game = new GameModel();
	    $games = $m_game->search($search);
	    
	    $this->getView()->assign(array('games'=>$games, 'search'=>$search));
	}
	
	//礼包
	public function giftbagAction()
	{
	    $gift_id = $this->getRequest()->get('gift_id', 0);
	    if( $gift_id < 1 ) {
	        $this->redirect('/game/index.html');
	        return false;
	    }
	    
	    $m_gift = new GiftbagModel();
	    $gift = $m_gift->get($gift_id);
	    $assign['gift'] = $gift;
	    if( empty($gift) ) {
	        $this->redirect('/game/index.html');
	        return false;
	    }
	    
	    $m_game = new GameModel();
	    $game = $m_game->fetch("game_id='{$gift['game_id']}'", 'game_id,name,type,classic,version,logo,giftbag,grade,support,in_short,play_times,game_type');
	    if( empty($game) ) {
	        $this->redirect('/game/index.html');
	        return false;
	    }
	    if( empty($game['giftbag']) ) {
	        $this->redirect('/game/index.html');
	        return false;
	    }
	    
	    $m_user = new UsersModel();
	    $assign['user'] = $m_user->getLogin();
	    if( $assign['user'] ) {
	        $assign['favorited'] = $m_user->isFavorited($assign['user']['user_id'], $game['game_id']);
	        
	        //领取记录
	        $log = $m_gift->userGiftLog($assign['user']['user_id'], $gift['gift_id']);
	        if( $log ) {
	            $assign['cdkey'] = $log[0]['cdkey'];
	        } else {
	            $assign['cdkey'] = '';
	        }
	    } else {
	        $assign['favorited'] = null;
	        $assign['cdkey'] = '';
	    }
	    
	    $game['grade'] = $m_game->gradeHtml($game['grade']);
	    $game['support'] = $m_game->supportFormat($game['support'] + $game['play_times']);
	    $assign['game'] = $game;
	    
	    $this->getView()->assign($assign);
	}

    /**
     * 区服表
     */
	public function serverAction(){
	    //1.查询条件
        //2.获取数据
        $this->getView()->assign('tc',$_GET['tc']);
    }
    //获取type,classic列表
    public function getServerAction()
    {
        $req = $this->getRequest();
        $tc = $req->get('order', 0);
        $tc = preg_replace('/[\%\*\'\"\\\]+/', '', $tc);
        $pn = $req->get('pn', 1);
        $limit = $req->get('limit', 6);
        $order = 'start_time asc';
        $selects = '*';
        $m_server = new ServerModel();
        $servers = array();
        $now_time=(string)date('Y-m-d H:i:s');
        $three_day_befor=(string)date("Y-m-d H:i:s",strtotime("-3 day"));
        $three_day_after=(string)date("Y-m-d H:i:s",strtotime("+3 day"));
        $condition="start_time between '{$three_day_befor}' and '{$three_day_after}'";
        if( $tc == 0 ) {
            $condition.=" and start_time< '{$now_time}'";//已开新服,时间大于当前,前三天
            $order = 'start_time desc';
            $servers = $m_server->fetchAll($condition, $pn, $limit, $selects, $order);
        } elseif( $tc == 1) {
            $condition.=" and start_time> '{$now_time}'";//新服预告
            $servers = $m_server->fetchAll($condition, $pn, $limit, $selects, $order);
        }
        $m_game=new GameModel();
        foreach ($servers as &$row){
            $game=$m_game->fetch(['game_id'=>$row['game_id']],'logo');
            $row['logo']=$game['logo'];
        }
        $m_game=new GameModel();
        foreach ($servers as &$row)
        {
            $game_info=$m_game->fetch(['game_id'=>$row['game_id']],'game_type,giftbag');
            $row['game_type'] = $game_info['game_type'];
            $row['giftbag'] = $game_info['giftbag'];
        }
        $this->getView()->assign('servers', $servers);
    }
    //不同环境下获取真实的IP
    function getIp(){
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if( $_SERVER['REMOTE_ADDR'])
            $ip = $_SERVER['REMOTE_ADDR'];
        else $ip = "Unknow";
        return $ip;
    }
    function countgameAction(){
        Yaf_Dispatcher::getInstance()->disableView();
        $game_id=$_POST['game_id'];
	    if($game_id){
        $m_game=new GameModel();
        $support=$m_game->fetch(['game_id'=>$game_id],'support');
        $m_game->update(['support'=>($support['support']+1)],['game_id'=>$game_id]);
        }
    }
}
