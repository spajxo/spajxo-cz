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
     * @param list<array{number: int, comments: int, overlap: int}> $screenshots comments kept per screenshot
     *        and how many at its top repeated the end of the previous one
     */
    public function __construct(
        public readonly array $comments,
        public readonly array $skippedLines = [],
        public readonly array $screenshots = [],
    ) {
    }
}
