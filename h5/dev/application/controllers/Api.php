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
            //开启事务
            $pdo = $m_user->getPdo();
            $pdo->beginTransaction();
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
                    $pdo->commit();
                    echo json_encode(['code' => 0, 'message' => $return]);
                } else {
                    $pdo->rollBack();
                    echo json_encode(['code' => 1, 'message' => '游戏发货失败,请联系客服处理']);
                }
            } else {
                $pdo->rollBack();
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
        $this->m_user = new UsersModel();
        $username = $request['username'];
        $password = $request['password'];
        $username = preg_replace('#[\'\"\%\#\*\?\\\]+#', '', substr($username, 0, 32));
        if (empty($username) || empty($password)) {
            $json['msg'] = '请输入用户名及密码！';
            exit(json_encode($json));
        }
        $remember = 0;
        $error = $this->m_user->login($username, $password, $remember);
        if ($error) {
            $json['msg'] = $error;
            exit(json_encode($json));
        } else {
            $json['info'] = $this->m_user->fetch(['username' => $username]);
        }
        exit(json_encode($json));
    }
//    //注销
//    public function logoutAction()
//    {
//        $s = Yaf_Session::getInstance();
//        $s->del('user_id');
//        $s->del('username');
//        $s->del('nickname');
//        $s->del('email');
//        if( isset($_COOKIE[$this->_ck_ui_name]) ) {
//            $domain = Yaf_Registry::get('config')->cookie->domain;
//            setcookie($this->_ck_ui_name, '', 1, '/', $domain);
//        }
//    }
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
            if (!$request[$value]) {
                $rs['status'] = 1002;
                $rs['msg'] = $value . "参数值必须!";
                echo json_encode($rs);
                die;
            }
        }
    }
}
