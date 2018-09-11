<?php

class Game_Login
{
    /**
     * 登录到游戏
     * 
     * @param int $uid
     * @param string $uname
     * @param int $gid
     * @param int $sid
     * @param string $url
     * @param string $key
     */
    static public function redirect($uid, $uname, $gid, $sid, $url, $key)
    {
        $time = time();
        $data = array(
            'user_id' => $uid,
            'username' => $uname,
            'game_id' => $gid,
            'server_id' => $sid,
            'time' => $time,
        );
        ksort($data);
        
        $data['sign'] = md5(implode('', $data).$key);
        
        $data['username'] = urlencode($data['username']);
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
        
        return $url;
    }
}
