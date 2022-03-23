<?php
try {
    $pdo = new PDO('mysql:host=database;port=3306;dbname=blog', 'mysql', 'mysql', [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}

echo '// Starting creation of DB <br>';
$pdo->exec(<<< SQL
    DROP SCHEMA IF EXISTS blog;
    CREATE SCHEMA blog;
    USE blog;
    create table authors
    (
        id         varchar(255) not null primary key,
        name       varchar(255) null,
        slug       varchar(255) null,
        avatar     tinytext null,
        created_at timestamp    null,
        deleted_at timestamp    null,
        updated_at timestamp    null
    );
    create table categories
    (
        id         varchar(255) not null primary key,
        name       varchar(255) null,
        slug       varchar(255) null,
        created_at timestamp    null,
        deleted_at timestamp    null,
        updated_at timestamp    null
    );
    create table posts
    (
        id           varchar(255) not null primary key,
        title        varchar(255) null,
        slug         varchar(255) null,
        body         text         null,
        published_at timestamp    null,
        excerpt      text         null,
        thumbnail    varchar(255) null,
        author_id    varchar(255) null,
        created_at   timestamp    null,
        deleted_at   timestamp    null default NULL,
        updated_at   timestamp    null
    );
    create table category_post
    (
        category_id varchar(255) not null,
        post_id     varchar(255) not null,
        primary key (category_id, post_id),
        constraint category_post_categories_id_fk
            foreign key (category_id) references categories (id)
                on update cascade on delete cascade,
        constraint category_post_posts_id_fk
            foreign key (post_id) references posts (id)
                on update cascade on delete cascade
    );
    SQL
);
echo '// Finished creating DB <br>';
echo '<a href="seed.php">Seed it now!</a>';
