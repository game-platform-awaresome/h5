<?php

class UsersController extends F_Controller_Backend
{
    private $_oldinfo;
    
    protected function beforeList()
    {
        $params['op'] = F_Helper_Html::Op_Edit;
        
        $search = $this->getRequest()->getQuery('search', array());
        $conds = '';
        $comma = '';
        if( !empty($search['reg_begin']) && !empty($search['reg_end']) ) {
            $search['reg_begin'] = strtotime($search['reg_begin']);
            $search['reg_end'] = strtotime($search['reg_end'].' 23:59:59');
            $conds = "reg_time BETWEEN {$search['reg_begin']} AND {$search['reg_end']}";
            $comma = ' AND ';
        }
        unset($search['reg_begin'], $search['reg_end']);
        foreach ($search as $k=>$v)
        {
            $v = trim($v);
            if( empty($v) ) continue;
            $conds .= "{$comma}{$k}='{$v}'";
            $comma = ' AND ';
        }
        $params['orderby'] = 'reg_time desc';
        $params['conditions'] = $conds;
        
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
    
    public function exportAction()
    {
        $req = $this->getRequest();
        $begin = $req->getPost('export_begin', '');
        $end = $req->getPost('export_end', '');
        $time_b = strtotime($begin);
        $time_e = strtotime($end.' 23:59:59');
        if( ! $time_b || ! $time_e ) {
            exit('时间格式错误！');
        }
        
        $uid = 0;
        $data = "UID,用户名,邮箱,平台币,首付时间,积分\r\n";
        $limit = 1000;
        while (1)
        {
            $sql = "SELECT user_id,username,email,money,first_pay,integral FROM user WHERE reg_time BETWEEN {$time_b} AND {$time_e} AND user_id>{$uid} ORDER BY user_id ASC LIMIT {$limit}";
            $tmp = $this->_model->fetchAllBySql($sql);
            if( empty($tmp) ) {
                break;
            }
            foreach ($tmp as $row)
            {
                $row['first_pay'] = $row['first_pay'] ? date('Y-m-d', $row['first_pay']) : '0';
                $data .= "{$row['user_id']},{$row['username']},{$row['email']},{$row['money']},{$row['first_pay']},{$row['integral']}\r\n";
            }
            if( count($tmp) < $limit ) {
                break;
            }
            $uid = $row['user_id'];
        }
        
        header('Expires: 0');
        header('Content-Type: application/force-download');
        header('Content-Transfer-Encoding: binary');
        header('Cache-control: no-cache');
        header('Pragma: no-cache');
        header('Cache-Component: must-revalidate, post-check=0, pre-check=0');
        header('Content-Length: '.strlen($data));
        header("Content-Disposition: attachment; filename=\"users_{$begin}_{$end}.csv\"");
        echo $data;
        exit;
    }
}
