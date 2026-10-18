<?php

namespace App\Losovani;

/**
 * Comments pasted as text, typically Claude's transcription of comment screenshots (see PROMPT).
 * One comment per line: "@username | text", replies start with "↳", unclear lines end with "[nejisté]".
 */
class PastedComments
{
    public const string PROMPT = <<<'PROMPT'
        Na přiložených screenshotech jsou komentáře pod příspěvkem na Instagramu. Přepiš všechny komentáře do seznamu přesně v tomto formátu a nic dalšího nepiš:

        @uzivatelske_jmeno | text komentáře

        Pravidla:
        - Jeden komentář = jeden řádek, ve stejném pořadí jako na screenshotech (shora dolů, screenshoty v pořadí, v jakém jsou nahrané).
        - Uživatelské jméno opiš přesně znak po znaku, včetně teček, podtržítek a číslic. Bez ověřovací značky a bez textu „Autor“.
        - Text komentáře opiš doslova včetně všech @označení a emoji. Víceřádkový komentář spoj do jednoho řádku mezerami.
        - Odpověď na jiný komentář (je pod ním odsazená) začni znakem ↳, například: ↳ @uzivatelske_jmeno | text odpovědi
        - Když je stejný komentář na dvou screenshotech (překryv), uveď ho jen jednou.
        - Vynech popisek příspěvku, časy (např. „2 d“), počty To se mi líbí, „Odpovědět“, „Zobrazit odpovědi“ a „Zobrazit překlad“.
        - Pokud jméno nebo text nejde spolehlivě přečíst, přidej na konec řádku [nejisté].

        Celý seznam dej do jednoho bloku kódu.
        PROMPT;

    private const string LINE = '~^(?<reply>↳|>|└)?\s*@?(?<username>[A-Za-z0-9._]{1,30})\s*[|│¦:]\s*(?<text>.*?)\s*(?<uncertain>\[nejist[ée]\])?$~u';

    public static function parse(string $text, string $owner): Post
    {
        $comments = [];
        $skipped = [];
        // Pasted comments have no real time; keep their order with one-second steps.
        $time = new \DateTimeImmutable('2000-01-01');

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);
            if ('' === $line || str_starts_with($line, '```')) {
                continue;
            }
            if (!preg_match(self::LINE, $line, $match)) {
                $skipped[] = $line;
                continue;
            }
            $comments[] = new Comment(
                $match['username'],
                $match['text'],
                $time = $time->modify('+1 second'),
                isReply: '' !== $match['reply'],
                uncertain: '' !== ($match['uncertain'] ?? ''),
            );
        }

        if ([] === $comments) {
            throw new \InvalidArgumentException('Ve vloženém textu jsem nenašel žádný komentář ve tvaru „@jméno | text“.');
        }

        return new Post($owner, '', null, $comments, hasTimestamps: false, skippedLines: $skipped);
    }
}
