<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator\Domain;

use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelNormalizedInternal\Transfer\NormalizedPhelFunction;
use PHPUnit\Framework\TestCase;

final class ApiSearchGeneratorTest extends TestCase
{
    private ApiSearchGenerator $generator;

    public function setUp(): void
    {
        $this->generator = new ApiSearchGenerator();
    }

    public function test_generate_search_index_one_item(): void
    {
        $actual = $this->generator->generateSearchIndex([
            'table' => [
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'table?',
                    'fnSignature' => '(table? x)',
                    'desc' => 'doc for table?',
                ]),
            ],
        ]);

        $expected = [
            [
                'fnName' => 'table?',
                'fnSignature' => '(table? x)',
                'desc' => 'doc for table?',
                'anchor' => 'table',
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_multiple_items_in_different_groups(): void
    {
        $actual = $this->generator->generateSearchIndex([
            'table' => [
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'table',
                    'fnSignature' => '(table & xs)',
                    'desc' => 'doc for table',
                ]),
            ],
            'not' => [
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'not',
                    'fnSignature' => '(not x)',
                    'desc' => 'doc for not',
                ]),
            ],
        ]);

        $expected = [
            [
                'fnName' => 'table',
                'fnSignature' => '(table & xs)',
                'desc' => 'doc for table',
                'anchor' => 'table',
            ],
            [
                'fnName' => 'not',
                'fnSignature' => '(not x)',
                'desc' => 'doc for not',
                'anchor' => 'not',
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_multiple_items_in_the_same_group(): void
    {
        $actual = $this->generator->generateSearchIndex([
            'table' => [
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'table',
                    'fnSignature' => '(table & xs)',
                    'desc' => 'doc for table',
                ]),
                    NormalizedPhelFunction::fromArray([
                    'fnName' => 'table?',
                    'fnSignature' => '(table? x)',
                    'desc' => 'doc for table?',
                ]),
            ],
        ]);

        $expected = [
            [
                'fnName' => 'table',
                'fnSignature' => '(table & xs)',
                'desc' => 'doc for table',
                'anchor' => 'table',
            ],
            [
                'fnName' => 'table?',
                'fnSignature' => '(table? x)',
                'desc' => 'doc for table?',
                'anchor' => 'table-1',
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_fn_name_with_slash_in_the_middle(): void
    {
        $actual = $this->generator->generateSearchIndex([
            'http-response' => [
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'http/response',
                    'fnSignature' => '',
                    'desc' => '',
                ]),
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'http/response?',
                    'fnSignature' => '',
                    'desc' => '',
                ]),
            ],
        ]);

        $expected = [
            [
                'fnName' => 'http/response',
                'fnSignature' => '',
                'desc' => '',
                'anchor' => 'http-response',
            ],
            [
                'fnName' => 'http/response?',
                'fnSignature' => '',
                'desc' => '',
                'anchor' => 'http-response-1',
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_fn_name_ending_with_minus(): void
    {
        $actual = $this->generator->generateSearchIndex([
            'defn' => [
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'defn',
                    'fnSignature' => '',
                    'desc' => '',
                ]),
                    NormalizedPhelFunction::fromArray([
                    'fnName' => 'defn-',
                    'fnSignature' => '',
                    'desc' => '',
                ]),
            ],
        ]);

        $expected = [
            [
                'fnName' => 'defn',
                'fnSignature' => '',
                'desc' => '',
                'anchor' => 'defn',
            ],
            [
                'fnName' => 'defn-',
                'fnSignature' => '',
                'desc' => '',
                'anchor' => 'defn-1',
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_fn_name_with_upper_case(): void
    {
        $actual = $this->generator->generateSearchIndex([
            'nan' => [
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'NAN',
                    'fnSignature' => '',
                    'desc' => '',
                ]),
                NormalizedPhelFunction::fromArray([
                    'fnName' => 'nan?',
                    'fnSignature' => '',
                    'desc' => '',
                ]),
            ],
        ]);

        $expected = [
            [
                'fnName' => 'NAN',
                'fnSignature' => '',
                'desc' => '',
                'anchor' => 'nan',
            ],
            [
                'fnName' => 'nan?',
                'fnSignature' => '',
                'desc' => '',
                'anchor' => 'nan-1',
            ],
        ];

        self::assertEquals($expected, $actual);
    }
}
