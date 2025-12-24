<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Domain;

use Phel\Api\Transfer\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;
use PHPUnit\Framework\TestCase;
use PhelWeb\ApiGenerator\Infrastructure\ApiJsonFile;

final class ApiJsonFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phel-api-json-test-' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/build');
        mkdir($this->tempDir . '/static');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/static/*') ?: []);
            rmdir($this->tempDir . '/static');
            rmdir($this->tempDir . '/build');
            rmdir($this->tempDir);
        }
    }

    public function test_generates_json_file_with_one_function(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                new PhelFunction(
                    namespace: 'core',
                    name: 'inc',
                    doc: '```phel\n(inc x)\n```\nIncrements a number by 1.',
                    signatures: ['(inc x)'],
                    description: 'Increments a number by 1.',
                    githubUrl: 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/core.phel#L100',
                ),
            ]);

        $generator = new ApiJsonFile($apiFacade, $this->tempDir . '/build');
        $generator->generate();

        $jsonFile = $this->tempDir . '/static/api.json';
        self::assertFileExists($jsonFile);

        $content = json_decode(file_get_contents($jsonFile), true);

        self::assertIsArray($content);
        self::assertCount(1, $content);
        self::assertEquals('inc', $content[0]['name']);
        self::assertIsArray($content[0]['signatures']);
        self::assertEquals(['(inc x)'], $content[0]['signatures']);
        self::assertEquals('Increments a number by 1.', $content[0]['description']);
    }

    public function test_generates_json_with_namespace_in_function_name(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                new PhelFunction(
                    namespace: 'base64',
                    name: 'encode',
                    doc: '```phel\n(encode s)\n```\nEncodes a string to Base64.',
                    signatures: ['(encode s)'],
                    description: 'Encodes a string to Base64.',
                    githubUrl: 'https://github.com/phel-lang/phel-lang/blob/main/src/phel/base64.phel#L4',
                ),
            ]);

        $generator = new ApiJsonFile($apiFacade, $this->tempDir . '/build');
        $generator->generate();

        $jsonFile = $this->tempDir . '/static/api.json';
        $content = json_decode(file_get_contents($jsonFile), true);

        // Should combine namespace and name for non-core functions
        self::assertEquals('base64/encode', $content[0]['name']);
    }

    public function test_generates_json_with_multiple_functions(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                new PhelFunction(
                    namespace: 'core',
                    name: 'inc',
                    doc: 'Increments by 1',
                    signatures: ['(inc x)'],
                    description: 'Increments by 1',
                ),
                new PhelFunction(
                    namespace: 'core',
                    name: 'dec',
                    doc: 'Decrements by 1',
                    signatures: ['(dec x)'],
                    description: 'Decrements by 1',
                ),
                new PhelFunction(
                    namespace: 'string',
                    name: 'upper',
                    doc: 'Uppercase string',
                    signatures: ['(upper s)'],
                    description: 'Uppercase string',
                ),
            ]);

        $generator = new ApiJsonFile($apiFacade, $this->tempDir . '/build');
        $generator->generate();

        $jsonFile = $this->tempDir . '/static/api.json';
        $content = json_decode(file_get_contents($jsonFile), true);

        self::assertCount(3, $content);
        self::assertEquals('inc', $content[0]['name']);
        self::assertIsArray($content[0]['signatures']);
        self::assertEquals('dec', $content[1]['name']);
        self::assertIsArray($content[1]['signatures']);
        self::assertEquals('string/upper', $content[2]['name']);
        self::assertIsArray($content[2]['signatures']);
    }

    public function test_multi_arity_signatures_are_comma_separated(): void
    {
        $apiFacade = $this->createStub(ApiFacadeInterface::class);
        $apiFacade->method('getPhelFunctions')
            ->willReturn([
                new PhelFunction(
                    namespace: 'core',
                    name: 'conj',
                    doc: 'Adds elements to a collection.',
                    signatures: ['(conj coll x)', '(conj coll x & xs)'],
                    description: 'Adds elements to a collection.',
                ),
            ]);

        $generator = new ApiJsonFile($apiFacade, $this->tempDir . '/build');
        $generator->generate();

        $jsonFile = $this->tempDir . '/static/api.json';
        $content = json_decode(file_get_contents($jsonFile), true);

        self::assertCount(1, $content);
        self::assertEquals('conj', $content[0]['name']);
        self::assertIsArray($content[0]['signatures']);
        self::assertEquals(['(conj coll x)', '(conj coll x & xs)'], $content[0]['signatures']);
    }
}
