<?php
date_default_timezone_set('Asia/Hong_Kong');

define('WWW_ROOT',    dirname(__FILE__));
define('APP_ROOT',    WWW_ROOT.'/hand');
define('CONFIG_ROOT', WWW_ROOT.'/config');
define('VENDOR_ROOT', WWW_ROOT.'/vendor');

require VENDOR_ROOT.'/autoload.php';
require APP_ROOT.'/app.php';

use \Phpmig\Adapter;
use \Pimple;
use \Hand;

$app = new \Hand\App(CONFIG_ROOT.'/default.php');
$db  = $app->registerDatabase();

$container = new Pimple();
// $container['db'] = function() use ($db) {
//     return $db->getConnection()->getPdo();
// };
$container['phpmig.adapter'] = function() use ($db) {
    return new Adapter\Illuminate\Database($db, 'migrations');
};
$container['phpmig.migrations_path'] = __DIR__ . DIRECTORY_SEPARATOR . 'migrations';

return $container;
