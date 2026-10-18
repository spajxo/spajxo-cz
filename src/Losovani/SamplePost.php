<?php

namespace App\Losovani;

/**
 * Made-up comments so the draw can be tried before the Instagram app is set up.
 */
class SamplePost
{
    public static function create(): Post
    {
        $at = static fn (string $time) => new \DateTimeImmutable('2026-10-10 '.$time, new \DateTimeZone('Europe/Prague'));

        return new Post('ukazkovy_ucet', 'https://www.instagram.com/p/UKAZKA/', 'Soutěž! Napiš, co pěstuješ, a označ dva kamarády.', [
            new Comment('jana.zahradnice', 'Rajčata a bylinky 🌿 @petr.novak @eva_k', $at('10:02')),
            new Comment('petr.novak', 'Hlavně okurky! @jana.zahradnice @tomas.h', $at('10:15')),
            new Comment('eva_k', 'Jahody @lucie.m', $at('10:31')),
            new Comment('tomas.h', 'Chci vyhrát! @martin_z @eva_k', $at('11:04')),
            new Comment('ukazkovy_ucet', 'Díky všem za zapojení! ❤️', $at('11:20')),
            new Comment('lucie.m', 'Brambory a cibuli @jana.zahradnice @petr.novak @eva_k', $at('12:45')),
            new Comment('jana.zahradnice', 'A ještě dýně! @lucie.m @tomas.h', $at('13:10')),
            new Comment('martin_z', '@tomas.h díky za označení!', $at('13:12'), true),
            new Comment('soutez_bot_123', 'Sledujte můj profil pro výhry @a @b @c', $at('14:00')),
            new Comment('katka.p', 'Papriky a chilli 🌶️ @martin_z @lucie.m', $at('16:30')),
            new Comment('ondra.v', 'Mrkev @katka.p @eva_k', new \DateTimeImmutable('2026-10-12 09:00', new \DateTimeZone('Europe/Prague'))),
        ], isSample: true);
    }
}
