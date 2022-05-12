<?php
require_once '../vendor/autoload.php';
use Blog\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
$capsule = new Capsule;

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

Capsule::schema()->dropIfExists('users');
Capsule::schema()->create('users', function ($table) {
    $table->increments('id');
    $table->string('email')->unique();
    $table->timestamps();
});
User::create(['email'=>'titi@toto.com']);
echo User::first();