<?php
require_once 'vendor/autoload.php';
$faker = Faker\Factory::create();

if (file_exists('./datas/posts')) {
    $files = scandir('./datas/posts');
    foreach ($files as $file) {
        if (strlen($file) > 3) unlink('./datas/posts/' . $file);
    }
}
$authors = [];
for ($i = 0; $i < rand(2, 8); $i++) {
    $author_name = $faker->name();
    $author_avatar = $faker->imageUrl(128, 128, 'people', true, $author_name);
    $authors [] = ['name' => $author_name, 'avatar' => $author_avatar];
}

$categories = [];
for ($i = 0; $i < rand(2, 8); $i++) {
    $category = substr($faker->sentence(2), 0, -1);
    $categories [] = $category;
}

for ($i = 0; $i < 30; $i++) {
    $post = new stdClass();
    $post->id = uniqid();
    $post->published_at = $faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s');
    $idx = rand(0, count($authors) - 1);
    $post->author_name = $authors[$idx]['name'];
    $post->author_avatar = $authors[$idx]['avatar'];
    $post->title = $faker->sentence(10);
    $post->excerpt = $faker->sentence(40);
    $post->body = '<p>' . implode('</p><p>', $faker->paragraphs(12)) . '</p>';
    $post->category = $categories[rand(0, count($categories) - 1)];
    file_put_contents('./datas/posts/' . $post->id . '.json', json_encode($post));
}
