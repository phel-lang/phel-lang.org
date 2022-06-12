<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractFactory;
use Phel\Run\RunFacadeInterface;
use PhelDocBuild\FileGenerator\Domain\ApiMarkdownGenerator;
use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelDocBuild\FileGenerator\Domain\PhelFnLoaderInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizerInterface;
use PhelDocBuild\FileGenerator\Infrastructure\ApiMarkdownFile;
use PhelDocBuild\FileGenerator\Infrastructure\ApiSearchFile;
use PhelDocBuild\FileGenerator\Infrastructure\PhelFnLoader;

final class Factory extends AbstractFactory
{
    public function createApiMarkdownFile(): ApiMarkdownFile
    {
        return new ApiMarkdownFile(
            $this->createApiMarkdownGenerator(),
            $this->getConfig()->getAppRootDir(),
        );
    }

    private function createApiMarkdownGenerator(): ApiMarkdownGenerator
    {
        return new ApiMarkdownGenerator(
            $this->createPhelFnNormalizer()
        );
    }

    public function createApiSearchFile(): ApiSearchFile
    {
        return new ApiSearchFile(
            $this->createPhelFnNormalizer(),
            $this->createApiSearchGenerator(),
            $this->getConfig()->getAppRootDir()
        );
    }

    private function createPhelFnNormalizer(): PhelFnNormalizerInterface
    {
        return new PhelFnNormalizer($this->createPhelFnLoader());
    }

    private function createPhelFnLoader(): PhelFnLoaderInterface
    {
        return new PhelFnLoader(
            $this->getRunFacade(),
            $this->getConfig()->getAppRootDir()
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
