<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator\Domain;

use PhelDocBuild\FileGenerator\Domain\ApiMarkdownGenerator;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizerInterface;
use PHPUnit\Framework\TestCase;

final class ApiMarkdownGeneratorTest extends TestCase
{
    public function test_generate_page_without_phel_functions(): void
    {
        $generator = new ApiMarkdownGenerator(
            $this->createStub(PhelFnNormalizerInterface::class)
        );

        $actual = $generator->generate();

        $expected = [
            '+++',
            'title = "API"',
            'weight = 110',
            'template = "page-api.html"',
            '+++',
            '',
        ];

        self::assertEquals($expected, $actual);
    }
}
