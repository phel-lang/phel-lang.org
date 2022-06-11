<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Infrastructure;

use PhelDocBuild\FileGenerator\Domain\OutputInterface;
use PhelDocBuild\FileGenerator\Domain\PhelFnNormalizer;

final class ApiMarkdownFile
{
    private OutputInterface $output;
    private PhelFnNormalizer $phelFnNormalizer;

    public function __construct(
        OutputInterface $output,
        PhelFnNormalizer $phelFnNormalizer
    ) {
        $this->output = $output;
        $this->phelFnNormalizer = $phelFnNormalizer;
    }

    public function render(): void
    {
        $groupedPhelFns = $this->phelFnNormalizer->getNormalizedGroupedPhelFns();

        $this->output->writeln("+++");
        $this->output->writeln("title = \"API\"");
        $this->output->writeln("weight = 110");
        $this->output->writeln("template = \"page-api.html\"");
        $this->output->writeln("+++\n");

        foreach ($groupedPhelFns as $values) {
            foreach ($values as ['fnName' => $fnName, 'doc' => $doc]) {
                $this->output->writeln("## `$fnName`\n");
                $this->output->write($doc);
                $this->output->writeln("\n");
            }
        }
    }
}
