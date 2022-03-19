<div>
    <form action="index.php"
          method="get">
        <select name="order-by"
                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="oldest" <?= $view->data['sort_order'] === -1 ? 'selected' : '' ?>>Oldest first</option>
            <option value="newest" <?= $view->data['sort_order'] === 1 ? 'selected' : '' ?>>Newest first</option>
        </select>
        <button type="submit"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md ml-1">Change
        </button>
    </form>
</div>