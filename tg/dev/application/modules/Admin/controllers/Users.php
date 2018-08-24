<?php

class UsersController extends F_Controller_Backend
{
    private $_oldinfo;
    
    protected function beforeList()
    {
//        $params['op'] = F_Helper_Html::Op_Edit;
        
        $search = $this->getRequest()->getQuery('search', array());
        $conds = '';
        $comma = '';
        //显示自己渠道的用户
        $s = Yaf_Session::getInstance();
        $channel_ids_condition=$s->get('channel_ids_condition');
        if( $search ) {
            if (!empty($search['reg_begin']) && !empty($search['reg_end'])) {
                $search['reg_begin'] = strtotime($search['reg_begin']);
                $search['reg_end'] = strtotime($search['reg_end'] . ' 23:59:59');
                $conds = "reg_time BETWEEN {$search['reg_begin']} AND {$search['reg_end']}";
                $comma = ' AND ';
            }
            unset($search['reg_begin'], $search['reg_end']);
            foreach ($search as $k => $v) {
                $v = trim($v);
                if (empty($v)) continue;
                $conds .= "{$comma}{$k}='{$v}'";
                $comma = ' AND ';
            }
            $conds.="  AND tg_channel in {$channel_ids_condition}";

        }else{
            $conds.="tg_channel in {$channel_ids_condition}";
        }
        $params['conditions'] = $conds;
        $params['op']=F_Helper_Html::Op_Null;
        $params['orderby'] = 'reg_time desc';
        return $params;
    }
    
    protected function beforeUpdate($id, &$info)
    {
        if( $id ) {
            $this->_oldinfo = $this->_model->fetch(array('user_id'=>$id), 'username,money');
        }
    }
    
    protected function afterUpdate($id, &$info)
    {
        if( empty($this->_oldinfo) ) {
            return '';
        }
        if( $this->_oldinfo['money'] != $info['money'] ) {
            $m_log = new AdminlogModel();
            $m_log->insert(array(
                'admin' => Yaf_Session::getInstance()->get('admin_name'),
                'content' => "修改用户 {$this->_oldinfo['username']} 的余额，{$this->_oldinfo['money']} -> {$info['money']}",
                'ymd' => date('Ymd'),
            ));
        }
    }
    
    public function deleteAction()
    {
        exit;
    }
}
