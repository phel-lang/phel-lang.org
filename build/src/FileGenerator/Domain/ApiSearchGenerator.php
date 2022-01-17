<?php

declare(strict_types=1);

namespace PhelDocBuild\FileGenerator\Domain;

final class ApiSearchGenerator
{
    /**
     * @return array<string,array{fnName:string,doc:string,anchor:string}>
     */
    public function generateSearchIndex(array $groupNormalizedData): array
    {
        $searchIndex = [];
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

        foreach ($groupNormalizedData as $groupKey => $values) {
            $groupFnNameAppearances[$groupKey] = 0;

            foreach ($values as ['fnName' => $fnName, 'fnSignature' => $fnSignature, 'desc' => $desc]) {
                $specialEndingChars = ['/', '=', '*', '?', '+', '>', '<', '-'];

                if ($groupFnNameAppearances[$groupKey] === 0) {
                    $anchor = $groupKey;
                    $groupFnNameAppearances[$groupKey]++;
                } else {
                    $fnName2 = str_replace($specialEndingChars, '', $fnName);
                    $anchor = $fnName2 . '-' . $groupFnNameAppearances[$groupKey]++;
                }

                $searchIndex[] = [
                    'fnName' => $fnName,
                    'fnSignature' => $fnSignature,
                    'desc' => $desc,
                    'anchor' => $anchor,
                ];
            }
        }

        return $searchIndex;
    }
}
