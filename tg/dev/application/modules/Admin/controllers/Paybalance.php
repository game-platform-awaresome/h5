<?php

class PaybalanceController extends F_Controller_Backend
{
    protected function beforeList()
    {
        //自动生成更新未结算记录
        $paybalance=new PaybalanceModel();
        $s = Yaf_Session::getInstance();
        $admin_id=$s->get('admin_id');
        $paybalanceinfo=$paybalance->fetch(['admin_id'=>$admin_id,'status'=>1]);//未申请
        $applybalancelastinfo=$paybalance->fetch("admin_id={$admin_id} and status =2");//申请中

        // SELECT *FROM cps.pay_balance WHERE `admin_id`=17 and `status` =3 and `end_time` = (select max(cps.pay_balance.end_time) from cps.pay_balance where `admin_id` = 17 and `status` =3);
        $paybalancelastinfo=$paybalance->fetch("admin_id={$admin_id} and status =3 and end_time = (select max(cps.pay_balance.end_time)  
                  from cps.pay_balance where `admin_id` = {$admin_id} and `status` =3)");
                  //已结算且时间最后一条
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
         //一个小时只更新一次
        if ($paybalanceinfo) {
            //根据最新时间刷新结算
            $paybalance->update($info, ['id' => $paybalanceinfo['id']]);
        } elseif(!$applybalancelastinfo && $admin_id!=1 && $total_balance>0){
            //插入一条结算信息
            $paybalance->insert($info);
        }
        $params=parent::beforeList();
        $params['op'] = F_Helper_Html::Op_Null;
        $params['orderby']='end_time desc ,status asc';
        if($admin_id!=1){
            $params['conditions']="admin_id ={$admin_id}";
        }else{
            $params['conditions']="status > 1";
        }
        return $params;
    }

    /**
     * 获取流水
     * @param $start_time
     * @param $end_time
     * @param $channel_ids_condition
     * @return int
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
                return round($stream,2);
                break;
            case 3:
                //二级代理
                $stream=$this->getStreamTotal($start_time,$end_time,$channel_ids_condition);
                return round($stream,2);
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
     * @param $admin_id
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
                $self_balance=$this->getSelfBalance($start_time,$end_time,$admin_id,$cps_type);
                $agent_balance=$this->getAgentBalance($start_time,$end_time,$admin_id,$channel_ids_condition);
                return round($self_balance+$agent_balance,2);
                break;
            case 3:
                //二级代理
                $self_balance=$this->getSelfBalance($start_time,$end_time,$admin_id,$cps_type);
                return round($self_balance,2);
                break;
            default:
                return 0;
                break;
        }
    }

    /**
     * 获取流水
     * @param $start_time 开始时间
     * @param $end_time 结束时间
     * @param $channel_ids_condition 条件
     * @return
     */
    private function getStreamTotal($start_time,$end_time,$channel_ids_condition)
    {
        $pay=new PayModel();
        $money=$pay->fetchAll("add_time  between {$start_time} and {$end_time} and pay_time > 0 and game_id > 0 and tg_channel in {$channel_ids_condition}",1,2000000,'SUM(money) as total');
        return $money[0]['total'];
    }

    /**
     * 自身结算
     * @param $start_time 开始时间
     * @param $end_time 结束时间
     * @param $admin_id 当前id
     * @param $cps_type 渠道类型
     * @return float|int
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
                $pay_list=$pay->fetchAll("add_time  between {$start_time} and {$end_time} and pay_time > 0 and game_id > 0 and tg_channel = {$admin_id}",1,2000000,'game_id,money,tg_channel,player_channel');
                $balance=0;
                foreach ($pay_list as $key=>$value){
                    if($value['player_channel']){
                        //间接获取的玩家
                        $divide_into=$game->fetch("game_id={$value['game_id']}",'divide_into');
                        $divide_into=(int)($divide_into['divide_into']-20);
                        $balance+=$value['money']*$divide_into/100;
                    }else{
                        //直接推广获取的玩家
                        $divide_into=$game->fetch("game_id={$value['game_id']}",'divide_into');
                        $divide_into=$divide_into['divide_into'];
                        $balance+=$value['money']*$divide_into/100;
                    }

                }
                return $balance;
            case 3:
                $pay_list=$pay->fetchAll("add_time  between {$start_time} and {$end_time} and pay_time > 0 and game_id > 0 and tg_channel = {$admin_id}",1,2000000,'game_id,money,tg_channel,player_channel');
                $balance=0;
                foreach ($pay_list as $key=>$value){
                    if($value['player_channel']){
                        //间接获取的玩家
                        $divide_into=$admin->fetch("admin_id = {$admin_id}",'divide_into');
                        $divide_into=(int)($divide_into['divide_into']-20);
                        $balance+=$value['money']*$divide_into/100;
                    }else{
                        //直接推广获取的玩家
                        $divide_into=$admin->fetch("admin_id = {$admin_id}",'divide_into');
                        $divide_into=$divide_into['divide_into'];
                        $balance+=$value['money']*$divide_into/100;
                    }
                }

                return $balance;
            default:
                return 0;
        }
    }

    /**
     * 获取代理结算
     * @param $start_time 开始时间
     * @param $end_time 结束时间
     * @param $admin_id 当前id
     * @param $channel_ids_condition 权限条件
     */
    private function getAgentBalance($start_time, $end_time, $admin_id,$channel_ids_condition)
    {
        $pay=new PayModel();
        $admin=new AdminModel();
        $game=new GameModel();
        $pay_list=$pay->fetchAll("add_time  between {$start_time} and {$end_time} and pay_time > 0 and game_id > 0 and tg_channel in {$channel_ids_condition} and tg_channel !={$admin_id}",1,2000000,'game_id,money,tg_channel');
        $balance=0;
        foreach ($pay_list as $key=>$value){
            //计算能获取的分成比例
            $admin_divide_into=$game->fetch("game_id={$value['game_id']}",'divide_into');//该一级代理能获取的分成
            $channel_divide_into=$admin->fetch("admin_id = {$value['tg_channel']}",'divide_into');//代理获取的分成
            $can_get_divide_into=(int)$admin_divide_into['divide_into']-(int)$channel_divide_into['divide_into'];//一级能享有的分成
            $balance+=$value['money']*$can_get_divide_into/100;
        }
        return $balance;
    }


    public function applyBalanceAction(){
        Yaf_Dispatcher::getInstance()->disableView();
        $request=$this->getRequest();
        $id=$request->get('id');
        $type=$request->get('type');
        $balance=new PaybalanceModel();
        if($type=='apply'){
            if($id && $balance->fetch(['id'=>$id])){
                $balance->update(['status'=>2,'add_time'=>time()],['id'=>$id]);
            }
        }elseif ($type=='complete'){
            if($id && $balance->fetch(['id'=>$id])){
                $balance->update(['status'=>3],['id'=>$id]);
            }
        }
        $this->redirect("list");
    }
}
