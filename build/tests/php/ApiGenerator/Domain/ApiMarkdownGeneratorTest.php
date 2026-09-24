<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Domain;

use Phel\Shared\Api\PhelFunction;
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
                '0 namespaces and 0 functions, grouped by area.',
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

    public function test_index_groups_namespaces_by_category_in_curated_order(): void
    {
        $index = $this->generateIndex(['string', 'json', 'core', 'brand-new']);

        self::assertContains('4 namespaces and 4 functions, grouped by area.', $index);
        $headings = array_values(array_filter($index, static fn (string $l): bool => str_starts_with($l, '<h2 ')));
        self::assertSame(
            [
                '<h2 id="core-language">Core language</h2>',
                '<h2 id="data-formats">Data formats</h2>',
                '<h2 id="other">Other</h2>',
            ],
            $headings,
        );

        $html = implode("\n", $index);
        self::assertLessThan(strpos($html, '/api/string/'), strpos($html, '/api/core/'));
        self::assertStringContainsString('<a href="#other">Other</a>', $html);
    }

    public function test_index_card_carries_curated_description_or_count_only(): void
    {
        $html = implode("\n", $this->generateIndex(['json', 'brand-new']));

        self::assertStringContainsString(
            '<a class="api-ns-card__link" href="/documentation/reference/api/json/"><span class="api-ns-card__head"><span class="api-namespace-grid__name">json</span><span class="api-namespace-grid__count">1</span></span><span class="api-ns-card__desc">Encode and decode JSON.</span></a>',
            $html,
        );
        self::assertStringContainsString(
            '<a class="api-ns-card__link" href="/documentation/reference/api/brand-new/"><span class="api-ns-card__head"><span class="api-namespace-grid__name">brand-new</span><span class="api-namespace-grid__count">1</span></span></a>',
            $html,
        );
    }

    public function test_index_nests_sub_namespaces_under_their_parent_card(): void
    {
        $index = $this->generateIndex(['test.gen', 'test', 'mock', 'orphan.child']);
        $html = implode("\n", $index);

        self::assertSame(1, substr_count($html, 'class="api-ns-card__link" href="/documentation/reference/api/test/"'));
        self::assertStringNotContainsString('class="api-ns-card__link" href="/documentation/reference/api/test-gen/"', $html);
        self::assertStringContainsString(
            '<li><a href="/documentation/reference/api/test-gen/"><code>test.gen</code><span class="api-ns-card__sub-count">1</span></a></li>',
            $html,
        );
        // No `orphan` parent, so the child keeps a card of its own.
        self::assertStringContainsString('class="api-ns-card__link" href="/documentation/reference/api/orphan-child/"', $html);
    }

    public function test_index_ends_with_json_note(): void
    {
        $index = $this->generateIndex(['core']);

        self::assertSame(
            '<p class="api-index-json">The full API is also available as JSON at <a href="/api.json"><code>/api.json</code></a>.</p>',
            $index[count($index) - 2],
        );
    }

    public function test_namespace_file_carries_curated_description(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray(['name' => 'encode', 'doc' => '', 'namespace' => 'json']),
            ]);

        $files = (new ApiMarkdownGenerator($apiFacade))->generate();

        self::assertSame('description = "Encode and decode JSON."', $files['json'][2]);
    }

    public function test_deprecation_notice_uses_themed_class_not_inline_color(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'old-fn',
                    'doc' => '',
                    'namespace' => 'ns-1',
                    'meta' => ['deprecated' => '0.9'],
                ]),
            ]);

        $files = (new ApiMarkdownGenerator($apiFacade))->generate();

        self::assertContains('<small><span class="api-deprecated">Deprecated</span>: 0.9</small>', $files['ns-1']);
    }

    /**
     * @param list<string> $namespaces one function each
     * @return list<string>
     */
    private function generateIndex(array $namespaces): array
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn(array_map(
                static fn (string $ns): PhelFunction => PhelFunction::fromArray([
                    'name' => 'fn',
                    'doc' => '',
                    'namespace' => $ns,
                ]),
                $namespaces,
            ));

        return (new ApiMarkdownGenerator($apiFacade))->generate()[ApiMarkdownGenerator::INDEX_KEY];
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
