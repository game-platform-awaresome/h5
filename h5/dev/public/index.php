<?php
ini_set('session.cookie_domain', 'h5.zyttx.com');
define('APPLICATION_PATH', realpath('../'));

$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");

$application->bootstrap()->run();
