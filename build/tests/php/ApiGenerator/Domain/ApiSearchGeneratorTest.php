<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Domain;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;
use PHPUnit\Framework\TestCase;
use PhelWeb\ApiGenerator\Application\ApiMarkdownGenerator;
use PhelWeb\ApiGenerator\Application\ApiSearchGenerator;

final class ApiSearchGeneratorTest extends TestCase
{
    private function generator(ApiFacadeInterface $apiFacade): ApiSearchGenerator
    {
        return new ApiSearchGenerator($apiFacade, new ApiMarkdownGenerator($apiFacade));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function apiOnly(array $items): array
    {
        $apiItems = array_filter($items, static fn ($item) => $item['type'] === 'api');
        return array_values($apiItems);
    }

    public function test_anchor_for_simple_function(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'table?',
                    'signatures' => ['(table? x)'],
                    'desc' => 'doc for table?',
                    'namespace' => 'core',
                ]),
            ]);

        $apiItems = $this->apiOnly($this->generator($apiFacade)->generateSearchIndex());

        self::assertCount(1, $apiItems);
        self::assertSame('core', $apiItems[0]['namespace']);
        self::assertSame('table', $apiItems[0]['anchor']);
        self::assertSame('/documentation/reference/api/core/#table', $apiItems[0]['path']);
    }

    public function test_collision_disambiguation_uses_dash_counter(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'table',
                    'desc' => 'doc for table',
                    'namespace' => 'core',
                ]),
                PhelFunction::fromArray([
                    'name' => 'table?',
                    'desc' => 'doc for table?',
                    'namespace' => 'core',
                ]),
            ]);

        $apiItems = $this->apiOnly($this->generator($apiFacade)->generateSearchIndex());

        self::assertSame('table', $apiItems[0]['anchor']);
        self::assertSame('table-1', $apiItems[1]['anchor']);
        self::assertSame('/documentation/reference/api/core/#table', $apiItems[0]['path']);
        self::assertSame('/documentation/reference/api/core/#table-1', $apiItems[1]['path']);
    }

    public function test_anchor_for_namespaced_function_includes_namespace(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'build-index',
                    'desc' => '',
                    'namespace' => 'ai',
                ]),
            ]);

        $apiItems = $this->apiOnly($this->generator($apiFacade)->generateSearchIndex());

        // Heading is `ai/build-index`; Zola anchor: ai-build-index.
        self::assertSame('ai-build-index', $apiItems[0]['anchor']);
        self::assertSame('ai', $apiItems[0]['namespace']);
        self::assertSame('/documentation/reference/api/ai/#ai-build-index', $apiItems[0]['path']);
    }

    public function test_namespace_with_backslash_slugs_to_dashes(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'coerce',
                    'desc' => '',
                    'namespace' => 'schema\\coercer',
                ]),
            ]);

        $apiItems = $this->apiOnly($this->generator($apiFacade)->generateSearchIndex());

        self::assertSame('schema\\coercer', $apiItems[0]['namespace']);
        self::assertSame('schema-coercer-coerce', $apiItems[0]['anchor']);
        self::assertSame(
            '/documentation/reference/api/schema-coercer/#schema-coercer-coerce',
            $apiItems[0]['path'],
        );
    }

    public function test_underscore_namespace_slugs_to_dash(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'get',
                    'desc' => '',
                    'namespace' => 'http_client',
                ]),
            ]);

        $apiItems = $this->apiOnly($this->generator($apiFacade)->generateSearchIndex());

        // URL slug uses dashes, anchor is derived from heading text (also dashed).
        self::assertSame('http_client', $apiItems[0]['namespace']);
        self::assertSame('http-client-get', $apiItems[0]['anchor']);
        self::assertSame('/documentation/reference/api/http-client/#http-client-get', $apiItems[0]['path']);
    }

    public function test_multi_arity_signatures_are_preserved(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                PhelFunction::fromArray([
                    'name' => 'conj',
                    'signatures' => ['(conj coll x)', '(conj coll x & xs)'],
                    'desc' => 'Adds elements to a collection.',
                    'namespace' => 'core',
                ]),
            ]);

        $apiItems = $this->apiOnly($this->generator($apiFacade)->generateSearchIndex());

        self::assertCount(1, $apiItems);
        self::assertSame(['(conj coll x)', '(conj coll x & xs)'], $apiItems[0]['signatures']);
        self::assertSame('/documentation/reference/api/core/#conj', $apiItems[0]['path']);
    }
}
