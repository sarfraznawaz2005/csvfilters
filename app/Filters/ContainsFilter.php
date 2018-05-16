<?php
/**
 * Created by PhpStorm.
 * User: Sarfaraz
 * Date: 5/16/2018
 * Time: 12:05 PM
 */

namespace App\Filters;

class ContainsFilter
{
    public static function filter(array &$rows, array $criteria, $opposite = false): void
    {
        foreach ($rows as $key => $row) {
            foreach ($criteria as $index => $criterion) {
                if ($opposite) {
                    if (isset($row[$index]) && stripos($row[$index], $criterion) !== false) {
                        unset($rows[$key]);
                    }
                } else {
                    if (isset($row[$index]) && stripos($row[$index], $criterion) === false) {
                        unset($rows[$key]);
                    }
                }
            }
        }
    }
}