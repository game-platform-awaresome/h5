<?php

class AdinstanceController extends F_Controller_Backend
{
    /**
     * @var AdposModel
     */
    private $m_adpos;
    
    public function init()
    {
        parent::init();
        $this->m_adpos = new AdposModel();
    }
    
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['orderby'] = 'pos_id ASC,display ASC';
        return $params;
    }
    
    protected function beforeEdit(&$info)
    {
        $ad_pos = null;
        if( $info ) {
            $ad_pos = $this->m_adpos->fetch("pos_id='{$info['pos_id']}'", 'pos_id,name,image,width,height');
        } else {
            $search = $this->getRequest()->getQuery('search', array());
            if( isset($search['pos_id']) && $search['pos_id'] ) {
                $ad_pos = $this->m_adpos->fetch("pos_id='{$search['pos_id']}'", 'pos_id,name,image,width,height');
            }
        }
        
        $all_pos = $this->m_adpos->fetchAll('', 1, 200, 'pos_id,name,image,width,height');
        
        $this->_view->assign('ad_pos', $ad_pos);
        $this->_view->assign('all_pos', $all_pos);
        $this->_view->assign('target', $this->_model->_target);
    }
    
    protected function beforeUpdate($id, &$info)
    {
        $info['on_time'] = strtotime($info['on_time']);
        $info['off_time'] = strtotime($info['off_time']);
    }
    
    protected function afterUpdate($id, &$info)
    {
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
            $path = '/game/ad_ins/';
            $path .= "{$id}.{$ext}";
            $dst = APPLICATION_PATH.'/public'.$path;
            $rs = move_uploaded_file($_FILES['image']['tmp_name'], $dst);
            if( ! $rs ) {
                return '不是有效的上传文件，请重新上传！';
            }
            $path .= '?'.time();
            $data['image'] = $path;
            
            $primary = $this->_model->getPrimary();
            $this->_model->update($data, array($primary=>$id));
        }
    }
    
    protected function beforeDelete($id)
    {
        $conds = "ad_id='{$id}'";
        $ad_ins = $this->_model->fetch($conds, 'image');
        if( empty($ad_ins) ) {
            return '广告不存在，可能已经被删除了。';
        }
        if( $ad_ins['image'] ) {
            $ad_ins['image'] = explode('?', $ad_ins['image']);
            $ad_ins['image'] = $ad_ins['image'][0];
            $file = APPLICATION_PATH."/public{$ad_ins['image']}";
            $rs = @unlink($file);
            if( ! $rs ) {
                return '删除广告图片时发生错误！';
            }
        }
        return '';
    }
}
