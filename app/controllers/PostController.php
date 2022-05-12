<?php

namespace Blog\Controllers;

use stdClass;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Blog\Models\Post;
use Blog\Models\Author;
use Blog\Models\Category;
use Cocur\Slugify\Slugify;
use JetBrains\PhpStorm\NoReturn;
use Blog\ViewComposers\AsideData;
use Blog\Request\Validators\StorePostRequest;

class PostController
{
    use StorePostRequest;
    use AsideData;

    public function __construct(
        private readonly Author $author_model = new Author(),
        private readonly Category $category_model = new Category(),
        private readonly Post $post_model = new Post(),
    ) {
    }

    public function index(): array
    {
        // Order setting from request
        $sort_order = isset($_GET['order-by']) && $_GET['order-by'] === 'oldest' ? 'ASC' : DEFAULT_SORT_ORDER;

        if (isset($_GET['author'])) {
            $posts = Post::with(['author', 'categories'])
                ->whereHas('author', function ($query) {
                    $query->where('slug', $_GET['author']);
                })
                ->orderBy('published_at', $sort_order)
                ->get();
        } elseif (isset($_GET['category'])) {
            $posts = Post::with(['author', 'categories'])
                ->whereHas('categories', function ($query) {
                    $query->where('slug', $_GET['category']);
                })
                ->orderBy('published_at', $sort_order)
                ->get();
        } else {
            $posts = Post::with(['categories', 'author'])
                ->orderBy('published_at', $sort_order)
                ->get();
        }

        // Rendering
        $view_data = [];
        $view_data['view'] = 'posts/index.php';
        $aside_data = $this->fetch_aside_data();
        $view_data['data'] = array_merge($aside_data, compact('posts'));
        return $view_data;
    }

    public function create(): array
    {
        $view_data = [];
        $view_data['view'] = 'posts/add-post.php';
        $view_data['data'] = $this->fetch_aside_data();

        return $view_data;
    }

    public function show(): array
    {
        if (!isset($_GET['slug'])) {
            header('location: 404.php'); // Idéalement 404
            exit;
        }
        $post = Post::with('categories')
            ->where('slug', $_GET['slug'])
            ->first();

        if (!$post) {
            header('Location: 404.php'); // Idéalement 404
            exit;
        }

        $view_data = [];
        $view_data['view'] = 'posts/single.php';
        $view_data['data'] = array_merge($this->fetch_aside_data(), compact('post'));

        return $view_data;
    }

    #[NoReturn] public function store(): void
    {
        if (!$this->has_validation_errors()) {
            $slugify = new Slugify();
            $post = new Post();
            $id = Uuid::uuid4();
            $post->id = $id;
            $post->title = $_POST['post-title'];
            $post->slug = $slugify->slugify($post->title);
            $post->body = $_POST['post-body'];
            $post->excerpt = $_POST['post-excerpt'];
            $post->thumbnail = '';
            $post->published_at = Carbon::now();
            $post->author_id = Author::inRandomOrder()->first()->id;
            $result = $post->save();
            if ($result) {
                $post->categories()->attach($_POST['post-category']);
                header('Location: index.php?action=show&slug='.$post->slug);
            } else {
                die($result);
            }
        } else {
            $_SESSION['old'] = $_POST;
            header('Location: index.php?action=create&resource=post');
        }
    }
}