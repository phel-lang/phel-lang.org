<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Application;

use Phel\Api\ApiFacadeInterface;

final readonly class ApiSearchGenerator
{
    private const SPECIAL_ENDING_CHARS = ['=', '*', '?', '+', '>', '<', '!'];

    public function __construct(
        private ApiFacadeInterface $apiFacade
    ) {
    }

    /**
     * @return array<string, array{
     *     fnName: string,
     *     fnSignature: string,
     *     desc: string,
     *     anchor: string,
     * }>
     */
    public function generateSearchIndex(): array
    {
        /**
         * Zola ignores the especial chars, and uses instead a number. This variable keep track
         * of the appearances and uses an autoincrement number to follow the proper link.
         *
         * For example, consider the two functions: `table` and `table?`
         * They belong to the same group `table`, and their anchor will be such as:
         * table  -> table
         * table? -> table-1
         */
        $groupFnNameAppearances = [];
        $result = [];
        $groupedPhelFns = $this->apiFacade->getPhelFunctions();

        foreach ($groupedPhelFns as $fn) {
            $groupKey = $fn->groupKey();
            $groupFnNameAppearances[$groupKey] ??= 0;

            if ($groupFnNameAppearances[$groupKey] === 0) {
                $anchor = $groupKey;
                $groupFnNameAppearances[$groupKey]++;
            } else {
                $sanitizedFnName = str_replace(['/', ...self::SPECIAL_ENDING_CHARS], ['-', ''], $fn->fnName());
                $anchor = rtrim($sanitizedFnName, '-') . '-' . $groupFnNameAppearances[$groupKey]++;
            }

            $result[] = [
                'fnName' => $fn->fnName(),
                'fnSignature' => $fn->fnSignature(),
                'desc' => $this->formatDescription($fn->description()),
                'anchor' => $anchor,
            ];
        }

        return $result;
    }

    /**
     * Transforms links `[printf](https://...)` into `<i>printf</i>`.
     */
    private function formatDescription(string $desc): string
    {
        return preg_replace('/\[(.*?)\]\((.*?)\)/', '<i>$1</i>', $desc);
    }
}
