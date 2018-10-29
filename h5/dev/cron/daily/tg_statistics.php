<?php

/**
 * 渠道平台统计 - 每小时更新一次当日数据
 */
function tg_statistics()
{
    $m_game = new GameModel();
    $m_pay=new PayModel();
    $m_bygame=new StatbygameModel('cps');
    $m_sign = new SignonlogModel();
    $m_admin=new AdminModel('cps');
    $tg_channels=$m_admin->fetchAll('1=1',1,20000,'admin_id');
    $ymd = date('Ymd', strtotime('-1 day'));
    $begin = strtotime($ymd);
    $end = strtotime($ymd.' 23:59:59');
    foreach ($tg_channels as $tg_channel) {
        $tg_channel = $tg_channel['admin_id'];
        //统计用户登录次数与人数
        $games = $m_sign->fetchAllBySql("select distinct game_id from h5.signon_log  inner join h5.`user`  on h5.signon_log.user_id = h5.`user`.user_id where h5.`user`.tg_channel =  {$tg_channel} and game_id>0 and h5.`signon_log`.time BETWEEN {$begin} AND {$end}");
        $peopel_stat = array();
        foreach ($games as $key => $game) {
            $game_id = $game['game_id'];
            $peopel_stat[] = $m_sign->fetchBySql("select game_id, COUNT(*) AS signon_times, COUNT(DISTINCT h5.signon_log.user_id) AS signon_people  from h5.signon_log  inner join h5.`user`  on h5.signon_log.user_id = h5.`user`.user_id where game_id={$game_id} and h5.`user`.tg_channel =  {$tg_channel} and h5.`signon_log`.time BETWEEN {$begin} AND {$end}");
        }
        if ($peopel_stat) {
            $conds = '';
            foreach ($peopel_stat as $people) {
                if ($m_bygame->fetch(['game_id' => $people['game_id'], 'admin_id' => $tg_channel,'ymd'=>$ymd])) {
                    $m_bygame->update($people, $conds . "admin_id={$tg_channel} AND game_id='{$people['game_id']}' AND ymd='$ymd'");
                } else {
                    $game = $m_game->fetch("game_id='{$people['game_id']}'", 'name,dev_id');
                    if ($game) {
                        $people['game_name'] = $game['name'];
                        $people['dev_id'] = $game['dev_id'];
                        $people['admin_id'] = $tg_channel;
                        $people['ymd'] = $ymd;
                        $m_bygame->insert($people, false);
                    }
                }
            }
        }
        //游戏统计
        $stat = $m_pay->fetchAll("tg_channel={$tg_channel} and game_id<>0 and pay_time BETWEEN {$begin} AND {$end} GROUP BY game_id", 1, 2000000, 'game_id, COUNT(pay_id) AS recharge_times, COUNT(DISTINCT user_id) AS recharge_people, SUM(money) AS recharge_money');
        if ($stat) {
            $conds = '';
            foreach ($stat as $row) {
                if ($m_bygame->fetch(['game_id' => $row['game_id'], 'admin_id' => $tg_channel,'ymd'=>$ymd])) {
                    $m_bygame->update($row, $conds . "admin_id={$tg_channel} AND game_id={$row['game_id']} AND ymd='{$ymd}'");
                } else {
                    $game = $m_game->fetch("game_id='{$row['game_id']}'", 'name,dev_id');
                    $row['game_name'] = $game['name'] ?? '未知';
                    $row['dev_id'] = $game['dev_id'] ?? '0';
                    $row['admin_id'] = $tg_channel;
                    $row['ymd'] = $ymd;
                    $m_bygame->insert($row, false);
                }
            }
        }
    }
}
