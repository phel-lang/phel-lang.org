<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator\Domain;

use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
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
                [
                    'fnName' => 'table?',
                    'fnSignature' => '(table? x)',
                    'desc' => 'doc for table?',
                ],
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
                [
                    'fnName' => 'table',
                    'fnSignature' => '(table & xs)',
                    'desc' => 'doc for table',
                ],
            ],
            'not' => [
                [
                    'fnName' => 'not',
                    'fnSignature' => '(not x)',
                    'desc' => 'doc for not',
                ],
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
                [
                    'fnName' => 'table',
                    'fnSignature' => '(table & xs)',
                    'desc' => 'doc for table',
                ],
                [
                    'fnName' => 'table?',
                    'fnSignature' => '(table? x)',
                    'desc' => 'doc for table?',
                ],
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
                [
                    'fnName' => 'http/response',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'http/response?',
                    'fnSignature' => '',
                    'desc' => '',
                ],
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
                [
                    'fnName' => 'defn',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'defn-',
                    'fnSignature' => '',
                    'desc' => '',
                ],
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
                [
                    'fnName' => 'NAN',
                    'fnSignature' => '',
                    'desc' => '',
                ],
                [
                    'fnName' => 'nan?',
                    'fnSignature' => '',
                    'desc' => '',
                ],
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
