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
        // comments_count as Instagram reports it (includes replies), to spot comments the API did not return
        public readonly ?int $expectedComments = null,
        // false for pasted comments: they keep their order but carry no real time
        public readonly bool $hasTimestamps = true,
        /** @var list<string> lines of pasted text that are not a comment */
        public readonly array $skippedLines = [],
    ) {
    }
}
