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
    function loginAction(){
//        $_REQUEST['data']='username%3Dtest%26password%3D123456%26q_id%3Dnull%26game_id%3Dnull';
        $request=urldecode($_REQUEST['data']);
        $request=$this->convertUrlQuery($request);
        $username=$request['username'];
        $password=$request['password'];
        // $username='liuqi9';
        // $password='123456';
        $m_user=new UsersModel();
        if($user=$m_user->fetch(['username'=>$username,md5($password)])){
            $data['status']=100;
            $info['user_id']=$user['user_id'];
            $info['user_name']=$user['nickname'];
            $info['user_password']=$user['password'];
            $info['q_id']=$request['q_id']??0;
            $info['game_id']=$request['game_id']??0;
            $data['info']=$info;
        }else{
            $data['msg']='账号或密码错误';
        }
        echo json_encode($data);
    }
    function registerAction(){
        echo json_encode(['code'=>1,'message'=>'注册成功']);
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