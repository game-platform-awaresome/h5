<?php

class NotifyController extends Yaf_Controller_Abstract
{
    private function deal($pay_id, $trade_no, $pay_type = '', $success = 'success', $fail = 'fail')
    {
        $conds = "pay_id='{$pay_id}'";
        $m_pay = new PayModel();
        $pay = $m_pay->fetch($conds, 'pay_id,user_id,username,to_uid,to_user,game_id,server_id,game_name,server_name,money,type,pay_time,finish_time,cp_order,channel,extra');
        if( (int)$pay['finish_time'] > 0 ) {
            exit($success);
        }
        
        $pay['pay_id'] = sprintf('%16.0f', $pay['pay_id']);
        $time = time();
        $m_user = new UsersModel();
        $user = $m_user->fetch("user_id='{$pay['to_uid']}'", 'money,first_pay');
        
        //充值平台币
        if( $pay['game_id'] == 0 && $pay['server_id'] == 0 ) {
            $deposit = $user['money'] + $pay['money'];
            
            $pdo = $m_user->getPdo();
            $pdo->beginTransaction();
            
            $rs1 = $m_user->changeMoney($pay['to_uid'], $pay['money']);
            $rs2 = $m_pay->update(array(
                'deposit' => $deposit,
                'pay_type' => $pay_type,
                'trade_no' => $trade_no,
                'pay_time' => $time,
                'finish_time' => $time,
            ), $conds);
            
            if( $rs1 && $rs2 ) {
                $pdo->commit();
                
                if( empty($user['first_pay']) ) {
                    $m_user->update(array('first_pay'=>$time), "user_id='{$pay['to_uid']}'");
                }
                
                exit($success);
            } else {
                $pdo->rollBack();
                exit($fail);
            }
            
        } else if( (int)$pay['pay_time'] == 0 ) {
            $rs = $m_pay->update(array(
                'pay_type' => $pay_type,
                'trade_no' => $trade_no,
                'pay_time' => $time,
            ), $conds);
            if( ! $rs ) {
                exit($fail);
            }
            if( empty($user['first_pay']) ) {
                $m_user->update(array('first_pay'=>$time), "user_id='{$pay['to_uid']}'");
            }
        }
        
        //充值到游戏
        if( $pay['server_id'] ) {
//            $m_server = new ServerModel();
//            $server = $m_server->fetch("server_id='{$pay['server_id']}'", 'recharge_url,sign_key');
//            $recharge_url = $server['recharge_url'];
//            $sign_key = $server['sign_key'];
//        } else {
            $m_game = new GameModel();
            $game = $m_game->fetch("game_id='{$pay['game_id']}'", 'recharge_url,sign_key');
            $recharge_url = $game['recharge_url'];
            $sign_key = $game['sign_key'];
        }
        
//        if( $pay['channel'] == 'egret' ) {
//            $egret = new Game_Channel_Egret();
//            $rs = $egret->notify($recharge_url, $sign_key, $pay);
//        } else {
            $rs = Game_Recharge::notify($recharge_url, $sign_key, $pay);
//        }
        
        if( $rs == '' ) {
            $m_pay->update(array('finish_time'=>$time), $conds);
            $status = $success;
        } else {
            $status = $fail;
        }
        
        exit($status);
    }
    
    //支付宝后台通知
    public function alipayAction()
    {
        $class = new Pay_Alipay_Mobile();
        $rs = $class->notify();
        if( $rs == false ) {
            exit('fail');
        }
        if( $rs['status'] == 'failed' ) {
            exit('success');
        }
        
        $this->deal($rs['pay_id'], $rs['trade_no']);
    }
    
    //爱贝后台通知
    public function iapppayAction()
    {
        $class = new Pay_Iapppay_Mobile();
        $rs = $class->notify();
        if( $rs == false ) {
            exit('fail');
        }
        
        $this->deal($rs['pay_id'], $rs['trade_no'], $rs['pay_type']);
    }

    /**
     * 金猪
     */
    public function pigpayAction(){
        //日志
        $m_log = new AdminlogModel();
        $m_log->insert(array(
            'admin' => '金猪支付',
            'content' => json_encode($_REQUEST),
            'ymd' => date('Ymd'),
        ));
        $class = new Pay_Pigpay_Mobile();
        $rs = $class->notify();
        if( $rs == false ) {
            exit('fail');
        }
        $this->deal($rs['pay_id'], $rs['trade_no']);
    }
}
