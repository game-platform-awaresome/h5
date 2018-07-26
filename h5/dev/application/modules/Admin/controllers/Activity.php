<?php

class ActivityController extends F_Controller_Backend
{
    protected function beforeList()
    {
        return array('orderby' => 'weight ASC,act_id DESC');
    }
    
    protected function beforeEdit(&$info)
    {
        $v = $this->getView();
        $chtml = '';
        if( $info ) {
            $info['in_short'] = preg_replace('/(?:<br[^>]*>)+/', '', $info['in_short']);
            $info['settings'] = empty($info['settings']) ? null : unserialize($info['settings']);
            $class = array_key_exists($info['controller'], $this->_model->_ctls) ? 'Activity_'.$info['controller'] : '';
            if( $class ) {
                $class = new $class();
                $chtml = $this->chtml($class, $info['settings']);
            }
        }
        $v->assign('chtml', $chtml);
        $v->assign('ctls', $this->_model->_ctls);
        $v->assign('conds', $this->_model->_conds);
        $v->assign('periods', $this->_model->_periods);
    }
    
    protected function beforeUpdate($id, &$info)
    {
        $info['in_short'] = str_replace(array("\r\n", "\r\n\r\n", "\n\n"), "\n", $info['in_short']);
        $info['in_short'] = str_replace("\n\n", "\n", $info['in_short']);
        $info['in_short'] = nl2br($info['in_short']);
        
        $settings = $this->getRequest()->getPost('settings', array());
        $tmp = array();
        foreach ($settings as $row)
        {
            $tmp[] = $row;
        }
        $info['settings'] = empty($tmp) ? '' : serialize($tmp);
        $info['begin_time'] = empty($info['begin_time']) ? 0 : strtotime($info['begin_time']);
        $info['end_time'] = empty($info['end_time']) ? 0 : strtotime($info['end_time']);
        return '';
    }
    
    protected function afterUpdate($id, &$info)
    {
        if( $_FILES['cover']['size'] > 0 && $_FILES['cover']['error'] == 0 ) {
            $img = getimagesize($_FILES['cover']['tmp_name']);
            if( ! $img ) {
                return '上传文件不是有效的图片文件！';
            }
            $ext = '';
            switch ($img[2])
            {
                case 1: $ext = 'gif'; break;
                case 2: $ext = 'jpg'; break;
                case 3: $ext = 'png'; break;
                default: return '不支持的图片文件格式！';
            }
            
            if( $info['cover'] ) {
                $info['cover'] = explode('?', $info['cover']);
                $info['cover'] = $info['cover'][0];
                $file = APPLICATION_PATH.'/public'.$info['cover'];
                if( file_exists($file) ) {
                    unlink($file);
                }
            }
            
            $path = '/game/activity/';
            $path .= "{$id}.{$ext}";
            $dst = APPLICATION_PATH.'/public'.$path;
            $rs = move_uploaded_file($_FILES['cover']['tmp_name'], $dst);
            if( ! $rs ) {
                return '不是有效的上传文件，请重新上传！';
            }
            $path .= '?'.time();
            $data['cover'] = $path;
        
            $primary = $this->_model->getPrimary();
            $this->_model->update($data, array($primary=>$id));
        }
    }
    
    protected function beforeDelete($id)
    {
        $conds = "act_id='{$id}'";
        $item = $this->_model->fetch($conds, 'cover');
        if( empty($item) ) {
            return '活动不存在，可能已经被删除了。';
        }
        if( $item['cover'] ) {
            $item['cover'] = explode('?', $item['cover']);
            $item['cover'] = $item['cover'][0];
            $file = APPLICATION_PATH."/public{$item['cover']}";
            $rs = @unlink($file);
            if( ! $rs ) {
                return '删除咨询封面时发生错误！';
            }
        }
        return '';
    }
    
    private function chtml($class, &$conf)
    {
        $html = '';
        if( $class->type == 'multi' ) {
            $html .= '<table id="act_box" style="width:auto">';
            
            $html .= '<thead style="border-top:1px solid #eee"><tr>';
            foreach ($class->config as &$row)
            {
                $html .= "<th>{$row[0]}</th>";
            }
            $html .= '<th></th>';
            $html.= '</tr></thead>';
            
            $html .= '<tbody>';
            if( $conf ) {
                foreach ($conf as $idx=>&$tmp)
                {
                    $html .= "<tr id=\"act_row_{$idx}\">";
                    foreach ($class->config as $k=>&$row)
                    {
                        if( $row[1] == 'input' ) {
                            $html .= "<td><input type=\"input\" name=\"settings[{$idx}][{$k}]\" value=\"{$tmp[$k]}\"></td>";
                        } elseif( $row[1] == 'select' ) {
                            $html .= "<td><select name=\"settings[{$idx}][{$k}]\">";
                            $html .= "<option value=\"\">--{$row[0]}--</option>";
                            foreach ($row[2] as $sk=>$sv)
                            {
                                $selected = $tmp[$k] == $sk ? ' selected="selected"' : '';
                                $html .= "<option value=\"{$sk}\"{$selected}>{$sv}</option>";
                            }
                            $html .= '</select></td>';
                        }
                    }
                    $html .= '<td><i class="ui-icons-red ui-icon-del"></i></td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr id="act_row_0">';
                foreach ($class->config as $k=>&$row)
                {
                    if( $row[1] == 'input' ) {
                        $html .= "<td><input type=\"input\" name=\"settings[0][{$k}]\"></td>";
                    } elseif( $row[1] == 'select' ) {
                        $html .= "<td><select name=\"settings[0][{$k}]\">";
                        $html .= "<option value=\"\">--{$row[0]}--</option>";
                        foreach ($row[2] as $sk=>$sv)
                        {
                            $html .= "<option value=\"{$sk}\">{$sv}</option>";
                        }
                        $html .= '</select></td>';
                    }
                }
                $html .= '<td><i class="ui-icons-red ui-icon-del"></i></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            
            $html .= '<tfoot style="border-bottom:1px solid #eee"><tr id="act_row_add"><td><i class="ui-icons-blue ui-icon-add"></i></td></tr></tfoot>';
            $html .= '</table>';
        } else {
            foreach ($class->config as $k=>&$row)
            {
                $html .= "<p><label>{$row[0]}</label>";
                $v = $conf && isset($conf[$k]) ? $conf[$k] : '';
                if( $row[1] == 'input' ) {
                    $html .= "<input class=\"text-input\" type=\"text\" name=\"settings[{$k}]\" value=\"{$v}\" />";
                } elseif( $row[1] == 'select' ) {
                    $html .= "<select class=\"text-input\" name=\"settings[{$k}]\">";
                    $html .= "<option value=\"\">--{$row[0]}--</option>";
                    foreach ($row[2] as $sk=>$sv)
                    {
                        $selected = $sk == $v ? ' selected="selected"' : '';
                        $html .= "<option value=\"{$sk}\"{$selected}>{$sv}</option>";
                    }
                    $html .= '</select>';
                }
                $html .= "</p>";
            }
        }
        return $html;
    }
    public function chtmlAction()
    {
        $req = $this->getRequest();
        $act_id = $req->getPost('act_id', 0);
        $c = $req->getPost('controller', '');
        if( ! array_key_exists($c, $this->_model->_ctls) ) {
            var_dump($c);
            exit;
        }
        $conf = null;
        if( $act_id > 0 ) {
            $act = $this->_model->fetch("act_id='{$act_id}'", 'act_id,settings');
            if( empty($act) ) {
                var_dump($act);
                exit;
            }
            $conf = empty($act['settings']) ? null : unserialize($act['settings']);
        }
        
        $class = 'Activity_'.$c;
        $class = new $class();
        $html = $this->chtml($class, $conf);
        exit($html);
    }
}
