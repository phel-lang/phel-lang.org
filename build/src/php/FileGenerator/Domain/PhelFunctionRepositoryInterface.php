<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

use Phel\Api\Transfer\PhelFunction;

interface PhelFunctionRepositoryInterface
{
    /**
     * @return array<string,list<PhelFunction>>
     */
    public function getAllGroupedFunctions(): array;
}
