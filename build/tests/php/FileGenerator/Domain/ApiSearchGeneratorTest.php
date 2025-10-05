<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Domain;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;
use PhelWeb\FileGenerator\Application\ApiSearchGenerator;
use PHPUnit\Framework\TestCase;

final class ApiSearchGeneratorTest extends TestCase
{
    public function test_generate_search_index_one_item(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'table?',
                    'signature' => '(table? x)',
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
                'signature' => '(table? x)',
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

    public function test_multiple_items_in_different_groups(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'table',
                    'signature' => '(table & xs)',
                    'desc' => 'doc for table',
                    'groupKey' => 'table',
                ]),
                PhelFunction::fromArray([
                    'name' => 'not',
                    'signature' => '(not x)',
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
                'signature' => '(table & xs)',
                'desc' => 'doc for table',
                'anchor' => 'table',
                'type' => 'api',
            ],
            [
                'id' => 'api_not',
                'name' => 'not',
                'signature' => '(not x)',
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
                    'signature' => '(table & xs)',
                    'desc' => 'doc for table',
                    'groupKey' => 'table',
                ]),
                PhelFunction::fromArray([
                    'name' => 'table?',
                    'signature' => '(table? x)',
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
                'signature' => '(table & xs)',
                'desc' => 'doc for table',
                'anchor' => 'table',
                'type' => 'api',
            ],
            [
                'id' => 'api_table?',
                'name' => 'table?',
                'signature' => '(table? x)',
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
                    'signature' => '',
                    'desc' => '',
                    'groupKey' => 'http-response',
                ]),
                PhelFunction::fromArray([
                    'name' => 'http/response?',
                    'signature' => '',
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
                'signature' => '',
                'desc' => '',
                'anchor' => 'http-response',
                'type' => 'api',
            ],
            [
                'id' => 'api_http/response?',
                'name' => 'http/response?',
                'signature' => '',
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
                    'signature' => '',
                    'desc' => '',
                    'groupKey' => 'defn',
                ]),
                PhelFunction::fromArray([
                    'name' => 'defn-',
                    'signature' => '',
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
                'signature' => '',
                'desc' => '',
                'anchor' => 'defn',
                'type' => 'api',
            ],
            [
                'id' => 'api_defn-',
                'name' => 'defn-',
                'signature' => '',
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
                    'signature' => '',
                    'desc' => '',
                    'groupKey' => 'nan',
                ]),
                PhelFunction::fromArray([
                    'name' => 'nan?',
                    'signature' => '',
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
                'signature' => '',
                'desc' => '',
                'anchor' => 'nan',
                'type' => 'api',
            ],
            [
                'id' => 'api_nan?',
                'name' => 'nan?',
                'signature' => '',
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
}
