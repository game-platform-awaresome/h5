<?php

class ApiController extends Yaf_Controller_Abstract
{
    //游戏直充
    public function payAction()
    {
        $req = $this->getRequest();
        $user_id = $req->get('user_id', 0);
        $sign = $req->get('sign', '');
        if( $user_id < 1 || strlen($sign) != 32 ) {
            exit('<b style="color:red">Params error.</b>');
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
        if( isset($_GET['server_id']) || isset($_POST['server_id']) ) {
            $srv_id_i = $req->get('server_id', 0);
            $srv_id_s = $req->get('server_id', '');
            if( strval($srv_id_i) === $srv_id_s ) {
                $arr['server_id'] = $srv_id_i;
            }
        }
        $time = time();
        if( ($arr['time'] + 300) < $time ) {
            exit('<b style="color:red">Time error.</b>');
        }
        
//        if( $arr['server_id'] ) {
//            $m_server = new ServerModel();
//            $server = $m_server->fetch("server_id='{$arr['server_id']}'", 'name,game_name,login_url,recharge_url,sign_key');
//            $server_name = $server['name'];
//            $game_name = $server['game_name'];
//            $login_url = $server['login_url'];
//            $recharge_url = $server['recharge_url'];
//            $sign_key = $server['sign_key'];
//        } else {
            $m_game = new GameModel();
            $game = $m_game->fetch("game_id='{$arr['game_id']}'", 'name,login_url,recharge_url,sign_key');
            $server_name = '';
            $game_name = $game['name'];
            $login_url = $game['login_url'];
            $recharge_url = $game['recharge_url'];
            $sign_key = $game['sign_key'];
//        }
        
        ksort($arr);
        $md5 = md5(implode('', $arr).$sign_key);
        if( strcmp($sign, $md5) != 0 ) {
            exit('<b style="color:red">Sign error.</b>');
        }
        
        //进行状态验证，以免非测试状态的游戏也在请求充值
        $m_devgms = new DevgamesModel();
        $devgms = $m_devgms->fetch("game_id='{$arr['game_id']}'", 'status');
        if( empty($devgms) ) {
            exit('<b style="color:red">Game not exists.</b>');
        }
        if( $devgms['status'] >= 6 ) {
            exit('<b style="color:red">Game is not on testing status.</b>');
        }
        
        $subject = $arr['subject'];
        $body = $arr['body'];
        unset($arr['subject'], $arr['body'], $arr['time']);
        
        $m_pay = new PayModel();
        $arr['pay_id'] = $m_pay->createPayId();
        $arr['to_uid'] = $arr['user_id'];
        $arr['to_user'] = $arr['username'];
        $arr['add_time'] = $time;
        $arr['pay_time'] = $time; //开放平台，无须支付
        $arr['game_name'] = $game_name;
        $arr['server_name'] = $server_name;
        $arr['type'] = 'deposit';
        $arr['cp_return'] = $arr['cp_return'] ? $arr['cp_return'] : '1';
        
        $rs = $m_pay->insert($arr, false);
        if( ! $rs ) {
            exit('<b style="color:red">Save payment failed.</b>');
        }
        
        $err = Game_Recharge::notify($recharge_url, $sign_key, $arr);
        if( $err ) {
            exit("<b style=\"color:red\">{$err}</b>");
        }
        $m_pay->update(array('finish_time'=>$time), "pay_id='{$arr['pay_id']}'");
        
        $return = $arr['cp_return'];
        if( $return == '1' ) {
            $return = Game_Login::redirect($user_id, $arr['username'], $arr['game_id'], $arr['server_id'], $login_url, $sign_key);
        }
        $this->getView()->assign('return', $return);
    }

    /**
     * 下载统计
     */
    public function countDownAction(){
        Yaf_Dispatcher::getInstance()->disableView();
        $ip=$this->getIp();
        $time=date('Y-m-d H:i:s');
        $myfile = fopen("/www2/wwwroot/code/h5/guanwang/dev/public/count.txt", "a") or die("Unable to open file!");
        //w  重写  a追加
        $txt = 'IP:'.$ip.'@'.$time."\n";
        fwrite($myfile, $txt);
    }
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
}
