<?php

declare(strict_types=1);

namespace PhelWebTests\Shared\Text;

use PHPUnit\Framework\TestCase;
use PhelWeb\Shared\Text\DocUrl;

final class DocUrlTest extends TestCase
{
    public function test_old_language_path_moves_under_language(): void
    {
        self::assertSame(
            '/documentation/language/control-flow/#loop',
            DocUrl::current('/documentation/control-flow/#loop'),
        );
    }

    public function test_renamed_anchor_points_at_the_new_section(): void
    {
        self::assertSame(
            '/documentation/language/php-interop/#read-and-write-a-php-array-in-place',
            DocUrl::current('/documentation/php-interop/#get-php-array-value'),
        );
    }

    public function test_absolute_url_keeps_its_host(): void
    {
        self::assertSame(
            'https://phel-lang.org/documentation/language/macros/#quote',
            DocUrl::current('https://phel-lang.org/documentation/macros/#quote'),
        );
    }

    public function test_current_url_is_unchanged(): void
    {
        self::assertSame(
            '/documentation/language/data-structures/#maps',
            DocUrl::current('/documentation/language/data-structures/#maps'),
        );
    }

    public function test_unrelated_url_is_unchanged(): void
    {
        self::assertSame(
            '/documentation/guides/testing/',
            DocUrl::current('/documentation/guides/testing/'),
        );
    }
}
