<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/17/017
 * Time: 15:50
 */

class SdkapiController extends Yaf_Controller_Abstract
{
    /**
     * 登录接口
     * 参数：username,password,q_id,game_id
     */
//if (code == 100) {
//JSONObject data = json.getJSONObject("info");
//String user_id = data.getString("user_id");
//String user_name = data.getString("user_name");
//String user_password = data.getString("user_password");
//String q_id = data.getString("q_id");
//String game_id = data.getString("game_id");
//Login.this.onLogin(user_id, user_name, user_password, q_id, game_id);
    function init(){
        Yaf_Dispatcher::getInstance()->disableView();
    }
    //登录
    function loginAction(){
//        $_REQUEST['data']='username%3Dtest%26password%3D123456%26q_id%3Dnull%26game_id%3Dnull';
        $request=urldecode($_REQUEST['data']);
        $request=$this->convertUrlQuery($request);
        $username=$request['username'];
        $password=$request['password'];
        // $username='liuqi9';
        // $password='123456';
        $m_user=new UsersModel();
        if($user=$m_user->fetch(['username'=>$username,'password'=>md5($password)])){
            $data['status']=100;
            $info['user_id']=$user['user_id'];
            $info['user_name']=$user['username'];
            $info['user_password']=$password;
            $info['q_id']=$request['q_id']??0;
            $info['game_id']=$request['game_id']??0;
            $data['info']=$info;
        }else{
            $data['status']=404;
            $data['msg']='账号或密码错误';
        }
        echo json_encode($data);
    }
    //注册
    function registerAction(){
        $request=urldecode($_REQUEST['data']);
        $request=$this->convertUrlQuery($request);
        $username=$request['username'];
        $password=$request['password'];
        // username&password&q_id&game_id&Device_Id&Android;
        $m_user=new UsersModel();
        $data['username']=$username;
        $data['password']=md5($password);
        $data['app']='sdk';
        $data['reg_time']=time();
        $data['tg_channel']=$request['q_id'];
        if($m_user->fetch(['username'=>$username],'user_id')){
            $data['status']=404;
            $data['msg']='该账号已被使用';
        }else{
            if($user_id=$m_user->insert($data,true)){
                $user=$m_user->fetch(['user_id'=>$user_id]);
                $data['status']=100;
                $info['user_id']=$user['user_id'];
                $info['user_name']=$user['username'];
                $info['user_password']=$password;
                $info['q_id']=$request['q_id']??0;
                $info['game_id']=$request['game_id']??0;
                $data['info']=$info;
        }else{
            $data['status']=404;
            $data['msg']='账号或密码错误';
        }
        }
      
        echo json_encode($data);
    }
    function convertUrlQuery($query)
    {
    $queryParts = explode('&', $query);
    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }
    return $params;
    }
}