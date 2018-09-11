<?php
/**
 * 支付宝-WAP支付
 */

class Pay_Alipay_Mobile
{
    public function redirect($pay_id, $money, $subject, $body, $it_b_pay = null)
    {
        $dir = dirname(__FILE__);
        include $dir.'/alipay.config.php';
        include $dir.'/lib/alipay_submit.class.php';
        
        $domain = empty($_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $suffix = Yaf_Registry::get('config')->application->url_suffix;
        $notify_url = "http://{$domain}/notify/alipay{$suffix}";
        $return_url = "http://{$domain}/pay/result{$suffix}";
        $show_url = "http://{$domain}/pay/index{$suffix}";
        
        $parameter = array(
            'service' => 'alipay.wap.create.direct.pay.by.user',
            'partner' => trim($alipay_config['partner']),
            'seller_id' => trim($alipay_config['seller_id']),
            'payment_type'	=> '1',
            'notify_url'	=> $notify_url,
            'return_url'	=> $return_url,
            'out_trade_no'	=> $pay_id,
            'subject'	=> $subject,
            'total_fee'	=> $money,
            'show_url'	=> $show_url,
            'body'	=> $body,
            '_input_charset'	=> trim(strtolower($alipay_config['input_charset'])),
        );
        if( is_string($it_b_pay) && $it_b_pay ) {
            $parameter['it_b_pay'] = $it_b_pay;
        }
        
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');
        echo $html_text;
    }
    
    public function notify()
    {
        $dir = dirname(__FILE__);
        include $dir.'/alipay.config.php';
        include $dir.'/lib/alipay_notify.class.php';
        
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if( ! $verify_result ) {
            return false;
        }
        
        $r = array(
            'pay_id' => $_POST['out_trade_no'],
            'trade_no' => $_POST['trade_no'],
        );
        switch ($_POST['trade_status'])
        {
            case 'TRADE_FINISHED':
            case 'TRADE_SUCCESS':
                $r['status'] = 'success';
                break;
            default:
                $r['status'] = 'failed';
                break;
        }
        return $r;
    }
}
