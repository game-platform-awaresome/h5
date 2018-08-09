<?php

class AdminController extends F_Controller_Backend
{

    protected function beforeList()
    {
        $params['op'] = F_Helper_Html::Op_Edit;
        $conds = '';
        $search = $this->getRequest()->getQuery('search', array());
        $s = Yaf_Session::getInstance();
        $channel_ids_condition=$s->get('channel_ids_condition');
        if( $search ) {
            $cmm = '';
            foreach ($search as $k=>$v)
            {
                if( empty($v) ) {
                    continue;
                }
                if( $k == 'username' ) {
                    $m_user = new UsersModel();
                    $user = $m_user->fetch("username='{$v}'", 'user_id');
                    if( $user ) {
                        $k = 'user_id';
                        $v = $user['user_id'];
                    } else {
                        continue;
                    }
                }
                $conds .= "{$cmm}{$k}='{$v}'";
                $cmm = ' AND ';
            }
            $conds.="  AND parent_id in {$channel_ids_condition}";
        }else{
            $conds.="parent_id in {$channel_ids_condition}";
        }
        $params['conditions']=$conds;
        return $params;
    }
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
//        $m_group = new AdmingroupModel();
//        $groups = $m_group->getAllGroups(0);
//        $html = '<select id="info_group_id" name="info[group_id]">';
//        $html .= '<option value="0">-请选择权限组-</option>';
//        $html .= $this->groupHtml($groups, 0);
//        $html .= '</select>';
//        $html .= "<script>\$('#info_group_id').val('{$info['group_id']}');</script>";
//        $this->getView()->assign('groups', $html);
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
	        $info['parent_id']=$login['admin_id'];
	        $info['add_ip'] = $_SERVER['REMOTE_ADDR'];
	        if($login['admin_id']==0){
	            $info['cps_type']=1;
            }elseif($info['parent_id']==1){
                $info['cps_type']=2;
            }else{
                $info['cps_type']=3;
            }
	    }
	    return '';
	}

    /**
     * 生成apk
     */
	public function apkAction(){
        $admin_id=$_SESSION['admin_id'];
        //1.修改文件
        $file_dir="/www/wwwroot/tool/apk/assets/apps/default/www/manifest.json";
        $json_string = file_get_contents($file_dir);
        $data = json_decode($json_string,true);
        var_dump($data);
        die;// 把JSON字符串转成PHP数组
//        $data[$code]=array("a"=>"as","b"=>"bs","c"=>"cs");
//        $json_strings = json_encode($data);
//        file_put_contents("text.json",$json_strings);//写入
//        $json_string = file_get_contents("text.json");// 从文件中读取数据到PHP变量
//        $data = json_decode($json_string,true);// 把JSON字符串转成PHP数组
//        $data["001"]["a"]="aas";
//        $json_strings = json_encode($data);
//        file_put_contents("text.json",$json_strings);//写入
        //2.压缩apk
        //3.返回链接

    }
}
