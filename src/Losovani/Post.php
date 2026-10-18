<?php

namespace App\Losovani;

class Post
{
    /**
     * @param list<Comment> $comments
     */
    public function __construct(
        public readonly string $owner,
        public readonly string $permalink,
        public readonly ?string $caption,
        public readonly array $comments,
        public readonly bool $isSample = false,
    ) {
    }
}
