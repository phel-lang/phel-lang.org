<?php

declare(strict_types=1);

namespace PhelWebTests\ErrorReference;

use Phel\Shared\Exceptions\ErrorCode;
use PHPUnit\Framework\TestCase;
use PhelWeb\ErrorReference\ErrorReferenceGenerator;

final class ErrorReferenceGeneratorTest extends TestCase
{
    public function test_every_error_code_has_a_curated_entry(): void
    {
        // Guards against a Phel bump adding an error code the docs do not cover.
        self::assertSame([], ErrorReferenceGenerator::missingCodes());
    }

    public function test_generate_writes_a_page_documenting_all_codes(): void
    {
        $file = sys_get_temp_dir() . '/phel-errors-' . uniqid() . '.md';

        try {
            (new ErrorReferenceGenerator($file))->generate();

            self::assertFileExists($file);
            $content = (string) file_get_contents($file);

            self::assertStringContainsString('title = "Error Reference"', $content);
            foreach (ErrorCode::cases() as $case) {
                self::assertStringContainsString($case->value, $content, "Missing {$case->value} in page");
            }
        } finally {
            @unlink($file);
        }
    }
}
