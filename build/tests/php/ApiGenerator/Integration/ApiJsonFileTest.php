<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Integration;

use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Framework\Gacela;
use Phel\Api\ApiFacade;
use PHPUnit\Framework\TestCase;
use PhelWeb\ApiGenerator\Infrastructure\ApiJsonFile;

final class ApiJsonFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phel-api-json-integration-' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/build');
        mkdir($this->tempDir . '/static');

        Gacela::bootstrap(__DIR__ . '/../../../..', static function (GacelaConfig $config): void {
            $config->addAppConfig('phel-config.php', 'phel-config-local.php');
        });
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

    public function test_generate_api_json_file_with_real_phel_api(): void
    {
        $generator = new ApiJsonFile(new ApiFacade(), $this->tempDir . '/build');
        $generator->generate();

        $jsonFile = $this->tempDir . '/static/api.json';
        self::assertFileExists($jsonFile);

        $content = json_decode(file_get_contents($jsonFile), true);

        self::assertIsArray($content);
        self::assertGreaterThan(100, count($content), 'Should contain many Phel functions');

        $firstFunction = $content[0];
        self::assertArrayHasKey('name', $firstFunction);
        self::assertArrayHasKey('signatures', $firstFunction);
        self::assertArrayHasKey('doc', $firstFunction);
        self::assertArrayHasKey('description', $firstFunction);
        self::assertArrayHasKey('githubUrl', $firstFunction);
        self::assertArrayHasKey('docUrl', $firstFunction);
        self::assertIsArray($firstFunction['signatures']);
    }

    public function test_all_phel_namespaces_are_present(): void
    {
        $generator = new ApiJsonFile(new ApiFacade(), $this->tempDir . '/build');
        $generator->generate();

        $jsonFile = $this->tempDir . '/static/api.json';
        $content = json_decode(file_get_contents($jsonFile), true);

        $namespaces = array_unique(array_column($content, 'namespace'));
        $expectedNamespaces = ['base64', 'core', 'debug', 'html', 'http', 'json', 'mock', 'php', 'repl', 'str', 'test'];

        self::assertCount(count($expectedNamespaces), $namespaces);
    }

    public function test_single_arity_signature_is_string(): void
    {
        $generator = new ApiJsonFile(new ApiFacade(), $this->tempDir . '/build');
        $generator->generate();

        $jsonFile = $this->tempDir . '/static/api.json';
        $content = json_decode(file_get_contents($jsonFile), true);

        // Test simple single-arity signature: (inc x) => ["(inc x)"]
        $incFunc = array_values(array_filter($content, fn($fn) => $fn['name'] === 'inc'))[0];
        self::assertIsArray($incFunc['signatures']);
        self::assertCount(1, $incFunc['signatures']);
        self::assertStringContainsString('inc', $incFunc['signatures'][0]);
        self::assertStringContainsString('x', $incFunc['signatures'][0]);
    }

    public function test_multi_arity_functions_have_comma_separated_signatures(): void
    {
        $generator = new ApiJsonFile(new ApiFacade(), $this->tempDir . '/build');
        $generator->generate();

        $jsonFile = $this->tempDir . '/static/api.json';
        $content = json_decode(file_get_contents($jsonFile), true);

        // csv-seq has 2 arities: ([filename]), ([filename options])
        $csvSeqFunc = array_values(array_filter($content, fn($fn) => $fn['name'] === 'csv-seq'))[0];
        self::assertIsArray($csvSeqFunc['signatures']);
        // Multi-arity should have multiple array elements
        self::assertGreaterThan(1, count($csvSeqFunc['signatures']), 'csv-seq should have multiple arities');

        // read-file-lazy has 2 arities: ([filename]), ([filename chunk-size])
        $readFileLazyFunc = array_values(array_filter($content, fn($fn) => $fn['name'] === 'read-file-lazy'))[0];
        self::assertIsArray($readFileLazyFunc['signatures']);
        self::assertGreaterThan(1, count($readFileLazyFunc['signatures']), 'read-file-lazy should have multiple arities');
    }
}
