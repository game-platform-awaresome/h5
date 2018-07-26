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
        $rand = mt_rand(1, 5);
        if( $rand == 3 ) {
            $time = strtotime('-15 days');
            $this->_model->delete("add_time<$time");
        }
        
        $params = parent::beforeList();
        $params['orderby'] = 'pay_id DESC';
        return $params;
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
        } elseif( $data['pay_time'] ) {
            $json['pay_time'] = $data['pay_time'];
        } else {
            $up_arr = array('pay_time'=>time());
            $rs2 = $this->_model->update($up_arr, "pay_id='{$pay_id}'");
            if( ! $rs2 ) {
                $json['error'] = '数据库错误，'.$this->_model->error();
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
                $json['error'] = Game_Recharge::notify($url['recharge_url'], $url['sign_key'], $data);
                
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
}
