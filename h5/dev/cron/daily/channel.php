<?php

/**
 * 推广渠道统计
 */
function channel()
{
    $m_stat = new TgstatisticsModel();
    $two_ago = date('Ymd', strtotime('-2 days'));
    $last = $m_stat->fetch("ymd='{$two_ago}'", 'MAX(user_id) AS user_id');
    if( $last ) {
        $user_id = intval($last['user_id']);
    } else {
        $user_id = 0;
    }
    
    $begin = strtotime('-1 days');
    $ymd = date('Ymd', $begin);
    $end = $begin + 86400 - 1;
    $max_uid = $user_id;
    $stat_id = 0;
    $m_stat->delete("ymd='{$ymd}'");
    
    $m_user = new UsersModel();
    $stat = $m_user->fetchAll("user_id>{$user_id} AND reg_time BETWEEN {$begin} AND {$end} AND tg_channel>0 GROUP BY tg_channel",
        1, 200, 'tg_channel AS channel, COUNT(user_id) AS reg_people, MAX(user_id) AS user_id');
    $exists = array();
    foreach ($stat as $row)
    {
        $row['ymd'] = $ymd;
        $stat_id = $m_stat->insert($row);
        $exists[$row['channel']] = $stat_id;
        $max_uid = $row['user_id'] > $max_uid ? $row['user_id'] : $max_uid;
    }
    
    $m_pay = new PayModel();
    $stat = $m_pay->fetchAll("pay_time BETWEEN {$begin} AND {$end} AND type<>'deposit' AND tg_channel>0 GROUP BY tg_channel",
        1, 200, 'tg_channel AS channel, COUNT(pay_id) AS pay_times, COUNT(DISTINCT user_id) AS pay_people, SUM(money) AS pay_money');
    foreach ($stat as $row)
    {
        if( isset($exists[$row['channel']]) ) {
            $m_stat->update($row, "stat_id='{$exists[$row['channel']]}'");
        } else {
            $row['ymd'] = $ymd;
            $stat_id = $m_stat->insert($row);
            $exists[$row['channel']] = $stat_id;
        }
    }
    
    $m_stat->update("user_id='{$max_uid}'", "stat_id='{$stat_id}'");
}
