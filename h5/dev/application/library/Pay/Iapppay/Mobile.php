<?php
/**
 * 爱贝-WAP支付
 */

class Pay_Iapppay_Mobile
{
    //支付跳转
    public function redirect($pay_id, $money, $subject, $body, $username)
    {
        $dir = dirname(__FILE__);
        include $dir.'/config.php';
        include $dir.'/base.php';
        
        $domain = empty($_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $suffix = Yaf_Registry::get('config')->application->url_suffix;
        $notify_url = "http://{$domain}/notify/iapppay{$suffix}";
        $return_url = "http://{$domain}/pay/result{$suffix}";
        $show_url = "http://{$domain}/pay/index{$suffix}";
        
        $order = array(
            'appid' => $appid,
            'waresid' => $prod_id,
            'waresname' => $subject,
            'cporderid' => $pay_id,
            'price' => floatval($money),
            'currency' => 'RMB',
            'appuserid' => $username,
            'notifyurl' => $notify_url,
        );
        
        $data = composeReq($order, $pri_key);
        $ret = request_by_curl($order_url, $data, 'rendered-by-dxj');
        //$log = new F_Helper_Log();
        //$log->debug($ret."\r\n");
        if( ! parseResp($ret, $pub_key, $json) ) {
            //$log->debug(var_export($json, true)."\r\n");
            echo "Parse response data failed!\n";
            exit;
        }
        
        $arr = array(
            'transid' => $json['transid'],
            'redirecturl' => $return_url,
        );
        $data = composeReq($arr, $pri_key);
        
        //支付跳转
        $url = 'https://web.iapppay.com/h5/exbegpay?'.$data;
        //header("Location: {$url}");
        return $url;
    }
    
    //后台通知
    public function notify()
    {
        $dir = dirname(__FILE__);
        include $dir.'/config.php';
        include $dir.'/base.php';
        
        $data = file_get_contents('php://input');
        if( ! parseResp($data, $pub_key, $json) ) {
            return false;
        }
        if( ! $json ) {
            return false;
        }
        if( ! isset($json['result']) || $json['result'] != 0 ) {
            return false;
        }
        
        switch ($json['paytype'])
        {
            case 1: $pay_type = '充值卡'; break;
            case 2: $pay_type = '游戏点卡'; break;
            case 4: $pay_type = '银行卡'; break;
            case 401: $pay_type = '支付宝'; break;
            case 402: $pay_type = '财付通'; break;
            case 501: $pay_type = '支付宝网页'; break;
            case 502: $pay_type = '财付通网页'; break;
            case 403: $pay_type = '微信支付'; break;
            case 5: $pay_type = '爱贝币'; break;
            case 6: $pay_type = '爱贝一键支付'; break;
            case 16: $pay_type = '百度钱包'; break;
            case 30: $pay_type = '移动话费'; break;
            case 31: $pay_type = '联通话费'; break;
            case 32: $pay_type = '电信话费'; break;
            default: $pay_type = '未知'; break;
        }
        
        return array(
            'pay_id' => $json['cporderid'],
            'trade_no' => $json['transid'],
            'pay_type' => $pay_type,
        );
    }
    
    //前台返回
    public function result()
    {
        $dir = dirname(__FILE__);
        include $dir.'/config.php';
        include $dir.'/base.php';
        
        if( ! parseResp($_SERVER['QUERY_STRING'], $pub_key, $json) ) {
            return false;
        }
        if( ! $json ) {
            return false;
        }
        if( isset($json['result']) ) {
            $result = $json['result'] == 0;
            return array(
                'pay_id' => $json['cporderid'],
                'trade_no' => $json['transid'],
                'money' => $json['money'],
                'result' => $result,
            );
        } else {
            return false;
        }
    }
}
