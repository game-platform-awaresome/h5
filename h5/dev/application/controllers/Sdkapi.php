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
    public function init()
    {
        Yaf_Dispatcher::getInstance()->disableView();
    }
    //登录
    public function loginAction()
    {
//        $_REQUEST['data']='username%3Dtest%26password%3D123456%26q_id%3Dnull%26game_id%3Dnull';
        $request = urldecode($_REQUEST['data']??'');
        $request = $this->convertUrlQuery($request);
        $this->checkParams($request, ['username', 'password']);
        $username = $request['username'];
        $password = $request['password'];
        $m_user   = new UsersModel();
        if ($user = $m_user->fetch(['username' => $username, 'password' => md5($password)])) {
            if($user['status']=='禁用'){
                $data['status'] = 404;
                $data['msg']    = '账号被禁用';
            }else {
                $data['status'] = 100;
                $info['user_id'] = $user['user_id'];
                $info['user_name'] = $user['username'];
                $info['user_password'] = $password;
                $info['q_id'] = $request['q_id'] ?? 0;
                $info['game_id'] = $request['game_id'] ?? 0;
                $data['info'] = $info;
            }
        } else {
            $data['status'] = 404;
            $data['msg']    = '账号或密码错误';
        }
        echo json_encode($data);
    }
    //注册
    public function registerAction()
    {
        $request = urldecode($_REQUEST['data']??'');
        $request = $this->convertUrlQuery($request);
        $this->checkParams($request, ['username', 'password']);
        $username           = $request['username'];
        $password           = $request['password'];
        $m_user             = new UsersModel();
        $data['username']   = $username;
        $data['password']   = md5($password);
        $data['app']        = 'sdk';
        $data['reg_time']   = time();
        $data['tg_channel'] = $request['q_id'];
        if ($m_user->fetch(['username' => $username], 'user_id')) {
            $data['status'] = 404;
            $data['msg']    = '该账号已被使用';
        } else {
            if ($user_id = $m_user->insert($data, true)) {
                $user                  = $m_user->fetch(['user_id' => $user_id]);
                $data['status']        = 100;
                $info['user_id']       = $user['user_id'];
                $info['user_name']     = $user['username'];
                $info['user_password'] = $password;
                $info['q_id']          = $request['q_id'] ?? 0;
                $info['game_id']       = $request['game_id'] ?? 0;
                $data['info']          = $info;
            } else {
                $data['status'] = 404;
                $data['msg']    = '账号或密码错误';
            }
        }
        echo json_encode($data);
    }

    /**
     * 修改密碼
     * @Authorliuqi
     * @DateTime    2018-08-22T10:46:03+0800
     * @return      [josn]
     */
    public function changePwdAction()
    {
        $request = urldecode($_REQUEST['data']??'');
        $request = $this->convertUrlQuery($request);
        $this->checkParams($request, ['username', 'password']);
        $username = $request['username'];
        $password = $request['password'];
//        String data = "username=&newpassword=";
        $m_user = new UsersModel();
        $user   = $m_user->fetch(['username' => $username], 'user_id');
        if ($user && $m_user->update(['password' => md5($password)], ['user_id' => $user['user_id']])) {
            $data['status'] = 100;
            $data['msg']    = '修改成功';
        } else {
            $data['status'] = 404;
            $data['msg']    = '没有找到该账户';
        }
        echo json_encode($data);
    }
    public function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params     = array();
        foreach ($queryParts as $param) {
            $item             = explode('=', $param);
            $params[$item[0]] = $item[1] ?? '';
        }
        return $params;
    }
    /**
     * 充值
     * @Author   liuqi
     * @DateTime 2018-08-22T14:06:06+0800
     * @return   [type]                   [description]
     */
    public function payAction()
    {
        // username 用户名 liuqi
        // user_id 用户id
        // q_id 代理id
        // game_id 游戏id
        // gold 充值金额
        // goods_id 商品id
        // role_id 角色id
        // role_name 角色名称
        // server_id 区服id 1
        // sdkorder  订单号 4bb31a31-31ee-48e6-839f-c9e478485d17
        //生成订单
        //*****************测试返回*********************//
            
        // $pay_id='5356027267923333';
        // $data['status']=100;
        // $data['msg']     = '创建订单成功';
        // $info['pay_url'] = 'http://' . $_SERVER['HTTP_HOST']  . '/pay/result?orderId=' . $pay_id.'&jinzhue='.$pay_id;
        // $data['info']    = $info;
        // echo json_encode($data);
        // die;
        //*****************测试返回*********************//
        $request = urldecode($_REQUEST['data']??'');
        $request = $this->convertUrlQuery($request);
        $this->checkParams(
            $request,
            ['username', 'user_id', 'q_id', 'game_id', 'gold', 'goods_id', 'role_id', 'role_name', 'server_id', 'order_number']
        );
        $m_pay     = new PayModel();
        $m_game    = new GameModel();
        $game_name = $m_game->fetch(['game_id' => $request['game_id']], 'name');
        $pay_id=$m_pay->createPayId();
        $m_user=new UsersModel();
        $user_info=$m_user->fetch(['user_id'=>$request['user_id']],'player_channel');
        $pay       = array(
            'pay_id'      => $pay_id,
            'user_id'     => $request['user_id'],
            'username'    => $request['username'],
            'role_id'    => $request['role_id'],
            'to_uid'      => $request['user_id'],
            'to_user'     => $request['username'],
            'game_id'     => $request['game_id'],
            'game_name'   => $game_name,
            'server_id'   => $request['server_id'],
            'server_name' => '',
            'money'       => $request['gold'],
            'deposit'     => 0,
            'type'        => '',
            'pay_type'    => '',
            'add_time'    => time(),
            'cp_order'    => $request['order_number'],
            'cp_return'    => 'android',
            'tg_channel'  => $request['q_id'],
            'extra'       => $request['goods_id'],
            'player_channel'=>$user_info['player_channel']
        );
        $m_pay->insert($pay);
        if ($m_pay->fetch(['pay_id'=>$pay_id],'pay_id')) {
            $data['status']  = 100;
            $data['msg']     = '创建订单成功';
            $info['pay_url'] = 'http://' . $_SERVER['HTTP_HOST']  . '/sdkapi/payh5?pay_id=' . $pay_id;
            $data['info']    = $info;
        } else {
            $data['status'] = 500;
            $data['msg']    = '创建订单失败';
        }
        echo json_encode($data);
    }
    public function payh5Action()
    {
        Yaf_Dispatcher::getInstance()->enableView();
        $m_pay  = new PayModel();
        $pay_id = $_GET['pay_id'] ?? 0;
        if ($pay = $m_pay->fetch(['pay_id' => $pay_id])) {
            $this->getView()->assign('pay', $pay);
            $conf = Yaf_Application::app()->getConfig();
            $this->getView()->assign(array('parities' => $conf->application->parities));
        } else {
            throw new Exception("未知订单", 1);

        }

    }
    /**
     * 订单查询
     * @Author   liuqi
     * @DateTime 2018-08-22T14:06:16+0800
     * @return   [type]                   [description]
     */
    public function paystatusAction()
    {
        $request = urldecode($_REQUEST['data']??'');
        $request = $this->convertUrlQuery($request);
        $this->checkParams($request, ['sdkorder']);
        $sdkorder=$request['sdkorder'];
        $m_pay=new PayModel();
        $pay=$m_pay->fetch(['cp_order'=>$sdkorder]);
        if($pay && $pay['pay_time']>0){   
            $data['status'] = 100;
            $data['msg']    = '支付完成';
        }else{
            $data['status'] = 500;
            $data['msg']    = '支付未完成';
        }
        echo json_encode($data);
    }
    /**
     * 创建角色
     * @Author   liuqi
     * @DateTime 2018-08-22T17:20:20+0800
     * @return   [type]                   [description]
     */
    public function roleCreateAction(){
        // qu_id=1
        // game_id=1
        // role_id=1
        // role_name=1
        // username=liuqi
        // server_id=1
        // user_id=9
        $request = urldecode($_REQUEST['data']??'');
        $request = $this->convertUrlQuery($request);
        $this->checkParams($request, ['qu_id','game_id','role_id','role_name','username','server_id','user_id']);
        $m_game_role=new GameRoleModel();
        $info['role_id']=$request['role_id'];
        $info['game_id']=$request['game_id'];
        $info['role_name']=$request['role_name'];
        $info['username']=$request['username'];
        $info['server_id']=$request['server_id'];
        $info['user_id']=$request['user_id'];
        $info['tg_channel']=$request['qu_id'];
        if($m_game_role->fetch($info)){
            $data['status'] = 500;
            $data['msg']    = '请勿重复创建';
        }else{
        $id=$m_game_role->insert($info,true);
         if($id){   
            $data['status'] = 100;
            $data['msg']    = '创建成功';
        }else{
            $data['status'] = 500;
            $data['msg']    = '创建失败';
        }
        }
        echo json_encode($data);
    }
    /**
     * 客服
     * @Author   liuqi
     * @DateTime 2018-08-23T10:54:47+0800
     * @return   [type]                   [description]
     */
    public function serviceAction(){
      $request = urldecode($_REQUEST['data']??'');
      $request = $this->convertUrlQuery($request); 
      $this->checkParams($request, ['q_id']);
      $tg_channel=$request['q_id'];
      $m_channel=new AdminModel('cps');
      $channel_info=$m_channel->fetch(['admin_id',$tg_channel],'admin_id as service_id,nickname as service_name,qq1 as service_qq');
      if($channel_info){
        $data['status']=100;
        $info['service_id']=$channel_info['service_id'];
        $info['service_name']=$channel_info['service_name'];
        $info['service_qq']=$channel_info['service_qq'];
        $data['info'][0]=$info;
      }else{
        $data['status']=200;
        $msg['暂无客服信息'];
      }
      echo json_encode($data);
    }
    /**
     * 检查参数
     * @Author   liuqi
     * @DateTime 2018-08-22T11:01:38+0800
     * @param    [type]
     * @param    array
     * @return   [type]
     */
    private function checkParams(array $request, array $check)
    {
        foreach ($check as $key => $value) {
            if (!array_key_exists($value, $request)) {
                $rs['status'] = 1001;
                $rs['msg']  = $value . "参数必须!";
                echo json_encode($rs);
                die;
            }
            if (!$request[$value]) {
                $rs['status'] = 1002;
                $rs['msg']  = $value . "参数值必须!";
                echo json_encode($rs);
                die;
            }
        }
    }
}
