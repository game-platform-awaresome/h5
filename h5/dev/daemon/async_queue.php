<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__).'/../'));

$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");

$config = $application->getConfig();
date_default_timezone_set($config['timezone']);

$application->execute('async_queue');

function async_queue()
{
    $dir = APPLICATION_PATH.'/cron/daily';
    $log = APPLICATION_PATH.'/logs/crond-daily-'.date('Y.m.d').'.log';
    $fp = fopen($log, 'ab');
    
    $files = scandir($dir);
    unset($files[0], $files[1]);
    foreach ($files as $file)
    {
        $func = str_replace('.php', '', $file);
        include "{$dir}/{$file}";
        $logstr = call_user_func($func);
        $date = date('Y-m-d H:i:s');
        fwrite($fp, "{$date}: $logstr\r\n");
    }
    fwrite($fp, "\r\n");
    fclose($fp);
}
