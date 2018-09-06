<?php

/**
 * 平台统计 - 每日凌晨更新上一日的数据
 */
function statistics()
{
    $m_stat = new StatisticsModel();
    $m_user = new UsersModel();
    $m_game = new GameModel();
    $pdo = $m_game->getPdo();
    $ymd = date('Ymd', strtotime('-1 day'));
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
        $user_id = intval($last['user_id']);
    }
    
    $begin = strtotime($ymd);
    $end = strtotime($ymd.' 23:59:59');
    
    //统计当前平台币存量
    $stat = $m_user->fetch('1', 'SUM(money) AS deposit');
    if( $last ) {
        $m_stat->update($stat, $conds);
    } else {
        $stat['ymd'] = $ymd;
        $m_stat->insert($stat, false);
    }
    
    //统计用户注册数
    $stat = $m_user->fetch("user_id>{$user_id} AND reg_time BETWEEN {$begin} AND {$end}", 'COUNT(user_id) AS reg_people, MAX(user_id) AS user_id');
    if( $stat['user_id'] == 0 ) {
        $stat['user_id'] = $user_id;
    }
    $m_stat->update($stat, $conds);
    $last['user_id'] = $stat['user_id'];
    
    //新增付费用户数
    $stat = $m_user->fetch("first_pay BETWEEN {$begin} AND {$end}", 'COUNT(user_id) AS new_payer');
    $m_stat->update($stat, $conds);
    
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
                $row['game_name'] = $game['name'];
                $row['ymd'] = $ymd;
                $row['dev_id'] = $game['dev_id'];
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
            
            if( $row['recharge_money'] > 0 ) {
                $pdo->exec("UPDATE game SET trade_money = trade_money + {$row['recharge_money']} WHERE game_id='{$row['game_id']}'");
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
    
    //开发者统计
    $dev_id = 0;
    $limit = 100;
    $m_dev = new DeveloperModel();
    $status = $m_dev->_froze_code - 1;
    $pdo_open = $m_dev->getPdo();
    $m_bydev = new StatbydevModel();
    while (true)
    {
        $devs = $m_dev->fetchAll("status > {$status} AND dev_id > {$dev_id}", 1, $limit, 'dev_id,username', 'dev_id ASC');
        if( empty($devs) ) {
            break;
        }
        
        foreach ($devs as $row)
        {
            //只统计正式上线的游戏
            $stmt = $pdo_open->query("SELECT GROUP_CONCAT(game_id) AS gids FROM dev_games WHERE dev_id='{$row['dev_id']}' AND status=9");
            $tmp = $stmt->fetch(PDO::FETCH_ASSOC);
            if( empty($tmp) || empty($tmp['gids']) ) {
                continue;
            }
            $stat = $m_bygame->fetch("{$conds} AND game_id IN({$tmp['gids']})",
                'SUM(signon_times) AS signon_times, SUM(signon_people) AS signon_people, SUM(recharge_times) AS recharge_times,
                SUM(recharge_people) AS recharge_people, SUM(recharge_money) AS recharge_money');
            $stat['ymd'] = $ymd;
            $stat['dev_id'] = $row['dev_id'];
            $stat['dev_name'] = $row['username'];
            $stat['signon_times']=$stat['signon_times']??0;
            $m_bydev->insert($stat, false);
            if( $stat['recharge_money'] > 0 ) {
                $pdo_open->exec("UPDATE developer SET trade_money = trade_money + {$stat['recharge_money']} WHERE dev_id='{$row['dev_id']}'");
            }
        }
        
        if( count($devs) < $limit ) {
            break;
        }
        $dev_id = $row['dev_id'];
    }
}
