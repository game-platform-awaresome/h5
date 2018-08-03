<?php

class TgstatisticsController extends F_Controller_Backend
{
    protected function beforeList()
    {
        //生成统计报表
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
            1, 200000, 'tg_channel AS channel, COUNT(user_id) AS reg_people, MAX(user_id) AS user_id');
        $exists = array();
        foreach ($stat as $row)
        {
            $row['ymd'] = $ymd;
            $stat_id = $m_stat->insert($row);
            $exists[$row['channel']] = $stat_id;
            $max_uid = $row['user_id'] > $max_uid ? $row['user_id'] : $max_uid;
        }
        //生成统计报表
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


        $m_ch = new TgchannelModel();
        $channel = $m_ch->fetchAll('', 1, 200, 'channel_id,name');
        $this->getView()->assign('channel', $channel);
        
        $params['op'] = F_Helper_Html::Op_Null;
        
        $search = $this->getRequest()->getQuery('search', array());
        $conds = '';
        $comma = '';
        if( !empty($search['ymd_begin']) && !empty($search['ymd_end']) ) {
            $ymd_begin = str_replace('-', '', $search['ymd_begin']);
            $ymd_end = str_replace('-', '', $search['ymd_end']);
            $conds = "ymd BETWEEN {$ymd_begin} AND {$ymd_end}";
            $comma = ' AND ';
        }
        //unset($search['reg_begin'], $search['reg_end']);
        foreach ($search as $k=>$v)
        {
            if( $k == 'ymd_begin' || $k == 'ymd_end' ) continue;
            $v = trim($v);
            if( empty($v) ) continue;
            $conds .= "{$comma}{$k}='{$v}'";
            $comma = ' AND ';
        }
        $params['conditions'] = $conds;
        
        return $params;
    }
}
