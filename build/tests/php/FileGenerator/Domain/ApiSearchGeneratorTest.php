<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator\Domain;

use Phel\Api\ApiFacadeInterface;
use Phel\Api\Transfer\PhelFunction;
use PhelDocBuild\FileGenerator\Application\ApiSearchGenerator;
use PHPUnit\Framework\TestCase;

final class ApiSearchGeneratorTest extends TestCase
{
    public function test_generate_search_index_one_item(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'fnName' => 'table?',
                    'fnSignature' => '(table? x)',
                    'desc' => 'doc for table?',
                    'groupKey' => 'table'
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
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
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'fnName' => 'table',
                    'fnSignature' => '(table & xs)',
                    'desc' => 'doc for table',
                    'groupKey' => 'table',
                ]),
                PhelFunction::fromArray([
                    'fnName' => 'not',
                    'fnSignature' => '(not x)',
                    'desc' => 'doc for not',
                    'groupKey' => 'not',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
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
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'fnName' => 'table',
                    'fnSignature' => '(table & xs)',
                    'desc' => 'doc for table',
                    'groupKey' => 'table',
                ]),
                PhelFunction::fromArray([
                    'fnName' => 'table?',
                    'fnSignature' => '(table? x)',
                    'desc' => 'doc for table?',
                    'groupKey' => 'table',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
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
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'fnName' => 'http/response',
                    'fnSignature' => '',
                    'desc' => '',
                    'groupKey' => 'http-response',
                ]),
                PhelFunction::fromArray([
                    'fnName' => 'http/response?',
                    'fnSignature' => '',
                    'desc' => '',
                    'groupKey' => 'http-response-1',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
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
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'fnName' => 'defn',
                    'fnSignature' => '',
                    'desc' => '',
                    'groupKey' => 'defn',
                ]),
                PhelFunction::fromArray([
                    'fnName' => 'defn-',
                    'fnSignature' => '',
                    'desc' => '',
                    'groupKey' => 'defn',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
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
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'fnName' => 'NAN',
                    'fnSignature' => '',
                    'desc' => '',
                    'groupKey' => 'nan',
                ]),
                PhelFunction::fromArray([
                    'fnName' => 'nan?',
                    'fnSignature' => '',
                    'desc' => '',
                    'groupKey' => 'nan',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
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
