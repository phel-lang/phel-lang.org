<?php

declare(strict_types=1);

namespace PhelWebTests\FileGenerator\Integration;

use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Framework\Gacela;
use Phel\Api\ApiFacade;
use PhelWeb\FileGenerator\Application\ApiMarkdownGenerator;
use PHPUnit\Framework\TestCase;

final class ApiMarkdownGeneratorTest extends TestCase
{
    /**
     * Useful for debugging $generator->generate();
     */
    public function test_generate_api_markdown_file(): void
    {
        Gacela::bootstrap(__DIR__ . '/../../../..', static function (GacelaConfig $config): void {
            $config->addAppConfig('phel-config.php', 'phel-config-local.php');
        });

        $generator = new ApiMarkdownGenerator(new ApiFacade());

        $generator->generate();

        $this->addToAssertionCount(1);
    }
}
