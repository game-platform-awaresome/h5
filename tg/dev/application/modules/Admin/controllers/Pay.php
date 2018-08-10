<?php

class PayController extends F_Controller_Backend
{
    public function init()
    {
        parent::init();
        $this->_view->assign('types', $this->_model->_types);
    }
    
    protected function beforeList()
    {
//        $params = parent::beforeList();
        $params['op'] = F_Helper_Html::Op_Null;
        $conds = '';
        $search = $this->getRequest()->getQuery('search', array());
        $s = Yaf_Session::getInstance();
        $channel_ids_condition=$s->get('channel_ids_condition');
        $search='pay_time>0';
        if( $search ) {
            $cmm = '';
            foreach ($search as $k=>$v)
            {
                if( empty($v) ) {
                    continue;
                }
                if( $k == 'username' ) {
                    $m_user = new UsersModel();
                    $user = $m_user->fetch("username='{$v}'", 'user_id');
                    if( $user ) {
                        $k = 'user_id';
                        $v = $user['user_id'];
                    } else {
                        continue;
                    }
                }
                $conds .= "{$cmm}{$k}='{$v}'";
                $cmm = ' AND ';
            }
            $conds.="  AND tg_channel in {$channel_ids_condition}";
        }else{
            $conds.="tg_channel in {$channel_ids_condition}";
        }
        if($conds){
            $conds.=' AND pay_time>0';
        }else{
            $conds.=' pay_time>0';
        }
        $params['conditions']=$conds;
        return $params;
    }
    
    public function apiAction()
    {
        
    }
    
    public function deleteAction()
    {
        exit;
    }
    
    /**
     * 设置为支付已完成
     */
    public function setpayAction()
    {
        $req = $this->getRequest();
        $pay_id = $req->get('pay_id', '');
        $json = array(
            'error' => '',
            'pay_time' => 0,
            'finish_time' => 0,
        );
        $data = $this->_model->fetch("pay_id='{$pay_id}'", 'user_id,username,to_uid,to_user,game_id,server_id,money,pay_time,finish_time');
        if( empty($data) ) {
            $json['error'] = '支付单不存在。';
        } elseif( $data['pay_time']) {
            $json['pay_time'] = $data['pay_time'];
        } else {
            $up_arr = array('pay_time'=>time());
            //充到平台
            if( $data['game_id'] == 0 ) {
                $up_arr['finish_time'] = $up_arr['pay_time'];
                $m_user = new UsersModel();
                $m_log = new AdminlogModel();
                $pdo = $this->_model->getPdo();
                $pdo->beginTransaction();
                
                $rs1 = $m_user->changeMoney($data['to_uid'], $data['money']);
                $rs2 = $this->_model->update($up_arr, "pay_id='{$pay_id}'");
                $rs3 = $m_log->insert(array(
                    'admin' => Yaf_Session::getInstance()->get('admin_name'),
                    'content' => "设置订单 {$pay_id} 为已支付状态，用户 {$data['to_user']} 的余额增加了 {$data['money']}￥。",
                    'ymd' => date('Ymd'),
                ));
                
                if( $rs1 && $rs2 && $rs3 ) {
                    $pdo->commit();
                    $json['finish_time'] = $up_arr['pay_time'];
                } else {
                    $pdo->rollBack();
                    $json['error'] = '数据库错误，103';
                }
            } else {
                $rs2 = $this->_model->update($up_arr, "pay_id='{$pay_id}'");
                if( ! $rs2 ) {
                    $json['error'] = '数据库错误，'.$this->_model->error();
                }
            }
            if( empty($json['error']) ) {
                $json['pay_time'] = $up_arr['pay_time'];
            }
        }
        
        $json['pay_time'] = $json['pay_time'] ? date('Y-m-d H:i:s', $json['pay_time']) : 0;
        $json['finish_time'] = $json['finish_time'] ? date('Y-m-d H:i:s', $json['finish_time']) : 0;
        exit(json_encode($json));
    }
    
    /**
     * 设置为支付已到账
     */
    public function setfinishAction()
    {
        $req = $this->getRequest();
        $pay_id = $req->get('pay_id', '');
        $json = array(
            'error' => '',
            'pay_time' => 0,
            'finish_time' => 0,
        );
        $data = $this->_model->fetch("pay_id='{$pay_id}'", 'pay_id,user_id,username,to_uid,to_user,game_id,server_id,game_name,server_name,money,type,pay_time,finish_time,cp_order,channel,extra');
        $json['pay_time'] = $data['pay_time'];
        if( empty($data) ) {
            $json['error'] = '支付单不存在。';
        } elseif( $data['pay_time'] == 0 ) {
            $json['error'] = '订单尚未支付，不能设置为已到账。';
        } elseif( $data['game_id'] == 0 ) {
            $up_arr = array('finish_time'=>time());
            $rs2 = $this->_model->update($up_arr, "pay_id='{$pay_id}'");
            if( ! $rs2 ) {
                $json['error'] = '数据库错误，'.$this->_model->error();
            } else {
                $json['finish_time'] = $up_arr['finish_time'];
            }
        } else { //充到游戏
            if( $data['server_id'] ) {
                $m_server = new ServerModel();
                $url = $m_server->fetch("server_id='{$data['server_id']}'", 'recharge_url,sign_key');
                if( empty($url['recharge_url']) || empty($url['sign_key']) ) {
                    $json['error'] = '请先设置游戏的充值接口地址及通信密钥！';
                }
            } else {
                $m_game = new GameModel();
                $url = $m_game->fetch("game_id='{$data['game_id']}'", 'recharge_url,sign_key');
                if( empty($url['recharge_url']) || empty($url['sign_key']) ) {
                    $json['error'] = '请先设置游戏区/服的充值接口地址及通信密钥！';
                }
            }
            if( $url ) {
                if( $data['channel'] == 'egret' ) {
                    $egret = new Game_Channel_Egret();
                    $json['error'] = $egret->notify($url['recharge_url'], $url['sign_key'], $data);
                } else {
                    $json['error'] = Game_Recharge::notify($url['recharge_url'], $url['sign_key'], $data);
                }
                
                if( $json['error'] == '' ) {
                    $time = time();
                    $rs2 = $this->_model->update(array('finish_time'=>$time), "pay_id='{$pay_id}'");
                    if( ! $rs2 ) {
                        $json['error'] = '数据库错误，'.$this->_model->error();
                    } else {
                        $json['finish_time'] = $time;
                    }
                }
            }
        }
        
        $json['pay_time'] = $json['pay_time'] ? date('Y-m-d H:i:s', $json['pay_time']) : 0;
        $json['finish_time'] = $json['finish_time'] ? date('Y-m-d H:i:s', $json['finish_time']) : 0;
        exit(json_encode($json));
    }

    /**
     * 渠道结算
     */
    public function balanceAction(){
        //1.查询记录

        //2.动态追加
    }
}
