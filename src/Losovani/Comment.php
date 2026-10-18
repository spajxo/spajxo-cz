<?php

namespace App\Losovani;

class Comment
{
    public function __construct(
        public readonly string $username,
        public readonly string $text,
        public readonly bool $isReply = false,
        // marked [nejisté] when transcribed from a screenshot
        public readonly bool $uncertain = false,
        // number of the screenshot it was transcribed from, 0 when the paste has no "# N" sections
        public readonly int $screenshot = 0,
    ) {
    }

    public function asTopLevel(): self
    {
        return new self($this->username, $this->text, false, $this->uncertain, $this->screenshot);
    }

    /**
     * Identifies the same comment transcribed from two screenshots: the account plus the people it tags.
     * The text itself is not compared because Claude may read a cut-off or emoji-heavy comment differently
     * each time; a comment without tags falls back to the start of its text.
     */
    public function key(): string
    {
        $mentions = $this->mentions();
        sort($mentions);

        return strtolower($this->username).'|'.([] !== $mentions
            ? implode(',', $mentions)
            : mb_substr(preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($this->text)), 0, 20));
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
