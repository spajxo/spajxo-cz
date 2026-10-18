<?php

namespace App\Losovani;

use Symfony\Component\HttpFoundation\Request;

class Criteria
{
    /**
     * @param list<string> $excluded lowercased usernames without @
     */
    public function __construct(
        public readonly string $keyword = '',
        public readonly int $minMentions = 0,
        public readonly bool $onePerUser = true,
        public readonly bool $includeReplies = false,
        public readonly array $excluded = [],
        public readonly int $winners = 1,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $excluded = preg_split('/[\s,;]+/', strtolower($request->request->getString('excluded')), -1, \PREG_SPLIT_NO_EMPTY);

        return new self(
            keyword: trim($request->request->getString('keyword')),
            minMentions: max(0, $request->request->getInt('min_mentions')),
            onePerUser: $request->request->getBoolean('one_per_user'),
            includeReplies: $request->request->getBoolean('include_replies'),
            excluded: array_values(array_map(static fn (string $name) => ltrim($name, '@'), $excluded)),
            winners: max(1, min(50, $request->request->getInt('winners', 1))),
        );
    }
}
