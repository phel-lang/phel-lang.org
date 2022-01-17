<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator;

use Phel\Lang\Collections\Map\PersistentMapInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnLoaderInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;
use PHPUnit\Framework\TestCase;

final class PhelFnNormalizerTest extends TestCase
{
    public function test_generate_search_index(): void
    {
        $phelFnLoader = $this->createMock(PhelFnLoaderInterface::class);
        $phelFnLoader->method('getNormalizedPhelFunctions')->willReturn([
            'test/table' => $this->createMock(PersistentMapInterface::class),
            'test/table?' => $this->createMock(PersistentMapInterface::class),
        ]);

        $generator = new PhelFnNormalizer($phelFnLoader);
        $actual = $generator->getNormalizedGroupedPhelFns();

        $expected = [
            'test-table' => [
                [
                    'fnName' => 'test/table',
                    'doc' => '',
                ],
                [
                    'fnName' => 'test/table?',
                    'doc' => '',
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }
}
