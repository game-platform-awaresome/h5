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
        $channe_id=$_GET['tg_channel']??1;
        $admin_id=$channe_id;
        $zip = new ZipArchive();
        $filename = "/www2/wwwroot/code/h5/open/dev/public/game/apk/{$game_id}.apk";//母包位置
        //复制一份到当前
        //判断游戏目录是否存在
        $path=APPLICATION_PATH."/public/game/apk/{$game_id}";
        if(!is_dir($path)){
            mkdir($path);
        }
        shell_exec(" 
        PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin;~/bin;
        export PATH;
        cp {$filename}  /www2/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/{$admin_id}.apk;
        > /dev/null 2>&1 &");
        $now_path=$path."/{$admin_id}.apk";
        if ($zip->open($now_path, ZIPARCHIVE::CREATE)!==TRUE) {
            exit("cannot open <$filename> ");
        }
        $zip->addFromString("META-INF/jiule_channelid", "{$admin_id}");
        $zip->addFromString("META-INF/jiule_gameid","{$game_id}");
        //$zip->addFile($thisdir . "/too.php","/testfromfile.php");
//        echo "numfiles: " . $zip->numFiles . " ";
//        echo "status:" . $zip->status . " ";
        $zip->close();
        $this->downFile($admin_id.'.apk',"/www2/wwwroot/code/h5/tg/dev/public/game/apk/{$game_id}/");
        Yaf_Dispatcher::getInstance()->disableView();
    }
    private function downFile($file_name,$file_dir){
        //检查文件是否存在
        if (! file_exists ( $file_dir . $file_name )) {
            header('HTTP/1.1 404 NOT FOUND');
        } else {
            //以只读和二进制模式打开文件
            $file = fopen ( $file_dir . $file_name, "rb" );
            //告诉浏览器这是一个文件流格式的文件
            Header ( "Content-type: application/octet-stream" );
            //请求范围的度量单位
            Header ( "Accept-Ranges: bytes" );
            //Content-Length是指定包含于请求或响应中数据的字节长度
            Header ( "Accept-Length: " . filesize ( $file_dir . $file_name ) );
            //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值。
            Header ( "Content-Disposition: attachment; filename=" . $file_name );
            //读取文件内容并直接输出到浏览器
            echo fread ( $file, filesize ( $file_dir . $file_name ) );
            fclose ( $file );
            exit ();
        }
    }
}
