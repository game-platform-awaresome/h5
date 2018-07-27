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
        $this->notify_url=$domain;
        $this->return_url=$domain;
        $config = Yaf_Registry::get('config')->goldpigpay;
        $this->userid=$config['userid'];
        $this->wooolID=$config['wooolID'];
    }
    public function redirect(array $pay)
    {
//        http://api.357p.com/?UserName=qq123456&Price=10&shouji=&PayID=16&userid=19027&wooolID=53498&jinzhua=&jinzhub=&jinzhuc=
        $params['UserName'] = $pay['username']; //玩家
        $params["Price"]    = $pay['money'];//金额
        $params['shouji']   = '';//qq号
        $params['PayID']    = $this->getPayID($pay['type']); //订单号
        $params['userid']   = $this->userid; //商户id
        $params['wooolID']  = $this->wooolID; //商户id
        $params["notifyurl"]  ='http://'.$this->notify_url;//回调地址
        $params["returnurl"]  ='http://'.$this->return_url;//跳转地址
        $params["jinzhua"]  =$pay['pay_id'];//订单号
        $params["jinzhub"]  = "";//
        $params["jinzhuc"]  = "";
        $acceptInfo = $this->http_post(API_URL,$params);
        echo $acceptInfo;
    }
    /**
     * 支付回调
     */
    public function payNotify(){
        $params=I('');
//        $params=json_decode('{"notify_id":"N201805061659498196427","merchant_id":"ad8f206c64954171b38c9a9c207dd9d7","increment_id":"CZ20180506165413","grandtotal":"0.1","receipt_amount":"0.1","currency":"CNY","trade_no":"4200000113201805064076385162","service":"smart_code_pay","rate":"4.4865","notify_time":"2018-05-06 16:59:49","created_at":"2018-05-06 16:54:22","gmt_payment":"2018-05-06 16:54:22","payment_channels":"WECHAT","subject":"\u5c0f\u5f20 vvip\u7ebd\u5e01\u652f\u4ed8","openid":"o4FhqwL59kXnFvST-7GvU3V9o4Mg","describe":"2018-05-06 16:54:20\u5c0f\u5f20 vvip\u7ebd\u5e01\u652f\u4ed8","trade_status":"TRADE_SUCCESS","signature":"9a9ce30f586e338c51fc53a61ecd268a","sign_type":"MD5"}',true);
        admin_option_log('EasyPay', 3, 'EasyPay支付回调参数' . json_encode($params,true)); //日志
        if($params['trade_status']=='TRADE_SUCCESS'){
            $data['increment_id']=$params['increment_id'];//订单号
            $increment_id=$data['increment_id'];
            $recharge_info=D('recharge')->where(['orderid'=>$increment_id])->find();
            //以下是移动端支付回调测试
            $order_phone=M('order')->where(['orderid'=>$increment_id])->find();
            if($order_phone['is_phone']==1){
                //订单支付
                $time=time();
                $rs1=D('order')->where(['orderid'=>$increment_id])->save(['pay_time'=>$time,'update_time'=>$time,'status'=>2]);
                //添加个人消费记录
                $order_info=D('order')->where(['ordrid'=>$increment_id])->find();
                $records_info['title']='二维码支付商品';
                $records_info['cid']=$order_info['customer_id'];
                $records_info['create_time']=$time;
                $records_info['sum']=$params['grandtotal'];
                $records_info['orderid']=$increment_id;
                $records_info['detail']=$order_info['detail'];
                $rs3=D('customerConsumerRecords')->add($records_info);
                $model=M();
                $model->startTrans();
                if ($rs1 && $rs3) {
                    $model->commit();
                    admin_option_log('EasyPay', 3, $increment_id.'支付支付回调完成'); //日志
                    echo 'success';//完成
                } else {
                    $model->rollback();
                    admin_option_log('EasyPay', 3, $increment_id.'支付回调失败'.$model->getError()); //日志
                    echo 'fail';
                }
            }
            if(!$recharge_info){
                $this->error('没有找到该订单');
            }
            if($recharge_info['status']==1){
                $this->error('该订单已充值');
            }
            //判断是充值订单还是支付订单
            $type=$recharge_info['type'];
            $money=$params['grandtotal'];//充值金额
            $model=M();
            $model->startTrans();
            $uid=$recharge_info['uid'];
            switch ($type){
                case 1:
                    //充值
                    if(!$recharge_info['consume_recharge_category']){//非定向
                        $youhui=0;
                        $favourable=M('favourable')->order('amount asc')->find();
                        foreach ($favourable as $key=>$value){
                            $amount     = $value['amount']; //充值金额
                            $favourable = $value['favourable']; //优惠
                            if ($money > $amount || $money == $amount) {
                                $youhui = $favourable;
                            }else {
                                break;
                            }
                        }
                        $rs1=D('recharge')->where(['orderid'=>$data['increment_id']])->save(array('status'=>1,'favourable'=>$youhui));
                        //最后累计金额、
                        $final_money = $money + $youhui;
                        if(!$uid){
                            $this->error('没有找到该客户');
                        }
                        $rs2=D('customer')->where(['id'=>$uid])->setInc('balance',$final_money);
                    }else{
                        //定向
                        $rs1=D('recharge')->where(['orderid'=>$data['increment_id']])->save(array('status'=>1));
                        $customer_consume_balance_id=D('customer_consume_balance')->where(['customer_id'=>$uid,'consume_category'=>$recharge_info['consume_recharge_category']])->getField('id');
                        if(!$customer_consume_balance_id){
                            $this->error('该客户没有该类型的定向充值，请联系管理员!');
                        }
                        $rs2=D('customer_consume_balance')->where(['id'=>$customer_consume_balance_id])->setInc('balance',$money);
                    }
                    if ($rs1 && $rs2) {
                        $model->commit();
                        admin_option_log('EasyPay', 3, $increment_id.'支付回调' . json_encode($params,true)); //日志
                        echo 'success';//完成
                    } else {
                        $model->rollback();
                        admin_option_log('EasyPay', 3, $increment_id.'支付回调失败' . json_encode($params,true)); //日志
                        echo 'fail';
                    }
                    break;
                case 2:
                    break;
                case 3:
                    //订单支付
                    $time=time();
                    $rs1=D('order')->where(['orderid'=>$increment_id])->save(['pay_time'=>$time,'update_time'=>$time,'status'=>2]);
                    $rs2=D('recharge')->where(['orderid'=>$data['increment_id']])->setField('status',1);
                    //添加个人消费记录
                    $order_info=D('order')->where(['ordrid'=>$increment_id])->find();
                    $records_info['title']='二维码支付商品';
                    $records_info['cid']=$order_info['customer_id'];
                    $records_info['create_time']=$time;
                    $records_info['sum']=$money;
                    $records_info['orderid']=$increment_id;
                    $records_info['detail']=$order_info['detail'];
                    $rs3=D('customerConsumerRecords')->add($records_info);
                    if ($rs1 && $rs2 && $rs3) {
                        $model->commit();
                        admin_option_log('EasyPay', 3, $increment_id.'支付支付回调完成'); //日志
                        echo 'success';//完成
                    } else {
                        $model->rollback();
                        admin_option_log('EasyPay', 3, $increment_id.'支付回调失败'.$model->getError()); //日志
                        echo 'fail';
                    }
                    break;
                default:
                    $this->error('支付类型错误');
                    break;
            }
        }else{
            echo 'fail';
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
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus  = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
    /*
     *
     *
     *  API 1.0版 获取自定义的加密方式  非微信  key的拼接方式不一样
     *  @param  Object obj 对象
     *  @param String  sign_key  签名秘钥
     *  @return String  签名
     *
     */
    public function getSignObjForAPI1_0($obj_arr, $sign_key)
    {
        ksort($obj_arr);
        $sign_str = '';
        $i        = 0;
        foreach ($obj_arr as $key => $val) {
            if ($i > 0) {
                $sign_str .= "&";
            }
            // add & to each parameter expcept the first one
            $sign_str .= "$key=$val";
            $i++;
        }
        return md5($sign_str . $sign_key);
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
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        {
            //移动端
            switch ($type){
                case 'alipay':
                    return 25;
                case 'wxpay':
                    return 16;
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
}
