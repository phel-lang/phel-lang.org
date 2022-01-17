<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator;

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
        $groupNormalizedData = [
            'table' => [
                [
                    'fnName' => 'table?',
                    'fnSignature' => '(table? x)',
                    'desc' => 'doc for table?',
                ],
            ],
        ];

        $actual = $this->generator->generateSearchIndex($groupNormalizedData);

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
        $groupNormalizedData = [
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
        ];

        $actual = $this->generator->generateSearchIndex($groupNormalizedData);

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
        $groupNormalizedData = [
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
        ];

        $actual = $this->generator->generateSearchIndex($groupNormalizedData);

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

    public function test_multiple_items_in_the_same_group_with_special_chars_in_fn_signature(): void
    {
        $groupNormalizedData = [
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
        ];

        $actual = $this->generator->generateSearchIndex($groupNormalizedData);

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
}
