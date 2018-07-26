<?php

class AdminController extends F_Controller_Backend
{
    private function groupHtml(&$groups, $deep = 0)
    {
        $html = '';
        foreach ($groups as &$row)
        {
            $pad = str_repeat('--', $deep);
            $html .= "<option value=\"{$row['group_id']}\">{$pad}{$row['name']}</option>";
            if( ! empty($row['lists']) ) {
                $html .= $this->groupHtml($row['lists'], $deep+1);
            }
        }
        return $html;
    }
    protected function beforeEdit(&$info)
    {
        $m_group = new AdmingroupModel();
        $groups = $m_group->getAllGroups(0);
        $html = '<select id="info_group_id" name="info[group_id]">';
        $html .= '<option value="0">-请选择权限组-</option>';
        $html .= $this->groupHtml($groups, 0);
        $html .= '</select>';
        $html .= "<script>\$('#info_group_id').val('{$info['group_id']}');</script>";
        $this->getView()->assign('groups', $html);
    }
    
	protected function beforeUpdate($id, &$info)
	{
	    if( $id && empty($info['password']) ) {
	        unset($info['password']);
	    } else {
	        $info['password'] = md5(trim($info['password']));
	    }
	    
	    if( empty($id) ) {
	        $login = $this->_model->getLogin();
	        $info['add_by'] = $login['username'];
	        $info['add_ip'] = $_SERVER['REMOTE_ADDR'];
	    }
	    return '';
	}
}
