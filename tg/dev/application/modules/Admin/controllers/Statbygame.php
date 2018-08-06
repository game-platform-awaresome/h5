<?php

class StatbygameController extends F_Controller_Backend
{
    protected function beforeList()
    {
        //游戏统计
        $m_pay=new PayModel();
        $m_bygame=new StatbygameModel();
        $m_game=new GameModel();
        $s = Yaf_Session::getInstance();
        $admin_id=$s->get('admin_id');
        $tg_channel=$admin_id;
        $stat = $m_pay->fetchAll("pay_time > 0 and tg_channel={$tg_channel} and game_id<>0 GROUP BY game_id", 1, 2000000, 'game_id, COUNT(pay_id) AS recharge_times, COUNT(DISTINCT user_id) AS recharge_people, SUM(money) AS recharge_money');
        if( $stat ) {
            $conds='';
            foreach ($stat as $row)
            {
                if( $m_bygame->fetch(['game_id'=>$row['game_id'],'admin_id'=>$admin_id])) {
                    $m_bygame->update($row, $conds."admin_id={$admin_id} AND game_id='{$row['game_id']}'");
                } else {
                    $game = $m_game->fetch("game_id='{$row['game_id']}'", 'name,dev_id');
                    $row['game_name'] = $game['name'];
                    $row['dev_id'] = $game['dev_id'];
                    $row['admin_id'] = $admin_id;
                    $m_bygame->insert($row, false);
                    $arr_bygame[$row['game_id']] = $game['dev_id'];
                }
            }
        }

        $params['op'] = F_Helper_Html::Op_Null;
        $conds = '';
        $search = $this->getRequest()->getQuery('search', array());
        $s = Yaf_Session::getInstance();
        $admin_id=$s->get('admin_id');
        if( $search ) {
            $cmm = '';
            foreach ($search as $k=>$v)
            {
                if( empty($v) ) {
                    continue;
                }
                $conds .= "{$cmm}{$k}='{$v}'";
                $cmm = ' AND ';
            }
            $conds.="AND admin_id = {$admin_id}";
        }else{
            $conds.="admin_id = {$admin_id}";
        }
        $params['conditions']=$conds;
        return $params;
    }

    public function editAction()
    {
        exit;
    }
    
    public function updateAction()
    {
        exit;
    }
    
    public function deleteAction()
    {
        exit;
    }
}
