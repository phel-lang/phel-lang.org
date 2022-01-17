<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

use Phel\Lang\Keyword;

final class PhelFnNormalizer
{
    private PhelFnLoaderInterface $phelFnLoader;

    public function __construct(PhelFnLoaderInterface $phelFnLoader)
    {
        $this->phelFnLoader = $phelFnLoader;
    }

    /**
     * @return array<string,array{fnName:string,doc:string}>
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

            $result[$groupKey][] = [
                'fnName' => $fnName,
                'doc' => $meta[Keyword::create('doc')] ?? '',
                'fnSignature' => $matches['fnSignature'] ?? '',
                'desc' => $matches['desc'] ?? '',
            ];
        }

        foreach ($result as $values) {
            usort($values, static fn(array $a, array $b) => $a['fnName'] <=> $b['fnName']);
        }

        return $result;
    }
}
