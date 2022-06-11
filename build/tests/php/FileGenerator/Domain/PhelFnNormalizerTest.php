<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator\Domain;

use Phel\Lang\Collections\Map\PersistentMapInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnLoaderInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;
use PHPUnit\Framework\TestCase;

final class PhelFnNormalizerTest extends TestCase
{
    public function test_group_key_one_function(): void
    {
        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'fn-name' => $this->createMock(PersistentMapInterface::class),
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'fn-name' => [
                [
                    'fnName' => 'fn-name',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
            ],
        ];

        self::assertSame($expected, $actual);
    }

    public function test_group_key_functions_in_different_groups(): void
    {
        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'fn-name-1' => $this->createMock(PersistentMapInterface::class),
            'fn-name-2' => $this->createMock(PersistentMapInterface::class),
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'fn-name-1' => [
                [
                    'fnName' => 'fn-name-1',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
            ],
            'fn-name-2' => [
                [
                    'fnName' => 'fn-name-2',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
            ],
        ];

        self::assertSame($expected, $actual);
    }

    public function test_group_key_functions_in_same_group_with_question_mark(): void
    {
        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'fn-name' => $this->createMock(PersistentMapInterface::class),
            'fn-name?' => $this->createMock(PersistentMapInterface::class),
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'fn-name' => [
                [
                    'fnName' => 'fn-name',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'fn-name?',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
            ],
        ];

        self::assertSame($expected, $actual);
    }

    public function test_group_key_functions_in_same_group_with_minus(): void
    {
        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'fn-name?' => $this->createMock(PersistentMapInterface::class),
            'fn-name-' => $this->createMock(PersistentMapInterface::class),
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'fn-name' => [
                [
                    'fnName' => 'fn-name?',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'fn-name-',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
            ],
        ];

        self::assertSame($expected, $actual);
    }

    public function test_group_key_functions_in_same_group_with_upper_case(): void
    {
        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'fn-name-' => $this->createMock(PersistentMapInterface::class),
            'FN-NAME' => $this->createMock(PersistentMapInterface::class),
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);
        $actual = $normalizer->getNormalizedGroupedPhelFns();

        $expected = [
            'fn-name' => [
                [
                    'fnName' => 'fn-name-',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'FN-NAME',
                    'doc' => '',
                    'fnSignature' => '',
                    'desc' => '',
                ],
            ],
        ];

        self::assertSame($expected, $actual);
    }

    public function test_skip_private_symbol(): void
    {
        $privateSymbol = $this->createMock(PersistentMapInterface::class);
        // Mocking the `$meta[Keyword::create('private')]`
        $privateSymbol->method('offsetExists')->willReturn(true);
        $privateSymbol->method('offsetGet')->willReturn(true);

        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'privateSymbol' => $privateSymbol,
        ]);

        $normalizer = new PhelFnNormalizer($phelFnLoader);

        self::assertEmpty($normalizer->getNormalizedGroupedPhelFns());
    }

    public function test_symbol_without_doc(): void
    {
        $symbol = $this->createStub(PersistentMapInterface::class);
        $symbol->method('offsetExists')->willReturn(true);
        // false -> relates to `isPrivate`
        // null  -> relates to `doc`
        $symbol->method('offsetGet')->willReturnOnConsecutiveCalls(false, null);

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

        self::assertSame($expected, $actual);
    }

    public function test_symbol_with_doc_and_desc(): void
    {
        $symbol = $this->createStub(PersistentMapInterface::class);
        $symbol->method('offsetExists')->willReturn(true);
        // false -> relates to `isPrivate`
        // '...' -> relates to `doc`
        $symbol->method('offsetGet')->willReturnOnConsecutiveCalls(
            false,
            'Constant for Not a Number (NAN) values.'
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

        self::assertSame($expected, $actual);
    }

    public function test_symbol_with_doc_and_desc_and_signature(): void
    {
        $symbol = $this->createStub(PersistentMapInterface::class);
        $symbol->method('offsetExists')->willReturn(true);
        // false -> relates to `isPrivate`
        // '...' -> relates to `doc`
        $symbol->method('offsetGet')->willReturnOnConsecutiveCalls(
            false,
            "```phel\n(array & xs)\n```\nCreates a new Array."
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

        self::assertSame($expected, $actual);
    }

    public function test_symbol_with_desc_with_link(): void
    {
        $symbol = $this->createStub(PersistentMapInterface::class);
        $symbol->method('offsetExists')->willReturn(true);
        // false -> relates to `isPrivate`
        // '...' -> relates to `doc`
        $symbol->method('offsetGet')->willReturnOnConsecutiveCalls(
            false,
            "Returns a formatted string. See PHP's [sprintf](http://...) for more information."
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

        self::assertSame($expected, $actual);
    }
}
