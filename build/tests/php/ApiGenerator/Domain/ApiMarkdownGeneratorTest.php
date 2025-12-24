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
            'aliases = [ "/api" ]',
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
            'aliases = [ "/api" ]',
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
            'aliases = [ "/api" ]',
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
            'aliases = [ "/api" ]',
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
}
