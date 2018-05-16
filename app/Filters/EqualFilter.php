<?php
/**
 * Created by PhpStorm.
 * User: Sarfaraz
 * Date: 5/16/2018
 * Time: 12:05 PM
 */

namespace App\Filters;

class EqualFilter
{
    public static function filter(array &$rows, array $criteria, $opposite = false): void
    {
        foreach ($rows as $key => $row) {
            foreach ($criteria as $index => $criterion) {
                if ($opposite) {
                    if (isset($row[$index]) && $row[$index] === $criterion) {
                        unset($rows[$key]);
                    }
                } else {
                    if (isset($row[$index]) && $row[$index] !== $criterion) {
                        unset($rows[$key]);
                    }
                }
            }
        }
    }
}