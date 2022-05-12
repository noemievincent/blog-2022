<?php

namespace Blog\Models;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed|UuidInterface $id
 * @property mixed|string $name
 * @property mixed $slug
 * @property mixed|string $avatar
 * @property mixed|string $email
 * @property mixed|string $password
 * @method static inRandomOrder()
 * @method static select(string $string)
 * @method static get(string[] $array)
 */

class Author extends Model
{
    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];
    protected $withCount = ['posts'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}