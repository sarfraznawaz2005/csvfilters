<?php
/**
 * Created by PhpStorm.
 * User: Sarfaraz
 * Date: 5/16/2018
 * Time: 12:05 PM
 */

namespace App\Filters;

class NotEmptyFilter
{
    public static function filter(array &$rows, array $criteria, $opposite = false): void
    {
        $index = $criteria[0];

        foreach ($rows as $key => $row) {
            if ($opposite) {
                if (isset($row[$index]) && !empty(trim($row[$index]))) {
                    unset($rows[$key]);
                }
            } else {
                if (isset($row[$index]) && empty(trim($row[$index]))) {
                    unset($rows[$key]);
                }
            }
        }
    }
}