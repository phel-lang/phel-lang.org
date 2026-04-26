<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Domain;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;
use PHPUnit\Framework\TestCase;
use PhelWeb\ApiGenerator\Application\ApiMarkdownGenerator;

final class ApiMarkdownGeneratorTest extends TestCase
{
    public function test_generate_without_phel_functions_returns_only_index(): void
    {
        $generator = new ApiMarkdownGenerator(
            $this->createStub(ApiFacadeInterface::class)
        );

        $files = $generator->generate();

        self::assertSame([ApiMarkdownGenerator::INDEX_KEY], array_keys($files));
        self::assertSame(
            [
                '+++',
                'title = "API"',
                'description = "Browse all built-in Phel namespaces and functions."',
                'weight = 110',
                'template = "page-api-index.html"',
                'sort_by = "title"',
                'insert_anchor_links = "right"',
                'aliases = ["/api", "/documentation/api"]',
                '+++',
                '',
                '> **Tip:** This documentation is also available in JSON format at [`/api.json`](/api.json).',
                '',
                'Browse the API by namespace:',
                '',
                '<ul class="api-namespace-grid">',
                '</ul>',
                '',
            ],
            $files[ApiMarkdownGenerator::INDEX_KEY],
        );
    }

    public function test_generate_with_one_phel_function_emits_namespace_file(): void
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

        $files = $generator->generate();

        self::assertContains('ns-1', array_keys($files));
        self::assertSame(
            [
                '+++',
                'title = "ns-1"',
                'template = "page-api-namespace.html"',
                '',
                '[extra]',
                'fn_count = 1',
                'namespace = "ns-1"',
                '+++',
                '',
                '### `ns-1/function-1`',
                'The doc from function 1',
                '',
            ],
            $files['ns-1'],
        );
    }

    public function test_generate_groups_functions_per_namespace(): void
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
        $files = $generator->generate();

        self::assertContains('core', array_keys($files));
        $core = $files['core'];

        self::assertSame('+++', $core[0]);
        self::assertSame('title = "core"', $core[1]);
        self::assertContains('### `function-1`', $core);
        self::assertContains('### `function-2`', $core);
    }

    public function test_generate_emits_one_file_per_namespace(): void
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
        $files = $generator->generate();

        self::assertSame(
            [ApiMarkdownGenerator::INDEX_KEY, 'ns-1', 'ns-2'],
            array_keys($files),
        );
    }

    public function test_see_also_link_within_namespace_uses_relative_anchor(): void
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
        $files = $generator->generate();
        $core = $files['core'];

        $seeAlso = array_values(array_filter(
            $core,
            static fn (string $line) => str_starts_with($line, '<div><strong>See also:</strong>'),
        ))[0];

        self::assertStringContainsString('<a href="#function-2"><code>function-2</code></a>', $seeAlso);
    }

    public function test_see_also_link_across_namespaces_uses_absolute_path(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'function-1',
                    'doc' => 'The doc from function 1',
                    'namespace' => 'core',
                    'meta' => [
                        'see-also' => new \ArrayIterator([
                            \Phel\Lang\Symbol::create('function-2'),
                        ]),
                    ],
                ]),
                PhelFunction::fromArray([
                    'name' => 'function-2',
                    'doc' => 'The doc from function 2',
                    'namespace' => 'http',
                ]),
            ]);

        $generator = new ApiMarkdownGenerator($apiFacade);
        $files = $generator->generate();
        $core = $files['core'];

        $seeAlso = array_values(array_filter(
            $core,
            static fn (string $line) => str_starts_with($line, '<div><strong>See also:</strong>'),
        ))[0];

        self::assertStringContainsString(
            '<a href="/documentation/reference/api/http/#http-function-2"><code>function-2</code></a>',
            $seeAlso,
        );
    }

    public function test_namespace_slug_replaces_backslash_with_dash(): void
    {
        $generator = new ApiMarkdownGenerator($this->createStub(ApiFacadeInterface::class));

        self::assertSame('schema-coercer', $generator->namespaceSlug('schema\\coercer'));
        self::assertSame('test-gen', $generator->namespaceSlug('test\\gen'));
        self::assertSame('http-client', $generator->namespaceSlug('http_client'));
        self::assertSame('core', $generator->namespaceSlug('core'));
    }
}
