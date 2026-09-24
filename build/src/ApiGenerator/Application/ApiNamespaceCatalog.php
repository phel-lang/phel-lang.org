<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Application;

/**
 * Curated grouping and one-line summaries for the API index. Phel `ns` forms
 * carry no docstrings, so the summaries live here, written from each
 * namespace's function docs.
 *
 * A namespace missing from CATEGORIES inherits its root namespace's category
 * (`schema.coercer` follows `schema`), otherwise lands in OTHER, so a new
 * namespace in a phel release always shows up on the index.
 */
final readonly class ApiNamespaceCatalog
{
    public const string OTHER = 'Other';

    /** @var array<string, list<string>> category => namespaces, in display order */
    private const array CATEGORIES = [
        'Core language' => ['core', 'string', 'php', 'match', 'walk', 'reader', 'async'],
        'Data formats' => ['json', 'edn', 'transit', 'base64'],
        'Web' => ['http', 'http_client', 'router', 'html'],
        'Testing' => ['test', 'mock', 'bench'],
        'Schema' => ['schema'],
        'Tooling' => ['repl', 'cli', 'pprint', 'trace', 'reflect', 'watch'],
        'AI' => ['ai'],
    ];

    /** @var array<string, string> */
    private const array DESCRIPTIONS = [
        'core' => 'The standard library: data structures, sequences, math, control flow, and macros.',
        'string' => 'String helpers: split, join, trim, pad, replace, and case conversion.',
        'php' => 'PHP interop: method calls, object creation, and PHP array access.',
        'match' => 'Pattern matching over a vector of targets.',
        'walk' => 'Generic tree walking, plus recursive map key conversion.',
        'reader' => 'Register handlers for custom reader tags.',
        'async' => 'A fiber-aware delay (sleep) for async code.',
        'json' => 'Encode and decode JSON.',
        'edn' => 'Read and write EDN strings.',
        'transit' => 'Read and write Transit+JSON-Verbose strings.',
        'base64' => 'Base64 encoding and decoding, including URL-safe Base64.',
        'http' => 'HTTP request and response values, built from PHP globals, and response emitting.',
        'http_client' => 'HTTP client for GET, POST, PUT, PATCH, DELETE, and HEAD requests.',
        'router' => 'Routing on Symfony Routing: match by path or name, generate URLs.',
        'html' => 'Compile Phel vectors to HTML, with escaping and raw strings.',
        'test' => 'Unit tests: deftest, is, testing, fixtures, and reporters.',
        'test.gen' => 'Generators and quick-check for property-based tests.',
        'test.rose' => 'Rose trees that drive shrinking of property-test values.',
        'test.selector' => 'Select tests by tag, namespace, and name.',
        'test.shrink' => 'Shrink failing property-test arguments.',
        'mock' => 'Mocks and spies that record their calls.',
        'bench' => 'Define and run benchmarks.',
        'schema' => 'Data schemas: validate, explain, coerce, generate data, and instrument functions.',
        'schema.coercer' => 'Coerce and conform values to a schema.',
        'schema.explainer' => 'Explain why a value does not match a schema.',
        'schema.generator' => 'Generate values from a schema.',
        'schema.instrument' => 'Wrap functions with schema checks.',
        'schema.registry' => 'Register and look up named schemas.',
        'schema.validator' => 'The core schema validation engine.',
        'repl' => 'REPL helpers: doc, source, apropos, namespace inspection, eval, and reload.',
        'cli' => 'Console apps on Symfony Console: commands, arguments, prompts, and styled output.',
        'pprint' => 'Pretty-print data structures.',
        'trace' => 'Trace function calls, printing arguments and results.',
        'reflect' => 'PHP reflection: classes, methods, properties, attributes, and enums.',
        'watch' => 'Watch files, reload changed namespaces, and run reload hooks.',
        'ai' => 'LLM client: chat, tool calls, structured extraction, and embeddings.',
    ];

    public function categoryOf(string $namespace): string
    {
        foreach ([$namespace, $this->rootOf($namespace)] as $candidate) {
            foreach (self::CATEGORIES as $category => $namespaces) {
                if (in_array($candidate, $namespaces, true)) {
                    return $category;
                }
            }
        }

        return self::OTHER;
    }

    public function descriptionOf(string $namespace): ?string
    {
        return self::DESCRIPTIONS[$namespace] ?? null;
    }

    /**
     * Groups namespaces by category, in display order. Empty categories are
     * dropped. Within a category, curated namespaces keep their curated order
     * and the rest follow alphabetically.
     *
     * @param list<string> $namespaces
     *
     * @return array<string, list<string>>
     */
    public function group(array $namespaces): array
    {
        $order = array_flip(array_merge(...array_values(self::CATEGORIES)));

        $grouped = [];
        foreach ([...array_keys(self::CATEGORIES), self::OTHER] as $category) {
            $members = array_values(array_filter(
                $namespaces,
                fn (string $ns): bool => $this->categoryOf($ns) === $category,
            ));
            if ($members === []) {
                continue;
            }

            usort($members, static fn (string $a, string $b): int => [
                $order[$a] ?? PHP_INT_MAX,
                $a,
            ] <=> [
                $order[$b] ?? PHP_INT_MAX,
                $b,
            ]);
            $grouped[$category] = $members;
        }

        return $grouped;
    }

    private function rootOf(string $namespace): string
    {
        return explode('.', $namespace, 2)[0];
    }
}
