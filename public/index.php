<?php
date_default_timezone_set("Asia/Hong_Kong");

define('WWW_ROOT',    dirname(dirname(__FILE__)));
define('APP_ROOT',    WWW_ROOT.'/hand');
define('CACHE_ROOT',  WWW_ROOT.'/cache');
define('CONFIG_ROOT', WWW_ROOT.'/config');
define('DATA_ROOT',   WWW_ROOT.'/data');
define('PUBLIC_ROOT', WWW_ROOT.'/public');
define('VENDOR_ROOT', WWW_ROOT.'/vendor');
define('STATIC_ROOT', PUBLIC_ROOT.'/static');

require VENDOR_ROOT.'/autoload.php';
require APP_ROOT.'/app.php';

$app = new \Hand\App(CONFIG_ROOT.'/default.php');
$app->registerAutoload();
$app->registerDatabase();
$app->registerSlim();
$app->registerSlimMiddleware();
$app->registerSlimView();
$app->registerSlimRoutes();
$app->registerSlimConfig();
$app->registerSlimHook();
$app->run();
