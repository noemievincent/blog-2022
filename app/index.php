<?php

use Models\Post;
use Models\Author;
use Carbon\Carbon;
use Models\Category;
use Ramsey\Uuid\Uuid;
use JetBrains\PhpStorm\NoReturn;
use Cocur\Slugify\Slugify;

require 'configs/config.php';

session_start();

require DOCUMENT_ROOT.'/vendor/autoload.php';

$action = $_REQUEST['action'] ?? 'index';
$callback = match ($action) {
    'create' => 'create',
    'show' => 'show',
    'store' => 'store',
    default => 'index',
};


// Controllers
function index(): stdClass
{
    $post_model = new Post();
    $author_model = new Author();
    $category_model = new Category();
    // Order setting from request
    $sort_order = isset($_GET['order-by']) && $_GET['order-by'] === 'oldest' ? 'ASC' : DEFAULT_SORT_ORDER;

    // Filter setting from request
    $filter = [];
    if (isset($_GET['category'])) {
        $filter['type'] = 'category';
        $filter['value'] = $_GET['category'];
        define('POSTS_COUNT', $post_model->count_by_category($_GET['category']));
    } elseif (isset($_GET['author'])) {
        $filter['type'] = 'author';
        $filter['value'] = $_GET['author'];
        define('POSTS_COUNT', $post_model->count_by_author($_GET['author']));
    } else {
        define('POSTS_COUNT', $post_model->count());
    }

    // Pagination setting from request
    define('MAX_PAGE', intdiv(POSTS_COUNT, PER_PAGE) + (POSTS_COUNT % PER_PAGE ? 1 : 0));

    $p = START_PAGE;
    if (isset($_GET['p'])) {
        if ((int) $_GET['p'] >= START_PAGE && (int) $_GET['p'] <= MAX_PAGE) {
            $p = (int) $_GET['p'];
        }
    }

    // Main data for request
    $posts = $post_model->get($filter, $sort_order, $p);

    // Aside data
    $authors = $author_model->get();
    $categories = $category_model->get();
    $most_recent_post = $post_model->latest();

    // Rendering
    $view_data = new stdClass();
    $view_data->name = 'index.php';
    $view_data->data = compact('posts', 'authors', 'categories', 'most_recent_post', 'p');
    return $view_data;
}

function create(): stdClass
{
    $category_model = new Category();
    $author_model = new Author();
    $post_model = new Post();

    $authors = $author_model->get();
    $categories = $category_model->get();
    $most_recent_post = $post_model->latest();

    $view_data = new stdClass();
    $view_data->name = 'add-post.php';
    $view_data->data = compact('authors', 'categories', 'most_recent_post');
    return $view_data;
}

function show(): stdClass
{
    $post_model = new Post();
    $author_model = new author();

    if (!isset($_GET['slug'])) {
        header('location: 404.php'); // Idéalement 404
        exit;
    }
    $post = $post_model->find_by_slug($_GET['slug']);

    if (!$post) {
        header('Location: 404.php'); // Idéalement 404
        exit;
    }
    $post_model->add_categories($post);

    $authors = $author_model->get();
    $categories = $author_model->get();
    $most_recent_post = $post_model->latest();

    $view_data = new stdClass();
    $view_data->name = 'single.php';
    $view_data->data = compact('post', 'authors', 'categories', 'most_recent_post');

    return $view_data;
}

#[NoReturn] function store(): void
{
    $post_model = new Post();
    $author_model = new Author();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!has_validation_errors()) {
            $slugify = new Slugify();
            $post = new stdClass();
            $post->id = Uuid::uuid4();
            $post->title = $_POST['post-title'];
            $post->slug = $slugify->slugify($post->title);
            $post->body = $_POST['post-body'];
            $post->excerpt = $_POST['post-excerpt'];
            $post->category_id = $_POST['post-category'];
            $post->thumbnail = '';
            $post->published_at = Carbon::now();
            $authors = $author_model->get();
            $count_authors = count($authors);
            $author = $authors[rand(0, $count_authors - 1)];
            $post->author_id = $author->id;
            $post->author_avatar = $author->avatar;

            $result = $post_model->save($post);
            if ($result === true) {
                header('Location: index.php?action=show&slug='.$post->slug);
            } else {
                die($result);
            };
        } else {
            $_SESSION['old'] = $_POST;
            header('Location: index.php?action=create');
        }
        exit;
    }

    header('Location: index.php');// Idéalement 404
    exit;
}

// Validator
function has_validation_errors(): bool
{
    $category_model = new Category();

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
    if (!$category_model->category_exists($_POST['post-category'])) {
        $_SESSION['errors']['category'] = 'La catégorie doit faire partie des catégories existantes';
    }
    return (bool) count($_SESSION['errors']);
}


$view = call_user_func($callback);
include VIEWS_PATH.$view->name;