<?php

namespace Blog\Models;

use Carbon\Carbon;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property mixed|UuidInterface $id
 * @property Carbon|false|mixed $created_at
 * @property Carbon|false|mixed $published_at
 * @property Carbon|false|mixed $updated_at
 * @property Carbon|mixed|null $deleted_at
 * @property mixed $author_id
 * @property mixed|string $title
 * @property mixed $slug
 * @property mixed|string $excerpt
 * @property mixed|string $thumbnail
 * @property mixed|string $body
 * @method static find(mixed $id)
 * @method static select(string $string)
 * @method static get(string[] $array)
 * @method static where(string $string, mixed $slug)
 */

class Post extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = [
        'published_at' => 'datetime:M j, Y - G:i',
        'id' => 'string',
    ];
    protected $withCount = ['comments'];

    public function categories(): belongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function author(): belongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function comments(): hasMany
    {
        return $this->hasMany(Comment::class);
    }
}
