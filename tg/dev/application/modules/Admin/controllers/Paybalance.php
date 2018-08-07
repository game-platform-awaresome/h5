<?php

class PaybalanceController extends F_Controller_Backend
{
    protected function beforeList()
    {
        //自动生成更新未结算记录
        $paybalance=new PaybalanceModel();
        $s = Yaf_Session::getInstance();
        $admin_id=$s->get('admin_id');
        $paybalanceinfo=$paybalance->fetch(['admin_id'=>$admin_id,'status'=>1]);
        $paybalancelastinfo=$paybalance->fetch(['admin_id'=>$admin_id,'status'=>3]);
        if($paybalancelastinfo){
            $start_time=$paybalancelastinfo['end_time'];
        }else{
            $start_time=1;
        }
        $end_time=time();
        $info['start_time']=$start_time;
        $info['end_time']=$end_time;
        $info['status']=1;
        $channel_ids_condition=$s->get('channel_ids_condition');
        $total_stream=$this->getStream($start_time,$end_time,$channel_ids_condition);//流水
        $total_balance=$this->getBalance($start_time,$end_time,$admin_id,$channel_ids_condition);//结算
        $info['total_stream']=$total_stream;
        $info['total_balance']=$total_balance;
        $info['admin_id']=$admin_id;
        if($paybalanceinfo){
            //根据最新时间刷新结算
            $paybalance->update($info,['id'=>$paybalanceinfo['id']]);
        }else{
            //插入一条结算信息
            $paybalance->insert($info);
        }
        $params=parent::beforeList();
        $params['op'] = F_Helper_Html::Op_Null;
        $params['orderby']='end_time desc';
        $params['conditions']="admin_id ={$admin_id}";
        return $params;
    }

    /**
     * 获取流水
     */
    private function getStream($start_time,$end_time,$channel_ids_condition){
        $s = Yaf_Session::getInstance();
        $cps_type=$s->get('cps_type');
        switch ($cps_type){
            case 1;
                return 0;
            case 2:
                //一级代理
                $stream=$this->getStreamTotal($start_time,$end_time,$channel_ids_condition);
                return (int)$stream;
                break;
            case 3:
                //二级代理
                $stream=$this->getStreamTotal($start_time,$end_time,$channel_ids_condition);
                return (int)$stream;
                break;
            default:
                return 0;
                break;
        }
    }

    /**
     * 获取结算
     * @param $start_time
     * @param $end_time
     * @param $channel_ids_condition
     * @return int
     */
    private function getBalance($start_time,$end_time,$admin_id,$channel_ids_condition)
    {
        $s = Yaf_Session::getInstance();
        $cps_type=$s->get('cps_type');
        switch ($cps_type){
            case 1;
                return 0;
            case 2:
                //一级代理
                $stream=$this->getSelfBalance($start_time,$end_time,$admin_id,$cps_type);
                $stream=$this->getAgentBalance($start_time,$end_time,$admin_id,$channel_ids_condition);
                return (int)$stream;
                break;
            case 3:
                //二级代理
                $stream=$this->getSelfBalance($start_time,$end_time,$admin_id);
                return (int)$stream;
                break;
            default:
                return 0;
                break;
        }
    }
    /**
     * 获取流水
     * @param $admin_id
     */
    private function getStreamTotal($start_time,$end_time,$channel_ids_condition)
    {
        $pay=new PayModel();
        $money=$pay->fetchAll("add_time  between {$start_time} and {$end_time} and pay_time > 0 and game_id > 0 and tg_channel in {$channel_ids_condition}",1,2000000,'SUM(money) as total');
        return $money[0]['total'];
    }

    /**
     * 自身结算
     * @param $start_time
     * @param $end_time
     * @param $channel_ids_condition
     */
    private function getSelfBalance($start_time, $end_time,$admin_id,$cps_type)
    {
        $pay=new PayModel();
        $game=new GameModel();
        $admin=new AdminModel();
        switch ($cps_type){
            case 1:
                return 0;
            case 2:
                $pay_list=$pay->fetchAll("add_time  between {$start_time} and {$end_time} and pay_time > 0 and game_id > 0 and tg_channel = {$admin_id}",1,2000000,'game_id,money,tg_channel');
                $balance=0;
                foreach ($pay_list as $key=>$value){
                    $divide_into=$game->fetch("game_id={$value['game_id']}",'divide_into');
                    $divide_into=$divide_into['divide_into'];
                    $balance+=$value['money']*$divide_into/100;
                }
                return $balance;
            case 3:
                $pay_list=$pay->fetchAll("add_time  between {$start_time} and {$end_time} and pay_time > 0 and game_id > 0 and tg_channel = {$admin_id}",1,2000000,'game_id,money,tg_channel');
                $balance=0;
                $divide_into=$admin->fetch("admin_id = {$admin_id}",'divide_into');
                $divide_into=$divide_into['divide_into'];
                foreach ($pay_list as $key=>$value){
                    $balance+=$value['money']*$divide_into/100;
                }
                return $balance;
            default:
                return 0;
        }
    }

    /**
     * 获取代理结算
     * @param $start_time
     * @param $end_time
     * @param $channel_ids_condition
     */
    private function getAgentBalance($start_time, $end_time, $admin_id,$channel_ids_condition)
    {
        $pay=new PayModel();
        $pay_list=$pay->fetchAll("add_time  between {$start_time} and {$end_time} and pay_time > 0 and game_id > 0 and tg_channel in {$channel_ids_condition} and tg_channel !={$admin_id}",1,2000000,'game_id,money,tg_channel');
        $balance=0;
        foreach ($pay_list as $key=>$value){

        }
    }
}
