<?php

define('APPLICATION_PATH', realpath('../'));

$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");

$application->bootstrap()->run();
