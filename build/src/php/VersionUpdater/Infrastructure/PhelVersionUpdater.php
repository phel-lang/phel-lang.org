<?php

declare(strict_types=1);

namespace PhelWeb\VersionUpdater\Infrastructure;

use Phel\Shared\Facade\ConsoleFacadeInterface;

final readonly class PhelVersionUpdater
{
    private const string REGEX_PHEL_VERSION_FINDER = '/^phel_version\s*=\s*"[^"]*"/m';

    public function __construct(
        private ConsoleFacadeInterface $consoleFacade,
        private string $configFile,
    ) {
    }

    public function update(): void
    {
        $configContent = file_get_contents($this->configFile);

        $phelVersion = $this->consoleFacade->getVersion();
        $updatedContent = preg_replace(
            self::REGEX_PHEL_VERSION_FINDER,
            'phel_version = "' . $phelVersion . '"',
            $configContent
        );

        if ($updatedContent !== $configContent) {
            file_put_contents($this->configFile, $updatedContent);
        }
    }
}
