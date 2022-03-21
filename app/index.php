<?php

use JetBrains\PhpStorm\NoReturn;

session_start();
const DEFAULT_SORT_ORDER = 1;
const VIEWS_PATH = __DIR__ . '/views/';
const PARTIALS_PATH = __DIR__ . '/views/partials/';
const DATAS_PATH = './datas/';
define('POST_FILES',
    array_filter(
        scandir(DATAS_PATH . 'posts'),
        fn($file_name) => str_ends_with($file_name, '.json')
    )
);
const PER_PAGE = 4;
const START_PAGE = 1;

$action = $_REQUEST['action'] ?? 'index';

$callback = match ($action) {
    'create' => 'create',
    'show' => 'show',
    'store' => 'store',
    default => 'index',
};

function index(): stdClass
{

    $sort_order = DEFAULT_SORT_ORDER;
    if (isset($_GET['order-by'])) {
        $sort_order = $_GET['order-by'] === 'oldest' ? -1 : 1;
    }

    $filter = [];
    if (isset($_GET['category'])) {
        $filter['type'] = 'category';
        $filter['value'] = $_GET['category'];
    } elseif (isset($_GET['author'])) {
        $filter['type'] = 'author_name';
        $filter['value'] = $_GET['author'];
    }

    $posts = get_posts($filter, $sort_order);
    $posts_count = count($posts);
    define('MAX_PAGE', intdiv($posts_count, PER_PAGE) + ($posts_count % PER_PAGE ? 1 : 0));

    $p = START_PAGE;
    if (isset($_GET['p'])) {
        if ((int)$_GET['p'] >= START_PAGE && (int)$_GET['p'] <= MAX_PAGE) {
            $p = (int)$_GET['p'];
        }
    }


    $posts = get_paginated_posts($posts, $p);
    $authors = get_authors();
    $categories = get_categories();
    $most_recent_post = get_most_recent_post();

    $view_data = new stdClass();
    $view_data->name = 'index.php';
    $view_data->data = compact('posts', 'authors', 'categories', 'most_recent_post', 'p');
    return $view_data;
}

function create(): stdClass
{
    $posts = get_posts();
    $authors = get_authors($posts);
    $categories = get_categories($posts);
    $most_recent_post = get_most_recent_post($posts);
    $view_data = new stdClass();
    $view_data->name = 'add-post.php';
    $view_data->data = compact('authors', 'categories', 'most_recent_post');
    return $view_data;
}

function show(): stdClass
{
    if (!isset($_GET['id'])) {
        header('404: index.php'); // Idéalement 404
        exit;
    }
    if (!in_array($_GET['id'] . '.json', POST_FILES)) {
        header('Location: 404.php'); // Idéalement 404
        exit;
    }
    $post = json_decode(file_get_contents(DATAS_PATH . 'posts/' . $_GET['id'] . '.json'));
    $posts = get_posts();
    $authors = get_authors($posts);
    $categories = get_categories($posts);
    $most_recent_post = get_most_recent_post($posts);
    $view_data = new stdClass();
    $view_data->name = 'single.php';
    $view_data->data = compact('post', 'authors', 'categories', 'most_recent_post');
    return $view_data;
}

#[NoReturn] function store(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!has_validation_errors()) {
            $post = new stdClass();
            $post->id = uniqid();
            $post->title = $_POST['post-title'];
            $post->body = $_POST['post-body'];
            $post->excerpt = $_POST['post-excerpt'];
            $post->category = $_POST['post-category'];
            $post->published_at = (new DateTime())->format('Y-m-d H:i:s');
            $post->author_name = "myriam dupont";
            $post->author_avatar = "https://via.placeholder.com/128x128.png/004466?text=people+myriam";

            file_put_contents('./datas/posts/' . $post->id . '.json', json_encode($post));

            header('Location: index.php?action=show&id=' . $post->id);
        } else {
            $_SESSION['old'] = $_POST;
            header('Location: index.php?action=create');
        }
        exit;
    }

    header('Location: index.php');// Idéalement 404
    exit;
}

function get_posts(array $filter = [], string $order = DEFAULT_SORT_ORDER): array
{
    $posts = get_all_posts();

    if ($filter !== []) {
        $posts = array_filter($posts, fn($p) => $p->{$filter['type']} === $filter['value']);
    }

    usort($posts, fn($p1, $p2) => $p1->published_at > $p2->published_at ? (-1 * $order) : (1 * $order));

    return $posts;
}

function get_most_recent_post(): stdClass
{
    $posts = get_all_posts();

    $most_recent_post = null;
    foreach ($posts as $post) {
        if (is_null($most_recent_post) || $post->published_at > $most_recent_post?->published_at) {
            $most_recent_post = $post;
        }
    }
    return $most_recent_post;
}

function get_all_posts(): array
{
    $posts = [];

    foreach (POST_FILES as $file_name) {
        $posts [] = json_decode(file_get_contents("./datas/posts/$file_name"));
    }

    return $posts;
}

function get_categories(): array
{
    $categories = [];
    $posts = get_all_posts();

    foreach ($posts as $post) {
        if (!in_array($post->category, array_keys($categories))) {
            $categories [$post->category] = 1;
        } else {
            $categories [$post->category] += 1;
        }
    }
    return $categories;
}

function get_authors(): array
{
    $authors = [];
    $posts = get_all_posts();

    foreach ($posts as $post) {
        if (!in_array($post->author_name, array_keys($authors))) {
            $author = new stdClass();
            $author->name = $post->author_name;
            $author->avatar = $post->author_avatar;
            $author->posts_count = 1;
            $authors [$post->author_name] = $author;
        } else {
            $authors [$post->author_name]->posts_count += 1;
        }
    }
    return $authors;
}

function has_validation_errors(): bool
{
    $_SESSION['errors'] = [];
    $_SESSION['old'] = [];

    if (mb_strlen($_POST['post-title']) < 5 || mb_strlen($_POST['post-title']) > 100) {
        $_SESSION['errors']['post-title'] = 'Le titre doit être avoir une taille comprise entre 5 et 100 caractères';
    }
    if (mb_strlen($_POST['post-excerpt']) < 20 || mb_strlen($_POST['post-excerpt']) > 200) {
        $_SESSION['errors']['post-excerpt'] = 'Le résumé doit être avoir une taille comprise entre 20 et 200 caractères';
    }
    if (mb_strlen($_POST['post-body']) < 100 || mb_strlen($_POST['post-body']) > 1000) {
        $_SESSION['errors']['post-body'] = 'Le texte doit être avoir une taille comprise entre 100 et 1000 caractères';
    }
    $categories = get_categories(get_posts());
    if (!in_array($_POST['post-category'], array_keys($categories))) {
        $_SESSION['errors']['category'] = 'La catégorie doit faire partie des catégories existantes';
    }
    return (bool)count($_SESSION['errors']);
}

function get_paginated_posts(array $posts, int $p): array
{
    $start = ($p - 1) * PER_PAGE;
    $last = $start + PER_PAGE - 1;

    return array_filter($posts, fn($p, $i) => ($i >= $start && $i <= $last), ARRAY_FILTER_USE_BOTH);

}

/**/


$view = call_user_func($callback);
include VIEWS_PATH . $view->name;


