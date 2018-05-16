<?php
/**
 * Created by PhpStorm.
 * User: Sarfaraz
 * Date: 5/16/2018
 * Time: 12:05 PM
 */

namespace App\Filters;

class EndsWithAnyFilter
{
    public static function filter(array &$rows, array $criteria, $opposite = false): void
    {
        foreach ($rows as $key => $row) {
            $matched = false;

            foreach ($criteria as $index => $criterion) {

                foreach ($criterion as $chars) {
                    if (isset($row[$index]) && preg_match("/${$chars}/", $row[$index])) {
                        $matched = true;
                    }
                }
            }

            if ($opposite) {
                if ($matched) {
                    unset($rows[$key]);
                }
            } else {
                if (!$matched) {
                    unset($rows[$key]);
                }
            }
        }
    }
}