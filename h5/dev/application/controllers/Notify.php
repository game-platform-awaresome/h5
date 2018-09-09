<?php

class NotifyController extends Yaf_Controller_Abstract
{
    private function deal($pay_id, $trade_no, $pay_type = '', $success = 'success', $fail = 'fail')
    {
        $conds = "pay_id='{$pay_id}'";
        $m_pay = new PayModel();
        $pay = $m_pay->fetch($conds, 'pay_id,user_id,username,role_id,to_uid,to_user,game_id,server_id,game_name,server_name,money,type,pay_time,finish_time,cp_order,channel,extra,player_channel');
        if( (int)$pay['finish_time'] > 0 ) {
            exit($success);
        }
        $player_channel=$pay['player_channel'];
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
                'type' => $pay_type,
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
                'type' => $pay_type,
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
        //if( $pay['server_id'] ) {
//            $m_server = new ServerModel();
//            $server = $m_server->fetch("server_id='{$pay['server_id']}'", 'recharge_url,sign_key');
//            $recharge_url = $server['recharge_url'];
//            $sign_key = $server['sign_key'];
//        } else {
            $m_game = new GameModel();
            $game = $m_game->fetch("game_id='{$pay['game_id']}'", 'recharge_url,sign_key');
            $recharge_url = $game['recharge_url'];
            $sign_key = $game['sign_key'];
        //}
        
//        if( $pay['channel'] == 'egret' ) {
//            $egret = new Game_Channel_Egret();
//            $rs = $egret->notify($recharge_url, $sign_key, $pay);
//        } else {
//            $rs = Game_Recharge::notify($recharge_url, $sign_key, $pay);
            $rs = Game_Recharge::notify($recharge_url, $sign_key, $pay);//异步通知游戏
            $rs='';
//        }
        
        if( $rs == '' ) {
            $m_pay->update(array('finish_time'=>$time,'pay_type' => $pay_type ,'type' => $pay_type,), $conds);
            $status = $success;
            //玩家代理获取分成
            if($player_channel>0 && $pay_type!='deposit'){
                $player_channel_info=$m_user->fetch(['user_id'=>$player_channel]);
                $old_money=$player_channel_info['money'];
                $add_money=$pay['money']*0.2;
                $final_moeny=(int)($old_money+$add_money);
                $m_user->update(['money'=>$final_moeny],['user_id'=>$player_channel]);
                //统计金额
                $pay_user=$m_user->fetch(['user_id'=>$pay['user_id']]);
                $player_channel_get=$pay_user['player_channel_get'];
                $final_player_channel_get=(int)($player_channel_get+$add_money);
                $m_user->update(['player_channel_get'=>$final_player_channel_get],['user_id'=>$pay['user_id']]);
                $m_log = new AdminlogModel();
                $m_log->insert(array(
                    'admin' => "玩家{$player_channel}获取订单{$pay_id}充值分成",
                    'content' => "分成前余额：{$old_money},分成金额：{$add_money},分成后余额：{$final_moeny}",
                    'ymd' => date('Ymd'),
                ));
            }
        } else {
            $m_pay->update(array('finish_time'=>'-1','pay_type' => $pay_type ,'type' => $pay_type,), $conds);
            $status = $fail;
        }
        
        exit($status);
    }
    
//    //支付宝后台通知
//    public function alipayAction()
//    {
//        $class = new Pay_Alipay_Mobile();
//        $rs = $class->notify();
//        if( $rs == false ) {
//            exit('fail');
//        }
//        if( $rs['status'] == 'failed' ) {
//            exit('success');
//        }
//        $this->deal($rs['pay_id'], $rs['trade_no']);
//    }
//
//    //爱贝后台通知
//    public function iapppayAction()
//    {
//        $class = new Pay_Iapppay_Mobile();
//        $rs = $class->notify();
//        if( $rs == false ) {
//            exit('fail');
//        }
//
//        $this->deal($rs['pay_id'], $rs['trade_no'], $rs['pay_type']);
//    }

    /**
     * 金猪
     */
    public function pigpayAction(){
        $m_log = new AdminlogModel();
        //订单验证
        $type=$_REQUEST['jinzhuc'];
        if($type!='deposit'){
        $num=$_REQUEST['OrderID'];
        $url='http://357p.com/api/mun.asp?userid=27641&mun='.$num;
        $html = file_get_contents($url);
        if($html==='0'){
            $m_log->insert(array(
                'admin' => '非法订单,ip:'.$this->getIp(),
                'content' => json_encode($_REQUEST),
                'ymd' => date('Ymd'),
            ));
            echo '非法订单,已记录访问ip,请勿违法犯罪之事';die;
        }else{
            $html_arr=json_decode($html,true);
            $okprice=$html_arr['okprice'];
            $pay_id =$_REQUEST['jinzhue'];
            $m_pay=new PayModel();
            $pay_info=$m_pay->fetch(['pay_id'=>$pay_id],'money');
            if($pay_info['money']!=$okprice){
                $m_log->insert(array(
                    'admin' => '非法订单,修改订单金额,ip:'.$this->getIp(),
                    'content' => json_encode($_REQUEST),
                    'ymd' => date('Ymd'),
                ));
                echo '非法订单修改订单金额,已记录访问ip,请勿违法犯罪之事';die;
            }
        }
        }
        //订单验证
        //日志
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
        $this->deal($rs['pay_id'], $rs['trade_no'],$rs['pay_type']);
    }

    /**
     * 重新通知
     */
    public function reDealAction(){
        $pay_id=$_GET['pay_id'];
        $m_pay=new PayModel();
        $pay=$m_pay->fetch(['pay_id'=>$pay_id]);
        $game_id=$pay['game_id'];
        $m_game=new GameModel();
        $game=$m_game->fetch(['game_id'=>$game_id]);
        $sign_key=$game['sign_key'];
        $recharge_url=$game['recharge_url'];
        $rs=Game_Recharge::notify($recharge_url, $sign_key, $pay);//异步通知游戏
        echo '通知成功,稍后刷新结果!';
        Yaf_Dispatcher::getInstance()->disableView();
    }
    //不同环境下获取真实的IP
    function getIp(){
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if( $_SERVER['REMOTE_ADDR'])
            $ip = $_SERVER['REMOTE_ADDR'];
        else $ip = "Unknow";
        return $ip;
    }
}
