<?php

/**
 * 平台统计 - 每小时更新一次当日数据
 */
function statistics()
{
    $m_stat = new StatisticsModel();
    $m_user = new UsersModel();
    $m_game = new GameModel();
    $ymd = date('Ymd');
    $conds = "ymd='{$ymd}'";
    
    $last = $m_stat->fetch($conds, 'user_id');
    if( empty($last) ) {
        $last = $m_stat->fetchBySql("SELECT user_id FROM {$m_stat->getTable()} ORDER BY ymd DESC LIMIT 1");
        if( $last ) {
            $user_id = intval($last['user_id']);
            $last = null;
        } else {
            $user_id = 0;
        }
    } else {
        $user_id = $last['user_id'];
    }
    
    $begin = strtotime($ymd);
    $end = strtotime($ymd.' 23:59:59');
    
    //统计用户注册数
    $stat = $m_user->fetch("user_id>{$user_id} AND reg_time BETWEEN {$begin} AND {$end}", 'COUNT(user_id) AS reg_people, MAX(user_id) AS user_id');
    if( $stat['user_id'] == 0 ) {
        $stat['user_id'] = $user_id;
    }
    if( $last ) {
        $m_stat->update($stat, $conds);
    } else {
        $stat['ymd'] = $ymd;
        $m_stat->insert($stat, false);
        $last['user_id'] = $stat['user_id'];
    }
    
    //检查记录是否存在
    $m_bygame = new StatbygameModel();
    $tmp = $m_bygame->fetchAll($conds, 1, 2000, 'game_id,dev_id');
    $arr_bygame = array();
    foreach ($tmp as $row){
        $arr_bygame[$row['game_id']] = $row['dev_id'];
    }
    
    //统计用户登录次数与人数
    $m_sign = new SignonlogModel();
    $stat = $m_sign->fetchAll("time BETWEEN {$begin} AND {$end} GROUP BY game_id", 1, 2000, 'game_id, COUNT(*) AS signon_times, COUNT(DISTINCT user_id) AS signon_people');
    if( $stat ) {
        foreach ($stat as $row)
        {
            if( $row['game_id'] == 0 ) {
                $m_stat->update(array('signon_times'=>$row['signon_times'], 'signon_people'=>$row['signon_people']), $conds);
            } elseif( isset($arr_bygame[$row['game_id']]) ) {
                $m_bygame->update($row, $conds." AND game_id='{$row['game_id']}'");
            } else {
                $game = $m_game->fetch("game_id='{$row['game_id']}'", 'name,dev_id');
                $row['game_name'] = $game['name']??'未知';
                $row['ymd'] = $ymd;
                $row['dev_id'] = $game['dev_id']??0;
                $m_bygame->insert($row, false);
                $arr_bygame[$row['game_id']] = $game['dev_id'];
            }
        }
    }
    
    //统计付费数据 - 平台
    $m_pay = new PayModel();
    $stat = $m_pay->fetch("pay_time BETWEEN {$begin} AND {$end} AND type<>'deposit'", 'COUNT(pay_id) AS pay_times, COUNT(DISTINCT user_id) AS pay_people, SUM(money) AS pay_money');
    $m_stat->update($stat, $conds);
    
    //统计充值数据 - 游戏
    $stat = $m_pay->fetchAll("finish_time BETWEEN {$begin} AND {$end} AND game_id<>0 GROUP BY game_id", 1, 2000, 'game_id, COUNT(pay_id) AS recharge_times, COUNT(DISTINCT user_id) AS recharge_people, SUM(money) AS recharge_money');
    if( $stat ) {
        foreach ($stat as $row)
        {
            if( isset($arr_bygame[$row['game_id']]) ) {
                $m_bygame->update($row, $conds." AND game_id='{$row['game_id']}'");
            } else {
                $game = $m_game->fetch("game_id='{$row['game_id']}'", 'name,dev_id');
                $row['game_name'] = $game['name'];
                $row['ymd'] = $ymd;
                $row['dev_id'] = $game['dev_id'];
                $m_bygame->insert($row, false);
                $arr_bygame[$row['game_id']] = $game['dev_id'];
            }
        }
    }
    
    //充值方式统计
    $stat = $m_pay->fetchAll("finish_time BETWEEN {$begin} AND {$end} GROUP BY type", 1, 20, 'type, COUNT(pay_id) AS recharge_times, COUNT(DISTINCT user_id) AS recharge_people, SUM(money) AS recharge_money');
    $m_bypay = new StatbypayModel();
    if( $stat ) {
        foreach ($stat as $row)
        {
            $tmp = $conds." AND type='{$row['type']}'";
            $has = $m_bypay->fetch($tmp, 'ymd');
            if( $has ) {
                $m_bypay->update($row, $tmp);
            } else {
                $row['ymd'] = $ymd;
                $m_bypay->insert($row, false);
            }
        }
    }
}
