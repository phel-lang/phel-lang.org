<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use Phel\Api\ApiFacadeInterface;
use Phel\Api\Transfer\PhelFunction;
use PhelDocBuild\FileGenerator\Domain\PhelFunctionRepositoryInterface;

final class PhelFunctionRepository implements PhelFunctionRepositoryInterface
{
    public function __construct(
        private ApiFacadeInterface $apiFacade,
        private array $allNamespaces = []
    ) {
    }

    /**
     * @return list<PhelFunction>
     */
    public function getAllPhelFunctions(): array
    {
        return $this->apiFacade->getPhelFunctions($this->allNamespaces);
    }
}
