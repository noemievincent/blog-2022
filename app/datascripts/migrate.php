<?php
require_once '../vendor/autoload.php';

use Illuminate\Database\Schema\Blueprint;
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


Capsule::schema()->dropIfExists('categories');
Capsule::schema()->create('categories', function ($table) {
    $table->string('id')->primary();
    $table->string('name')->nullable();
    $table->string('slug')->nullable()->unique();
    $table->timestamps();
    $table->softDeletes();
});

Capsule::schema()->dropIfExists('authors');
Capsule::schema()->create('authors', function ($table) {
    $table->string('id')->primary();
    $table->string('name')->nullable();
    $table->string('slug')->nullable()->unique();
    $table->tinyText('avatar')->nullable();
    $table->string('email')->unique();
    $table->string('password');
    $table->timestamps();
    $table->softDeletes();
});

Capsule::schema()->dropIfExists('posts');
Capsule::schema()->create('posts', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->string('title')->nullable();
    $table->string('slug')->nullable()->unique();
    $table->text('body')->nullable();
    $table->mediumText('excerpt')->nullable();
    $table->tinytext('thumbnail')->nullable();
    $table->string('author_id');
    $table->timestamp('published_at');
    $table->timestamps();
    $table->softDeletes();
    $table->foreign('author_id')->references('id')->on('authors');
});

Capsule::schema()->dropIfExists('category_post');
Capsule::schema()->create('category_post', function (Blueprint $table) {
    $table->string('category_id');
    $table->string('post_id');
    $table->primary(['post_id', 'category_id']);
    $table->timestamps();
    $table->softDeletes();
    $table->foreign('category_id')->references('id')->on('categories');
    $table->foreign('post_id')->references('id')->on('posts');
});

Capsule::schema()->dropIfExists('comments');
Capsule::schema()->create('comments', function (Blueprint $table) {
    $table->string('id');
    $table->text('body');
    $table->string('author_id');
    $table->string('post_id');
    $table->timestamps();
    $table->softDeletes();
    $table->foreign('author_id')->references('id')->on('authors');
    $table->foreign('post_id')->references('id')->on('posts');
});


/*
$pdo->exec(<<< SQL
    create table comments
    (
        id         varchar(255) not null primary key,
        body       text         null,
        author_id  varchar(255) not null,
        post_id    varchar(255) not null,
        created_at timestamp    default CURRENT_TIMESTAMP,
        deleted_at timestamp    null,
        updated_at timestamp    default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        constraint comment_author_id_fk
            foreign key (author_id) references authors (id)
                on update cascade on delete cascade, 
        constraint comment_post_id_fk
            foreign key (post_id) references posts (id)
                on update cascade on delete cascade 
    );

    SQL
);
echo '// Finished creating DB <br>';
echo '<a href="seed.php">Seed it now!</a>';
*/