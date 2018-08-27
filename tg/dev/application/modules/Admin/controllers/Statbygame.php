<?php

class StatbygameController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $m_game = new GameModel();
        $m_pay=new PayModel();
        $m_bygame=new StatbygameModel();
        $m_sign = new SignonlogModel();
        $s = Yaf_Session::getInstance();
        $admin_id=$s->get('admin_id');
        $tg_channel=$admin_id;
        //统计用户注册数
//        $signon_people = $m_user->fetch("tg_channel ={$tg_channel}", 'COUNT(user_id) AS reg_people');
        //统计用户登录次数与人数
        $games=$m_sign->fetchAllBySql("select distinct game_id from h5.signon_log  inner join h5.`user`  on h5.signon_log.user_id = h5.`user`.user_id where h5.`user`.tg_channel =  {$tg_channel} and game_id>0");
        $peopel_stat=array();
        foreach ($games as $key=>$game){
            $game_id=$game['game_id'];
            $peopel_stat[]=$m_sign->fetchBySql("select game_id, COUNT(*) AS signon_times, COUNT(DISTINCT h5.signon_log.user_id) AS signon_people  from h5.signon_log  inner join h5.`user`  on h5.signon_log.user_id = h5.`user`.user_id where game_id={$game_id} and h5.`user`.tg_channel =  {$tg_channel}");
        }
        if($peopel_stat){
            $conds='';
            foreach ($peopel_stat as $people){
                if( $m_bygame->fetch(['game_id'=>$people['game_id'],'admin_id'=>$admin_id])) {
                    $m_bygame->update($people, $conds."admin_id={$admin_id} AND game_id='{$people['game_id']}'");
                } else {
                    $game = $m_game->fetch("game_id='{$people['game_id']}'", 'name,dev_id');
                    if($game) {
                        $people['game_name'] = $game['name'];
                        $people['dev_id'] = $game['dev_id'];
                        $people['admin_id'] = $admin_id;
                        $m_bygame->insert($people, false);
                    }
                }
            }
        }
        //游戏统计
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
