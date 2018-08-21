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
    function login(){
        $username=$_REQUEST['username'];
        $password=$_REQUEST['password'];
        $q_id=$_REQUEST['qid'];
        $game_id=$_REQUEST['game_id'];
        $user=new UsersModel();
        echo json_encode(['code'=>1,'message'=>'登陆成功']);
    }
    function register(){
        echo json_encode(['code'=>1,'message'=>'注册成功']);
    }
}