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
        $info['start_time']=$start_time;
        $info['end_time']=time();
        $info['status']=1;
        $info['total_stream']=0;
        $info['total_balance']=0;
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
        return $params;
    }
}
