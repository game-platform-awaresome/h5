<?php

class SmscodeMobileModel extends F_Model_Pdo
{
	protected $_table = 'smscode_mobile';
	protected $_primary = 'mobile';
	
	public function getTableLabel()
	{
		return '短信手机发送日志';
	}
	
	/**
	 * 发送手机验证码
	 * 
	 * @param string $mobile
	 * @param int $ip
	 * @param string $message
	 * @return mixed
	 */
	public function send($mobile, $ip, $message)
	{
	    $time = time();
	    $pdo = $this->getPdo();
	    $stm = $pdo->query("SELECT * FROM smscode_ip WHERE ip='{$ip}' LIMIT 1");
	    $ip_log = $stm->fetch(PDO::FETCH_ASSOC);
	    if( $ip_log && ($ip_log['send_time'] + 10) > $time ) {
	        return '你的IP请求过于频繁，请稍后再试！';
	    }
	    
	    $conds = "mobile='{$mobile}'";
	    $mb_log = $this->fetch($conds);
	    if( $mb_log ) {
	        if( ($mb_log['send_time'] + 60) > $time ) {
	            return '该手机号请求过于频繁，请稍后再试！';
	        }
	        if( $mb_log['day_times'] >= 10 ) {
	            return '该手机号当日短信已用完！';
	        }
	    }
	    
	    if( $ip_log ) {
	        $pdo->exec("UPDATE smscode_ip SET send_time='{$time}',day_times=day_times+1 WHERE ip='{$ip}'");
	    } else {
	        $pdo->exec("INSERT INTO smscode_ip SET ip='{$ip}',send_time='{$time}',day_times=1");
	    }
	    
	    $xcode = mt_rand(100000, 999999);
	    if( $mb_log ) {
	        $rs = $pdo->exec("UPDATE smscode_mobile SET xcode='{$xcode}',send_time='{$time}',day_times=day_times+1 WHERE {$conds}");
	    } else {
	        $rs = $this->insert(array(
	            'mobile' => $mobile,
	            'xcode' => $xcode,
	            'send_time' => $time,
	            'day_times' => 1,
	        ), false);
	    }
	    
	    if( ! $rs ) {
	        return '短信验证码发送失败，请稍后重试！';
	    }
	    
	    $url = 'http://sdk4rptws.eucp.b2m.cn:8080/sdkproxy/sendsms.action';
	    $conf = Yaf_Application::app()->getConfig();
	    $message = str_replace('{xcode}', $xcode, $message);
	    $post = 'cdkey='.$conf->emay->cdkey;
	    $post .= '&password='.$conf->emay->password;
	    $post .= '&phone='.$mobile;
	    $post .= '&message='.urlencode($message);
	    
	    //发送验证码
	    $curl = new F_Helper_Curl();
	    $xml = $curl->request($url, $post);
	    $h_xml = new F_Helper_Xml();
	    $rs = $h_xml->parse($xml);
	    
	    if( $rs == false ) {
	        file_put_contents(APPLICATION_PATH.'/logs/emay.log', $xml);
	        return 1;
	    }
	    if( $rs['error'] != 0 ) {
	        file_put_contents(APPLICATION_PATH.'/logs/emay.log', "{$rs['error']}: {$rs['message']}\r\n");
	        return 2;
	    }
	    
	    //记录成功日志
	    $ymd = date('Ymd', $time);
	    $message = addslashes($message);
	    $pdo->exec("INSERT INTO smscode_log(mobile,ip,message,ymd) VALUES('{$mobile}','{$ip}','{$message}','{$ymd}')");
	    
	    return $xcode;
	}
	
	/**
	 * 判断验证码是否有效
	 * 
	 * @param string $mobile
	 * @param int $xcode
	 * @return string $err
	 */
	public function check($mobile, $xcode)
	{
	    if( strlen($xcode) != 6 ) {
	        return '短信验证码格式错误！';
	    }
	    $time = time();
	    $mb_log = $this->fetch("mobile='{$mobile}'");
	    if( empty($mb_log) ) {
	        return '短信验证码未发送成功！';
	    }
	    if( ($mb_log['send_time'] + 1800) < $time ) {
	        return '短信验证码已过期，请重新发送！';
	    }
	    if( strcmp($mb_log['xcode'], $xcode) != 0 ) {
	        return '短信验证码错误，请核对！';
	    }
	    return '';
	}
	
	//重置验证日志
	public function clear()
	{
	    $time = strtotime(date('Y-m-d')) - 600;
	    $this->delete("send_time<{$time}");
	    
	    $pdo = $this->getPdo();
	    $pdo->exec("DELETE FROM smscode_ip WHERE send_time<{$time}");
	}
}
