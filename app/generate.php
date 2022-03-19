<?php

//$posts = json_decode(file_get_contents('./datas/post-1.json'), true);
$posts = scandir('./datas/posts');
foreach ($posts as $post) {
    if (strlen($post > 4)) {
        $content = json_decode(file_get_contents('./datas/posts/' . $post));
        $content->id = substr($post, 0, -5);
        file_put_contents('./datas/posts/' . $post, json_encode($content));

    }
}
