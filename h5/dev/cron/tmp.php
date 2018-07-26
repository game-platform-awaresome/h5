<?php

set_time_limit(180);
define('APPLICATION_PATH', dirname(dirname(__FILE__)));

$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");

$config = $application->getConfig();
date_default_timezone_set($config['timezone']);

include APPLICATION_PATH.'/cron/daily/sitemap.php';
$application->execute('sitemap');
