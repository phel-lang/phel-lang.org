<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractFactory;
use Phel\Run\RunFacadeInterface;
use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelDocBuild\FileGenerator\Domain\MdPageRenderer;
use PhelDocBuild\FileGenerator\Domain\OutputInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnLoaderInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;
use PhelDocBuild\FileGenerator\Infrastructure\ApiMarkdownFile;
use PhelDocBuild\FileGenerator\Infrastructure\ApiSearchFile;
use PhelDocBuild\FileGenerator\Infrastructure\PhelFnLoader;

/**
 * @method Config getConfig()
 */
final class Factory extends AbstractFactory
{
    public function createApiMarkdownFile(): ApiMarkdownFile
    {
        return new ApiMarkdownFile(
            $this->createOutput(),
            $this->createPhelFnNormalizer(),
        );
    }

    public function createDocFileGenerator(): ApiSearchFile
    {
        return new ApiSearchFile(
            $this->createPhelFnNormalizer(),
            $this->createApiSearchGenerator(),
            $this->getConfig()->getSrcDir()
        );
    }

    private function createOutput(): OutputInterface
    {
        return new class() implements OutputInterface {

            public function write(string $line): void
            {
                echo $line;
            }

            public function writeln(string $line): void
            {
                echo $line . PHP_EOL;
            }
        };
    }

    private function createPhelFnNormalizer(): PhelFnNormalizer
    {
        return new PhelFnNormalizer($this->createPhelFnLoader());
    }

    private function createPhelFnLoader(): PhelFnLoaderInterface
    {
        return new PhelFnLoader(
            $this->getRunFacade(),
            $this->getConfig()->getSrcDir()
        );
    }

    private function createApiSearchGenerator(): ApiSearchGenerator
    {
        return new ApiSearchGenerator();
    }

    private function getRunFacade(): RunFacadeInterface
    {
        return $this->getProvidedDependency(DependencyProvider::FACADE_PHEL_RUN);
    }
}
