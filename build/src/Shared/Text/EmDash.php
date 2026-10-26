<?php

declare(strict_types=1);

namespace PhelWeb\Shared\Text;

/**
 * Site-wide rule: no em dash or en dash may reach content/.
 *
 * Both the API reference and the release pages are generated from prose we do
 * not control (Phel docstrings, GitHub release bodies), so both have to strip
 * these dashes on the way out. Keeping the replacement table here means the two
 * generators cannot drift apart.
 *
 * A spaced dash is a clause break and becomes a comma. A bare en dash is a
 * range ("1.8–8x") and becomes a hyphen, so the numbers keep their meaning.
 */
final class EmDash
{
    private const SEARCH = [
        ' &mdash; ', ' &mdash;', '&mdash; ', '&mdash;', ' — ', ' —', '— ', '—',
        ' &ndash; ', ' – ', '&ndash;', '–',
    ];

    private const REPLACE = [
        ', ', ',', ', ', ',', ', ', ',', ', ', ',',
        ', ', ', ', '-', '-',
    ];

    public static function strip(string $text): string
    {
        return str_replace(self::SEARCH, self::REPLACE, $text);
    }
}
