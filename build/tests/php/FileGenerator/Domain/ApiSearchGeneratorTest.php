<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator\Domain;

use Phel\Api\Transfer\PhelFunction;
use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelDocBuild\FileGenerator\Domain\PhelFunctionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ApiSearchGeneratorTest extends TestCase
{
    public function test_generate_search_index_one_item(): void
    {
        $repository = $this->createStub(PhelFunctionRepositoryInterface::class);
        $repository->method('getAllGroupedFunctions')
            ->willReturn([
                'table' => [
                    PhelFunction::fromArray([
                        'fnName' => 'table?',
                        'fnSignature' => '(table? x)',
                        'desc' => 'doc for table?',
                    ]),
                ],
            ]);

        $generator = new ApiSearchGenerator($repository);
        $actual = $generator->generateSearchIndex();

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
        $repository = $this->createStub(PhelFunctionRepositoryInterface::class);
        $repository->method('getAllGroupedFunctions')
            ->willReturn([
                'table' => [
                    PhelFunction::fromArray([
                        'fnName' => 'table',
                        'fnSignature' => '(table & xs)',
                        'desc' => 'doc for table',
                    ]),
                ],
                'not' => [
                    PhelFunction::fromArray([
                        'fnName' => 'not',
                        'fnSignature' => '(not x)',
                        'desc' => 'doc for not',
                    ]),
                ],
            ]);

        $generator = new ApiSearchGenerator($repository);
        $actual = $generator->generateSearchIndex();

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
        $repository = $this->createStub(PhelFunctionRepositoryInterface::class);
        $repository->method('getAllGroupedFunctions')
            ->willReturn([
                'table' => [
                    PhelFunction::fromArray([
                        'fnName' => 'table',
                        'fnSignature' => '(table & xs)',
                        'desc' => 'doc for table',
                    ]),
                    PhelFunction::fromArray([
                        'fnName' => 'table?',
                        'fnSignature' => '(table? x)',
                        'desc' => 'doc for table?',
                    ]),
                ],
            ]);

        $generator = new ApiSearchGenerator($repository);
        $actual = $generator->generateSearchIndex();

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
        $repository = $this->createStub(PhelFunctionRepositoryInterface::class);
        $repository->method('getAllGroupedFunctions')
            ->willReturn([
                'http-response' => [
                    PhelFunction::fromArray([
                        'fnName' => 'http/response',
                        'fnSignature' => '',
                        'desc' => '',
                    ]),
                    PhelFunction::fromArray([
                        'fnName' => 'http/response?',
                        'fnSignature' => '',
                        'desc' => '',
                    ]),
                ],
            ]);

        $generator = new ApiSearchGenerator($repository);
        $actual = $generator->generateSearchIndex();

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
        $repository = $this->createStub(PhelFunctionRepositoryInterface::class);
        $repository->method('getAllGroupedFunctions')
            ->willReturn([
                'defn' => [
                    PhelFunction::fromArray([
                        'fnName' => 'defn',
                        'fnSignature' => '',
                        'desc' => '',
                    ]),
                    PhelFunction::fromArray([
                        'fnName' => 'defn-',
                        'fnSignature' => '',
                        'desc' => '',
                    ]),
                ],
            ]);

        $generator = new ApiSearchGenerator($repository);
        $actual = $generator->generateSearchIndex();

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
        $repository = $this->createStub(PhelFunctionRepositoryInterface::class);
        $repository->method('getAllGroupedFunctions')
            ->willReturn([
                'nan' => [
                    PhelFunction::fromArray([
                        'fnName' => 'NAN',
                        'fnSignature' => '',
                        'desc' => '',
                    ]),
                    PhelFunction::fromArray([
                        'fnName' => 'nan?',
                        'fnSignature' => '',
                        'desc' => '',
                    ]),
                ],
            ]);

        $generator = new ApiSearchGenerator($repository);
        $actual = $generator->generateSearchIndex();

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
