<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Domain;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;
use PHPUnit\Framework\TestCase;
use PhelWeb\ApiGenerator\Application\ApiMarkdownGenerator;

final class ApiMarkdownGeneratorTest extends TestCase
{
    public function test_generate_page_without_phel_functions(): void
    {
        $generator = new ApiMarkdownGenerator(
            $this->createStub(ApiFacadeInterface::class)
        );

        $expected = [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            'aliases = ["/api", "/documentation/api"]',
            '+++',
            '',
            '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
            '',
        ];

        self::assertEquals($expected, $generator->generate());
    }

    public function test_generate_page_with_one_phel_function(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'function-1',
                    'doc' => 'The doc from function 1',
                    'groupKey' => 'group-1',
                    'namespace' => 'ns-1',
                ]),
            ]);

        $generator = new ApiMarkdownGenerator($apiFacade);

        $expected = [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            'aliases = ["/api", "/documentation/api"]',
            '+++',
            '',
            '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
            '',
            '',
            '---',
            '',
            '## `ns-1`',
            '',
            '### `ns-1/function-1`',
            'The doc from function 1',
            '',
        ];

        self::assertEquals($expected, $generator->generate());
    }

    public function test_generate_page_with_multiple_phel_functions_in_same_group(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'function-1',
                    'doc' => 'The doc from function 1',
                    'namespace' => 'core',
                ]),
                PhelFunction::fromArray([
                    'name' => 'function-2',
                    'doc' => 'The doc from function 2',
                    'namespace' => 'core',
                ]),
            ]);

        $generator = new ApiMarkdownGenerator($apiFacade);

        $expected = [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            'aliases = ["/api", "/documentation/api"]',
            '+++',
            '',
            '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
            '',
            '',
            '---',
            '',
            '## `core`',
            '',
            '### `function-1`',
            'The doc from function 1',
            '',
            '### `function-2`',
            'The doc from function 2',
            '',
        ];

        self::assertEquals($expected, $generator->generate());
    }

    public function test_generate_page_with_multiple_phel_functions_in_different_groups(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'function-1',
                    'doc' => 'The doc from function 1',
                    'namespace' => 'ns-1',
                ]),
                PhelFunction::fromArray([
                    'name' => 'function-2',
                    'doc' => 'The doc from function 2',
                    'namespace' => 'ns-2',
                ]),
            ]);

        $generator = new ApiMarkdownGenerator($apiFacade);

        $expected = [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            'aliases = ["/api", "/documentation/api"]',
            '+++',
            '',
            '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
            '',
            '',
            '---',
            '',
            '## `ns-1`',
            '',
            '### `ns-1/function-1`',
            'The doc from function 1',
            '',
            '',
            '---',
            '',
            '## `ns-2`',
            '',
            '### `ns-2/function-2`',
            'The doc from function 2',
            '',
        ];

        self::assertEquals($expected, $generator->generate());
    }

    public function test_generate_page_with_see_also_section_in_footer(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'function-1',
                    'doc' => 'The doc from function 1',
                    'namespace' => 'core',
                    'githubUrl' => 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L100',
                    'meta' => [
                        'see-also' => new \ArrayIterator([
                            \Phel\Lang\Symbol::create('function-2'),
                        ]),
                    ],
                ]),
                PhelFunction::fromArray([
                    'name' => 'function-2',
                    'doc' => 'The doc from function 2',
                    'namespace' => 'core',
                    'githubUrl' => 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L200',
                ]),
            ]);

        $generator = new ApiMarkdownGenerator($apiFacade);

        $expected = [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            'aliases = ["/api", "/documentation/api"]',
            '+++',
            '',
            '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
            '',
            '',
            '---',
            '',
            '## `core`',
            '',
            '### `function-1`',
            'The doc from function 1',
            '',
            '<div class="api-footer">',
            '<div><strong>See also:</strong> [`function-2`](#function-2)</div>',
            '<div><a href="https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L100">View source</a></div>',
            '</div>',
            '',
            '',
            '### `function-2`',
            'The doc from function 2',
            '',
            '<div class="api-footer">',
            '<div><a href="https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L200">View source</a></div>',
            '</div>',
            '',
            '',
        ];

        self::assertEquals($expected, $generator->generate());
    }

    public function test_generate_page_with_multiple_see_also_references_in_footer(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'assoc-in',
                    'doc' => 'Associates a value in a nested associative structure',
                    'namespace' => 'core',
                    'githubUrl' => 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L1403',
                    'meta' => [
                        'see-also' => new \ArrayIterator([
                            \Phel\Lang\Symbol::create('get-in'),
                            \Phel\Lang\Symbol::create('update-in'),
                            \Phel\Lang\Symbol::create('dissoc-in'),
                        ]),
                    ],
                ]),
                PhelFunction::fromArray([
                    'name' => 'get-in',
                    'doc' => 'Gets a value from a nested structure',
                    'namespace' => 'core',
                    'githubUrl' => 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L1350',
                ]),
                PhelFunction::fromArray([
                    'name' => 'update-in',
                    'doc' => 'Updates a value in a nested structure',
                    'namespace' => 'core',
                    'githubUrl' => 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L1420',
                ]),
                PhelFunction::fromArray([
                    'name' => 'dissoc-in',
                    'doc' => 'Dissociates a value in a nested structure',
                    'namespace' => 'core',
                    'githubUrl' => 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L1440',
                ]),
            ]);

        $generator = new ApiMarkdownGenerator($apiFacade);

        $output = $generator->generate();

        // Find the "See also" line for assoc-in (it should be in the api-footer now)
        $seeAlsoLine = array_values(array_filter($output, fn($line) => str_starts_with($line, '<div><strong>See also:</strong>')))[0];

        // The see-also section should NOT include individual source links
        self::assertStringContainsString('[`get-in`](#get-in)', $seeAlsoLine);
        self::assertStringContainsString('[`update-in`](#update-in)', $seeAlsoLine);
        self::assertStringContainsString('[`dissoc-in`](#dissoc-in)', $seeAlsoLine);

        // Make sure there are no individual source links in the see-also line
        self::assertStringNotContainsString('href=', $seeAlsoLine);
    }

    public function test_generate_page_with_source_link_only_in_footer(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'simple-func',
                    'doc' => 'A simple function with source link',
                    'namespace' => 'core',
                    'githubUrl' => 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L500',
                ]),
            ]);

        $generator = new ApiMarkdownGenerator($apiFacade);

        $expected = [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            'aliases = ["/api", "/documentation/api"]',
            '+++',
            '',
            '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
            '',
            '',
            '---',
            '',
            '## `core`',
            '',
            '### `simple-func`',
            'A simple function with source link',
            '',
            '<div class="api-footer">',
            '<div><a href="https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L500">View source</a></div>',
            '</div>',
            '',
            '',
        ];

        self::assertEquals($expected, $generator->generate());
    }
}
