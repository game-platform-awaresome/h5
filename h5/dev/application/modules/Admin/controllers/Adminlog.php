<?php

class AdminlogController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['op'] = F_Helper_Html::Op_Null;
        $params['orderby']='op_time desc';
        $search = $this->getRequest()->getQuery('search', array());
        $conds = '';
        $comma = '';
        foreach ($search as $k=>$v){
            $v = trim($v);
            if( empty($v) ) continue;
            if($k=='content'){
                $conds .= "{$comma}{$k} LIKE '%{$v}%'";
                $comma = ' AND ';
            }else{
                $conds .= "{$comma}{$k} = '{$v}'";
                $comma = ' AND ';
            }
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
