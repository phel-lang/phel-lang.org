<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator\Domain;

use Phel\Api\ApiFacadeInterface;
use Phel\Api\Transfer\NormalizedPhelFunction;
use PhelDocBuild\FileGenerator\Domain\ApiMarkdownGenerator;
use PHPUnit\Framework\TestCase;

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
        ];

        self::assertEquals($expected, $generator->generate());
    }

    public function test_generate_page_with_one_phel_function(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getNormalizedGroupedFunctions')
            ->willReturn([
                'group-1' => [
                    NormalizedPhelFunction::fromArray([
                        'fnName' => 'function-1',
                        'doc' => 'The doc from function 1',
                    ]),
                ],
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
            '## `function-1`',
            'The doc from function 1',
        ];

        self::assertEquals($expected, $generator->generate());
    }

    public function test_generate_page_with_multiple_phel_functions_in_same_group(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getNormalizedGroupedFunctions')
            ->willReturn([
                'group-1' => [
                    NormalizedPhelFunction::fromArray([
                        'fnName' => 'function-1',
                        'doc' => 'The doc from function 1',
                    ]),
                    NormalizedPhelFunction::fromArray([
                        'fnName' => 'function-2',
                        'doc' => 'The doc from function 2',
                    ]),
                ],
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
            '## `function-1`',
            'The doc from function 1',
            '## `function-2`',
            'The doc from function 2',
        ];

        self::assertEquals($expected, $generator->generate());
    }

    public function test_generate_page_with_multiple_phel_functions_in_different_groups(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getNormalizedGroupedFunctions')
            ->willReturn([
                'group-1' => [
                    NormalizedPhelFunction::fromArray([
                        'fnName' => 'function-1',
                        'doc' => 'The doc from function 1',
                    ]),
                ],
                'group-2' => [
                    NormalizedPhelFunction::fromArray([
                        'fnName' => 'function-2',
                        'doc' => 'The doc from function 2',
                    ]),
                ],
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
            '## `function-1`',
            'The doc from function 1',
            '## `function-2`',
            'The doc from function 2',
        ];

        self::assertEquals($expected, $generator->generate());
    }
}
