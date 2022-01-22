<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator;

use Phel\Lang\Collections\Map\PersistentMapInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnLoaderInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;
use PHPUnit\Framework\TestCase;

final class PhelFnNormalizerTest extends TestCase
{
    public function test_generate_search_index(): void
    {
        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'test/table' => $this->createMock(PersistentMapInterface::class),
            'test/table?' => $this->createMock(PersistentMapInterface::class),
            'test/table-' => $this->createMock(PersistentMapInterface::class),
            'TEST/TABLE' => $this->createMock(PersistentMapInterface::class),
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'test-table' => [
                [
                    'fnName' => 'test/table',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'test/table?',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'test/table-',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'TEST/TABLE',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_skip_private_symbol(): void
    {
        $privateSymbol = $this->createMock(PersistentMapInterface::class);
        $privateSymbol->method('offsetExists')->willReturn(true);
        $privateSymbol->method('offsetGet')->willReturn(true);

        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'privateSymbol' => $privateSymbol,
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [];

        self::assertEquals($expected, $actual);
    }

    public function test_symbol_without_doc(): void
    {
        $symbol = $this->createStub(PersistentMapInterface::class);
        $symbol->method('offsetExists')->willReturn(true);
        $symbol->method('offsetGet')->will(
            $this->onConsecutiveCalls(false, null)
        );

        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            '*compile-mode*' => $symbol,
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'compile-mode' => [
                [
                    'fnName' => '*compile-mode*',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_symbol_with_doc_and_desc(): void
    {
        $symbol = $this->createStub(PersistentMapInterface::class);
        $symbol->method('offsetExists')->willReturn(true);
        $symbol->method('offsetGet')->will(
            $this->onConsecutiveCalls(false, 'Constant for Not a Number (NAN) values.')
        );

        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'NAN' => $symbol,
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'nan' => [
                [
                    'fnName' => 'NAN',
                    'doc' => 'Constant for Not a Number (NAN) values.',
                    'fnSignature' => '',
                    'desc' => 'Constant for Not a Number (NAN) values.',
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_symbol_with_doc_and_desc_and_signature(): void
    {
        $symbol = $this->createStub(PersistentMapInterface::class);
        $symbol->method('offsetExists')->willReturn(true);
        $symbol->method('offsetGet')->will(
            $this->onConsecutiveCalls(false, "```phel\n(array & xs)\n```\nCreates a new Array.")
        );

        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'array' => $symbol,
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'array' => [
                [
                    'fnName' => 'array',
                    'doc' => "```phel\n(array & xs)\n```\nCreates a new Array.",
                    'fnSignature' => '(array & xs)',
                    'desc' => 'Creates a new Array.',
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_symbol_with_desc_with_link(): void
    {
        $symbol = $this->createStub(PersistentMapInterface::class);
        $symbol->method('offsetExists')->willReturn(true);
        $symbol->method('offsetGet')->will(
            $this->onConsecutiveCalls(false, "Returns a formatted string. See PHP's [sprintf](http://...) for more information.")
        );

        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'format' => $symbol,
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'format' => [
                [
                    'fnName' => 'format',
                    'doc' => "Returns a formatted string. See PHP's [sprintf](http://...) for more information.",
                    'fnSignature' => '',
                    'desc' => "Returns a formatted string. See PHP's <i>sprintf</i> for more information.",
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }
}
