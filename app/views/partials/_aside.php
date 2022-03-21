<div class="hidden w-4/12 -mx-8 lg:block">
    <div class="px-8">
        <h1 class="mb-4 text-xl font-bold text-gray-700">Authors</h1>
        <div class="flex flex-col max-w-sm px-6 py-4 mx-auto bg-white rounded-lg shadow-md">
            <ul class="-mx-4">
                <?php foreach ($view->data['authors'] as $author_name => $author_infos): ?>
                    <li class="flex items-center mb-3"><img
                                src="<?= $author_infos->avatar ?>"
                                alt="avatar"
                                class="object-cover w-10 h-10 mx-4 rounded-full">
                        <p><a href="index.php?author=<?= $author_name ?>"
                              class="mx-1 font-bold text-gray-700 hover:underline"><?= $author_name ?></a>
                            <span class="text-sm font-light text-gray-700">Created <?= $author_infos->posts_count ?> Posts</span>
                        </p>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
    <div class="px-8 mt-10">
        <h1 class="mb-4 text-xl font-bold text-gray-700">Categories</h1>
        <div class="flex flex-col max-w-sm px-4 py-6 mx-auto bg-white rounded-lg shadow-md">
            <ul>

                <?php foreach ($view->data['categories'] as $category_name => $post_count): ?>
                    <li class="mb-3"><a href="index.php?category=<?= $category_name ?>"
                                        class="mx-1 font-bold text-gray-700 hover:text-gray-600 hover:underline">
                            <?= ucwords($category_name) ?></a> contains <?= $post_count ?> posts
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
    <div class="px-8 mt-10">
        <h1 class="mb-4 text-xl font-bold text-gray-700">Recent Post</h1>
        <div class="flex flex-col max-w-sm px-8 py-6 mx-auto bg-white rounded-lg shadow-md">
            <div class="flex items-center justify-center">
                <a href="?category=<?= $view->data['most_recent_post']->category ?>"
                   class="px-2 py-1 text-sm text-green-100 bg-gray-600 rounded hover:bg-gray-500"><?= ucwords($view->data['most_recent_post']->category) ?></a>
            </div>
            <div class="mt-4">
                <a href="index.php?action=show&id=<?= $view->data['most_recent_post']->id ?>"
                   class="font-bold text-lg font-medium text-gray-700 hover:underline"><?= $view->data['most_recent_post']->title ?></a>
            </div>
            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center"><img
                            src="<?= $view->data['most_recent_post']->author_avatar ?>"
                            alt="avatar"
                            class="object-cover w-8 h-8 rounded-full">
                    <a href="?author=<?= $view->data['most_recent_post']->author_name ?>"
                       class="font-bold mx-3 text-sm text-gray-700 hover:underline"><?= $view->data['most_recent_post']->author_name ?></a>
                </div>
                <span
                        class="text-sm font-light text-gray-600"><?= (new DateTime($view->data['most_recent_post']->published_at))->format('M j, Y') ?></span>
            </div>
        </div>
    </div>
</div>