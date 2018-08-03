<?php
/**
 * 金猪支付
 */
const API_URL='http://api.357p.com';//接口地址
const ALI_PAY_PC=25;//支付宝PC
const ALI_PAY_MOBILE=26;//支付宝手机
const WX_PAY_PC=16;//微信PC
const WX_PAY_MOBILE=29;//微信手机
class Pay_Pigpay_Mobile
{
    protected $notify_url;//回调地址参数
    protected $return_url;
    private $userid;//商户ID
    private $wooolID;//商户平台id
    public function __construct()
    {
        $domain = empty($_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $domain ="www.zyttx.com";
        $this->notify_url=$domain;
        $this->return_url=$domain;
        $config = Yaf_Registry::get('config')->goldpigpay;
        $this->userid=$config['userid'];
        $this->wooolID=$config['wooolID'];
    }
    public function redirect(array $pay)
    {
//        http://api.357p.com/?UserName=qq123456&Price=10&shouji=&PayID=16&userid=19027&wooolID=53498&jinzhua=&jinzhub=&jinzhuc=
        $suffix = Yaf_Registry::get('config')->application->url_suffix;
        $params['UserName'] = $pay['username']; //玩家
        $params["Price"]    = $pay['money'];//金额
        $params['shouji']   = '';//qq号
        $params['PayID']    = $this->getPayID($pay['type']); //订单号
        $params['userid']   = $this->userid; //商户id
        $params['wooolID']  = $this->wooolID; //商户id
        $params["notifyurl"]  ='http://'.$this->notify_url.'/notify/pigpay'.$suffix;//回调地址
        $params["returnurl"]  ='http://'.$this->return_url.'/pay/result'.$suffix;//跳转地址
        $params["jinzhua"]  =$pay['pay_id'];//订单号
        $params["jinzhub"]  = "123";//
        $params["jinzhuc"]  = "123";
        $params["jinzhue"]  = "123";
        $acceptInfo = $this->http_post(API_URL,$params);
        echo $acceptInfo;
    }
    //后台通知
    public function notify()
    {
        //记录日志
//        $m_log = new AdminlogModel();
//        $m_log->insert(array(
//            'admin' => '金猪支付',
//            'content' => json_encode($_POST),
//            'ymd' => date('Ymd'),
//        ));
        $r = array(
            'pay_id' => $_REQUEST['jinzhue'],
            'trade_no' => $_REQUEST['OrderID'],
            'pay_type' => $_REQUEST['jinzhuc'],
        );
        return $r;
    }
    //前台返回
    public function result()
    {
        $pay=new PayModel();
        $pay_info=$pay->fetch(['pay_id'=>$_GET['jinzhue']]);
        if( isset($pay_info['pay_time']) && $pay_info['pay_time']>0) {
            return array(
                'pay_id' => $pay_info['pay_id'],
                'trade_no' => $pay_info['trade_no'],
                'money' => $pay_info['money'],
                'result' => true,
            );
        } else {
            return false;
        }
    }
    /**
     * GET 请求
     * @param string $url
     */
    function http_get($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }
    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    public function http_post($url, $param, $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, 1);
        $sContent = curl_exec($oCurl);
        $aStatus  = curl_getinfo($oCurl);
        curl_close($oCurl);
        return $sContent;
//        if (intval($aStatus["http_code"]) == 200) {
//            return $sContent;
//        } else {
//            return false;
//        }
    }
    /*
     *
     *将对象的属性名与属性值拼接成URL格式的字符串
     * obj 参数对象 如果字符串中包含中文 或者http 则进行转码
     * @return String 拼接好的字符串
     *
     *
     */
// make equivalent PHP function to JS encodeURIComponent
    public function encodeURIComponent($str)
    {
        $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
        return strtr(rawurlencode($str), $revert);
    }
    /**
     * @Author   liuqi
     * @DateTime 2017-09-19
     * @version  [version]
     * @param    [type]     $obj_arr [description]
     * @return   [type]              [description]
     */
    public function getEncodeUrlStrFromObj($obj_arr)
    {
        $encode_str = "";
        $i          = 0;
        foreach ($obj_arr as $key => $val) {
            if ($i > 0) {
                $encode_str .= "&";
            }
            // add & to each parameter expcept the first one
            if (preg_match("/[\x7f-\xff]/", $val)) {
                // With Chinese character
                $encode_str .= "$key=" . $this->encodeURIComponent($val);
            } else {
                // No Chinese character
                $encode_str .= "$key=$val";
            }
            $i++;
        }
        return ($encode_str);
    }

    /**
     * 获取通道id
     */
    private function getPayID($type)
    {
        //1.判断PC还是移动端
        if ($this->isMobile())
        {
            //移动端
            switch ($type){
                case 'alipay':
                    return 26;
                case 'wxpay':
                    return 29;
                default:
                    throw new Exception('未知的支付方式');
            }

        }else{
            //PC
            switch ($type){
                case 'alipay':
                    return 25;
                case 'wxpay':
                    return 16;
                default:
                    throw new Exception('未知的支付方式');
            }
        }
    }
    /**
     * 移动端判断
     */
    private function isMobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备
        if (isset ($_SERVER['HTTP_VIA']))
        {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT']))
        {
            $clientkeywords = array ('nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT']))
        {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
            {
                return true;
            }
        }
        return false;
    }
}
