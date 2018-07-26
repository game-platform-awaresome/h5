<?php

class TgstatisticsController extends F_Controller_Backend
{
    protected function beforeList()
    {
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
    
    public function deleteAction()
    {
        exit;
    }
    
    public function exportAction()
    {
        $req = $this->getRequest();
        $begin = $req->getPost('export_begin', '');
        $end = $req->getPost('export_end', '');
        $channel = $req->getPost('channel', 0);
        $ymd_b = str_replace('-', '', $begin);
        $ymd_e = str_replace('-', '', $end);
        if( ! $ymd_b || ! $ymd_e ) {
            exit('日期格式错误！');
        }
        $conds = "s.ymd BETWEEN {$ymd_b} AND {$ymd_e}";
        if( $channel ) {
            $conds .= " AND s.channel='{$channel}'";
            $dl_file = "tg_stat_{$channel}_{$begin}_{$end}.csv";
        } else {
            $dl_file = "tg_stat_{$begin}_{$end}.csv";
        }
        
        $stat_id = 0;
        $data = "日期,渠道,访问量,IP数,注册人数,支付次数,支付人数,支付金额\r\n";
        $limit = 400;
        while (1)
        {
            $sql = "SELECT s.stat_id,s.ymd,c.name,s.pv,s.ip,s.reg_people,s.pay_times,s.pay_people,s.pay_money FROM h5_tg.statistics AS s
                LEFT JOIN h5_tg.channel AS c ON s.channel=c.channel_id
                WHERE {$conds} AND s.stat_id>{$stat_id} ORDER BY s.stat_id ASC LIMIT {$limit}";
            $tmp = $this->_model->fetchAllBySql($sql);
            if( empty($tmp) ) {
                break;
            }
            foreach ($tmp as $row)
            {
                $stat_id = $row['stat_id'];
                unset($row['stat_id']);
                $data .= implode(',', $row);
                $data .= "\r\n";
            }
            if( count($tmp) < $limit ) {
                break;
            }
        }
        
        header('Expires: 0');
        header('Content-Type: application/force-download');
        header('Content-Transfer-Encoding: binary');
        header('Cache-control: no-cache');
        header('Pragma: no-cache');
        header('Cache-Component: must-revalidate, post-check=0, pre-check=0');
        header('Content-Length: '.strlen($data));
        header("Content-Disposition: attachment; filename=\"{$dl_file}\"");
        echo $data;
        exit;
    }
}
