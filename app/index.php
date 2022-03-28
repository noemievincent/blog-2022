<?php

use JetBrains\PhpStorm\NoReturn;

session_start();

define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
require DOCUMENT_ROOT.'/vendor/autoload.php';
const DEFAULT_SORT_ORDER = 'DESC';
const VIEWS_PATH = DOCUMENT_ROOT.'/views/';
const PARTIALS_PATH = DOCUMENT_ROOT.'/views/partials/';
const DSN = 'mysql:host=database;dbname=blog;port=3306';
const MYSQL_USER = 'mysql';
const MYSQL_PWD = 'mysql';
const PER_PAGE = 4;
const START_PAGE = 1;
const PDO_OPTIONS = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];

try {
    define('PDO_CONNECTION', new PDO(DSN, MYSQL_USER, MYSQL_PWD, PDO_OPTIONS));
} catch (PDOException $e) {
    echo($e->getMessage());
    exit;
}

define('POSTS_COUNT', get_posts_count());
define('MAX_PAGE', intdiv(POSTS_COUNT, PER_PAGE) + (POSTS_COUNT % PER_PAGE ? 1 : 0));


$action = $_REQUEST['action'] ?? 'index';
$callback = match ($action) {
    'create' => 'create',
    'show' => 'show',
    'store' => 'store',
    default => 'index',
};

function index(): stdClass
{
    $sort_order = isset($_GET['order-by']) && $_GET['order-by'] === 'oldest' ? 'ASC' : DEFAULT_SORT_ORDER;

    $filter = [];
    if (isset($_GET['category'])) {
        $filter['type'] = 'category';
        $filter['value'] = $_GET['category'];
    } elseif (isset($_GET['author'])) {
        $filter['type'] = 'author_name';
        $filter['value'] = $_GET['author'];
    }


    $p = START_PAGE;
    if (isset($_GET['p'])) {
        if ((int) $_GET['p'] >= START_PAGE && (int) $_GET['p'] <= MAX_PAGE) {
            $p = (int) $_GET['p'];
        }
    }

    $posts = get_posts($filter, $sort_order, $p);

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
        header('location: 404.php'); // Idéalement 404
        exit;
    }
    $pdost = PDO_CONNECTION->prepare(<<<SQL
    SELECT posts.id as post_id, 
           posts.title as post_title, 
           posts.body as post_body, 
           posts.published_at as post_published_at, 
           a.id as post_author_id, 
           a.name as post_author_name,
           a.avatar as post_author_avatar
    FROM posts 
    JOIN authors a on posts.author_id = a.id
    WHERE posts.id = :id;
    SQL
    );
    $pdost->execute(['id' => $_GET['id']]);
    $post = $pdost->fetch();
    if (!$post) {
        header('Location: 404.php'); // Idéalement 404
        exit;
    }
    $pdost = PDO_CONNECTION->prepare(<<<SQL
    SELECT c.name as post_category_name,
           c.id as post_category_id
    FROM category_post cp 
    JOIN categories c on cp.category_id = c.id
    WHERE cp.post_id = :id;
    SQL
    );
    $pdost->execute(['id' => $_GET['id']]);
    $categories = $pdost->fetchAll();
    $post->categories = $categories;
    /*
    $posts = get_posts();
    $authors = get_authors($posts);
    $categories = get_categories($posts);
    $most_recent_post = get_most_recent_post($posts);
    */
    $view_data = new stdClass();
    $view_data->name = 'single.php';
    //$view_data->data = compact('post', 'authors', 'categories', 'most_recent_post');
    $view_data->data = compact('post');
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

            file_put_contents('./datas/posts/'.$post->id.'.json', json_encode($post));

            header('Location: index.php?action=show&id='.$post->id);
        } else {
            $_SESSION['old'] = $_POST;
            header('Location: index.php?action=create');
        }
        exit;
    }

    header('Location: index.php');// Idéalement 404
    exit;
}


function get_posts(array $filter = [], string $order = DEFAULT_SORT_ORDER, int $page = 1): array
{
    $posts = [];
    $start = ($page - 1) * PER_PAGE;
    $per_page = PER_PAGE;

    if (isset($filter['type'])) {
        $value = $filter['value'];
        if ($filter['type'] === 'category') {
            $category = get_category_by_slug($value);
            if ($category) {
                $posts = get_posts_by_category($category->id, $order, $start, $per_page);
            }
        } elseif ($filter['type'] === 'author') {
            $author = get_author_by_slug($value);
            if ($author) {
                $posts = get_posts_by_author($author->id, $order, $start, $per_page);
            }
        }
    } else {
        $posts = get_posts_unfiltered($order, $start, $per_page);
    }
    add_categories_to_posts($posts);
    return $posts;
}

function add_categories_to_posts(array &$posts)
{
    foreach ($posts as $post) {
        $post->post_categories = get_post_categories($post->post_id);
    }
}

function add_categories_to_post(stdClass &$post)
{
    $post->post_categories = get_post_categories($post->post_id);
}

function get_posts_count(): string
{
    $sql = <<<SQL
                SELECT count(*) 
                FROM posts p;
            SQL;

    return PDO_CONNECTION->query($sql)->fetchColumn();
}

function get_post_categories($id): array
{
    $sql = <<<SQL
            SELECT c.slug as category_slug, c.name as category_name
            FROM categories c 
            JOIN category_post cp on c.id = cp.category_id
            WHERE cp.post_id = :id;
        SQL;
    $statement = PDO_CONNECTION->prepare($sql);
    $statement->execute([':id' => $id]);

    return $statement->fetchAll();
}

function get_posts_unfiltered(string $order, int $start, int $per_page): array
{
    $sql = <<<SQL
            SELECT p.id as post_id, 
                   p.slug as post_slug, 
                   p.title as post_title, 
                   p.excerpt as post_excerpt,
                   p.published_at as post_published_at,
                   a.avatar as post_author_avatar,
                   a.name as post_author_name,
                   a.slug as post_author_slug
            FROM posts p 
            JOIN authors a on p.author_id = a.id
            ORDER BY published_at $order
            LIMIT $start, $per_page;
        SQL;

    return PDO_CONNECTION->query($sql)->fetchAll();
}

function get_posts_by_category(int $id, string $order, int $start, int $per_page): array
{
    $sql = <<<SQL
                SELECT p.id as post_id, 
                       p.title as post_title, 
                       p.published_at as post_published_at,
                       a.avatar as post_author_avatar,
                       a.name as post_author_name,
                       a.slug as post_author_slug
                FROM posts p
                JOIN authors a on p.author_id = a.id
                JOIN category_post cp on p.id = cp.post_id
                WHERE cp.post_id = :id
                ORDER BY published_at $order
                LIMIT $start, $per_page;
            SQL;

    $statement = PDO_CONNECTION->prepare($sql);
    $statement->execute([':id' => $id]);

    return $statement->fetchAll();
}

function get_posts_by_author(int $id, string $order, int $start, int $per_page): array
{
    $sql = <<<SQL
                SELECT p.id as post_id, 
                       p.title as post_title, 
                       p.published_at as post_published_at,
                       a.avatar as post_author_avatar,
                       a.name as post_author_name,
                       a.slug as post_author_slug
                FROM posts p
                JOIN authors a on p.author_id = a.id
                WHERE author_id = :id
                ORDER BY published_at $order
                LIMIT $start, $per_page;
            SQL;

    $statement = PDO_CONNECTION->prepare($sql);
    $statement->execute([':id' => $id]);

    return $statement->fetchAll();
}

function get_most_recent_post(): stdClass
{
    $sql = <<<SQL
                SELECT p.id as post_id, 
                   p.slug as post_slug, 
                   p.title as post_title, 
                   p.excerpt as post_excerpt,
                   p.published_at as post_published_at,
                   a.avatar as post_author_avatar,
                   a.name as post_author_name,
                   a.slug as post_author_slug
                FROM posts p 
                JOIN authors a on p.author_id = a.id
                ORDER BY published_at DESC
                LIMIT 1;
            SQL;

    $post = PDO_CONNECTION->query($sql)->fetch();
    add_categories_to_post($post);

    return $post;
}

function get_categories(): array
{
    $sql = <<<SQL
                SELECT c.name, c.slug, count(p.id) as posts_count
                FROM categories c
                JOIN category_post cp on c.id = cp.category_id
                JOIN posts p on cp.post_id = p.id
                GROUP BY c.name, c.slug
                ORDER BY c.name;
            SQL;

    return PDO_CONNECTION->query($sql)->fetchAll();
}

function get_category_by_slug($slug): stdClass|bool
{
    $sql = <<<SQL
            SELECT * FROM categories WHERE slug = :slug;
        SQL;
    $statement = PDO_CONNECTION->prepare($sql);
    $statement->execute([':slug' => $slug]);

    return $statement->fetch();
}

function get_authors(): array
{
    $sql = <<<SQL
                SELECT a.name, a.avatar, a.slug, count(posts.id) as posts_count
                FROM posts
                JOIN authors a on posts.author_id = a.id
                GROUP BY a.id
            SQL;

    return PDO_CONNECTION->query($sql)->fetchAll();
}

function get_author_by_slug($slug): stdClass|bool
{
    $sql = <<<SQL
            SELECT * FROM authors WHERE slug = :slug;
        SQL;
    $statement = PDO_CONNECTION->prepare($sql);
    $statement->execute([':slug' => $slug]);

    return $statement->fetch();
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
    return (bool) count($_SESSION['errors']);
}

/**/


$view = call_user_func($callback);
include VIEWS_PATH.$view->name;


