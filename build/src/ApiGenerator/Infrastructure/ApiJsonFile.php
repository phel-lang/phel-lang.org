<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Infrastructure;

use Phel\Shared\Api\PhelFunction;
use Phel\Shared\Facade\ApiFacadeInterface;

use function json_encode;

/**
 * Writes static/api.json, the public machine-readable API dump.
 *
 * This is a projection of PhelFunction, not a parallel definition of it: the
 * subset of fields and the flattened `name` are a published contract that must
 * stay stable even if the upstream value object grows new properties.
 *
 * @psalm-type TApiJsonEntry = array{
 *     namespace: string,
 *     name: string,
 *     description: string,
 *     doc: string,
 *     signatures: list<string>,
 *     githubUrl: string,
 *     docUrl: string,
 *     meta: array<string, mixed>,
 * }
 */
final readonly class ApiJsonFile
{
    public function __construct(
        private ApiFacadeInterface $apiFacade,
        private string $appRootDir
    ) {
    }

    public function generate(): void
    {
        $phelFunctions = $this->apiFacade->getPhelFunctions();

        $jsonData = array_map($this->toJsonEntry(...), $phelFunctions);

        file_put_contents(
            $this->appRootDir . '/../static/api.json',
            json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @return TApiJsonEntry
     */
    private function toJsonEntry(PhelFunction $fn): array
    {
        return [
            'namespace' => $fn->namespace,
            'name' => $fn->nameWithNamespace(),
            'description' => $fn->description,
            'doc' => $fn->doc,
            'signatures' => $fn->signatures,
            'githubUrl' => $fn->githubUrl,
            'docUrl' => $fn->docUrl,
            'meta' => $fn->meta,
        ];
    }
}
