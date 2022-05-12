<?php
session_start();

use Illuminate\Database\Capsule\Manager as Capsule;

require './configs/config.php';
require DOCUMENT_ROOT.'/vendor/autoload.php';
$capsule = new Capsule();

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'blog',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$route = require(DOCUMENT_ROOT.'/utils/router.php');

$controllerName = 'Blog\\Controllers\\'.$route['controller'];

$controller = new $controllerName();

$data = call_user_func([$controller, $route['callback']]);
extract($data);

require VIEWS_PATH.$view;

unset($_SESSION['errors'], $_SESSION['old']);