<?php

namespace Blog\Models;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property mixed|UuidInterface $id
 * @property mixed|string $name
 * @property mixed $slug
 * @method static select(string $string)
 * @method static find(mixed $_POST)
 */

class Category extends Model
{
    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string'
    ];
    protected $withCount = ['posts'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

}