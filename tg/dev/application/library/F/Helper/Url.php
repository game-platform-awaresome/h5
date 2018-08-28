<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/15/015
 * Time: 14:59
 */
//域名助手
class F_Helper_Url
{
    function getUrl(){
        var_dump($_SERVER['SERVER_NAME']);
    }

    /**
     * 获取域名中代理的标识
     */
    function getUrlSign(){
        $url=isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $info=explode('.',$url);
        if(is_numeric($info[0])){
            return $info[0];//三级代理域名
        }else{
            return $_GET['user']??1;//一级域名，支持带参数
        }
    }
}