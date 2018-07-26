<?php

define('APPLICATION_PATH', dirname(dirname(__FILE__)));

$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");

$config = $application->getConfig();
date_default_timezone_set($config['timezone']);

$application->execute('monthly');

function monthly()
{
    $dir = APPLICATION_PATH.'/cron/monthly';
    $log = APPLICATION_PATH.'/logs/crond-monthly-'.date('Y.m.d').'.log';
    
    $files = scandir($dir);
    unset($files[0], $files[1]);
    $log_arr = array();
    foreach ($files as $file)
    {
        $func = str_replace('.php', '', $file);
        include "{$dir}/{$file}";
        $err = call_user_func($func);
        if( $err ) {
            $log_arr[] = $err;
        }
        sleep(1);
    }
    
    if( $log_arr ) {
        $fp = fopen($log, 'ab');
        $date = date('Y-m-d H:i:s');
        $log_str = implode("\r\n", $log_arr);
        fwrite($fp, "{$date}: {$log_str}\r\n");
        fwrite($fp, "\r\n");
        fclose($fp);
    }
}
