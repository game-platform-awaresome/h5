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
    public function test2Action(){
        $channel=new TgchannelModel();
        var_dump($channel->fetchAll());
        $article=new ArticleModel();
        $articles=$article->fetchAll();
        die;
    }

    /**
     * 游戏登录
     */
    public function loginAction(){
        //打印参数
        $sign=isset($_GET['sign'])?preg_replace('/[^0-9a-f]+/','',$_GET['sign']):'';
        if(strlen($sign)!=32){
            exit('Params error.');
        }
        $params=array(
            'user_id'=>intval($_GET['user_id']),
            'username'=>urldecode($_GET['username']),
            'game_id'=>intval($_GET['game_id']),
            'server_id'=>isset($_GET['server_id'])?intval($_GET['server_id']):'',
            'time'=>intval($_GET['time']),
        );
        if(($params['time']+300)<time()){//避免链接被人盗用
            exit('Time error.');
        }
        ksort($params);//数组重新排序
        $key='123456';
        $verify=md5(implode('',$params).$key);//生成校验码
        if(strcmp($verify,$sign)!=0){//比对校验码
            exit('Sign error.');
        }
        echo '参数校验成功,缓存数据';
        $this->getView()->assign(array('params'=>$params));
        //记录用户及游戏信息，在充值时需要传回给纳米游戏
        //save($params['user_id'], $params['username'], $params['game_id'], $params['server_id']);
        }

        /**
        * 发起充值
        */
        public function payAction(){
//            var_dump($_REQUEST);
            $params=$_REQUEST;
            //签名
            $pay_url='http://www.h5.com/api/pay.html';//游戏充值地址
            $key='123456';//游戏密钥
            $params=array(
            'user_id'=>$params['user_id'],
            'username'=>$params['username'],//用户名在计算检验码之后再urlencode编码，登录时则先urldecode解码
            'game_id'=>$params['game_id'],
            'server_id'=>$params['server_id'],
            'money'=>$params['money'],
            'subject'=>$params['subject'],
            'body'=>$params['body'],
            'cp_order'=>date('YmdHis').rand(1,9999),
            'cp_return'=>$params['cp_return'],
            'time'=>time(),//3分钟内有效
            'extra'=>'',//允许为空的参数也可以不传递
            );
//            $md5=md5('10774180731291460210774180731291460227101268100元宝153302732910test122c5a644c4374cc4c88fe9d31f7a16b7b');
//            10774180731291460210774180731291460227101268100元宝153302732910test122c5a644c4374cc4c88fe9d31f7a16b7b
            ksort($params);//数组重新排序

            $md5=implode('',$params).$key;
            $params['sign']=md5(implode('',$params).$key);
            $params['username']=urlencode($params['username']);
            $params['subject']=urlencode($params['subject']);
            $params['body']=urlencode($params['body']);//如果包含非ASC字符的话
            $params['cp_return']=urlencode($params['cp_return']);
            $comma='?';
            foreach($params as $k=>$v)
            {
                $pay_url.="{$comma}{$k}={$v}";
                $comma='&';
            }
            $this->redirect($pay_url);
//            var_dump($pay_url);
//            exit($pay_url);
        }

    /**
     * 支付成功
     */
        function  payReturnAction(){
//            echo 'fails';
            var_dump($_REQUEST);
            echo 'success';
            exit;
        }
        function qrcodeAction(){

        }

}