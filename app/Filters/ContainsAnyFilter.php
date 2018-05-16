<?php
/**
 * Created by PhpStorm.
 * User: Sarfaraz
 * Date: 5/16/2018
 * Time: 12:05 PM
 */

namespace App\Filters;

class ContainsAnyFilter
{
    public static function filter(array &$rows, array $criteria, $opposite = false): void
    {
        foreach ($rows as $key => $row) {
            $contains = false;

            foreach ($criteria as $index => $criterion) {

                foreach ($criterion as $chars) {
                    if (isset($row[$index]) && stripos($row[$index], $chars) !== false) {
                        $contains = true;
                    }
                }
            }

            if ($opposite) {
                if ($contains) {
                    unset($rows[$key]);
                }
            } else {
                if (!$contains) {
                    unset($rows[$key]);
                }
            }
        }
    }
}