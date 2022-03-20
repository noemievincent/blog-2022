<?php
session_start();
define('VIEWS_PATH', './views/');
define('PARTIALS_PATH', './views/partials/');
define('DATAS_PATH', './datas/');
define('POST_FILES', array_filter(scandir(DATAS_PATH . 'posts'), fn($file_name) => str_ends_with($file_name, '.json')));
define('PER_PAGE', 5);
define('POST_COUNT', count(POST_FILES) - 2);
define('MAX_PAGE', intdiv(POST_COUNT, PER_PAGE) + (POST_COUNT % PER_PAGE ? 1 : 0));

$action = $_REQUEST['action'] ?? 'index';

$callback = match ($action) {
    'create' => 'create',
    'show' => 'show',
    'store' => 'store',
    default => 'index',
};

function index(): stdClass
{
    $p = 1;
    if (isset($_GET['p'])) {
        if ((int)$_GET['p'] >= 1 && (int)$_GET['p'] <= MAX_PAGE) {
            $p = (int)$_GET['p'];
        }
    }
    $posts = get_posts($p);
    $authors = get_authors($posts);
    $categories = get_categories($posts);
    $most_recent_post = get_most_recent_post($posts);

    $view_data = new stdClass();
    $view_data->name = 'index.php';
    $view_data->data = compact('posts', 'authors', 'categories', 'most_recent_post');
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
        header('Location: index.php'); // Idéalement 404
        exit;
    }
    if (!in_array($_GET['id'] . '.json', POST_FILES)) {
        header('Location: index.php'); // Idéalement 404
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

function store(): void
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
            $post->author_name = "Myriam Dupont";
            $post->author_avatar = "https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=3174&q=80";

            file_put_contents('./datas/posts/' . $post->id . '.json', json_encode($post));

            header('Location: index.php?action=show&id=' . $post->id);
            exit;
        } else {
            $_SESSION['old'] = $_POST;
            header('Location: index.php?action=create');
            exit;
        }
    }

    header('Location: index.php');// Idéalement 404
    exit;
}

function get_posts(int $p = 0): array
{
    foreach (POST_FILES as $file_name) {
        $posts [] = json_decode(file_get_contents("./datas/posts/$file_name"));
    }

    $sort_order = 1;
    if (isset($_GET['order-by'])) {
        $sort_order = $_GET['order-by'] === 'oldest' ? -1 : 1;
    }
    usort($posts, fn($p1, $p2) => $p1->published_at > $p2->published_at ? (-1 * $sort_order) : (1 * $sort_order));

    if ($p === 0) {
        return $posts;
    } else {
        $start = ($p - 1) * PER_PAGE;
        $last = $start + PER_PAGE - 1;
        return array_filter($posts, fn($p, $i) => ($i >= $start && $i <= $last), ARRAY_FILTER_USE_BOTH);
    }
}

function get_aside_datas(array $posts): array
{
    $authors = get_authors($posts);
    $categories = get_categories($posts);
    $most_recent_posts = get_most_recent_post($posts);
    return compact('authors', 'categories', 'most_recent_posts');
}

function get_most_recent_post(array $posts): stdClass
{
    $most_recent_post = null;
    foreach ($posts as $post) {
        if (is_null($most_recent_post) || $post->published_at > $most_recent_post?->published_at) {
            $most_recent_post = $post;
        }
    }
    return $most_recent_post;
}

function get_categories(array $posts): array
{
    $categories = [];
    foreach ($posts as $post) {
        if (!in_array($post->category, array_keys($categories))) {
            $categories [$post->category] = 1;
        } else {
            $categories [$post->category] += 1;
        }
    }
    return $categories;
}

function get_authors(array $posts): array
{
    $authors = [];
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


/**/


$view = call_user_func($callback);
include VIEWS_PATH . $view->name;


