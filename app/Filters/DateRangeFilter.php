<?php
/**
 * Created by PhpStorm.
 * User: Sarfaraz
 * Date: 5/16/2018
 * Time: 12:05 PM
 */

namespace App\Filters;

class DateRangeFilter
{
    public static function filter(array &$rows, array $criteria, $opposite = false): void
    {
        foreach ($rows as $key => $row) {
            $isWithinRange = false;

            foreach ($criteria as $index => $criterion) {

                if (!isset($row[$index])) {
                    continue;
                }

                $dated = strtotime($row[$index]);
                $sDate = strtotime($criterion[0]);
                $eDate = strtotime($criterion[1]);

                if ($dated >= $sDate && $dated <= $eDate) {
                    $isWithinRange = true;
                }

                if ($opposite) {
                    if ($isWithinRange) {
                        unset($rows[$key]);
                    }
                } else {
                    if (!$isWithinRange) {
                        unset($rows[$key]);
                    }
                }
            }
        }
    }
}