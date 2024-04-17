<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator;

use Gacela\Framework\AbstractFactory;
use Gacela\Framework\Config\Config;
use Phel\Api\ApiFacadeInterface;
use PhelDocBuild\FileGenerator\Domain\ApiMarkdownGenerator;
use PhelDocBuild\FileGenerator\Domain\ApiSearchGenerator;
use PhelDocBuild\FileGenerator\Infrastructure\ApiMarkdownFile;
use PhelDocBuild\FileGenerator\Infrastructure\ApiSearchFile;

/**
 * @method Config getConfig()
 */
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
            $this->getPhelApiFacade(),
        );
    }

    public function createApiSearchFile(): ApiSearchFile
    {
        return new ApiSearchFile(
            $this->createApiSearchGenerator(),
            $this->getConfig()->getAppRootDir()
        );
    }

    private function createApiSearchGenerator(): ApiSearchGenerator
    {
        return new ApiSearchGenerator(
            $this->getPhelApiFacade(),
        );
    }

    private function getPhelApiFacade(): ApiFacadeInterface
    {
        return $this->getProvidedDependency(DependencyProvider::FACADE_PHEL_API);
    }
}
