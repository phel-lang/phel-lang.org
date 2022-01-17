<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

final class ApiSearchGenerator
{
    private const SPECIAL_ENDING_CHARS = ['=', '*', '?', '+', '>', '<', '!'];

    /**
     * @return array<string,array{fnName:string,fnSignature:string,desc:string,anchor:string}>
     */
    public function generateSearchIndex(array $groupNormalizedData): array
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
        foreach ($groupNormalizedData as $groupKey => $values) {
            $groupFnNameAppearances[$groupKey] = 0;

            foreach ($values as ['fnName' => $fnName, 'fnSignature' => $fnSignature, 'desc' => $desc]) {
                if ($groupFnNameAppearances[$groupKey] === 0) {
                    $anchor = $groupKey;
                    $groupFnNameAppearances[$groupKey]++;
                } else {
                    $sanitizedFnName = str_replace(['/', ...self::SPECIAL_ENDING_CHARS], ['-', ''], $fnName);
                    $anchor = rtrim($sanitizedFnName, '-') . '-' . $groupFnNameAppearances[$groupKey]++;
                }

                $result[] = [
                    'fnName' => $fnName,
                    'fnSignature' => $fnSignature,
                    'desc' => $desc,
                    'anchor' => $anchor,
                ];
            }
        }

        return $result;
    }
}
