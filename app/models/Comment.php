<?php

namespace Blog\Models;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed|UuidInterface $id
 * @property mixed $author_id
 * @property mixed|string $body
 * @property mixed $post_id
 */
class Comment extends Model
{
    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];

    public function post(): belongsTo
    {
        return $this->belongsTo(Post::class);
    }
}