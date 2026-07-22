<?php

declare(strict_types=1);

namespace PhelWeb\Shared\Text;

/**
 * Site-wide rule: no em dash may reach content/.
 *
 * Both the API reference and the release pages are generated from prose we do
 * not control (Phel docstrings, GitHub release bodies), so both have to strip
 * em dashes on the way out. Keeping the replacement table here means the two
 * generators cannot drift apart.
 */
final class EmDash
{
    private const SEARCH = [' &mdash; ', ' &mdash;', '&mdash; ', '&mdash;', ' — ', ' —', '— ', '—'];

    private const REPLACE = [', ', ',', ', ', ',', ', ', ',', ', ', ','];

    public static function strip(string $text): string
    {
        return str_replace(self::SEARCH, self::REPLACE, $text);
    }
}
