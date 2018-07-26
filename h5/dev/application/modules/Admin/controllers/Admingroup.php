<?php

class AdmingroupController extends F_Controller_Backend
{
    public function editAction()
    {
        $req = $this->getRequest();
        $group_id = $req->get('group_id', 0);
        $row = $this->_model->fetch("group_id='{$group_id}'", 'parent_id');
        $parent_id = $row['parent_id'];
        $m_gm = new GroupmenuModel();
        if( $parent_id == 0 ) {
            $assign['parent'] = null;
        } else {
            $tmp = $m_gm->fetchAll("group_id='{$parent_id}'", 1, 500, 'menu_id');
            $assign['parent'] = array();
            foreach ($tmp as &$row)
            {
                $assign['parent'][$row['menu_id']] = null;
            }
        }
        $tmp = $m_gm->fetchAll("group_id='{$group_id}'", 1, 500, 'menu_id');
        $assign['self'] = array();
        foreach ($tmp as &$row)
        {
            $assign['self'][$row['menu_id']] = null;
        }
        
        $m_menu = new AdminmenuModel();
        $assign['menu'] = $m_menu->fetchAll('parent_id=0', 1, 50, 'menu_id,parent_id,name,controller', 'display ASC');
        foreach ($assign['menu'] as $k=>&$row)
        {
            $assign['menu'][$k]['children'] = $m_menu->fetchAll("parent_id='{$row['menu_id']}'", 1, 12, 'menu_id,parent_id,name,controller', 'display ASC');
        }
        
        $assign['group_id'] = $group_id;
        $this->getView()->assign($assign);
    }
    
    public function updateAction()
    {
        $req = $this->getRequest();
        $group_id = $req->getPost('group_id', 0);
        $menu = $req->getPost('menu', '');
        $ctls = $req->getPost('ctls', '');
        if( empty($group_id) || empty($menu) || empty($ctls) ) {
            exit('参数不能为空！');
        }
        
        $menu = explode(',', $menu);
        $ctls = explode(',', $ctls);
        $menu_ins = array();
        $ctls_ins = array();
        foreach ($menu as $menu_id)
        {
            $menu_ins[] = "('{$group_id}','{$menu_id}')";
        }
        foreach ($ctls as $ctl)
        {
            $ctls_ins[] = "('{$group_id}','{$ctl}')";
        }
        $menu_ins = implode(',', $menu_ins);
        $ctls_ins = implode(',', $ctls_ins);
        
        $m_gm = new GroupmenuModel();
        $m_gc = new GroupcontrollerModel();
        $gm_tb = $m_gm->getTable();
        $gc_tb = $m_gc->getTable();
        $menu_ins = "INSERT INTO {$gm_tb}(group_id,menu_id) VALUES{$menu_ins}";
        $ctls_ins = "INSERT INTO {$gc_tb}(group_id,controller) VALUES{$ctls_ins}";
        $pdo = $this->_model->getPdo();
        $pdo->exec("DELETE FROM {$gm_tb} WHERE group_id='{$group_id}'");
        $pdo->exec("DELETE FROM {$gc_tb} WHERE group_id='{$group_id}'");
        $pdo->exec($menu_ins);
        $pdo->exec($ctls_ins);
        exit('1');
    }
    
    private function groupHtml(&$groups, $parent_id = 0)
    {
        $html = "<ol class=\"sortable\" data-pid=\"{$parent_id}\">";
        foreach ($groups as &$row)
        {
            $class = $row['child_no'] > 0 ? 'ui-icon-expand' : 'ui-icon-empty';
            $html .= "<li data-gid=\"{$row['group_id']}\">";
            $html .= "<i class=\"ui-icons-blue {$class}\"></i><span>{$row['name']}</span>";
            $html .= '<i class="ui-icons-blue ui-icon-add-son" title="添加子权限组"></i>';
            $html .= '<i class="ui-icons-blue ui-icon-rule" title="分配权限"></i>';
            $html .= '<i class="ui-icons-red ui-icon-del" title="删除此权限组"></i>';
            if( ! empty($row['lists']) ) {
                $html .= $this->groupHtml($row['lists'], $row['group_id']);
            }
            $html .= '</li>';
        }
        $html .= '<li class="item-add"><i class="ui-icons-blue ui-icon-add-bro" title="添加同级权限组"></i><div class="clear"></div></li>';
        $html .= '</ol>';
        return $html;
    }
    public function listAction()
    {
        $groups = $this->_model->getAllGroups(0);
        $html = $this->groupHtml($groups, 0);
        $this->getView()->assign('html', $html);
    }
    
    public function ajaxaddAction()
    {
        $req = $this->getRequest();
        if( ! $req->isXmlHttpRequest() ) {
            exit;
        }
        $arr['parent_id'] = $req->getPost('pid', 0);
        $arr['child_no'] = 0;
        $arr['name'] = $req->getPost('name', '');
        $arr['weight'] = $req->getPost('weight', 1);
        if( empty($arr['name']) ) {
            exit('权限组名称不能为空！');
        }
        $gid = $this->_model->insert($arr);
        if( $gid > 0 ) {
            if( $arr['parent_id'] > 0 ) {
                $pdo = $this->_model->getPdo();
                $pdo->exec("UPDATE admin_group SET child_no=child_no+1 WHERE group_id='{$arr['parent_id']}'");
            }
            exit("{$gid}");
        } else {
            exit('权限组添加失败，请重试！');
        }
    }
    
    public function changenameAction()
    {
        $req = $this->getRequest();
        if( ! $req->isXmlHttpRequest() ) {
            exit;
        }
        $conds['group_id'] = $req->getPost('gid', 0);
        $data['name'] = $req->getPost('name', '');
        if( $conds['group_id'] == 0 || $data['name'] == '' ) {
            exit;
        }
        $rs = $this->_model->update($data, $conds);
        exit($rs ? '1' : '权限组名称修改失败！');
    }
    
    public function resortAction()
    {
        $req = $this->getRequest();
        if( ! $req->isXmlHttpRequest() ) {
            exit;
        }
        $info = $req->getPost('info', array());
        if( empty($info) ) {
            exit;
        }
        foreach ($info as $idx=>$gid)
        {
            $idx += 1;
            $this->_model->update("weight='{$idx}'", "group_id='{$gid}'");
        }
        exit;
    }
    
    private function cascadeDelete($pid)
    {
        $groups = $this->_model->fetchAll("parent_id='{$pid}'", 1, 50, 'group_id,child_no');
        $m_gm = F_Model_Pdo::getInstance('Groupmenu');
        $m_gc = F_Model_Pdo::getInstance('Groupcontroller');
        foreach ($groups as &$row)
        {
            if( $row['child_no'] > 0 ) {
                $this->cascadeDelete($row['group_id']);
            }
            $this->_model->delete("group_id='{$row['group_id']}'");
            $m_gm->delete("group_id='{$row['group_id']}'");
            $m_gc->delete("group_id='{$row['group_id']}'");
        }
    }
    protected function beforeDelete($id)
    {
        $row = $this->_model->fetch("group_id='{$id}'", 'parent_id,child_no');
        if( empty($row) ) {
            return '权限组不存在！';
        }
        if( $row['parent_id'] != 0 ) {
            $pdo = $this->_model->getPdo();
            $rs = $pdo->exec("UPDATE admin_group SET child_no=child_no-1 WHERE group_id='{$row['parent_id']}'");
            if( ! $rs ) {
                return '父权限更新失败！';
            }
        }
        if( $row['child_no'] > 0 ) {
            $this->cascadeDelete($id);
        }
        $m_gm = F_Model_Pdo::getInstance('Groupmenu');
        $m_gc = F_Model_Pdo::getInstance('Groupcontroller');
        $m_gm->delete("group_id='{$id}'");
        $m_gc->delete("group_id='{$id}'");
        return '';
    }
}
