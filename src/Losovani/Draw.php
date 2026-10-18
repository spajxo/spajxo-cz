<?php

namespace App\Losovani;

/**
 * Applies the criteria to every comment, then picks distinct winners from the valid entries.
 */
class Draw
{
    /**
     * @return array{
     *     entries: list<array{comment: Comment, reason: ?string}>,
     *     valid: list<Comment>,
     *     participants: list<string>,
     *     winners: list<Comment>,
     *     drawnAt: \DateTimeImmutable,
     * }
     */
    public function run(Post $post, Criteria $criteria): array
    {
        $owner = strtolower($post->owner);
        $entries = [];
        $valid = [];
        $seen = [];

        // The API returns newest first; with one entry per user the first comment counts.
        $comments = $post->comments;
        if ($post->hasTimestamps) {
            usort($comments, static fn (Comment $a, Comment $b) => $a->timestamp <=> $b->timestamp);
        }

        foreach ($comments as $comment) {
            $username = strtolower($comment->username);
            $mentions = array_diff($comment->mentions(), [$username, $owner]);

            $reason = match (true) {
                $username === $owner => 'autor příspěvku',
                in_array($username, $criteria->excluded, true) => 'vyřazený účet',
                $comment->isReply && !$criteria->includeReplies => 'odpověď na komentář',
                $post->hasTimestamps && null !== $criteria->deadline && $comment->timestamp > $criteria->deadline => 'po uzávěrce',
                '' !== $criteria->keyword && false === mb_stripos($comment->text, $criteria->keyword) => 'chybí „'.$criteria->keyword.'“',
                count($mentions) < $criteria->minMentions => sprintf('označení: %d z %d', count($mentions), $criteria->minMentions),
                $criteria->onePerUser && isset($seen[$username]) => 'další komentář stejného účtu',
                default => null,
            };

            if (null === $reason) {
                $seen[$username] = true;
                $valid[] = $comment;
            }
            $entries[] = ['comment' => $comment, 'reason' => $reason];
        }

        return [
            'entries' => $entries,
            'valid' => $valid,
            'participants' => array_keys($seen),
            'winners' => $this->pick($valid, $criteria->winners),
            'drawnAt' => new \DateTimeImmutable('now', new \DateTimeZone('Europe/Prague')),
        ];
    }

    /**
     * Draws entries with random_int (CSPRNG); a user wins at most once even with several entries.
     *
     * @param list<Comment> $entries
     *
     * @return list<Comment>
     */
    private function pick(array $entries, int $count): array
    {
        $winners = [];
        $won = [];
        while (count($winners) < $count && [] !== $entries) {
            $index = random_int(0, count($entries) - 1);
            $comment = $entries[$index];
            $winners[] = $comment;
            $won[] = strtolower($comment->username);
            $entries = array_values(array_filter($entries, static fn (Comment $entry) => !in_array(strtolower($entry->username), $won, true)));
        }

        return $winners;
    }
}
