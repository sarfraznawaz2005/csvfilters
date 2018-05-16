<?php
/**
 * Created by PhpStorm.
 * User: Sarfaraz
 * Date: 5/16/2018
 * Time: 12:05 PM
 */

namespace App\Filters;

class EqualsAnyFilter
{
    public static function filter(array &$rows, array $criteria, $opposite = false): void
    {
        foreach ($rows as $key => $row) {
            $equals = false;

            foreach ($criteria as $index => $criterion) {

                foreach ($criterion as $chars) {
                    if (isset($row[$index]) && $chars === $row[$index]) {
                        $equals = true;
                    }
                }
            }

            if ($opposite) {
                if ($equals) {
                    unset($rows[$key]);
                }
            } else {
                if (!$equals) {
                    unset($rows[$key]);
                }
            }
        }
    }
}