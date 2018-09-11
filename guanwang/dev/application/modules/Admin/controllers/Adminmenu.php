<?php

class AdminmenuController extends F_Controller_Backend
{
    protected function beforeList()
    {
        return array(
            'orderby' => 'display ASC',
        );
    }
    
    protected function beforeEdit(&$info)
    {
        $parents = $this->_model->fetchAll('parent_id=0', 1, 50, 'menu_id,name');
        $this->_view->assign('parents', $parents);
    }
}
