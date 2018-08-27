<?php

class IndexController extends Yaf_Controller_Abstract
{
	public function indexAction()
	{
		$url='http://'.$_SERVER['SERVER_NAME'].'/admin/index/index';
        $this->redirect($url);
	}
    public function akpgameAction(){
        $game_id=$_GET['game_id']??die('游戏id必须');
        $admin_id=$_SESSION['admin_id'];
        $zip = new ZipArchive();
        $filename = "/www/wwwroot/code/h5/open/dev/public/game/apk/{$game_id}.apk";//母包位置
        //复制一份到当前
        //判断游戏目录是否存在
        $path=APPLICATION_PATH."/public/game/apk/{$game_id}";
        if(!is_dir($path)){
            mkdir($path);
        }
        shell_exec(" 
        PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin;~/bin;
        export PATH;
        cp {$filename}  /www/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/{$admin_id}.apk;
        > /dev/null 2>&1 &");
        $now_path=$path."/{$admin_id}.apk";
        if ($zip->open($now_path, ZIPARCHIVE::CREATE)!==TRUE) {
            exit("cannot open <$filename> ");
        }
        $zip->addFromString("META-INF/lefengwan_channelid", "{$admin_id}");
        $zip->addFromString("META-INF/lefengwan_gameid","{$game_id}");
        //$zip->addFile($thisdir . "/too.php","/testfromfile.php");
        echo "numfiles: " . $zip->numFiles . " ";
        echo "status:" . $zip->status . " ";
        $zip->close();
        echo "刷新重试";
    }
}
