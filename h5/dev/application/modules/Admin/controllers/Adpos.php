<?php

class AdposController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['orderby'] = 'display ASC';
        $params['op'] = F_Helper_Html::Op_Edit;
        return $params;
    }
    
    protected function afterUpdate($id, &$info)
    {
        $m_adins = new AdinstanceModel();
        $m_adins->update(array('name' => $info['name']), array('pos_id'=>$id));
        
        $data = null;
        if( $_FILES['image']['size'] > 0 && $_FILES['image']['error'] == 0 ) {
            $img = getimagesize($_FILES['image']['tmp_name']);
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
            
            if( $info['image'] ) {
                $info['image'] = explode('?', $info['image']);
                $info['image'] = $info['image'][0];
                $file = APPLICATION_PATH.'/public'.$info['image'];
                if( file_exists($file) ) {
                    unlink($file);
                }
            }
            
            $path = '/game/ad_pos/';
            $path .= "{$id}.{$ext}";
            $dst = APPLICATION_PATH.'/public'.$path;
            $rs = move_uploaded_file($_FILES['image']['tmp_name'], $dst);
            if( ! $rs ) {
                return '不是有效的上传文件，请重新上传！';
            }
            $path .= '?'.time();
            $data['image'] = $path;
        }
        
        if( $_FILES['preview']['size'] > 0 && $_FILES['preview']['error'] == 0 ) {
            $img = getimagesize($_FILES['preview']['tmp_name']);
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
            
            if( $info['preview'] ) {
                $info['preview'] = explode('?', $info['preview']);
                $info['preview'] = $info['preview'][0];
                $file = APPLICATION_PATH.'/public'.$info['preview'];
                if( file_exists($file) ) {
                    unlink($file);
                }
            }
            
            $path = '/game/ad_pos/';
            $path .= "{$id}-preview.{$ext}";
            $dst = APPLICATION_PATH.'/public'.$path;
            $rs = move_uploaded_file($_FILES['preview']['tmp_name'], $dst);
            if( ! $rs ) {
                return '不是有效的上传文件，请重新上传！';
            }
            $path .= '?'.time();
            $data['preview'] = $path;
        }
        
        if( $data ) {
            $primary = $this->_model->getPrimary();
            $this->_model->update($data, array($primary=>$id));
        }
    }
    
    protected function beforeDelete($id)
    {
        $conds = "pos_id='{$id}'";
        $ad_pos = $this->_model->fetch($conds, 'image');
        if( empty($ad_pos) ) {
            //return '广告位不存在，可能已经被删除了。';
        } else {
            if( $ad_pos['image'] ) {
                $ad_pos['image'] = explode('?', $ad_pos['image']);
                $ad_pos['image'] = $ad_pos['image'][0];
                $file = APPLICATION_PATH."/public{$ad_pos['image']}";
                @unlink($file);
            }
            if( $ad_pos['preview'] ) {
                $ad_pos['preview'] = explode('?', $ad_pos['preview']);
                $ad_pos['preview'] = $ad_pos['preview'][0];
                $file = APPLICATION_PATH."/public{$ad_pos['preview']}";
                @unlink($file);
            }
        }
        
        $m_adins = new AdinstanceModel();
        $ad_ins = $m_adins->fetchAll($conds, 1, 100, 'image');
        foreach ($ad_ins as &$ins)
        {
            if( $ins['image'] ) {
                $ins['image'] = explode('?', $ins['image']);
                $ins['image'] = $ins['image'][0];
                $file = APPLICATION_PATH."/public{$ins['image']}";
                @unlink($file);
            }
        }
        $m_adins->delete($conds);
        
        return '';
    }
    
    public function cacheAction()
    {
        $msg = '';
        $req = $this->getRequest();
        $pos_id = $req->getPost('pos_id', 0);
        
        if( $pos_id == -1 ) {
            $ad_pos = $this->_model->fetchAll('', 1, 200);
        } else {
            $ad_pos = $this->_model->fetchAll('', 1, 200, 'pos_id,name');
        }
        $this->_view->assign('ad_pos', $ad_pos);
        $this->_view->assign('title', '更新'.$this->_model->getTableLabel().'缓存');
        if( $pos_id == 0 ) {
            $this->_view->assign('msg', $msg);
            return true;
        }
        
        $m_adins = new AdinstanceModel();
        $dir = APPLICATION_PATH.'/application/cache/adpos/';
        if( $pos_id == -1 ) {
            $files = scandir($dir);
            unset($files[0], $files[1]);
            foreach ($files as $file)
            {
                $file = "{$dir}{$file}";
                @unlink($file);
            }
            
            foreach ($ad_pos as $row)
            {
                $file = "{$dir}{$row['pos_code']}.php";
                $row['ads'] = $m_adins->fetchAll("pos_id='{$row['pos_id']}' AND visible=1", 1, 30, 'ad_id,image,subject,url,on_time,off_time', 'display ASC');
                $data = '<?php';
                $data .= "\r\n\r\nreturn ";
                $data .= var_export($row, true);
                $data .= ";\r\n";
                file_put_contents($file, $data);
            }
            $total = count($ad_pos);
            $msg = "{$total}个广告位已更新！";
        } else {
            $row = $this->_model->fetch("pos_id='{$pos_id}'");
            $row['ads'] = $m_adins->fetchAll("pos_id='{$row['pos_id']}' AND visible=1", 1, 30, 'ad_id,image,subject,url,on_time,off_time', 'display ASC');
            $file = "{$dir}{$row['pos_code']}.php";
            $data = '<?php';
            $data .= "\r\n\r\nreturn ";
            $data .= var_export($row, true);
            $data .= ";\r\n";
            file_put_contents($file, $data);
            $msg = "广告位“{$row['name']}”已更新！";
        }
        
        $this->_view->assign('msg', $msg);
        return true;
    }
}
