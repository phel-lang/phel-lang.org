<?php

declare(strict_types=1);

namespace PhelWebTests\Shared\Text;

use PHPUnit\Framework\TestCase;
use PhelWeb\Shared\Text\EmDash;

final class EmDashTest extends TestCase
{
    public function test_spaced_em_dash_becomes_comma(): void
    {
        self::assertSame('fast, and small', EmDash::strip('fast — and small'));
    }

    public function test_html_em_dash_becomes_comma(): void
    {
        self::assertSame('fast, and small', EmDash::strip('fast &mdash; and small'));
    }

    public function test_spaced_en_dash_becomes_comma(): void
    {
        self::assertSame('fast, and small', EmDash::strip('fast – and small'));
    }

    public function test_bare_en_dash_range_becomes_hyphen(): void
    {
        self::assertSame('~1.8-8x faster', EmDash::strip('~1.8–8x faster'));
    }

    public function test_html_en_dash_range_becomes_hyphen(): void
    {
        self::assertSame('pages 3-5', EmDash::strip('pages 3&ndash;5'));
    }

    public function test_text_without_dashes_is_unchanged(): void
    {
        self::assertSame('plain-text (no dashes)', EmDash::strip('plain-text (no dashes)'));
    }
}
