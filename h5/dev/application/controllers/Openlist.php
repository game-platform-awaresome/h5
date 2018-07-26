<?php

class OpenlistController extends Yaf_Controller_Abstract
{
    public function indexAction()
    {
        $week = array(' 周日',' 周一',' 周二',' 周三',' 周四',' 周五',' 周六');
        $assign['today'] = date('Y-m-d').$week[date('w')];
        $tmr = strtotime('+1 days');
        $assign['tomorrow'] = date('Y-m-d', $tmr).$week[date('w', $tmr)];
        
        $m_adpos = new AdposModel();
        $assign['banner'] = $m_adpos->getByCode('open_list_banner', 1);
        
        $this->getView()->assign($assign);
    }
    public function listAction()
    {
        $req = $this->getRequest();
        $type = $req->get('type', '');
        $pn = $req->get('pn', 1);
        $limit = $req->get('limit', 8);
        
        $where = '';
        switch ($type)
        {
            case 'today':
                $today = strtotime('today');
                $today_e = $today + 86399;
                $where = "ol.open_time BETWEEN $today AND $today_e";
                $order = 'ol.open_time ASC';
                break;
            case 'tomorrow':
                $tomorrow = strtotime('tomorrow');
                $tomorrow_e = $tomorrow + 86399;
                $where = "ol.open_time BETWEEN $tomorrow AND $tomorrow_e";
                $order = 'ol.open_time ASC';
                break;
            case 'opened':
                $today = strtotime('today');
                $where = "ol.open_time < $today";
                $order = 'ol.open_time DESC';
                break;
        }
        
        $assign['list'] = array();
        if( $where ) {
            $offset = ($pn - 1) * $limit;
            if( $offset < 0 ) $offset = 0;
            
            $m_open = new OpenlistModel();
            $assign['list'] = $m_open->fetchAllBySql("SELECT ol.*, g.giftbag, g.logo FROM open_list AS ol
                LEFT JOIN game AS g ON ol.game_id=g.game_id
                WHERE {$where} ORDER BY {$order} LIMIT {$offset},{$limit}");
        }
        
        $assign['now'] = time();
        $this->getView()->assign($assign);
    }
}
