<?php

class Game_Recharge
{
    /**
     * 充值到游戏
     * 
     * @param string $url
     * @param string $key
     * @param array &$pay array(pay_id,user_id,username,to_uid,to_user,game_id,server_id,money)
     * @return string $error
     */
    static public function notify($url, $key, &$pay)
    {
        if( $url == '' ) {
            return '游戏的充值地址为空，无法完成充值！';
        }
        if( $key == '' ) {
            return '游戏的密钥为空，无法完成充值！';
        }
        
        $time = time();
        $data = array(
            'user_id' => $pay['user_id'],
            'username' => $pay['username'],
            'to_uid' => $pay['to_uid'],
            'to_user' => $pay['to_user'],
            'pay_id' => $pay['pay_id'],
            'money' => $pay['money'],
            'game_id' => $pay['game_id'],
            'server_id' => $pay['server_id'],
            'cp_order' => $pay['cp_order'],
            'time' => $time,
        );
        if( isset($pay['extra']) && $pay['extra'] ) {
            $data['extra'] = $pay['extra'];
        }
        ksort($data);
        
        $data['sign'] = md5(implode('', $data).$key);
        
        $data['username'] = urlencode($data['username']);
        $data['to_user'] = urlencode($data['to_user']);
        $query = '';
        foreach ($data as $k=>$v)
        {
            $query .= "&{$k}={$v}";
        }
        $url = rtrim($url, '&?');
        if( strpos($url, '?') === false ) {
            $url .= '?';
            $url .= ltrim($query, '&');
        } else {
            $url .= $query;
        }
        
        $curl = new F_Helper_Curl();
        $ret = $curl->request($url);
        //记录日志
        $adminlog=new AdminlogModel();
        $adminlog->insert(['admin'=>'支付通知','content'=>$url,'ymd'=>date('Ymd'),'op_time'=>date('Y-m-d H;i;s')]);
        if( trim($ret) == 'success' ) {
            return '';
        } else {
            $log = new F_Helper_Log();
            $log->debug("Recharge Notify:\r\n");
            $log->debug("{$url}\r\n{$ret}\r\n\r\n");
            return '充值到游戏时发生错误，请联系客服。';
        }
    }
}
