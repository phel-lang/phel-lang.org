<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator\Infrastructure;

use Phel\Shared\Facade\ApiFacadeInterface;

use function json_encode;

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

        $jsonData = array_map(
            fn($fn) => [
                'namespace' => $fn->namespace,
                'name' => $fn->nameWithNamespace(),
                'description' => $fn->description,
                'doc' => $fn->doc,
                'signatures' => $fn->signatures,
                'githubUrl' => $fn->githubUrl,
                'docUrl' => $fn->docUrl,
                'meta' => $fn->meta ?? [],
            ],
            $phelFunctions
        );

        file_put_contents(
            $this->appRootDir . '/../static/api.json',
            json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
