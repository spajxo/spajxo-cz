<?php

namespace App\Losovani;

/**
 * Comments pasted as text, typically Claude's transcription of comment screenshots (see PROMPT).
 * One comment per line: "@username | text", replies start with "↳", unclear lines end with "[nejisté]",
 * and a "# N" line starts the comments of screenshot N.
 *
 * Claude transcribes every screenshot in full and the overlap between neighbouring screenshots is
 * removed here: asking Claude to deduplicate also merged identical comments a person really posted twice.
 */
class PastedComments
{
    public const string PROMPT = <<<'PROMPT'
        Na přiložených screenshotech jsou komentáře pod příspěvkem na Instagramu. Přepiš je do seznamu přesně v tomto formátu a nic dalšího nepiš:

        # 1
        @uzivatelske_jmeno | text komentáře
        @uzivatelske_jmeno | text komentáře
        # 2
        @uzivatelske_jmeno | text komentáře

        Pravidla:
        - Každý screenshot začni řádkem „# číslo“ podle pořadí, v jakém jsou screenshoty nahrané, a pod něj vypiš VŠECHNY komentáře, které jsou na něm vidět, shora dolů. Řádky „# číslo“ nevynechávej: podle nich se pozná překryv mezi screenshoty.
        - Nic neslučuj a neodstraňuj duplicity. Komentář, který je dole na jednom screenshotu a znovu nahoře na dalším, vypiš u obou; opakování se odstraní automaticky. Když stejný člověk napsal dva stejné komentáře, vypiš oba.
        - Jeden komentář = jeden řádek. Víceřádkový komentář spoj do jednoho řádku mezerami.
        - Uživatelské jméno opiš přesně znak po znaku, včetně teček, podtržítek a číslic. Bez ověřovací značky a bez textu „Autor“.
        - Text komentáře opiš doslova včetně všech @označení a emoji.
        - Odpověď na jiný komentář (je pod ním odsazená) začni znakem ↳, například: ↳ @uzivatelske_jmeno | text odpovědi
        - Komentář useknutý na horním nebo dolním okraji screenshotu vypiš, jen když je vidět celé jméno i celý text.
        - Vynech popisek příspěvku, časy (např. „2 d“), počty To se mi líbí, „Odpovědět“, „Zobrazit odpovědi“ a „Zobrazit překlad“.
        - Pokud jméno nebo text nejde spolehlivě přečíst, přidej na konec řádku [nejisté].

        Celý seznam dej do jednoho bloku kódu.
        PROMPT;

    private const string LINE = '~^(?<reply>↳|>|└)?\s*@?(?<username>[A-Za-z0-9._]{1,30})\s*[|│¦:]\s*(?<text>.*?)\s*(?<uncertain>\[nejist[ée]\])?$~u';
    private const string SCREENSHOT = '~^(?:#+\s*)?(?:screenshot|snímek|obrázek)?\s*(?<number>\d{1,3})\s*[:.)]?$~iu';

    public static function parse(string $text): Post
    {
        /** @var array<int, list<Comment>> $groups comments per screenshot, 0 = before any "# N" line */
        $groups = [];
        $skipped = [];
        $screenshot = 0;

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);
            if ('' === $line || str_starts_with($line, '```')) {
                continue;
            }
            if (preg_match(self::SCREENSHOT, $line, $match)) {
                $screenshot = (int) $match['number'];
                $groups[$screenshot] ??= [];
                continue;
            }
            if (!preg_match(self::LINE, $line, $match)) {
                $skipped[] = $line;
                continue;
            }
            $groups[$screenshot][] = new Comment(
                $match['username'],
                $match['text'],
                isReply: '' !== $match['reply'],
                uncertain: '' !== ($match['uncertain'] ?? ''),
                screenshot: $screenshot,
            );
        }

        $comments = [];
        $screenshots = [];
        $repeats = 0;
        $previous = [];
        foreach ($groups as $number => $group) {
            if (0 === $number) {
                // Without "# N" lines the screenshot borders are unknown: drop runs that repeat right away.
                [$kept, $overlap] = self::withoutImmediateRepeats($group);
            } else {
                $overlap = self::overlap($previous, $group);
                $kept = array_slice($group, $overlap);
                $screenshots[] = ['number' => $number, 'comments' => count($kept), 'overlap' => $overlap];
            }
            array_push($comments, ...$kept);
            $repeats += $overlap;
            $previous = $group;
        }

        if ([] === $comments) {
            throw new \InvalidArgumentException('Ve vloženém textu jsem nenašel žádný komentář ve tvaru „@jméno | text“.');
        }

        return new Post($comments, $skipped, $screenshots, $repeats);
    }

    /**
     * How many comments at the start of $next repeat the end of $previous (the scroll overlap).
     *
     * @param list<Comment> $previous
     * @param list<Comment> $next
     */
    private static function overlap(array $previous, array $next): int
    {
        $tail = array_map(static fn (Comment $comment) => $comment->key(), $previous);
        $head = array_map(static fn (Comment $comment) => $comment->key(), $next);

        for ($size = min(count($tail), count($head)); $size > 0; --$size) {
            if (array_slice($tail, -$size) === array_slice($head, 0, $size)) {
                return $size;
            }
        }

        return 0;
    }

    /**
     * Drops every run of comments that directly repeats the run before it (A B A B → A B, A A → A).
     *
     * @param list<Comment> $comments
     *
     * @return array{list<Comment>, int} the kept comments and how many were dropped
     */
    private static function withoutImmediateRepeats(array $comments): array
    {
        $kept = [];
        $dropped = 0;
        for ($i = 0, $count = count($comments); $i < $count; ++$i) {
            $rest = array_slice($comments, $i);
            $overlap = self::overlap($kept, $rest);
            if ($overlap > 0) {
                $dropped += $overlap;
                $i += $overlap - 1;
                continue;
            }
            $kept[] = $comments[$i];
        }

        return [$kept, $dropped];
    }
}
