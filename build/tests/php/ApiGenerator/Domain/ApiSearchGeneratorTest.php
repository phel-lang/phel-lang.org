<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Domain;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;
use PHPUnit\Framework\TestCase;
use PhelWeb\ApiGenerator\Application\ApiSearchGenerator;

final class ApiSearchGeneratorTest extends TestCase
{
    public function test_generate_search_index_one_item(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'table?',
                    'signatures' => ['(table? x)'],
                    'desc' => 'doc for table?',
                    'groupKey' => 'table'
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
        $actual = $generator->generateSearchIndex();

        $expected = [
            [
                'id' => 'api_table?',
                'name' => 'table?',
                'signatures' => ['(table? x)'],
                'desc' => 'doc for table?',
                'anchor' => 'table',
                'type' => 'api',
            ],
        ];

        // Filter out documentation items for this test
        $apiItems = array_filter($actual, fn($item) => $item['type'] === 'api');
        $apiItems = array_values($apiItems); // Re-index array

        self::assertEquals($expected, $apiItems);
    }

    public function test_generate_search_index_one_item_and_multiple_signatures(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'memoize-lru',
                    'signatures' => ['(memoize-lru f)', '(memoize-lru f max-size)'],
                    'desc' => 'doc for memoize-lru',
                    'groupKey' => 'memoize'
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
        $actual = $generator->generateSearchIndex();

        $expected = [
            [
                'id' => 'api_memoize-lru',
                'name' => 'memoize-lru',
                'signatures' => ['(memoize-lru f)', '(memoize-lru f max-size)'],
                'desc' => 'doc for memoize-lru',
                'anchor' => 'memoize',
                'type' => 'api',
            ],
        ];

        // Filter out documentation items for this test
        $apiItems = array_filter($actual, fn($item) => $item['type'] === 'api');
        $apiItems = array_values($apiItems); // Re-index array

        self::assertEquals($expected, $apiItems);
    }

    public function test_multiple_items_in_different_groups(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'table',
                    'signatures' => ['(table & xs)'],
                    'desc' => 'doc for table',
                    'groupKey' => 'table',
                ]),
                PhelFunction::fromArray([
                    'name' => 'not',
                    'signatures' => ['(not x)'],
                    'desc' => 'doc for not',
                    'groupKey' => 'not',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
        $actual = $generator->generateSearchIndex();

        $expected = [
            [
                'id' => 'api_table',
                'name' => 'table',
                'signatures' => ['(table & xs)'],
                'desc' => 'doc for table',
                'anchor' => 'table',
                'type' => 'api',
            ],
            [
                'id' => 'api_not',
                'name' => 'not',
                'signatures' => ['(not x)'],
                'desc' => 'doc for not',
                'anchor' => 'not',
                'type' => 'api',
            ],
        ];

        // Filter out documentation items for this test
        $apiItems = array_filter($actual, fn($item) => $item['type'] === 'api');
        $apiItems = array_values($apiItems); // Re-index array

        self::assertEquals($expected, $apiItems);
    }

    public function test_multiple_items_in_the_same_group(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'table',
                    'signatures' => ['(table & xs)'],
                    'desc' => 'doc for table',
                    'groupKey' => 'table',
                ]),
                PhelFunction::fromArray([
                    'name' => 'table?',
                    'signatures' => ['(table? x)'],
                    'desc' => 'doc for table?',
                    'groupKey' => 'table',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
        $actual = $generator->generateSearchIndex();

        $expected = [
            [
                'id' => 'api_table',
                'name' => 'table',
                'signatures' => ['(table & xs)'],
                'desc' => 'doc for table',
                'anchor' => 'table',
                'type' => 'api',
            ],
            [
                'id' => 'api_table?',
                'name' => 'table?',
                'signatures' => ['(table? x)'],
                'desc' => 'doc for table?',
                'anchor' => 'table-1',
                'type' => 'api',
            ],
        ];

        // Filter out documentation items for this test
        $apiItems = array_filter($actual, fn($item) => $item['type'] === 'api');
        $apiItems = array_values($apiItems); // Re-index array

        self::assertEquals($expected, $apiItems);
    }

    public function test_fn_name_with_slash_in_the_middle(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'http/response',
                    'signatures' => [''],
                    'desc' => '',
                    'groupKey' => 'http-response',
                ]),
                PhelFunction::fromArray([
                    'name' => 'http/response?',
                    'signatures' => [''],
                    'desc' => '',
                    'groupKey' => 'http-response-1',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
        $actual = $generator->generateSearchIndex();

        $expected = [
            [
                'id' => 'api_http/response',
                'name' => 'http/response',
                'signatures' => [''],
                'desc' => '',
                'anchor' => 'http-response',
                'type' => 'api',
            ],
            [
                'id' => 'api_http/response?',
                'name' => 'http/response?',
                'signatures' => [''],
                'desc' => '',
                'anchor' => 'http-response-1',
                'type' => 'api',
            ],
        ];

        // Filter out documentation items for this test
        $apiItems = array_filter($actual, fn($item) => $item['type'] === 'api');
        $apiItems = array_values($apiItems); // Re-index array

        self::assertEquals($expected, $apiItems);
    }

    public function test_fn_name_ending_with_minus(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'defn',
                    'signatures' => [''],
                    'desc' => '',
                    'groupKey' => 'defn',
                ]),
                PhelFunction::fromArray([
                    'name' => 'defn-',
                    'signatures' => [''],
                    'desc' => '',
                    'groupKey' => 'defn',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
        $actual = $generator->generateSearchIndex();

        $expected = [
            [
                'id' => 'api_defn',
                'name' => 'defn',
                'signatures' => [''],
                'desc' => '',
                'anchor' => 'defn',
                'type' => 'api',
            ],
            [
                'id' => 'api_defn-',
                'name' => 'defn-',
                'signatures' => [''],
                'desc' => '',
                'anchor' => 'defn-1',
                'type' => 'api',
            ],
        ];

        // Filter out documentation items for this test
        $apiItems = array_filter($actual, fn($item) => $item['type'] === 'api');
        $apiItems = array_values($apiItems); // Re-index array

        self::assertEquals($expected, $apiItems);
    }

    public function test_fn_name_with_upper_case(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'NAN',
                    'signatures' => [''],
                    'desc' => '',
                    'groupKey' => 'nan',
                ]),
                PhelFunction::fromArray([
                    'name' => 'nan?',
                    'signatures' => [''],
                    'desc' => '',
                    'groupKey' => 'nan',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
        $actual = $generator->generateSearchIndex();

        $expected = [
            [
                'id' => 'api_NAN',
                'name' => 'NAN',
                'signatures' => [''],
                'desc' => '',
                'anchor' => 'nan',
                'type' => 'api',
            ],
            [
                'id' => 'api_nan?',
                'name' => 'nan?',
                'signatures' => [''],
                'desc' => '',
                'anchor' => 'nan-1',
                'type' => 'api',
            ],
        ];

        // Filter out documentation items for this test
        $apiItems = array_filter($actual, fn($item) => $item['type'] === 'api');
        $apiItems = array_values($apiItems); // Re-index array

        self::assertEquals($expected, $apiItems);
    }

    public function test_multi_arity_signature_is_comma_separated(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'conj',
                    'signatures' => ['(conj coll x)', '(conj coll x & xs)'],
                    'desc' => 'Adds elements to a collection.',
                    'groupKey' => 'conj',
                ]),
            ]);

        $generator = new ApiSearchGenerator($apiFacade);
        $actual = $generator->generateSearchIndex();

        // Filter out documentation items for this test
        $apiItems = array_filter($actual, fn($item) => $item['type'] === 'api');
        $apiItems = array_values($apiItems);

        self::assertCount(1, $apiItems);
        self::assertEquals(['(conj coll x)', '(conj coll x & xs)'], $apiItems[0]['signatures']);
    }
}
