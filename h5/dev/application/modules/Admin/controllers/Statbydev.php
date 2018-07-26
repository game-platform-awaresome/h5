<?php

class StatbydevController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params['orderby'] = 'ymd DESC';
        $params['op'] = F_Helper_Html::Op_Null;
        
        $search = $this->getRequest()->getQuery('search', array());
        $conds = '';
        $comma = '';
        if( !empty($search['ymd_begin']) || !empty($search['ymd_end']) ) {
            $search['ymd_begin'] = !empty($search['ymd_begin']) ? str_replace('-', '', $search['ymd_begin']) : '19700101';
            $search['ymd_end'] = !empty($search['ymd_end']) ? str_replace('-', '', $search['ymd_end']) : date('Ymd');
            $conds = "ymd BETWEEN {$search['ymd_begin']} AND {$search['ymd_end']}";
            $comma = ' AND ';
        }
        if( !empty($search['dev_id']) ) {
            $conds .= "{$comma}dev_id='{$search['dev_id']}'";
        }
        $params['conditions'] = $conds;
        
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
