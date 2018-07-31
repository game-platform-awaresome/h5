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
        echo '参数校验成功,保存信息，充值时可用';
        //记录用户及游戏信息，在充值时需要传回给纳米游戏
        //save($params['user_id'], $params['username'], $params['game_id'], $params['server_id']);
        }

}