<?php

namespace App\Losovani;

class Comment
{
    public function __construct(
        public readonly string $username,
        public readonly string $text,
        public readonly \DateTimeImmutable $timestamp,
        public readonly bool $isReply = false,
    ) {
    }

    /**
     * Distinct @handles in the text, lowercased.
     *
     * @return list<string>
     */
    public function mentions(): array
    {
        preg_match_all('/@([A-Za-z0-9._]{1,30})/', $this->text, $matches);

        return array_values(array_unique(array_map(strtolower(...), $matches[1])));
    }
}
