<?php

class GameroleController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params=parent::beforeList();
        $params['op'] = F_Helper_Html::Op_Null;
        $params['orderby'] = 'id desc';
        return $params;
    }
}
