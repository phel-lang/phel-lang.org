<?php

declare(strict_types=1);

namespace PhelWeb\ApiGenerator;

use Gacela\Framework\AbstractFactory;
use Gacela\Framework\Config\Config;
use Phel\Shared\Facade\ApiFacadeInterface;
use PhelWeb\ApiGenerator\Application\ApiMarkdownGenerator;
use PhelWeb\ApiGenerator\Application\ApiSearchGenerator;
use PhelWeb\ApiGenerator\Infrastructure\ApiMarkdownFile;
use PhelWeb\ApiGenerator\Infrastructure\ApiSearchFile;

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
