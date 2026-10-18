<?php

namespace App\Losovani;

/**
 * The pasted comments of one post, in the order they were pasted.
 */
class Post
{
    /**
     * @param list<Comment> $comments
     * @param list<string>  $skippedLines lines of the pasted text that are not a comment
     */
    public function __construct(
        public readonly array $comments,
        public readonly array $skippedLines = [],
    ) {
    }
}
