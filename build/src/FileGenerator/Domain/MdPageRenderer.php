<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

final class MdPageRenderer
{
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function renderMdPage(array $groupNormalizedData): void
    {
        $this->output->writeln("+++");
        $this->output->writeln("title = \"API\"");
        $this->output->writeln("weight = 110");
        $this->output->writeln("template = \"page-api.html\"");
        $this->output->writeln("+++\n");

        foreach ($groupNormalizedData as $values) {
            foreach ($values as ['fnName' => $fnName, 'doc' => $doc]) {
                $this->output->writeln("## `$fnName`\n");
                $this->output->write($doc);
                $this->output->writeln("\n");
            }
        }
    }
}