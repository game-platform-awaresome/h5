<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/25/025
 * Time: 11:12
 */

class TestController extends Yaf_Controller_Abstract
{
    public function indexAction(){
        $info = "1\t2\t3\t4\t5\t6";
        var_dump($info);
        $time=time() + 86400 * 7;
        $info = F_Helper_Mcrypt::authcode($info, 'ENCODE');
        var_dump(Yaf_Registry::get('config')->mcrypt->key);
        var_dump($info);
        setcookie('test', $info, $time, '/');
        var_dump($_COOKIE['test']);
        $info2 = F_Helper_Mcrypt::authcode($_COOKIE['test'],'DECODE');
        var_dump($info2);
        die;
    }

}