<?php

class Game_Channel_Egret
{
    private $appid = '18545';
    private $appkey = 'CsHrZi71umFIRn35xlgZx';
    
    /**
     * 登录白鹭游戏
     * 
     * @param array &$user
     * @param array &$gm_src
     */
    public function login(&$user, &$gm_src)
    {
        $time = time();
        $data = array(
            'appId' => $this->appid,
            'time' => $time,
            'userId' => $user['user_id'],
        );
        $str = '';
        foreach ($data as $k=>$v)
        {
            $str .= "{$k}={$v}";
        }
        $str .= $this->appkey;
        $sign = md5($str);
        
        $ext = "{$gm_src['game_id']},{$gm_src['server_id']}";
        $data = array_merge($data, array(
            'userName' => $user['username'],
            'userImg' => $user['avatar'],
            'userSex' => $user['sex'],
            'channelExt' => $ext,
            'sign' => $sign,
        ));
        
        $query = '';
        foreach ($data as $k=>$v)
        {
            $query .= "&{$k}={$v}";
        }
        
        $url = rtrim($gm_src['login_url'], '&?');
        if( strpos($url, '?') === false ) {
            $url .= '?'.ltrim($query, '&');
        } else {
            $url .= $query;
        }
        
        return $url;
    }
    
    /**
     * 验证白鹭的支付请求并返回格式化支付信息
     * 
     * @param Yaf_Request_Http $req
     * @return $pay
     */
    public function pay($req)
    {
        $sign = strtolower($req->get('sign', ''));
        $sign = preg_replace('/[^0-9a-f]+/', '', $sign);
        if( strlen($sign) != 32 ) {
            return 'Sign error.';
        }
        
        $data = array(
            'appId' => $this->appid,
            'egretOrderId' => $req->get('egretOrderId', ''),
            'gameId' => $req->get('gameId', 0),
            'goodsId' => $req->get('goodsId', 0),
            'money' => $req->get('money', 0),
            'time' => $req->get('time', ''),
            'userId' => $req->get('userId', 0),
        );
        $str = '';
        foreach ($data as $k=>$v)
        {
            $str .= "{$k}={$v}";
        }
        $str .= $this->appkey;
        $verify = md5($str);
        
        if( strcmp($sign, $verify) != 0 ) {
            return 'Sign not match.';
        }
        
        $username = $req->get('userName', '');
        $pay = array(
            'user_id' => $data['userId'],
            'username' => $username,
            'to_uid' => $data['userId'],
            'to_user' => $username,
            'money' => $data['money'],
            'add_time' => $data['time'],
            'cp_order' => $data['egretOrderId'],
            'cp_return' => $req->get('gameUrl', ''),
            'channel' => 'egret',
            'extra' => $req->get('ext', ''),
            'subject' => $req->get('goodsName', ''),
        );
        
        $ext = $req->get('channelExt', '');
        $ext = explode(',', $ext);
        if( is_numeric($ext[0]) && is_numeric($ext[1]) ) {
            $pay['game_id'] = $ext[0];
            $pay['server_id'] = $ext[1];
        } else {
            $pay['chnl_gid'] = $data['gameId'];
        }
        
        return $pay;
    }
    
    /**
     * 通知白鹭充值成功
     * 
     * @param string $url
     * @param string $key
     * @param array &$pay
     * @return string $error
     */
    public function notify($url, $key, &$pay)
    {
        $key = $this->appkey;
        
        $data = array(
            'orderId' => $pay['pay_id'],
            'userId' => $pay['to_uid'],
            'money' => $pay['money'],
            'ext' => $pay['extra'],
            'time' => time(),
        );
        ksort($data);
        
        $str = '';
        foreach ($data as $k=>$v)
        {
            $str .= "{$k}={$v}";
        }
        $str .= $key;
        $data['sign'] = md5($str);
        
        $data = http_build_query($data);
        $curl = new F_Helper_Curl();
        $rs = $curl->request($url, $data);
        
        $rs = strtolower(trim($rs));
        if( $rs != 'success' ) {
            $log= new F_Helper_Log();
            $log->debug("Egret Pay Notify:\r\n{$url}\r\n{$data}\r\n{$rs}\r\n\r\n");
        }
        
        return $rs == 'success' ? '' : '游戏充值失败！';
    }
}
