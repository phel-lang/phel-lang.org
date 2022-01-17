<?php

declare(strict_types=1);

namespace PhelDocBuildTests\FileGenerator;

use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PHPUnit\Framework\TestCase;

final class ApiSearchGeneratorTest extends TestCase
{
    public function test_generate_search_index(): void
    {
        $generator = new ApiSearchGenerator();
        $groupNormalizedData = [
            'table' => [
                [
                    'fnName' => 'table',
                    'doc' => 'doc for table',
                ],
                [
                    'fnName' => 'table?',
                    'doc' => 'doc for table?',
                ],
            ],
        ];

        $actual = $generator->generateSearchIndex($groupNormalizedData);

        $expected = [
            [
                'fnName' => 'table',
                'doc' => 'doc for table',
                'anchor' => 'table',
            ],
            [
                'fnName' => 'table?',
                'doc' => 'doc for table?',
                'anchor' => 'table-1',
            ],
        ];

        self::assertEquals($expected, $actual);
    }
}
