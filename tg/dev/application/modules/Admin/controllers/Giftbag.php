<?php

class GiftbagController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['orderby'] = 'add_time DESC';
        $params['op'] = F_Helper_Html::Op_Null;
        return $params;
    }

    /**
     * 领取
     */
    public function getAction(){
        $gift_id=$_GET['gift_id'];
        $tg_id=$_SESSION['admin_id'];
        $m_tggiftbag=new TggiftbagModel();
        $m_giftbag=new GiftbagModel();
        $gift_info=$m_giftbag->fetch(['gift_id'=>$gift_id]);
        if($m_tggiftbag->fetch(['tg_channel'=>$tg_id,'giftbag_id'=>$gift_id]) && !$gift_info){
            $this->redirect('/admin/giftbag/list.html');
        }else{
            $m_gift_keys=new GiftbagcdkeyModel();
            //取20个礼包
            $gift_keys=$m_gift_keys->fetchAll(['gift_id'=>$gift_id,'user_id'=>0,'get_time'=>0],1,20);
            $gift_keys_value= array_column($gift_keys, 'cdkey');
            //保存到渠道记录
            $m_tggiftbag->insert(['tg_channel'=>$tg_id,'giftbag_id'=>$gift_id,'details'=>json_encode($gift_keys_value,true)]);
            //做领取标记
            if($gift_info['type']=='limited') {
                foreach ($gift_keys as $key => $value) {
                    $m_gift_keys->update(['get_time' => time()], ['gift_id' => $gift_id, 'cdkey' => $value['cdkey']]);
                }
            }
            $now_used=$gift_info['used']+20;
            $m_giftbag->update(['used'=>$now_used],['gift_id'=>$gift_id]);
            $this->redirect('/admin/giftbag/list.html');
        }
    }

    /**
     * 详情
     */
    public function detailAction(){
        $gift_id=$_GET['gift_id'];
        $tg_id=$_SESSION['admin_id'];
        $m_tggiftbag=new TggiftbagModel();
        $m_giftbag=new GiftbagModel();
        $gift_keys=$m_tggiftbag->fetch(['tg_channel'=>$tg_id,'giftbag_id'=>$gift_id]);
        $gift_bag=$m_giftbag->fetch(['gift_id'=>$gift_id]);
        $this->getView()->assign('gift_keys',$gift_keys);
        $this->getView()->assign('gift_bag',$gift_bag);
    }
}
