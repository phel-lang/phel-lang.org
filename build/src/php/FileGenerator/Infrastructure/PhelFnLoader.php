<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use Phel\Lang\Collections\Map\PersistentMapInterface;
use Phel\Lang\TypeFactory;
use Phel\Run\RunFacade;
use PhelDocBuild\FileGenerator\Domain\PhelFnLoaderInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

final class PhelFnLoader implements PhelFnLoaderInterface
{
    /**
     * Prevent executing the RunCommand multiple times
     */
    private static bool $wasRunCommandExecuted = false;

    private RunFacade $runFacade;

    private string $srcDir;

    public function __construct(RunFacade $runFacade, string $srcDir)
    {
        $this->runFacade = $runFacade;
        $this->srcDir = $srcDir;
    }

    /**
     * @return array<string,PersistentMapInterface>
     */
    public function getNormalizedPhelFunctions(): array
    {
        if (!self::$wasRunCommandExecuted) {
            $this->runFacade
                ->getRunCommand()
                ->run(
                    new ArrayInput(['path' => $this->srcDir . '/phel/doc.phel']),
                    new ConsoleOutput()
                );
            self::$wasRunCommandExecuted = true;
        }

        /** @var array<string,PersistentMapInterface> $normalizedData */
        $normalizedData = [];
        foreach ($GLOBALS['__phel'] as $ns => $functions) {
            $normalizedNs = str_replace('phel\\', '', $ns);
            $moduleName = $normalizedNs === 'core' ? '' : $normalizedNs . '/';
            foreach ($functions as $fnName => $fn) {
                $fullFnName = $moduleName . $fnName;

                $normalizedData[$fullFnName] = $GLOBALS['__phel_meta'][$ns][$fnName]
                    ?? TypeFactory::getInstance()->emptyPersistentMap();
            }
        }
        ksort($normalizedData);

        return $normalizedData;
    }
}