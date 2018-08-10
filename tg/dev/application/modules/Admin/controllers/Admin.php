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
            $conds.="  AND parent_id in {$channel_ids_condition} or admin_id = {$_SESSION['admin_id']}";
        }else{
            $conds.="parent_id in {$channel_ids_condition} or admin_id = {$_SESSION['admin_id']}";
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
        $file_dir="/www/wwwroot/tool/1/assets/apps/default/www/manifest.json";
        $json_string = file_get_contents($file_dir);
        $data = json_decode($json_string,true);
        $launch_path="http://h5.zyttx.com?user=".$admin_id;
        $developer_url="http://h5.zyttx.com?user=".$admin_id;
        $boxname=$_SESSION['boxname'];
         // 把JSON字符串转成PHP数组
        $data['name']=$boxname;
        $data['launch_path']=$launch_path;
        $data['developer']['url']=$developer_url;
        $json_strings = json_encode($data);
        file_put_contents($file_dir,$json_strings);//写入

        //修改APP名字
        $file_dir2="/www/wwwroot/tool/1/res/values/strings.xml";
        $doc = new DOMDocument();
        $doc->load($file_dir2);
        $resources = $doc -> getElementsByTagName("resources");
        //遍历
        foreach ($resources as $resource) {
            //将id=3的title设置为33333
            if($resource->getAttribute('name')=='app_name'){
                $resource->nodeValue($boxname);
            }
        }
        //对文件做修改后，一定要记得重新sava一下，才能修改掉原文件
        $doc -> save($file_dir2);
        //2. 编译app
        shell_exec("
        PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin:/home/java/jdk1.8.0_181:/home/java/jdk1.8.0_181/lib/:/home/java/jdk1.8.0_181/bin;export PATH;
        export JAVA_HOME CLASSPATH PATH;
        cd /www/wwwroot/tool;
        apktool b 1;
        /cp /www/wwwroot/tool/1/dist/1.apk 1.apk;
        cd /www/wwwroot/tool;
        java -jar signapk.jar  testkey.x509.pem testkey.pk8  1.apk {$admin_id}.apk; 
        mv -f {$admin_id}.apk /www/wwwroot/xgame.zyttx.com/apk/;
        rm -rf 1.apk;  > /dev/null 2>&1 &");
        sleep(2);
        //3.返回链接
        echo '正在打包,请稍等1-2分钟刷新页面！';
        die;
//        $this->redirect('index/right');
    }
}
