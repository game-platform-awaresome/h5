<?php

class GameController extends F_Controller_Backend
{
    public function init()
    {
        parent::init();
        $this->_view->assign('classic', $this->_model->_classic);
        $this->_view->assign('types', $this->_model->_types);
        $this->_view->assign('labels', $this->_model->_labels);
        $this->_view->assign('corner', $this->_model->_corner);
        $this->_view->assign('channels', $this->_model->_channels);
        $this->_view->assign('load_types', $this->_model->_load_types);
        $this->_view->assign('screens', $this->_model->_screens);
    }

    protected function beforeList()
    {
        $search = $this->getRequest()->getQuery('search', array());
        $conds = 'dev_id > 0 and visible = 1';
        $comma = ' AND ';
        if( !empty($search['add_begin']) && !empty($search['add_end']) ) {
            $search['add_end'] .= ' 23:59:59';
            $conds = "{$comma}add_time BETWEEN '{$search['add_begin']}' AND '{$search['add_end']}'";
        }
        unset($search['add_begin'], $search['add_end']);
        foreach ($search as $k=>$v)
        {
            $v = trim($v);
            if( empty($v) ) continue;
            $conds .= "{$comma}{$k}='{$v}'";
            $comma = ' AND ';
        }
        $params['conditions'] = $conds;
        $params['op'] = F_Helper_Html::Op_Null;
        $params['orderby'] = 'game_id desc';
        return $params;
    }
    
    protected function beforeEdit(&$info)
    {
        if( empty($info) ) {
            return ;
        }
        $info['screenshots'] = empty($info['screenshots']) ? array() : unserialize($info['screenshots']);
    }
    
	protected function beforeUpdate($id, &$info)
	{
	    foreach ($info as $k=>$v)
	    {
	        $info[$k] = trim($v);
	    }
	    
	    //处理搜索
	    $pinyin = F_Helper_Pinyin::convert($info['name']);
	    $py_sp = F_Helper_Pinyin::convert($info['name'], 1);
	    $py_mx = F_Helper_Pinyin::mixConvert($info['name']);
	    if( $py_mx != $pinyin ) {
	        $pinyin .= " {$py_mx}";
	    }
	    $info['initial'] = substr($py_sp, 0, 1);
	    $info['search'] = "{$info['name']} {$pinyin} {$py_sp} {$info['type']} {$info['tag']}";
	    $info['search'] = substr(preg_replace('/\s{2,}/', ' ', $info['search']), 0, 255);
	    
	    return '';
	}
	
	protected function afterUpdate($id, &$info)
	{
	    $conds = "game_id='{$id}'";
	    $up_arr = null;
	    
	    $m_server = new ServerModel();
	    $m_server->update(array('game_name'=>$info['name']), $conds);
	    
	    if( $_FILES['logo']['size'] > 0 && $_FILES['logo']['error'] == 0 ) {
	        $logo = $this->_model->fetch("game_id={$id}", 'logo');
	        
	        $img = getimagesize($_FILES['logo']['tmp_name']);
	        if( ! $img ) {
	            return '上传的LOGO不是有效的图片文件！';
	        }
	        $ext = '';
	        switch ($img[2])
	        {
	            case 1: $ext = 'gif'; break;
	            case 2: $ext = 'jpg'; break;
	            case 3: $ext = 'png'; break;
	            default: return '不支持的图片文件格式！';
	        }
	        //删除原来的LOGO
	        if( $logo['logo'] ) {
	            $logo = explode('?', $logo['logo']);
	            $logo = $logo[0];
	            $logo = APPLICATION_PATH."/public{$logo}";
	            if( file_exists($logo) ) {
	                @unlink($logo);
	            }
	        }
	        $path = '/game/logo/';
	        $path .= "{$id}.{$ext}";
	        $dst = APPLICATION_PATH.'/public'.$path;
	        $rs = move_uploaded_file($_FILES['logo']['tmp_name'], $dst);
	        if( ! $rs ) {
	            return '不是有效的上传文件，请重新上传！';
	        }
	        $path .= '?'.time();
	        $up_arr['logo'] = $path;
	    }
	    
	    $data = $this->_model->fetch($conds, 'screenshots');
	    if( $data && $data['screenshots'] ) {
	        $images = unserialize($data['screenshots']);
	    } else {
	        $images = array();
	    }
	    
	    //删除截图
	    $delsss = isset($_POST['delsss']) ? $_POST['delsss'] : array();
	    
	    foreach ($_FILES['screenshots']['tmp_name'] as $idx=>$tmp_name)
	    {
	        $error = $_FILES['screenshots']['error'][$idx];
	        $size = $_FILES['screenshots']['size'][$idx];
	        $idx += 1;
	        //删除优先
	        if( isset($delsss[$idx]) || ($images[$idx] && $error == 0 && $size > 0) ) {
	            $file = explode('?', $images[$idx]);
	            $file = $file[0];
	            $file = APPLICATION_PATH."/public{$file}";
	            if( file_exists($file) ) {
	                @unlink($file);
	            }
	            $images[$idx] = '';
	        }
	        if( $error == 0 && $size > 0 ) {
	            $img = getimagesize($tmp_name);
	            if( ! $img ) {
	                continue;
	            }
	            $ext = '';
	            switch ($img[2])
	            {
	                case 1: $ext = 'gif'; break;
	                case 2: $ext = 'jpg'; break;
	                case 3: $ext = 'png'; break;
	                default: break;
	            }
	            if( $ext == '' ) {
	                continue;
	            }
	            $path = '/game/screenshots/';
	            $path .= "{$id}-{$idx}.{$ext}";
	            $dst = APPLICATION_PATH.'/public'.$path;
	            $rs = move_uploaded_file($tmp_name, $dst);
	            if( ! $rs ) {
	                continue;
	            }
	            $path .= '?'.time();
	            $images[$idx] = $path;
	        } elseif( empty($images[$idx]) ) {
	            $images[$idx] = '';
	        }
	    }
	    if( $images ) {
	        $up_arr['screenshots'] = serialize($images);
	    }
	    if( $up_arr ) {
	        $this->_model->update($up_arr, $conds);
	    }
	}
	
	protected function beforeDelete($id)
	{
	    $conds = "game_id='{$id}'";
	    $game = $this->_model->fetch($conds, 'name,logo,screenshots,details');
	    if( empty($game) ) {
	        return '游戏不存在，可能已经被删除了。';
	    }
	    if( $game['logo'] ) {
	        $game['logo'] = explode('?', $game['logo']);
	        $game['logo'] = $game['logo'][0];
	        $file = APPLICATION_PATH."/public{$game['logo']}";
	        $rs = @unlink($file);
	        if( ! $rs ) {
	            return '删除游戏LOGO时发生错误！';
	        }
	    }
	    
	    if( $game['screenshots'] ) {
	        $shots = unserialize($game['screenshots']);
	        if( is_array($shots) ) {
	            foreach ($shots as $file)
	            {
	                $file = explode('?', $file);
	                $file = $file[0];
	                $file = APPLICATION_PATH."/public{$file}";
	                @unlink($file);
	            }
	        }
	    }
	    if( $game['details'] ) {
	        $times = preg_match_all('#<img.*?src=\"([^\"]+)\"#i', $game['details'], $matches);
	        if( $times ) {
	            foreach ($matches[1] as $file)
	            {
	                $file = APPLICATION_PATH."/public{$file}";
	                @unlink($file);
	            }
	        }
	    }
	    
	    $m_server = new ServerModel();
	    $m_server->delete($conds);
	    
	    $m_log = new AdminlogModel();
	    $m_log->insert(array(
	        'admin' => Yaf_Session::getInstance()->get('admin_name'),
	        'content' => "删除了游戏《{$game['name']}》（ID：{$id}）、所属的区/服、LOGO图片及游戏截图。",
	        'ymd' => date('Ymd'),
	    ));
	    
	    return '';
	}
	
	public function cacheAction()
	{
	    //按首字母缓存
	    $start = 0;
	    $limit = 100;
	    $data = array();
	    while (1)
	    {
	        $tmp = $this->_model->fetchAllBySql("SELECT game_id,name,initial,visible,prepay FROM game 
	            WHERE initial<>'' AND game_id>{$start} ORDER BY initial ASC,weight ASC LIMIT {$limit}");
	        if( empty($tmp) ) {
	            break;
	        }
	        foreach ($tmp as $row)
	        {
	            $data[$row['initial']][] = $row;
	        }
	        if( count($tmp) < $limit ) {
	            break;
	        }
	        $start = $row['game_id'];
	    }
	    $file = APPLICATION_PATH."/application/cache/game/initial.php";
	    $content = '<?php';
	    $content .= "\r\n\r\nreturn ";
	    $content .= var_export($data, true);
	    $content .= ";\r\n";
	    file_put_contents($file, $content);
	    
	    $this->forward('admin', 'message', 'success', array('msg'=>'游戏缓存已经更新！'));
	    return false;
	}
	
	//首页今日推荐
	public function recommendAction()
	{
	    $req = $this->getRequest();
	    $file = APPLICATION_PATH.'/application/cache/game/recommend.php';
	    if( $req->isPost() ) {
	        $gids = $req->getPost('gids', array());
	        $assign['gids'] = $gids;
	        if( $gids ) {
    	        $data = '<?php';
    	        $data .= "\r\n//游戏首页-今日推荐\r\nreturn '";
    	        $data .= implode(',', $gids);
    	        $data .= "';\r\n";
    	        file_put_contents($file, $data);
	        }
	    } else if( file_exists($file) ) {
	        $assign['gids'] = include $file;
	        $assign['gids'] = explode(',', $assign['gids']);
	    } else {
	        $assign['gids'] = array();
	    }
	    
	    $assign['initial'] = include dirname($file).'/initial.php';
	    
	    $this->getView()->assign($assign);
	}
}
