<?php

declare(strict_types=1);

namespace PhelWeb\Shared\Text;

/**
 * Mirrors Zola's default heading-anchor slugifier: lowercase, replace any
 * non-alphanumeric/dash character with `-`, collapse runs, trim.
 *
 * Two generators depend on this producing the exact same string: the markdown
 * generator writes the `#anchor` hrefs into the API pages, and the search
 * generator writes the `#anchor` fragments into static/api_search.json. If the
 * two implementations drift, every cross-reference and every search hit points
 * at a fragment Zola never emitted, so they share one implementation.
 *
 * Disambiguation suffixes (`-1`, `-2`, ...) are layered on top by the caller,
 * the same way Zola handles repeated heading text.
 */
final class ZolaAnchor
{
    public static function fromHeading(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text) ?? '';
        $text = preg_replace('/-+/', '-', $text) ?? '';

        return trim($text, '-');
    }
}
