<?php

class IndexController extends Yaf_Controller_Abstract
{
    public function indexAction()
    {
        $url = 'http://' . $_SERVER['SERVER_NAME'] . '/admin/index/index';
        $this->redirect($url);
    }

    public function akpgameAction()
    {
        $game_id = $_GET['game_id'] ?? die('游戏id必须');
        $channe_id = $_GET['tg_channel'] ?? 1;
        $admin_id = $channe_id;
        $zip = new ZipArchive();
        $filename = "/www2/wwwroot/code/h5/open/dev/public/game/apk/{$game_id}.apk";//母包位置
        //复制一份到当前
        //判断游戏目录是否存在
        $path = APPLICATION_PATH . "/public/game/apk/{$game_id}";
        if (!is_dir($path)) {
            mkdir($path);
        }
        shell_exec(" 
        PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin;~/bin;
        export PATH;
        cp {$filename}  /www2/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/{$admin_id}.apk;
        > /dev/null 2>&1 &");
        $now_path = $path . "/{$admin_id}.apk";
        if ($zip->open($now_path, ZIPARCHIVE::CREATE) !== TRUE) {
            exit("cannot open <$filename> ");
        }
        $zip->addFromString("META-INF/jiule_channelid", "{$admin_id}");
        $zip->addFromString("META-INF/jiule_gameid", "{$game_id}");
        //$zip->addFile($thisdir . "/too.php","/testfromfile.php");
//        echo "numfiles: " . $zip->numFiles . " ";
//        echo "status:" . $zip->status . " ";
        $zip->close();
        $this->downFile($admin_id . '.apk', "/www2/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/");
        Yaf_Dispatcher::getInstance()->disableView();
    }

    /**
     * 盒子下载
     */
    public function akpgame2Action()
    {
        $admin_id = $_REQUEST['tg_channel'];
        $admin = new AdminModel();
        //1.修改文件
        $file_dir = "/www2/wwwroot/tool/1/assets/apps/default/www/manifest.json";
        $json_string = file_get_contents($file_dir);
        $data = json_decode($json_string, true);
        $launch_path = "http://{$admin_id}.h5.zyttx.com";
        $developer_url = "http://{$admin_id}.h5.zyttx.com";
        $boxname = $admin->fetch(['admin_id' => $admin_id], 'boxname');
        if ($boxname == '') {
            $boxname = '游戏盒子';
        } else {
            $boxname = $boxname['boxname'];
        }
        // 把JSON字符串转成PHP数组
        $data['name'] = $boxname;
        $data['launch_path'] = $launch_path;
        $data['developer']['url'] = $developer_url;
        $json_strings = json_encode($data);
        file_put_contents($file_dir, $json_strings);//写入
        //修改APP名字
        $file_dir2 = "/www2/wwwroot/tool/1/res/values/strings.xml";
        $doc = new DOMDocument();
        $doc->load($file_dir2);
        $strings = $doc->getElementsByTagName("string");
        //遍历
        foreach ($strings as $string) {
            //将id=3的title设置为33333
            if ($string->getAttribute('name') == 'app_name') {
                $string->nodeValue = $boxname;
            }
        }
        //对文件做修改后，一定要记得重新sava一下，才能修改掉原文件
        $doc->save($file_dir2);
        //2. 编译app
        shell_exec("
        PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin:/home/java/jdk1.8.0_181:/home/java/jdk1.8.0_181/lib/:/home/java/jdk1.8.0_181/bin;export PATH;
        export JAVA_HOME CLASSPATH PATH;
        cd /www2/wwwroot/tool;
        apktool b 1;
        cp /www2/wwwroot/tool/1/dist/1.apk  /www2/wwwroot/tool/;
        cd /www2/wwwroot/tool;
        java -jar signapk.jar  testkey.x509.pem testkey.pk8  1.apk {$admin_id}.apk; 
        mv -f /www2/wwwroot/tool/{$admin_id}.apk  /www2/wwwroot/xgame.zyttx.com/apk/;
        rm -rf /www2/wwwroot/tool/1.apk;
         > /dev/null 2>&1 &");
        sleep(5);
        $this->redirect("http://xgame.zyttx.com/apk/new{$admin_id}.apk");
        Yaf_Dispatcher::getInstance()->disableView();
    }

    /**
     * 新版盒子下载
     */
    public function akpgame3Action()
    {
        $admin_id = $_REQUEST['tg_channel'] ?? 1;
        if (file_exists("/www2/wwwroot/xgame.zyttx.com/apk/new{$admin_id}.apk")) {
            $this->redirect("http://xgame.zyttx.com/apk/new{$admin_id}.apk");
        } else {
            //1.修改文件
            $file_dir = "/www2/wwwroot/tool/base/assets/widget/jiule_channelid";
            file_put_contents($file_dir, $admin_id);//写入
            //2. 编译app
            shell_exec("
        PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin:/home/java/jdk1.8.0_181:/home/java/jdk1.8.0_181/lib/:/home/java/jdk1.8.0_181/bin;export PATH;
        export JAVA_HOME CLASSPATH PATH;
        cd /www2/wwwroot/tool;
        apktool b base;
        cp /www2/wwwroot/tool/base/dist/base.apk  /www2/wwwroot/tool/;
        cd /www2/wwwroot/tool;
        java -jar signapk.jar  testkey.x509.pem testkey.pk8  base.apk new{$admin_id}.apk; 
        mv -f /www2/wwwroot/tool/new{$admin_id}.apk  /www2/wwwroot/xgame.zyttx.com/apk/;
        rm -rf /www2/wwwroot/tool/base.apk;
        rm -rf /www2/wwwroot/tool/base/dist/base.apk;
         > /dev/null 2>&1 &");
            sleep(5);
        $this->redirect("http://xgame.zyttx.com/apk/new{$admin_id}.apk");
        }
        Yaf_Dispatcher::getInstance()->disableView();
    }

    private function downFile($file_name, $file_dir)
    {
        //检查文件是否存在
        if (!file_exists($file_dir . $file_name)) {
            header('HTTP/1.1 404 NOT FOUND');
        } else {
            //以只读和二进制模式打开文件
            $file = fopen($file_dir . $file_name, "rb");
            //告诉浏览器这是一个文件流格式的文件
            Header("Content-type: application/octet-stream");
            //请求范围的度量单位
            Header("Accept-Ranges: bytes");
            //Content-Length是指定包含于请求或响应中数据的字节长度
            Header("Accept-Length: " . filesize($file_dir . $file_name));
            //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值。
            Header("Content-Disposition: attachment; filename=" . $file_name);
            //读取文件内容并直接输出到浏览器
            echo fread($file, filesize($file_dir . $file_name));
            fclose($file);
            exit ();
        }
    }
}
