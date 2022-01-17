<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

interface OutputInterface
{
    public function write(string $line): void;

    public function writeln(string $line): void;
}
