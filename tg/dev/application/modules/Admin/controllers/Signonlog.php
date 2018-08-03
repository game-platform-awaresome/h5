<?php

class SignonlogController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['op'] = F_Helper_Html::Op_Null;
        if( isset($params['conditions']) ) {
            if( preg_match('#time=\'([^\']+)\'#', $params['conditions'], $match) ) {
                $begin = strtotime($match[1].' 00:00:00');
                $end = strtotime($match[1].' 23:59:59');
                $params['conditions'] = preg_replace('#time=\'([^\']+)\'#', "time BETWEEN {$begin} AND {$end}", $params['conditions']);
            }
        }
        //按时间最新的在前面
        $params['orderby']='time desc';
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
