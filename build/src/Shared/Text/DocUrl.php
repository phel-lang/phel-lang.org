<?php

declare(strict_types=1);

namespace PhelWeb\Shared\Text;

/**
 * Phel docstrings link to doc pages by URL, and those URLs follow the site
 * layout of the Phel release that wrote them. A moved page still redirects,
 * but the redirect drops the #anchor, so the API reference rewrites old paths
 * and renamed anchors to where the section lives now.
 */
final class DocUrl
{
    private const LANGUAGE_PAGES = [
        'control-flow',
        'data-structures',
        'functions-and-recursion',
        'global-and-local-bindings',
        'interfaces',
        'macros',
        'namespaces',
        'php-interop',
    ];

    private const RENAMED_ANCHORS = [
        'functions-and-recursion#apply-functions' => 'apply-and-compose',
        'interfaces#defining-interfaces' => 'define-and-implement-an-interface',
        'namespaces#namespace-ns' => 'declare-a-namespace',
        'php-interop#get-php-array-value' => 'read-and-write-a-php-array-in-place',
        'php-interop#set-php-array-value' => 'read-and-write-a-php-array-in-place',
        'php-interop#append-php-array-value' => 'read-and-write-a-php-array-in-place',
        'php-interop#unset-php-array-value' => 'read-and-write-a-php-array-in-place',
    ];

    public static function current(string $url): string
    {
        $pattern = '#^(?<host>https://phel-lang\.org)?/documentation/(?:language/)?(?<page>'
            . implode('|', self::LANGUAGE_PAGES)
            . ')/?(?:\#(?<anchor>[\w-]+))?$#';

        if (preg_match($pattern, $url, $m) !== 1) {
            return $url;
        }

        $anchor = $m['anchor'] ?? '';
        $anchor = self::RENAMED_ANCHORS[$m['page'] . '#' . $anchor] ?? $anchor;

        return $m['host'] . '/documentation/language/' . $m['page'] . '/'
            . ($anchor === '' ? '' : '#' . $anchor);
    }
}
