<?php

require_once '../vendor/autoload.php';

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Blog\Models\Post;
use Blog\Models\Author;
use Blog\Models\Comment;
use Blog\Models\Category;
use Cocur\Slugify\Slugify;
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


$faker = Faker\Factory::create();
define('AUTHORS_COUNT', rand(2, 8));
define('CATEGORIES_COUNT', rand(2, 8));
define('POSTS_COUNT', rand(30, 50));
$slugify = new Slugify();


//////////////////////////////
/// AUTHORS
//////////////////////////////

echo '// Creating authors <br>';

for ($i = 0; $i < AUTHORS_COUNT; $i++) {
    $author = new Author();
    $author->id = Uuid::uuid4();
    $author->name = $i > 0 ? strtolower($faker->name()) : 'Dominique Vilain';
    $author->slug = $slugify->slugify($author->name);
    $author->avatar = $faker->imageUrl(128, 128, 'people', true, $author->name);
    $author->email = $i > 0 ? $faker->unique()->safeEmail : 'dominique.vilain@hepl.be';
    $author->password = password_hash('change_this', PASSWORD_DEFAULT);
    $author->save();
}
$authors = Author::all();

//////////////////////////////
/// CATEGORIES
//////////////////////////////

echo '// Creating categories <br>';


for ($i = 0; $i < CATEGORIES_COUNT; $i++) {
    $category = new Category();
    $category->id = Uuid::uuid4();
    $category->name = strtolower(substr($faker->sentence(2), 0, -1));
    $category->slug = $slugify->slugify($category->name);
    $category->save();
}
$categories = Category::all();
//////////////////////////////
/// POSTS
//////////////////////////////

echo '// Creating posts <br>';
for ($i = 0; $i < POSTS_COUNT; $i++) {
    $post = new Post();
    $post->id = Uuid::uuid4();
    $creation_date = Carbon::create($faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'));
    $post->created_at = $creation_date;
    $post->published_at = $creation_date->addDays(rand(0, 1) * rand(2, 20));
    $post->updated_at = rand(0, 10) ? $post->created_at : $post->created_at->addWeeks(rand(2, 8));
    $post->deleted_at = rand(0, 10) ? null : Carbon::now();
    $post->author_id = $authors[rand(0, AUTHORS_COUNT - 1)]->id;
    $post->title = $faker->sentence(10);
    $post->slug = $slugify->slugify($post->title);
    $post->excerpt = $faker->sentence(40);
    $post->thumbnail = $faker->imageUrl(640, 480, 'landscape', true);
    $post->body = '<p>'.implode('</p><p>', $faker->paragraphs(12)).'</p>';

    $post->save();
}
$posts = Post::all();
//////////////////////////////
/// COMMENTS
//////////////////////////////

$mod = rand(2, 3);
for ($i = 0; $i < POSTS_COUNT; $i++) {
    if ($i % $mod) {
        $comments_count = rand(1, 7);
        $post_id = $posts[$i]->id;
        for ($j = 0; $j < $comments_count; $j++) {
            $comment = new Comment();
            $comment->id = Uuid::uuid4();
            $comment->author_id = $authors[rand(0, AUTHORS_COUNT - 1)]->id;
            $comment->post_id = $post_id;
            $comment->body = $faker->text;
            $comment->save();
        }
    }
}

//////////////////////////////
/// CATEGORY_POST
//////////////////////////////

echo '// Creating relationships between categories and posts <br>';

for ($i = 0; $i < POSTS_COUNT; $i++) {
    $post_id = $posts[$i]->id;
    for ($j = rand(0, intdiv(CATEGORIES_COUNT, 2)); $j < CATEGORIES_COUNT; $j += rand(1, CATEGORIES_COUNT)) {
        $category_id = $categories[$j]->id;
        Post::find($post_id)->categories()->attach($category_id);
    }
}

echo '<a href="../index.php">Back to website!</a>';