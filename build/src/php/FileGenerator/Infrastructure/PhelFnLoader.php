<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use Phel\Lang\Collections\Map\PersistentMapInterface;
use Phel\Lang\Registry;
use Phel\Lang\TypeFactory;
use Phel\Run\RunFacade;
use PhelDocBuild\FileGenerator\Domain\PhelFnLoaderInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

final class PhelFnLoader implements PhelFnLoaderInterface
{
    /** Prevent executing the RunCommand multiple times */
    private static bool $wasRunCommandExecuted = false;

    private RunFacade $runFacade;

    private string $appRootDir;

    public function __construct(RunFacade $runFacade, string $appRootDir)
    {
        $this->runFacade = $runFacade;
        $this->appRootDir = $appRootDir;
    }

    /**
     * @return array<string,PersistentMapInterface>
     */
    public function getNormalizedPhelFunctions(): array
    {
        $this->loadAllPhelFunctions();

        /** @var array<string,PersistentMapInterface> $normalizedData */
        $normalizedData = [];
        foreach ($this->getNamespaces() as $ns) {
            $normalizedNs = str_replace('phel\\', '', $ns);
            $moduleName = $normalizedNs === 'core' ? '' : $normalizedNs . '/';

            foreach ($this->getDefinitionsInNamespace($ns) as $fnName => $fn) {
                $fullFnName = $moduleName . $fnName;

                $normalizedData[$fullFnName] = $this->getPhelMeta($ns, $fnName);
            }
        }
        ksort($normalizedData);

        return $normalizedData;
    }

    private function loadAllPhelFunctions(): void
    {
        if (self::$wasRunCommandExecuted) {
            return;
        }

        $this->runFacade->getRunCommand()->run(
            new ArrayInput(['path' => $this->appRootDir . '/../phel/doc.phel']),
            new ConsoleOutput()
        );

        self::$wasRunCommandExecuted = true;
    }

    /**
     * @return list<string>
     */
    private function getNamespaces(): array
    {
        return Registry::getInstance()->getNamespaces();
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefinitionsInNamespace(string $ns): array
    {
        return Registry::getInstance()->getDefinitionInNamespace($ns);
    }

    private function getPhelMeta(string $ns, string $fnName): PersistentMapInterface
    {
        return Registry::getInstance()->getDefinitionMetaData($ns, $fnName)
            ?? TypeFactory::getInstance()->emptyPersistentMap();
    }
}
