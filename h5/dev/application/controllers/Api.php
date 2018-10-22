<?php

class ApiController extends Yaf_Controller_Abstract
{
    private $_ck_ui_name = 'u_auth';

    //游戏直充
    public function payAction()
    {
        $req = $this->getRequest();
        $user_id = $req->get('user_id', 0);
        $sign = $req->get('sign', '');
        if ($user_id < 1 || strlen($sign) != 32) {
            exit('Params error.');
        }
        $sign = preg_replace('/[^0-9a-f]+/', '', $sign);
        $arr = array(
            'user_id' => $user_id,
            'username' => $req->get('username', ''),
            'game_id' => $req->get('game_id', 0),
            'money' => $req->get('money', 0),
            'subject' => $req->get('subject', ''),
            'body' => $req->get('body', ''),
            'cp_order' => $req->get('cp_order', ''),
            'cp_return' => $req->get('cp_return', ''),
            'time' => $req->get('time', 0),
            'extra' => $req->get('extra', ''),
        );
        if (isset($_GET['server_id']) || isset($_POST['server_id'])) {
            $srv_id_i = $req->get('server_id', 0);
            $srv_id_s = $req->get('server_id', '');
            if (strval($srv_id_i) === $srv_id_s) {
                $arr['server_id'] = $srv_id_i;
            }
        }
        $time = time();
        if (($arr['time'] + 300) < $time) {
            exit('Time error.');
        }
//        if( $arr['server_id'] ) {
//            $m_server = new ServerModel();
//            $server = $m_server->fetch("server_id='{$arr['server_id']}'", 'name,game_name,login_url,sign_key');
//            $server_name = $server['name'];
//            $game_name = $server['game_name'];
//            $login_url = $server['login_url'];
//            $sign_key = $server['sign_key'];
//        } else {
        $m_game = new GameModel();
        $game = $m_game->fetch("game_id='{$arr['game_id']}'", 'name,login_url,sign_key');
        $server_name = '';
        $game_name = $game['name'];
        $login_url = $game['login_url'];
        $sign_key = $game['sign_key'];
//        }

        ksort($arr);
        $md5 = md5(implode('', $arr) . $sign_key);
        if (strcmp($sign, $md5) != 0) {
            exit('Sign error.');
        }
        $subject = $arr['subject'];
        $body = $arr['body'];
        $subject = $arr['subject'];
        unset($arr['subject'], $arr['body'], $arr['time']);

        $m_pay = new PayModel();
        $arr['pay_id'] = $m_pay->createPayId();
        $arr['to_uid'] = $arr['user_id'];
        $arr['username'] = urldecode($arr['username']); //解决有些商户并没有urldecode的兼容性问题
        $arr['to_user'] = $arr['username'];
        $arr['add_time'] = $time;
        $arr['game_name'] = $game_name;
        $arr['server_name'] = $server_name;
        $arr['type'] = 'pigpay';
        $game_id = $req->get('game_id', 0);
        if ($game_id) {
            $ip = $this->getIp();
            $host = Yaf_Registry::get('config')->redis->host;
            $port = Yaf_Registry::get('config')->redis->port;
            $conf = array('host' => $host, 'port' => $port);
            $redis = F_Helper_Redis::getInstance($conf);
            $back_url = $redis->get('global_url' . $ip);
            $cp_return = "http://" . $back_url . "/game/play.html?game_id={$req->get('game_id')}";
        } else {
            $cp_return = $req->get('cp_return', '');
        }
        $arr['cp_return'] = $cp_return;
        $user = new UsersModel();
        $user_info = $user->fetch(['user_id' => $user_id], 'tg_channel,player_channel');
        $arr['tg_channel'] = $user_info['tg_channel'];
        $arr['player_channel'] = $user_info['player_channel'];
        $rs = $m_pay->insert($arr, false);
        if (!$rs) {
            exit('Save payment failed.');
        }

        //判断用户是否有足够的平台币
//        $m_user = new UsersModel();
//        $user = $m_user->fetch("user_id='{$user_id}'", 'money');
//        if( $user && $user['money'] >= $arr['money'] ) {
//            $return = $arr['cp_return'];
//            if( $return == '1' ) {
//                $return = Game_Login::redirect($user_id, $arr['username'], $arr['game_id'], $arr['server_id'], $login_url, $sign_key);
//            }
//            $this->forward('api', 'gotop', array('pay'=>$arr, 'subject'=>$subject, 'deposit'=>$user['money'], 'return'=>$return));
//            return false;
//        }

//        if( empty($subject) ) {
//            $conf = Yaf_Application::app()->getConfig();
//            if( $arr['server_id'] ) {
//                $subject = "充值到《{$arr['game_name']}，{$arr['server_name']}》 - {$conf['application']['sitename']}";
//            } else {
//                $subject = "充值到《{$arr['game_name']}》 - {$conf['application']['sitename']}";
//            }
//        }

//        $pig_pay = new Pay_Pigpay_Mobile();
//        $url = $pig_pay->redirect($arr['pay_id'], $arr['money'], $subject, $body, $arr['username']);
//        $pay_pigpay_mobile=
//        var_dump($arr);
//        die;
        $pay['pay_id'] = $arr['pay_id'];
        $pay['to_user'] = $arr['username'];
        $pay['user_id'] = $arr['user_id'];
        $pay['money'] = $arr['money'];
        $pay['cp_order'] = $arr['cp_order'];
        $pay['cp_return'] = $arr['cp_return'];
        $pay['extra'] = $arr['extra'];
        $pay['game_name'] = $arr['game_name'];
        $pay['server_name'] = $arr['server_name'];
        $pay['subject'] = $subject ? $subject : $arr['game_name'] . '游戏充值';
        $pay['body'] = $body ? $body : '元宝';
        $this->getView()->assign('pay', $pay);
        $conf = Yaf_Application::app()->getConfig();
        $this->getView()->assign(array('parities' => $conf->application->parities));

//          $this->redirect("/pay/numstype.html?server_id={$arr['server_id']}&game_id={$arr['game_id']}&money={$arr['money']}&cp_order={$arr['cp_order']}&cp_return={$arr['cp_return']}");
    }

    /**
     * 通过平台币充值
     */
    public function payByDepositAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        $params = $_REQUEST;
        $user_id = $params['user_id'];
        $pay_id = $params['jinzhue'];
        //查询订单
        $conds = "pay_id='{$pay_id}'";
        $m_pay = new PayModel();
        $pay = $m_pay->fetch($conds);
        if ((int)$pay['finish_time'] > 0) {
            echo json_encode(['code' => 1, 'message' => '请勿重复提交']);
            return false;
        }
        $arr = array(
            'money' => $pay['money'],
            'username' => $pay['username'],
            'game_id' => $pay['game_id'],
            'server_id' => $pay['server_id'],
            'cp_return' => $pay['cp_return'] ? $pay['cp_return'] : '1'
        );
        //游戏信息
        $m_game = new GameModel();
        $game = $m_game->fetch("game_id='{$arr['game_id']}'", 'name,login_url,sign_key');
        $server_name = '';
        $game_name = $game['name'];
        $login_url = $game['login_url'];
        $sign_key = $game['sign_key'];
        $m_user = new UsersModel();
        $user = $m_user->fetch("user_id='{$user_id}'", 'money');
        if ($user && $user['money'] >= $arr['money']) {
            $return = $arr['cp_return'];
            if ($return == '1') {
                $return = Game_Login::redirect($user_id, $arr['username'], $arr['game_id'], $arr['server_id'], $login_url, $sign_key);
            }
//            'pay_id' => $_REQUEST['jinzhue'],
//            'trade_no' => $_REQUEST['OrderID'],
//            'pay_type' => $_REQUEST['jinzhuc'],
            //减扣平台币
            $m_user = new UsersModel();
            $now_money = (int)($user['money'] - $arr['money']);
            $rs1 = $m_user->update(array('money' => $now_money), "user_id='{$user_id}'");
//            $this->forward('notify', 'pigpay',$params);
            if ($rs1) {
                $trade_no = date('YmdHis') . rand(1, 9999);
                $url = 'http://' . $_SERVER['SERVER_NAME'] . "/notify/pigpay?jinzhue={$params['jinzhue']}&jinzhuc={$params['jinzhuc']}&OrderID={$trade_no}";
                $curl = new F_Helper_Curl();
                $rs = $curl->request($url);
                if ($rs == 'success' || $rs == 'ok') {
                    echo json_encode(['code' => 0, 'message' => $return]);
                } else {
                    echo json_encode(['code' => 1, 'message' => '游戏发货失败,请联系客服处理']);
                }
            } else {
                echo json_encode(['code' => 1, 'message' => '发货失败']);
            }
            return false;
        } else {
            echo json_encode(['code' => 1, 'message' => '平台币不足']);
            die;
            return false;
        }
    }

    //跳转到顶层框架
    public function gotopAction()
    {
        $req = $this->getRequest();
        $arr = $req->getParams();
        if (count($arr) != 4 || empty($arr['pay']) || empty($arr['deposit']) || empty($arr['return'])) {
            exit;
        }
        $this->getView()->assign($arr);
    }

    //选择支付方式
    public function typeAction()
    {
        $req = $this->getRequest();
        if (!$req->isPost()) {
            exit;
        }
        $arr['pay'] = $req->getPost('pay', array());
        $arr['subject'] = $req->getPost('subject', '');
        $arr['deposit'] = $req->getPost('deposit', 0);
        $arr['return'] = $req->getPost('return', '');
        if (empty($arr['pay']) || empty($arr['deposit']) || empty($arr['return'])) {
            exit;
        }

        $conf = Yaf_Application::app()->getConfig();
        $arr['parities'] = $conf->application->parities;

        $this->getView()->assign($arr);
    }

    /**
     * 获取游戏列表
     * game_type 游戏类型 h5\手游
     */
    public function gameListAction()
    {
        $request = $_POST;
        $this->checkParams($request, ['game_type']);
        $game_type = $request['game_type'];
        $m_game = new GameModel();
        $assign['top_games'] = $m_game->getTopByType($pn = 1, $limit = 5, $type = '', $game_type);//分类
        $assign['new_games'] = $m_game->getListByAttr('new', 1, 5, $game_type);
        $assign['hot_games'] = $m_game->getListByAttr('hot', 1, 5, $game_type);
//        $assign['article_list'] = $m_game->getListByAttr('hot', 1, 5, $game_type);
        $m_article = new ArticleModel();
        $list=$m_article->fetchAll("visible=1 and type!='代理公告'", 1, 4, 'article_id,cover,title,up_time', 'weight ASC,article_id DESC');
        foreach ($list as &$row)
        {
            $row['up_time'] = $m_article->formatTime($row['up_time']);
        }
        $assign['info'] =$list;
        echo json_encode($assign, true);
        die;
    }

    /**
     * 获取游戏列表
     * game_type 游戏类型 h5\手游
     */
    public function gameListByTypeAction()
    {
        $request = $_POST;
        $this->checkParams($request, ['game_type','tc','pn']);
        $game_type = $request['game_type'];
        $tc =$request['tc'];
        $tc = preg_replace('/[\%\*\'\"\\\]+/', '', $tc);
        $pn =$request['pn']??1;
        $limit =8;
        $order = 'game_id DESC';
        $selects = 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times,game_type,package_name,package_size';
        $m_game = new GameModel();
        $games = array();
        if ($tc == '全部') {
            $games = $m_game->fetchAll("visible=1 and game_type='{$game_type}'", $pn, $limit, $selects, $order);
        } elseif (in_array($tc, $m_game->_classic)) {
            $games = $m_game->fetchAll("visible=1 AND  classic='{$tc}' and game_type='{$game_type}'", $pn, $limit, $selects, $order);
        }
        foreach ($games as &$row) {
            $row['grade'] = $m_game->gradeHtml($row['grade']);
            $row['support'] = $m_game->supportFormat($row['support'] + $row['play_times']);
        }
        echo json_encode($games, true);
        die;
    }
    function getArticleListAction(){
        $req = $this->getRequest();
        $type = $req->getPost('type', '');
        $type = preg_replace('/[\'\"\\\]+/', '', $type);
        $pn = $req->getPost('pn', 1);
        $limit = 8;
        $m_article = new ArticleModel();
        if( $type == '综合' ) {
            $conds = 'visible=1';
        } else if( in_array($type, $m_article->_types) ) {
            $conds = "type='{$type}' AND visible=1";
        } else {
            exit;
        }
        $conds .= " and type!='代理公告'";//过滤公告
        $list = $m_article->fetchAll($conds, $pn, $limit, 'article_id,cover,title,up_time', 'weight ASC,article_id DESC');
        foreach ($list as &$row)
        {
            $row['up_time'] = $m_article->formatTime($row['up_time']);
        }
        $assign['list']=$list;
        $assign['type']=$type;
        echo json_encode($assign, true);
        die;
    }

    /**
     * 获取游戏
     */
    function getGamesBySortAction(){
        $request = $_GET;
        $this->checkParams($request, ['game_type','pn','type']);
        $game_type = $request['game_type'];
        $type = $request['type'];
        $m_game = new GameModel();
        if($type=='new'){
            $res=$m_game->getListByAttr('new', $request['pn'], 10, $game_type);
//            $assign=$res['new_games'];
        }elseif($type=='hot'){
            $res=$m_game->getListByAttr('hot', $request['pn'], 10, $game_type);
//            $assign=$res['hot_games'];
        }else{
//            $assign='fail';
        }
        echo json_encode($res, true);
        die;
    }
    /**
     * 获取文章详情
     */
    function getArticleDeatilAction(){
        $request = $_GET;
        $article_id = $request['article_id'];
        $m_article = new ArticleModel();
        $assign['info'] = $m_article->fetch("article_id='{$article_id}' AND visible=1");
        //正则匹配标签，加上绝对路径<img src="/
        $assign['info'] = str_replace("<img src=\"","<img src=\"http://h5.zyttx.com",$assign['info']);
        echo json_encode($assign, true);
        die;
    }

    /**
     * 获取查询列表
     */
    public function gameSearchListAction(){
        $request = $_POST;
        $this->checkParams($request, ['game_type','name','pn']);
        $game_type = $request['game_type'];
        $pn =$request['pn']??1;
        $limit =20;
        $order = 'game_id DESC';
        $selects = 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times,game_type,package_name,package_size';
        $m_game = new GameModel();
        $m_gift = new GiftbagModel();
        $games = $m_game->fetchAll("visible=1 and game_type='{$game_type}' and name like '%{$request['name']}%'", $pn, $limit, $selects, $order);
        $gifts = $m_gift->fetchAllBySql("select h5.giftbag.name,game_name,nums,used,content,gift_id,h5.game.logo from h5.giftbag  inner join h5.`game`  on h5.giftbag.game_id = h5.`game`.game_id where h5.`game`.game_type =  '{$game_type}' and h5.giftbag.game_name like '%{$request['name']}%'");
        foreach ($gifts as &$value){
            $value['content']=unserialize($value['content']);
            if($request['user_id']??0){
                $rs=$m_gift->fetchBySql("select cdkey from h5.user_cdkey where user_id = {$request['user_id']} and gift_id = {$value['gift_id']}");
                $value['cdkey'] = $rs['cdkey'];
            }
        }
        $assign['game']=$games;
        $assign['gift']=$gifts;
        echo json_encode($assign, true);
        die;
    }
    /**
     * 获取查询列表
     */
    public function giftListAction(){
        $request = $_POST;
        $this->checkParams($request, ['game_type','pn']);
        $game_type = $request['game_type'];
        $pn =$request['pn'];
        $limit = 11;
        $offset = ($pn - 1) * $limit;
        $order = 'h5.giftbag.game_id DESC';
        $m_gift = new GiftbagModel();
        $gifts = $m_gift->fetchAllBySql("select h5.giftbag.name,game_name,nums,used,content,gift_id,h5.game.logo from h5.giftbag  inner join h5.`game`  on h5.giftbag.game_id = h5.`game`.game_id where h5.`game`.game_type =  '{$game_type}' order by {$order}  LIMIT {$offset},{$limit} ");
//        $log="select h5.giftbag.name,game_name,nums,used,content,h5.giftbag.gift_id,h5.game.logo from h5.giftbag  inner join h5.`game`  on h5.giftbag.game_id = h5.`game`.game_id where h5.`game`.game_type =  '{$game_type}' order by {$order}  LIMIT {$offset},{$limit}";
        foreach ($gifts as &$value){
            $value['content']=unserialize($value['content']);
            if($request['user_id']??0) {
                $rs=$m_gift->fetchBySql("select cdkey from h5.user_cdkey where user_id = {$request['user_id']} and gift_id = {$value['gift_id']}");
                $value['cdkey'] = $rs['cdkey'];
            }
        }
        $assign['gift']=$gifts;
        echo json_encode($assign, true);
        die;
    }
    /**
     * 获取区服列表
     */
    public function serverListAction(){
        $request = $_POST;
        $this->checkParams($request, ['index','game_type','pn']);
        $pn =$request['pn'];
        $limit = 8;
        $offset = ($pn - 1) * $limit;
        $order = 'start_time asc';
        $m_server = new ServerModel();
        $servers = array();
        $now_time=(string)date('Y-m-d H:i:s');
        $three_day_befor=(string)date("Y-m-d H:i:s",strtotime("-3 day"));
        $three_day_after=(string)date("Y-m-d H:i:s",strtotime("+3 day"));
        $condition="start_time between '{$three_day_befor}' and '{$three_day_after}' and game_type='{$request['game_type']}'";
        if( $request['index']==0 ) {
            $condition.=" and start_time< '{$now_time}'";//已开新服,时间大于当前,前三天
            $order = 'start_time desc';
            $servers_list = $m_server->fetchAllBySql("select h5.game.*,h5.server.*,h5.server.name as server_name,h5.server.add_time as server_add_time from h5.server left join h5.game on h5.game.game_id=h5.server.game_id  where {$condition}  order by {$order}  LIMIT {$offset},{$limit}");
            $servers['start']=$servers_list;
        }
        if($request['index']==1) {
            $condition.=" and start_time> '{$now_time}'";//新服预告
            $servers_list = $m_server->fetchAllBySql("select h5.game.*,h5.server.*,h5.server.name as server_name,h5.server.add_time as server_add_time from h5.server left join h5.game on h5.game.game_id=h5.server.game_id  where {$condition}  order by {$order}  LIMIT {$offset},{$limit}");
            $servers['will_start']=$servers_list;
        }
        echo json_encode($servers, true);
        die;
    }
    /**
     * 获取广告位列表
     * game_type
     */
    public function gameAdposAction()
    {
        $m_adpos = new AdposModel();
        $banner = $m_adpos->getByCode('game_index_banner', 3);
        echo json_encode($banner['ads'], true);
        die;
    }

    public function loginAction()
    {
        $json = array('msg' => 'success', 'xcode' => 'false', 'fwd' => '');
        $request = $_POST;
        $this->checkParams($request, ['username', 'password']);
        $m_user = new UsersModel();
        $username = $request['username'];
        $password = $request['password'];
        $username = preg_replace('#[\'\"\%\#\*\?\\\]+#', '', substr($username, 0, 32));
        if (empty($username) || empty($password)) {
            $json['msg'] = '请输入用户名及密码！';
            exit(json_encode($json));
        }
        $remember = 1;
        $error = $m_user->login($username, $password, $remember);
        if ($error) {
            $json['msg'] = $error;
            exit(json_encode($json));
        } else {
            $json['info'] = $m_user->fetch(['username' => $username]);
        }
        exit(json_encode($json));
    }
    public function loginoutAction(){
        $m_user = new UsersModel();
        $m_user->logout();
        exit(json_encode(array('status'=>'success')));
    }
    //领取礼包
    function getGiftAction(){
        $request = $_POST;
        $this->checkParams($request, ['user_id', 'gift_id']);
        $gift_id =$request['gift_id'];
        if( $gift_id < 1 ) {
            exit;
        }
        $cdkey = '';
        $m_gift = new GiftbagModel();
        $error = $m_gift->sendout($request['user_id'], $gift_id, $cdkey);
        if( $error == '' ) {
            exit(json_encode(array('msg'=>'领取成功!','cdkey'=>$cdkey)));
        } else {
            exit(json_encode(array('msg'=>$error,'cdkey'=>'')));
        }
    }
    //游戏详情
    function getGameDeatilAction(){
        $request = $_POST;
        $this->checkParams($request, ['game_id']);
        $m_game=new GameModel();
        $game = $m_game->fetch("game_id='{$request['game_id']}'");
        if( empty($game) ) {
            exit(json_encode(array('msg'=>'没有查到游戏详情!')));
        }
        $game['grade'] = $m_game->gradeHtml($game['grade']);
        $game['support'] = $m_game->supportFormat($game['support'] + $game['play_times']);

        $game['screenshots'] = empty($game['screenshots']) ? array() : unserialize($game['screenshots']);
        $assign['game'] = $game;
        //礼包详情
        $order = 'h5.giftbag.game_id DESC';
        $m_gift = new GiftbagModel();
        $game_id=$request['game_id'];
        $gifts = $m_gift->fetchAllBySql("select h5.giftbag.name,game_name,nums,used,content,gift_id,h5.game.logo from h5.giftbag  inner join h5.`game`  on h5.giftbag.game_id = h5.`game`.game_id where h5.`game`.game_id =  '{$game_id}' order by {$order}");
//        $log="select h5.giftbag.name,game_name,nums,used,content,h5.giftbag.gift_id,h5.game.logo from h5.giftbag  inner join h5.`game`  on h5.giftbag.game_id = h5.`game`.game_id where h5.`game`.game_type =  '{$game_type}' order by {$order}  LIMIT {$offset},{$limit}";
        foreach ($gifts as &$value){
            $value['content']=unserialize($value['content']);
            if($request['user_id']??0) {
                $rs=$m_gift->fetchBySql("select cdkey from h5.user_cdkey where user_id = {$request['user_id']} and gift_id = {$value['gift_id']}");
                $value['cdkey'] = $rs['cdkey'];
            }
        }
        $assign['gift'] = $gifts;
        //开服列表
        $m_server=new ServerModel();
//        $server_list=$m_server->fetchAll(['game_id'=>$request['game_id'],'start_time'=>],1,30,'*','start_time asc');
        $now_date=date('Y-m-d');
        $server_list=$m_server->fetchAllBySql("select * from server where game_id={$request['game_id']} and start_time>'{$now_date}' order by start_time asc");
        $assign['server']=$server_list;
        //资讯列表
        $m_article = new ArticleModel();
        $list=$m_article->fetchAll("game_id={$request['game_id']} and visible=1 and type!='代理公告'", 1, 10, 'article_id,cover,title,up_time', 'up_time DESC,weight ASC,article_id DESC');
        foreach ($list as &$row)
        {
            $row['up_time'] = $m_article->formatTime($row['up_time']);
        }
        $assign['info'] =$list;
//        //评论列表
//        $m_comment=new CommentModel();
//        $comment_list=$m_comment->fetchAll(['game_id']);
        exit(json_encode($assign));
    }
    function getCommentAction(){
        $request = $_GET;
        $this->checkParams($request, ['game_id','pn']);
        $pn = $request['pn'];
        $limit = 20;
        if( $pn < 1 || $limit < 1 ) {
            exit;
        }
        $m_comment=new CommentModel();
        $comment_list=$m_comment->getComment($request['game_id'],$pn,$limit);
        exit(json_encode($comment_list));
    }
    function addCommentAction(){
        $request = $_POST;
        $this->checkParams($request, ['parent_id','game_id','comm_cont','user_id']);
        $data=$request;
        $data['comm_time']=time();
        $data['like_num']=0;
        $m_comment=new CommentModel();
        if($m_comment->insert($data)){
            exit(json_encode(array('status'=>'success')));
        }else{
            exit(json_encode(array('status'=>'fail')));
        }
    }
    function addcommentlikeAction(){
        $request = $_GET;
        $this->checkParams($request, ['comm_id','user_id']);
        $m_commtlike=new CommentlikeModel();
        $rs=$m_commtlike->fetch(['comm_id'=>$request['comm_id'],'user_id'=>$request['user_id']]);
        if($rs){
            exit(json_encode(array('status'=>'fail','info'=>'你已点过赞了!')));
        }else{
            $m_commtlike->insert($request);
            $m_commtlike->fetchBySql("update comment set like_num = like_num+1 where comm_id={$request['comm_id']}");
            exit(json_encode(array('status'=>'success')));
        }
    }

    /**
     * 我的礼包
     */
    function myGiftBagAction(){
        $request = $_GET;
        $this->checkParams($request, ['user_id','pn','limit']);
        //礼包详情
        $m_user = new UsersModel();
        $m_game = new GameModel();
        $pn = $request['pn'];
        $limit = $request['limit'];
        if( $pn < 1 || $limit < 1 ) {
            exit;
        }
        $m_gift = new GiftbagModel();
        $logs = $m_user->giftLogs($request['user_id'], $pn, $limit);
        foreach ($logs as &$value){
            $gift=$m_gift->fetch(['gift_id'=>$value['gift_id']]);
            $game=$m_game->fetch(['game_id'=>$gift['game_id']],'logo');
            $value['content']=unserialize($gift['content']);
            $value['game_name']=$gift['game_name'];
            $value['name']=$gift['name'];
            $value['nums']=$gift['nums'];
            $value['used']=$gift['used'];
            $value['logo']=$game['logo'];
        }
        exit(json_encode($logs));
    }

    /**
 * 充值记录
 */
    function mypaylogAction(){
        $request = $_GET;
        $this->checkParams($request, ['user_id','pn','limit']);
        //礼包详情
        $pn = $request['pn'];
        $limit = $request['limit'];
        if( $pn < 1 || $limit < 1 ) {
            exit;
        }
        $m_pay = new PayModel();
        $logs = $m_pay->fetchAll("user_id='{$request['user_id']}' and pay_time > 0", $pn, $limit, 'pay_id,to_user,game_id,game_name,money,add_time', 'add_time DESC');
        exit(json_encode($logs));
    }
    /**
     * 最近在玩记录
     */
    function gamesLogsAction(){
        $request = $_GET;
        $this->checkParams($request, ['user_id']);
        $m_user = new UsersModel();
        $games = $m_user->getPlayGames($request['user_id'], 1, 12);
        exit(json_encode($games));
    }
    /**
     * 收藏记录
     */
    function favoritesAction(){
        $request = $_GET;
        $this->checkParams($request, ['user_id']);
        $m_user = new UsersModel();
        $games = $m_user->getFavorites($request['user_id'], 1, 12);
        exit(json_encode($games));
    }
    function isloginAction(){
        $m_user = new UsersModel();
        if($m_user->getLogin()){
            exit(json_encode(array('status'=>'success')));
        }else{
            exit(json_encode(array('status'=>'fail')));
        }
    }

    /**
     * 玩家推广统计
     */
    function playerChannelAction(){
        $request = $_GET;
        $this->checkParams($request, ['user_id']);
        $user_id=$request['user_id'];
        $m_user=new UsersModel();
        $count=$m_user->fetchBySql("select COUNT(*) AS number ,SUM(player_channel_get) as money  from h5.user where player_channel={$user_id}");
        $assign['money'] = $count['money'];
        $assign['number'] = $count['number'];
        exit(json_encode($assign));
    }
    function getGameDownloadUrlAction(){
        //获取下载资源链接
        $request = $_GET;
        $this->checkParams($request, ['game_id','channel_id']);
        $game_id=$request['game_id'];
        $channel_id=$request['channel_id'];
        //判断是否有包,没有则分包后下载
        $admin_id=$channel_id;
        if(file_exists("/www2/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/$admin_id.'.apk'")){
            $this->redirect("http://yun.zyttx.com/game/apk/{$game_id}/{$admin_id}.apk");
//            $this->downFile($admin_id.'.apk',"/www2/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/");
        }else {
            $zip = new ZipArchive();
            $filename = "/www2/wwwroot/code/h5/open/dev/public/game/apk/{$game_id}.apk";//母包位置
            //复制一份到当前
            //判断游戏目录是否存在
            $path = APPLICATION_PATH . "/public/game/apk/{$game_id}";
            if (!is_dir($path)) {
                mkdir($path);
            }
            shell_exec(" 
        PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin;~/bin;
        export PATH;
        cp {$filename}  /www2/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/{$admin_id}.apk;
        > /dev/null 2>&1 &");
            $now_path = $path . "/{$admin_id}.apk";
            if ($zip->open($now_path, ZIPARCHIVE::CREATE) !== TRUE) {
                exit("cannot open <$filename> ");
            }
            $zip->addFromString("META-INF/jiule_channelid", "{$admin_id}");
            $zip->addFromString("META-INF/jiule_gameid", "{$game_id}");
            //$zip->addFile($thisdir . "/too.php","/testfromfile.php");
//        echo "numfiles: " . $zip->numFiles . " ";
//        echo "status:" . $zip->status . " ";
            $zip->close();
//            $this->downFile($admin_id . '.apk', "/www2/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/");
            $this->redirect("http://yun.zyttx.com/game/apk/{$game_id}/{$admin_id}.apk");
        }
        Yaf_Dispatcher::getInstance()->disableView();
    }
    //不同环境下获取真实的IP
    function getIp()
    {
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else $ip = "Unknow";
        return $ip;
    }

    /**
     * 检查参数
     * @Author   liuqi
     * @DateTime 2018-08-22T11:01:38+0800
     * @param    [type]
     * @param    array
     * @return   [type]
     */
    private function checkParams(array $request, array $check)
    {
        foreach ($check as $key => $value) {
            if (!array_key_exists($value, $request)) {
                $rs['status'] = 1001;
                $rs['msg'] = $value . "参数必须!";
                echo json_encode($rs);
                die;
            }
            if ($request[$value]==='') {
                $rs['status'] = 1002;
                $rs['msg'] = $value . "参数值必须!";
                echo json_encode($rs);
                die;
            }
        }
    }
}
