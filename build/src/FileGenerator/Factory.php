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
use PhelDocBuild\FileGenerator\Infrastructure\DocFileGenerator;
use PhelDocBuild\FileGenerator\Infrastructure\PhelFnLoader;

final class Factory extends AbstractFactory
{
    public function createDocFileGenerator(): DocFileGenerator
    {
        return new DocFileGenerator(
            $this->createMdPageRenderer(),
            $this->createPhelFnNormalizer(),
            $this->createApiSearchGenerator()
        );
    }

    private function createMdPageRenderer(): MdPageRenderer
    {
        return new MdPageRenderer($this->createOutput());
    }

    private function createOutput(): OutputInterface
    {
        return new class() implements OutputInterface {

            public function write(string $line): void
            {
                echo "$line";
            }

            public function writeln(string $line): void
            {
                echo "$line\n";
            }
        };
    }

    private function createPhelFnNormalizer(): PhelFnNormalizer
    {
        return new PhelFnNormalizer($this->createPhelFnLoader());
    }

    private function createPhelFnLoader(): PhelFnLoaderInterface
    {
        return new PhelFnLoader($this->getRunFacade());
    }

    private function getRunFacade(): RunFacadeInterface
    {
        return $this->getProvidedDependency(DependencyProvider::FACADE_PHEL_RUN);
    }

    public function createApiSearchGenerator(): ApiSearchGenerator
    {
        return new ApiSearchGenerator();
    }
}
