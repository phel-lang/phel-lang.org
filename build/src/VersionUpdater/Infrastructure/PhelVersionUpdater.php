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
        $configContent = (string) file_get_contents($this->configFile);

        $fullVersion = $this->consoleFacade->getVersion();
        $phelVersion = preg_replace('/-.*$/', '', $fullVersion) ?? $fullVersion;
        $updatedContent = preg_replace(
            self::REGEX_PHEL_VERSION_FINDER,
            'phel_version = "' . $phelVersion . '"',
            $configContent
        ) ?? $configContent;

        if ($updatedContent !== $configContent) {
            file_put_contents($this->configFile, $updatedContent);
        }
    }
}
