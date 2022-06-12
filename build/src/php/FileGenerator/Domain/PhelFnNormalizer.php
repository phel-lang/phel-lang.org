<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

use Phel\Lang\Keyword;

final class PhelFnNormalizer implements PhelFnNormalizerInterface
{
    private PhelFnLoaderInterface $phelFnLoader;

    public function __construct(PhelFnLoaderInterface $phelFnLoader)
    {
        $this->phelFnLoader = $phelFnLoader;
    }

    /**
     * @return array<string,array{fnName:string,doc:string,fnSignature:string,desc:string}>
     */
    public function getNormalizedGroupedPhelFns(): array
    {
        $normalizedData = $this->phelFnLoader->getNormalizedPhelFunctions();

        $result = [];
        foreach ($normalizedData as $fnName => $meta) {
            $isPrivate = $meta[Keyword::create('private')] ?? false;
            if ($isPrivate) {
                continue;
            }

            $groupKey = preg_replace(
                '/[^a-zA-Z0-9\-]+/',
                '',
                str_replace('/', '-', $fnName)
            );

            $doc = $meta[Keyword::create('doc')] ?? '';
            $pattern = '#(```phel\n(?<fnSignature>.*)\n```\n)?(?<desc>.*)#s';
            preg_match($pattern, $doc, $matches);

            $result[strtolower(rtrim($groupKey, '-'))][] = [
                'fnName' => $fnName,
                'doc' => $doc,
                'fnSignature' => $matches['fnSignature'] ?? '',
                'desc' => $this->formatDescription($matches['desc'] ?? ''),
            ];
        }

        foreach ($result as $values) {
            usort($values, static fn (array $a, array $b) => $a['fnName'] <=> $b['fnName']);
        }

        return $result;
    }

    /**
     * The $desc is in Markdown format, the regex transforms links `[printf](https://...)` into `<i>printf</i>`.
     */
    private function formatDescription(string $desc): string
    {
        return preg_replace(
            '#\[([^\]]+)\]\(([^\)]+)\)#',
            '<i>\1</i>',
            $desc
        );
    }
}
